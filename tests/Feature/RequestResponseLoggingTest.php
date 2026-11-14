<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class RequestResponseLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('request_response_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('method', 10);
            $table->text('path');
            $table->longText('request_data')->nullable();
            $table->unsignedSmallInteger('response_status');
            $table->longText('response_data')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->timestamps();
        });

        Route::get('/_request-log-test', fn () => response()->json([
            'message' => 'ok',
            'access_token' => 'response-secret',
        ]));

        Route::get('/_request-log-client-error-test', fn () => response()->json([
            'message' => 'Invalid input',
        ], 422));

        Route::get('/_request-log-error-test', function (): never {
            throw new LogicException('Logical error');
        });
    }

    public function test_it_logs_requests_and_responses_with_sensitive_json_fields_redacted(): void
    {
        $this->getJson('/_request-log-test?password=request-secret&name=Sam')
            ->assertOk();

        $log = DB::table('request_response_logs')->where('path', '_request-log-test')->first();

        $this->assertNotNull($log);
        $this->assertSame(200, $log->response_status);
        $this->assertSame('GET', $log->method);
        $this->assertIsInt($log->duration_ms);
        $this->assertSame([
            'password' => '[REDACTED]',
            'name' => 'Sam',
        ], json_decode($log->request_data, true));
        $this->assertSame([
            'message' => 'ok',
            'access_token' => '[REDACTED]',
        ], json_decode($log->response_data, true));
    }

    public function test_it_logs_error_responses_returned_without_an_exception(): void
    {
        $this->getJson('/_request-log-client-error-test')
            ->assertUnprocessable();

        $log = DB::table('request_response_logs')->where('path', '_request-log-client-error-test')->first();

        $this->assertNotNull($log);
        $this->assertSame(422, $log->response_status);
        $this->assertNull($log->exception_class);
        $this->assertSame(['message' => 'Invalid input'], json_decode($log->response_data, true));
    }

    public function test_it_logs_thrown_errors_and_their_rendered_responses(): void
    {
        $this->getJson('/_request-log-error-test')
            ->assertInternalServerError();

        $log = DB::table('request_response_logs')->where('path', '_request-log-error-test')->first();

        $this->assertNotNull($log);
        $this->assertSame(500, $log->response_status);
        $this->assertSame(LogicException::class, $log->exception_class);
        $this->assertSame('Logical error', $log->exception_message);
        $this->assertSame(1, DB::table('request_response_logs')->where('path', '_request-log-error-test')->count());
    }
}
