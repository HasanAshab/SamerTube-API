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
    Schema::create('reports', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('reportable_id');
      $table->string('reportable_type');
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->string('reason', 100);
      $table->timestamps();
    });
  }

  /**
  * Reverse the migrations.
  *
  * @return void
  */
  public function down() {
    Schema::dropIfExists('reports');
  }
};