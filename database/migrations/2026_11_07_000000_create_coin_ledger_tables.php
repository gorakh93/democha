<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coins')) {
            Schema::create('coins', function (Blueprint $table) {
                $table->id();
                $table->string('userid', 25);
                $table->string('bill_id', 25);
                $table->string('coin', 25)->default('0');
                $table->integer('amount')->nullable();
                $table->unsignedInteger('balance_after')->nullable();
                $table->string('type', 20)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('coins', function (Blueprint $table) {
                if (! Schema::hasColumn('coins', 'amount')) {
                    $table->integer('amount')->nullable();
                }
                if (! Schema::hasColumn('coins', 'balance_after')) {
                    $table->unsignedInteger('balance_after')->nullable();
                }
                if (! Schema::hasColumn('coins', 'type')) {
                    $table->string('type', 20)->nullable();
                }
            });
        }

        if (Schema::hasColumn('coins', 'coin')) {
            DB::table('coins')
                ->whereNull('amount')
                ->update(['amount' => DB::raw('CAST(coin AS SIGNED)')]);
        }
        DB::table('coins')->whereNull('type')->update(['type' => 'EARN']);
        Schema::table('coins', function (Blueprint $table) {
            $table->index(['bill_id', 'type'], 'coins_bill_type_idx');
            $table->index(['userid', 'type', 'created_at'], 'coins_user_month_idx');
        });

        Schema::create('coin_monthly_budgets', function (Blueprint $table) {
            $table->date('month_start')->primary();
            $table->unsignedInteger('earned_coins')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_monthly_budgets');
        if (Schema::hasTable('coins')) {
            Schema::table('coins', function (Blueprint $table) {
                $table->dropIndex('coins_bill_type_idx');
                $table->dropIndex('coins_user_month_idx');
                $table->dropColumn(['amount', 'balance_after', 'type']);
            });
        }
    }
};
