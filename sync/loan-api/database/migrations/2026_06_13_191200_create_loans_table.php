<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->timestamp('loan_date');
            $table->timestamp('due_date');
            $table->timestamp('return_date')->nullable();
            $table->string('status')->default('borrowed'); // borrowed, returned
            $table->timestamps();
        });

        Schema::create('loan_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->string('action'); // e.g. borrowed, returned, fine_paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_histories');
        Schema::dropIfExists('loans');
    }
};
