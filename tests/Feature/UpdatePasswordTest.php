<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function test_it_requires_sanctum_authentication(): void
    {
        $this->postJson('/api/update-password', [
            'userid' => 1,
            'new_password' => 'new-password',
        ])->assertUnauthorized();
    }

    public function test_it_updates_the_authenticated_users_password_without_an_old_password(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/update-password', [
            'userid' => $user->id,
            'new_password' => 'new-password',
        ])->assertOk()->assertJson([
            'message' => 'Password updated successfully',
            'status' => 200,
        ]);

        $this->assertSame(md5('new-password'), DB::table('users')->where('id', $user->id)->value('password'));
    }

    public function test_it_ignores_null_and_empty_old_password_values(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        foreach ([null, ''] as $index => $oldPassword) {
            $newPassword = 'new-password-' . $index;

            $this->postJson('/api/update-password', [
                'userid' => $user->id,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ])->assertOk()->assertJson([
                'message' => 'Password updated successfully',
                'status' => 200,
            ]);

            $this->assertSame(md5($newPassword), DB::table('users')->where('id', $user->id)->value('password'));
        }
    }

    public function test_it_keeps_validating_a_provided_old_password(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/update-password', [
            'userid' => $user->id,
            'old_password' => 'incorrect-password',
            'new_password' => 'new-password',
        ])->assertOk()->assertJson([
            'message' => 'Old password is not correct',
            'status' => 401,
        ]);
    }

    public function test_it_does_not_allow_updating_another_users_password(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/update-password', [
            'userid' => $user->id + 1,
            'new_password' => 'new-password',
        ])->assertOk()->assertJson([
            'message' => 'You can only update your own password',
            'status' => 403,
        ]);
    }

    private function createUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'password' => md5('old-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }
}
