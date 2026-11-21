<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plant details on the website: a care guide (Markdown; a video link on its own line becomes a
 * player), video links, and (until the club uploads more photos) sample photo keys for the gallery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->text('details')->nullable()->after('tip_bn');
            $table->text('details_bn')->nullable()->after('details');
            $table->json('videos')->nullable()->after('details_bn');
            $table->json('gallery')->nullable()->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn(['details', 'details_bn', 'videos', 'gallery']);
        });
    }
};
