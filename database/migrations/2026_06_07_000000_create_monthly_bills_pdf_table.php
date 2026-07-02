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
        if (!Schema::hasTable('monthly_bills_pdf')) {
            Schema::create('monthly_bills_pdf', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('userid');
                $table->unsignedTinyInteger('month');
                $table->unsignedSmallInteger('year');
                $table->string('bills_pdf');
                $table->timestamps();
                
                $table->unique(['userid', 'month', 'year']);
                $table->index('userid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_bills_pdf');
    }
};
