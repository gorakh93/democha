<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HomeMonthlyBudgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->decimal('m_budget', 10, 2)->nullable();
            $table->decimal('m_left', 10, 2)->nullable();
            $table->integer('coins')->default(0);
        });

        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->date('bill_date')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->integer('is_process')->default(0);
        });
    }

    public function test_it_updates_remaining_budget_using_only_current_month_bills(): void
    {
        $userId = DB::table('users')->insertGetId([
            'm_budget' => 200,
            'm_left' => 200,
            'coins' => 125,
        ]);
        Sanctum::actingAs(User::findOrFail($userId));
        $now = now();

        DB::table('bills')->insert([
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->startOfMonth()->toDateString(),
                'total_amount' => 25.50,
                'is_process' => 1,
            ],
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->toDateString(),
                'total_amount' => 30,
                'is_process' => 0,
            ],
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                'total_amount' => 100,
                'is_process' => 1,
            ],
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->addMonthNoOverflow()->startOfMonth()->toDateString(),
                'total_amount' => 70,
                'is_process' => 0,
            ],
            [
                'userid' => $userId + 1,
                'bill_date' => $now->copy()->toDateString(),
                'total_amount' => 500,
                'is_process' => 1,
            ],
        ]);

        $this->postJson('/api/home', ['userid' => $userId])
            ->assertOk()
            ->assertJson([
                'message' => 'Monthly budget updated successfully',
                'data' => [
                    'monthly_budget' => 200,
                    'current_month_amount' => 55.5,
                    'm_left' => 144.5,
                    'total_coin' => 125,
                    'scanned_bills' => 4,
                    'pending_reviews' => 2,
                ],
                'status' => 200,
            ]);

        $this->assertSame(144.5, (float) DB::table('users')->where('id', $userId)->value('m_left'));
    }
}
