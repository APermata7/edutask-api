<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('submission_id')
                ->nullable()
                ->after('id')
                ->constrained('submissions')
                ->cascadeOnDelete();

            $table->foreignId('lecturer_id')
                ->nullable()
                ->after('submission_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('feedback')->nullable()->after('score');

            $table->unique('submission_id');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->foreignId('submission_id')
                ->nullable()
                ->after('id')
                ->constrained('submissions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->after('submission_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['submission_id', 'user_id']);
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique(['submission_id']);
            $table->dropForeign(['submission_id']);
            $table->dropForeign(['lecturer_id']);
            $table->dropColumn(['submission_id', 'lecturer_id', 'feedback']);
        });
    }
};
