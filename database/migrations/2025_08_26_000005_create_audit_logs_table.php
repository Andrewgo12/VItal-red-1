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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // User information
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();

            // Action information
            $table->string('action'); // create, update, delete, view, etc.
            $table->string('resource'); // medical_request, gmail_monitor, etc.
            $table->string('method'); // GET, POST, PUT, DELETE
            $table->text('url');
            $table->string('path');

            // Request details
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->integer('status_code');
            $table->decimal('duration_ms', 8, 2)->nullable(); // Request duration in milliseconds
            $table->json('request_data')->nullable(); // Sanitized request data

            // Additional metadata
            $table->json('metadata')->nullable(); // Additional context data
            $table->text('description')->nullable(); // Human-readable description

            // Timestamps
            $table->timestamp('timestamp');
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['resource']);
            $table->index(['timestamp']);
            $table->index(['ip_address']);
            $table->index(['status_code']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
