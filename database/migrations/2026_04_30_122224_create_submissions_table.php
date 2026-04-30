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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            
            // 1. Relasi ke tabel users (siapa yang mengumpulkan)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // 2. Judul tugas atau keterangan tugasnya
            $table->string('task_title'); 
            
            // 3. Link gambar / file tugas yang diupload
            $table->string('file_url'); 
            
            // 4. Nilai dari admin (nullable berarti boleh kosong sebelum dinilai)
            $table->integer('grade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
