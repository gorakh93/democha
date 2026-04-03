<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('gstnumber')->nullable();
            $table->string('bill_number')->nullable();
            $table->decimal('cgst', 10, 2)->nullable();
            $table->decimal('igst', 10, 2)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('merchant_name')->nullable();
            $table->date('bill_date')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('gross_amount', 10, 2)->nullable();
            $table->string('order_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'gstnumber',
                'bill_number',
                'cgst',
                'igst',
                'phone',
                'email',
                'merchant_name',
                'bill_date',
                'total_amount',
                'gross_amount',
                'order_number'
            ]);
        });
    }
};