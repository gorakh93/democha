<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        });

        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->date('bill_date')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
        });
    }

    public function test_it_updates_remaining_budget_using_only_current_month_bills(): void
    {
        $userId = DB::table('users')->insertGetId([
            'm_budget' => 200,
            'm_left' => 200,
        ]);
        $now = now();

        DB::table('bills')->insert([
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->startOfMonth()->toDateString(),
                'total_amount' => 25.50,
            ],
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->toDateString(),
                'total_amount' => 30,
            ],
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                'total_amount' => 100,
            ],
            [
                'userid' => $userId,
                'bill_date' => $now->copy()->addMonthNoOverflow()->startOfMonth()->toDateString(),
                'total_amount' => 70,
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
                ],
                'status' => 200,
            ]);

        $this->assertSame(144.5, (float) DB::table('users')->where('id', $userId)->value('m_left'));
    }
}
