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
        Schema::table('circle_incentives', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('notes')->comment('هل تم منع صرف الحافز بسبب عدم وجود فائض كافي');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circle_incentives', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }
};
