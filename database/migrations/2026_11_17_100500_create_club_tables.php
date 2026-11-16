<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notices, meetings and polls, the committee list, CMS pages, site settings and enquiries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('tag', 50)->nullable();
            $table->string('tag_bn', 50)->nullable();
            $table->text('summary')->nullable();
            $table->text('summary_bn')->nullable();
            $table->text('body')->nullable();
            $table->text('body_bn')->nullable();
            $table->string('audience', 20)->default('public')->index();
            $table->boolean('pinned')->default(false);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->date('held_on');
            $table->text('minutes')->nullable();
            $table->json('attendees')->nullable();
            $table->timestamps();
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question');
            $table->string('question_bn')->nullable();
            $table->json('options');
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('choice');
            $table->timestamps();

            $table->unique(['poll_id', 'member_id']);
        });

        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('role', 100);
            $table->string('role_bn', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('photo')->nullable();
            $table->unsignedSmallInteger('since')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->json('blocks')->nullable();
            $table->json('draft')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->index();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->json('payload')->nullable();
            $table->string('locale', 2)->default('en');
            $table->timestamp('handled_at')->nullable()->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('committee_members');
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('notices');
    }
};
