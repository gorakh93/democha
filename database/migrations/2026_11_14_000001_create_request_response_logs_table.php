<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_response_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('method', 10);
            $table->text('path');
            $table->longText('request_data')->nullable();
            $table->unsignedSmallInteger('response_status');
            $table->longText('response_data')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['response_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_response_logs');
    }
};
