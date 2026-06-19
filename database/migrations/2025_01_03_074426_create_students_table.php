<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('nim', 15)->unique();
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 20)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('phone_number', 15)->nullable();
            $table->string('prodi', 25)->nullable();
            $table->integer('semester')->nullable();
            $table->string('class', 10)->nullable();
            $table->timestamps();

            // 🔥 Streak System
            $table->integer('streak')->default(0);
            $table->date('last_played')->nullable();

            // EXP
            $table->integer('exp')->default(0);

            // 🏆 Score System
            $table->integer('weekly_score')->default(0); // Skor yang direset setiap minggu
            $table->integer('total_score')->default(0);  // Akumulasi total skor sepanjang waktu

            // Current Challenge
            $table->foreignId('current_challenge_id')->nullable()->constrained('challenges')->onDelete('set null');
            $table->foreignId('current_section_id')->nullable()->constrained('sections')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
