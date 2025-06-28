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
        Schema::table('financial_custodies', function (Blueprint $table) {
            $table->string('disbursement_method')->nullable()->after('notes')->comment('طريقة صرف العهدة: حضوري أو تحويل بنكي');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_custodies', function (Blueprint $table) {
            $table->dropColumn('disbursement_method');
        });
    }
};
