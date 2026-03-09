<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('status')->default('published'); // Back-compat
            // Make fields nullable for drafts
            $table->text('story_subject')->nullable()->change();
            $table->string('story_type')->nullable()->change();
            $table->string('age_group')->nullable()->change();
            $table->string('image_style')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('status');
            // Revert to required (might fail if nulls exist, but standard practice)
            $table->text('story_subject')->nullable(false)->change();
            $table->string('story_type')->nullable(false)->change();
            $table->string('age_group')->nullable(false)->change();
            $table->string('image_style')->nullable(false)->change();
        });
    }
};
