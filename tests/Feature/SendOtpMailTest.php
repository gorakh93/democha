<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SendOtpMailTest extends TestCase
{
    public function test_forget_password_sends_the_generated_otp_to_the_user_email(): void
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

        $email = 'user@example.com';
        DB::table('users')->insert(['email' => $email]);
        config(['services.resend.key' => 'test-resend-key']);
        Http::fake([
            'api.resend.com/emails' => Http::response(['id' => 'email-123'], 200),
        ]);

        $response = $this->postJson('/api/forget-password', ['email' => $email]);

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
}
