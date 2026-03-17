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
            $table->id(); // id INT IDENTITY(1,1) PRIMARY KEY 
            $table->string('activity_name', 255); // activity_name NVARCHAR(255) 
            $table->string('activity_unique_id', 100)->unique(); // activity_unique_id NVARCHAR(100) UNIQUE [cite: 1, 3]
            $table->dateTime('start_date'); // start_date DATETIME 
            $table->dateTime('end_date'); // end_date DATETIME [cite: 2]
            $table->integer('quota'); // quota INT [cite: 2]
            $table->dateTime('created_at')->useCurrent(); // DEFAULT GETDATE() [cite: 2]
            $table->dateTime('updated_at')->useCurrent(); // DEFAULT GETDATE() 

            // 建立索引 [cite: 4]
            $table->index('activity_unique_id', 'IX_serial_activity_unique_id');
            $table->index(['start_date', 'end_date'], 'IX_serial_activity_dates');
        });

        // 2. 建立 serial_detail 
        Schema::create('serial_detail', function (Blueprint $table) {
            $table->id(); // id INT IDENTITY(1,1) PRIMARY KEY 
            
            // 外鍵約束：serial_activity_id 關聯 serial_activity.id [cite: 8]
            $table->foreignId('serial_activity_id')
                  ->constrained('serial_activity')
                  ->onDelete('cascade'); // ON DELETE CASCADE [cite: 8]

            $table->string('orderno', 8)->nullable(); // orderno NVARCHAR(8) NULL 
            $table->string('content', 8)->unique(); // content NVARCHAR(8) UNIQUE [cite: 5, 8]
            $table->integer('status')->default(0); // status INT DEFAULT 0 
            $table->text('note')->nullable(); // note NVARCHAR(MAX) NULL 
            $table->dateTime('start_date'); // start_date DATETIME 
            $table->dateTime('end_date'); // end_date DATETIME 
            $table->dateTime('created_at')->useCurrent(); // DEFAULT GETDATE() 
            $table->dateTime('updated_at')->nullable(); // updated_at DATETIME NULL 

            // 建立索引 [cite: 9, 10]
            $table->index('serial_activity_id', 'IX_serial_detail_activity_id');
            $table->index('orderno', 'IX_serial_detail_orderno');
            $table->index('status', 'IX_serial_detail_status');
            $table->index(['start_date', 'end_date'], 'IX_serial_detail_dates');
        });

        // 3. 建立 serial_log [cite: 11]
        Schema::create('serial_log', function (Blueprint $table) {
            $table->id(); // id INT IDENTITY(1,1) PRIMARY KEY [cite: 11]
            $table->string('api_name', 100); // api_name NVARCHAR(100) [cite: 11]
            $table->string('host', 50); // host NVARCHAR(50) [cite: 11]
            $table->string('api', 255); // api NVARCHAR(255) [cite: 11]
            $table->longText('request'); // request NVARCHAR(MAX) [cite: 12]
            $table->dateTime('request_at'); // request_at DATETIME [cite: 12]
            $table->longText('response')->nullable(); // response NVARCHAR(MAX) NULL [cite: 12]
            $table->dateTime('response_at')->nullable(); // response_at DATETIME NULL [cite: 12, 13]
            $table->dateTime('created_at')->useCurrent(); // DEFAULT GETDATE() [cite: 13]

            // 建立索引 [cite: 14]
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
