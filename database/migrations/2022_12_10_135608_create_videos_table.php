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
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('title', 72);
            $table->string('description', 300);
            $table->integer('duration');
            $table->string('video_url');
            $table->string('thumbnail_url');
            $table->string('link');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->integer('allow_comments')->default(1);
            $table->string('visibility');
            $table->float('average_view_duration')->nullable();
            $table->decimal('watch_time')->default(0);
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
