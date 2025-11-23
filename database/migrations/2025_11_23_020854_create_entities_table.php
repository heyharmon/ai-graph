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
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graph_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['product_service', 'location', 'customer_type', 'attribute', 'competitor']);
            $table->string('name');
            $table->string('normalized_name')->nullable(); // For deduplication (v2)
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable(); // Includes source URLs
            $table->timestamps();
            
            $table->index(['graph_id', 'type']);
            $table->index(['graph_id', 'normalized_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
