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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graph_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('page_type')->nullable(); // service, location, about, blog, etc.
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->timestamp('crawled_at')->nullable();
            $table->timestamps();
            
            $table->unique(['graph_id', 'url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
