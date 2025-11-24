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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graph_id')->constrained('graphs')->onDelete('cascade');
            $table->string('url');
            $table->string('title')->nullable();
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->enum('status', ['discovered', 'failed'])->default('discovered');
            $table->timestamp('discovered_at')->nullable();
            $table->timestamps();

            $table->unique(['graph_id', 'url']);
            $table->index('graph_id');
            $table->index('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
