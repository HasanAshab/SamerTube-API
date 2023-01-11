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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->string('country', 20);
            $table->string('logo_path')->nullable();
            $table->string('logo_url');
            $table->string('description', 500)->default('');
            $table->integer('total_views')->default(0);
            $table->integer('total_videos')->default(0);
            $table->integer('total_likes')->default(0);
            $table->integer('total_comments')->default(0);
            $table->integer('total_subscribers')->default(0);
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
        Schema::dropIfExists('channels');
    }
};
