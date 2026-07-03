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
        Schema::create('version', function (Blueprint $table) {
            $table->id();
            $table->string('version_number')->unique();
            $table->string('app_name')->nullable();
            $table->text('description')->nullable();
            $table->text('features')->nullable();
            $table->text('bug_fixes')->nullable();
            $table->string('status')->default('active'); // active, deprecated, beta
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version');
    }
};
