<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('check_batches')
                ->cascadeOnDelete();

            $table->string('input');
            $table->string('domain');

            $table->enum('status', [
                'queued',
                'checking',
                'completed',
                'failed',
            ])->default('queued');

            // Blacklist
            $table->enum('blacklist_status', [
                'clean',
                'listed',
                'unknown',
            ])->nullable();

            $table->json('blacklists')->nullable();

            // DNS
            $table->json('dns_records')->nullable();

            // Mail provider detection
            $table->string('provider')->nullable();
            $table->text('detection_evidence')->nullable();

            // Error information
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_checks');
    }
};