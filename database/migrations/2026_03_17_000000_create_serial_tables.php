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
        // 1. 建立 serial_activity 
        Schema::create('serial_activity', function (Blueprint $table) {
            $table->id();
            $table->string('activity_name', 255);
            $table->string('activity_unique_id', 100)->unique();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('quota');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();

            // 建立索引
            $table->index('activity_unique_id', 'IX_serial_activity_unique_id');
            $table->index(['start_date', 'end_date'], 'IX_serial_activity_dates');
        });

        // 2. 建立 serial_detail 
        Schema::create('serial_detail', function (Blueprint $table) {
            $table->id();

            // 手動分開寫，避免 foreignId() 自動建索引後再重複建立
            $table->unsignedBigInteger('serial_activity_id');
            $table->foreign('serial_activity_id', 'FK_serial_detail_activity')
                  ->references('id')
                  ->on('serial_activity')
                  ->onDelete('cascade');

            $table->string('orderno', 8)->nullable();
            $table->string('content', 8)->unique();
            $table->integer('status')->default(0);
            $table->text('note')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            // 建立索引
            $table->index('serial_activity_id', 'IX_serial_detail_activity_id');
            $table->index('orderno', 'IX_serial_detail_orderno');
            $table->index('status', 'IX_serial_detail_status');
            $table->index(['start_date', 'end_date'], 'IX_serial_detail_dates');
        });

        // 3. 建立 serial_log
        Schema::create('serial_log', function (Blueprint $table) {
            $table->id();
            $table->string('api_name', 100);
            $table->string('host', 50);
            $table->string('api', 255);
            $table->text('request');
            $table->dateTime('request_at');
            $table->text('response')->nullable();
            $table->dateTime('response_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            // 建立索引
            $table->index('request_at', 'IX_serial_log_request_at');
            $table->index('api_name', 'IX_serial_log_api_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 依照外鍵依賴順序刪除：先刪除子表，再刪除父表
        Schema::dropIfExists('serial_log');
        Schema::dropIfExists('serial_detail');
        Schema::dropIfExists('serial_activity');
    }
};
