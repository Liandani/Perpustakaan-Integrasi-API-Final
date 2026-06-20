<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->timestamp('due_date');
            $table->timestamp('return_date')->nullable();
            $table->integer('late_days')->default(0);
            $table->integer('fine_per_day')->default(2000);
            $table->integer('total_fine')->default(0);
            $table->string('status')->default('no_fine'); // no_fine, unpaid, paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
