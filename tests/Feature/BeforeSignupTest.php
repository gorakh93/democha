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
        config(['services.resend.key' => 'test-resend-key']);
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
                && $request['to'] === $email
                && $request['subject'] === 'Your OTP For Verification'
                && str_contains($request['html'], $otp);
        });
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
    }
}
