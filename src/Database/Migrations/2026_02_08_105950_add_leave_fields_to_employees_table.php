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
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_transferred_employee')->default(false)->after('annual_leave_days')->comment('موظف منقول للنظام');
            $table->smallInteger('opening_leave_balance')->nullable()->after('is_transferred_employee')->comment('الرصيد الافتتاحي للإجازات (يمكن أن يكون سالب)');
            $table->smallInteger('leave_balance_adjustments')->default(0)->after('opening_leave_balance')->comment('تعديلات الرصيد (+/-)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['is_transferred_employee', 'opening_leave_balance', 'leave_balance_adjustments']);
        });
    }
};
