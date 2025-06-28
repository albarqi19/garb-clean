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
        Schema::table('student_curricula', function (Blueprint $table) {
            if (!Schema::hasColumn('student_curricula', 'completion_percentage')) {
                $table->decimal('completion_percentage', 5, 2)->default(0)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_curricula', function (Blueprint $table) {
            if (Schema::hasColumn('student_curricula', 'completion_percentage')) {
                $table->dropColumn('completion_percentage');
            }
        });
    }
};
