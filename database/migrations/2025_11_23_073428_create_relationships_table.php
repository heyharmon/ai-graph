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
            $table->foreignId('source_entity_id')->constrained('entities')->onDelete('cascade');
            $table->foreignId('target_entity_id')->constrained('entities')->onDelete('cascade');
            $table->string('relationship_type'); // offered_in, used_by, has_attribute, grouped_into, competes_with
            $table->timestamps();

            $table->index(['graph_id', 'relationship_type']);
            $table->unique(['source_entity_id', 'target_entity_id', 'relationship_type'], 'unique_relationship');
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
