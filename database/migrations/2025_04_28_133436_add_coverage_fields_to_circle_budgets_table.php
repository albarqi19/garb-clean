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
        Schema::table('circle_budgets', function (Blueprint $table) {
            $table->decimal('monthly_cost', 10, 2)->default(0)->after('remaining_budget')->comment('التكلفة الشهرية الكلية للحلقة');
            $table->decimal('monthly_salaries_cost', 10, 2)->default(0)->after('monthly_cost')->comment('تكلفة الرواتب الشهرية للحلقة');
            $table->float('coverage_months', 8, 2)->default(0)->after('monthly_salaries_cost')->comment('عدد الأشهر التي يغطيها المبلغ المتبقي');
            $table->date('coverage_end_date')->nullable()->after('coverage_months')->comment('تاريخ نهاية تغطية الميزانية');
            $table->boolean('is_at_risk')->default(false)->after('coverage_end_date')->comment('هل الحلقة في خطر مالي (تغطية أقل من 3 أشهر)');
            $table->boolean('has_surplus')->default(false)->after('is_at_risk')->comment('هل يوجد فائض في ميزانية الحلقة');
            $table->decimal('surplus_amount', 10, 2)->default(0)->after('has_surplus')->comment('مقدار الفائض المالي المتاح للحوافز أو العهد');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circle_budgets', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_cost',
                'monthly_salaries_cost',
                'coverage_months',
                'coverage_end_date',
                'is_at_risk',
                'has_surplus',
                'surplus_amount',
            ]);
        });
    }
};
