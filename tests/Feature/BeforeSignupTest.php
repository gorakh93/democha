<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BeforeSignupTest extends TestCase
{
    public function test_it_sends_an_otp_when_the_email_is_not_registered(): void
    {
        $this->createSignupTables();
        $email = 'new-user@example.com';
        config([
            'services.resend.key' => 'test-resend-key',
            'services.resend.from' => 'verify@example.com',
        ]);
        Http::fake([
            'api.resend.com/emails' => Http::response(['id' => 'email-123'], 200),
        ]);

        $response = $this->postJson('/api/before-signup', ['email' => $email]);

        $response->assertOk()->assertJson([
            'message' => 'OTP generated successfully',
            'status' => 200,
        ]);

        $otp = DB::table('otp_logs')->where('email', $email)->value('otp');
        $this->assertMatchesRegularExpression('/^\d{5}$/', $otp);

        Http::assertSent(function (ClientRequest $request) use ($email, $otp): bool {
            return $request->url() === 'https://api.resend.com/emails'
                && $request->hasHeader('Authorization', 'Bearer test-resend-key')
                && $request['from'] === 'verify@example.com'
                && $request['to'] === $email
                && $request['subject'] === 'Your OTP For Verification'
                && str_contains($request['html'], $otp);
        });
    }

    public function test_it_returns_the_resend_error_when_delivery_is_rejected(): void
    {
        $this->createSignupTables();
        config([
            'services.resend.key' => 'test-resend-key',
            'services.resend.from' => 'verify@example.com',
        ]);
        Http::fake([
            'api.resend.com/emails' => Http::response([
                'message' => 'The sender domain has not been verified',
            ], 403),
        ]);

        $response = $this->postJson('/api/before-signup', ['email' => 'new-user@example.com']);

        $response->assertStatus(200)->assertJson([
            'message' => 'Error generating OTP: Email service failed to send the OTP (HTTP 403): The sender domain has not been verified',
            'status' => 500,
        ]);
        $this->assertDatabaseCount('otp_logs', 0);
    }

    public function test_it_uses_the_fixed_otp_without_sending_email_when_testing_is_enabled(): void
    {
        $this->createSignupTables();
        $email = 'test-user@example.com';
        DB::table('testing')->insert(['otp' => 1]);
        Http::fake();

        $this->postJson('/api/before-signup', ['email' => $email])
            ->assertOk()
            ->assertJson([
                'message' => 'OTP generated successfully',
                'status' => 200,
            ]);

        $this->assertSame('12345', DB::table('otp_logs')->where('email', $email)->value('otp'));
        Http::assertNothingSent();
    }

    public function test_it_rejects_an_email_that_is_already_registered(): void
    {
        $this->createSignupTables();
        $email = 'existing-user@example.com';
        DB::table('users')->insert(['email' => $email]);
        Http::fake();

        $response = $this->postJson('/api/before-signup', ['email' => $email]);

        $response->assertStatus(409)->assertJson([
            'message' => 'Email is already registered',
            'status' => 409,
        ]);
        $this->assertDatabaseCount('otp_logs', 0);
        Http::assertNothingSent();
    }

    private function createSignupTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
        });
        Schema::create('otp_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('otp');
            $table->dateTime('created_at');
        });
        Schema::create('testing', function (Blueprint $table): void {
            $table->id();
            $table->string('otp', 1);
        });
        DB::table('testing')->insert(['otp' => 0]);
    }
}
