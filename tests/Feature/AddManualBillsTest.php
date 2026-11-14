<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddManualBillsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->string('merchant_name')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('gross_amount', 10, 2)->nullable();
            $table->decimal('gst', 10, 2)->nullable();
            $table->string('merchant_type')->nullable();
            $table->date('bill_date')->nullable();
            $table->string('bill_number')->nullable();
            $table->decimal('cgst', 10, 2)->nullable();
            $table->decimal('igst', 10, 2)->nullable();
            $table->decimal('sgst', 10, 2)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('order_number')->nullable();
            $table->string('bill_file')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_saves_gst_and_splits_it_between_cgst_and_sgst(): void
    {
        $response = $this->postJson('/api/add-manual-bills', [
            'userid' => 25,
            'merchant_name' => 'Local shop',
            'amount' => 118,
            'gst' => 18,
            'merchant_type' => 'Shopping',
            'bill_date' => '2026-11-10',
        ]);

        $response->assertOk()->assertJson([
            'message' => 'Bill added successfully',
            'status' => 200,
        ]);

        $this->assertDatabaseHas('bills', [
            'id' => $response->json('data.bill_id'),
            'gst' => 18,
            'cgst' => 9,
            'sgst' => 9,
            'igst' => 0,
        ]);
    }
}
