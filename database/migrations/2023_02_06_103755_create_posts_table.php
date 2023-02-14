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
  public function up() {
    Schema::create('posts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('channel_id')->constrained()->onDelete('cascade');
      $table->string('type');
      $table->text('content');
      $table->string('visibility');
      $table->integer('shared_id')->nullable();
      $table->integer('total_votes')->nullable();
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
    public function down() {
      Schema::dropIfExists('posts');
    }
  };