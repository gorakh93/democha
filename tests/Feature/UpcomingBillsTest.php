<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpcomingBillsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('mbills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->string('bill_name');
            $table->date('due_date')->nullable();
            $table->string('repeats')->nullable();
            $table->string('amount')->nullable();
        });
    }

    public function test_it_returns_upcoming_bills_as_a_json_array(): void
    {
        $today = today();
        DB::table('mbills')->insert([
            [
                'userid' => 25,
                'bill_name' => 'Internet Recharge',
                'due_date' => $today->copy()->addDays(22)->toDateString(),
                'repeats' => 'monthly',
                'amount' => '399',
            ],
            [
                'userid' => 25,
                'bill_name' => 'Dth',
                'due_date' => $today->copy()->addDays(17)->toDateString(),
                'repeats' => 'monthly',
                'amount' => '200',
            ],
            [
                'userid' => 25,
                'bill_name' => 'Dish',
                'due_date' => $today->copy()->addDays(23)->toDateString(),
                'repeats' => 'monthly',
                'amount' => '300',
            ],
        ]);

        $response = $this->postJson('/api/upcoming-bills', ['userid' => '25']);

        $response->assertOk()->assertJson([
            'message' => 'Upcoming bills retrieved successfully',
            'status' => 200,
        ]);

        $bills = $response->json('data');
        $this->assertIsArray($bills);
        $this->assertSame([0, 1, 2], array_keys($bills));
        $this->assertSame(['Internet Recharge', 'Dth', 'Dish'], array_column($bills, 'bill_name'));
    }
}
