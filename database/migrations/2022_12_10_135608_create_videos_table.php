<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->integer('channel_id');
            $table->string('title', 72);
            $table->string('description', 300);
            $table->string('duration');
            $table->string('video_path');
            $table->string('video_url');
            $table->string('thumbnail_path');
            $table->string('thumbnail_url');
            $table->string('link');
            $table->integer('category');
            $table->string('visibility');
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('dislike_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('videos');
    }
};
