<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_batches', function (Blueprint $table) {
            $table->string('filename')->nullable()->after('type');
            $table->unsignedInteger('queued')->default(0)->after('total');
            $table->unsignedInteger('checking')->default(0)->after('queued');
            $table->string('status')->default('queued')->after('failed');
        });
    }

    public function down(): void
    {
        Schema::table('check_batches', function (Blueprint $table) {
            $table->dropColumn([
                'filename',
                'queued',
                'checking',
                'status',
            ]);
        });
    }
};