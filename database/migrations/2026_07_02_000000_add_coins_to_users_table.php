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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'coins')) {
                $table->integer('coins')->default(0)->nullable();
            }
            if (!Schema::hasColumn('users', 'daily_coin_count')) {
                $table->integer('daily_coin_count')->default(0)->nullable();
            }
            if (!Schema::hasColumn('users', 'daily_coin_reset_date')) {
                $table->date('daily_coin_reset_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'monthly_coin_count')) {
                $table->integer('monthly_coin_count')->default(0)->nullable();
            }
            if (!Schema::hasColumn('users', 'monthly_coin_reset_date')) {
                $table->date('monthly_coin_reset_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'tier')) {
                $table->string('tier')->default('Bronze')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'coins',
                'daily_coin_count',
                'daily_coin_reset_date',
                'monthly_coin_count',
                'monthly_coin_reset_date',
                'tier'
            ]);
        });
    }
};
