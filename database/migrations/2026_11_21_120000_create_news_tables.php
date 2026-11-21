<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * News: stories from the para in categories, each with a cover photo, text (Markdown, a video
 * link on its own line becomes a player), a list of video links and a set of photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name', 80);
            $table->string('name_bn', 80)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained('news_categories')->nullOnDelete();
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->string('summary', 400);
            $table->string('summary_bn', 400)->nullable();
            $table->text('body')->nullable();
            $table->text('body_bn')->nullable();
            $table->json('videos')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('photo', 50)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_posts');
        Schema::dropIfExists('news_categories');
    }
};
