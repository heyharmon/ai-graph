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
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graph_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_entity_id')->constrained('entities')->onDelete('cascade');
            $table->foreignId('to_entity_id')->constrained('entities')->onDelete('cascade');
            $table->string('type'); // offers_in, uses, has_attribute, grouped_into, etc.
            $table->float('strength')->nullable()->default(1.0); // For v2
            $table->json('source_urls')->nullable();
            $table->timestamps();
            
            $table->unique(['graph_id', 'from_entity_id', 'to_entity_id', 'type']);
            $table->index(['graph_id', 'from_entity_id']);
            $table->index(['graph_id', 'to_entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationships');
    }
};
