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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commenter_id');
            $table->foreign('commenter_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('text', 300);
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->integer('heart')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('dislike_count')->default(0);
            $table->integer('reply_count')->default(0);
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
        Schema::dropIfExists('comments');
    }
};
