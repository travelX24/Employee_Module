<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE employees MODIFY annual_leave_days DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0'
        );

        DB::statement(
            'ALTER TABLE employees MODIFY opening_leave_balance DECIMAL(8,2) NULL DEFAULT NULL'
        );

        DB::statement(
            'ALTER TABLE employees MODIFY leave_balance_adjustments DECIMAL(8,2) NOT NULL DEFAULT 0'
        );
    }

    public function down(): void
    {
        $hasFractions = DB::table('employees')
            ->whereRaw('annual_leave_days <> FLOOR(annual_leave_days)')
            ->orWhereRaw('(opening_leave_balance IS NOT NULL AND opening_leave_balance <> FLOOR(opening_leave_balance))')
            ->orWhereRaw('leave_balance_adjustments <> FLOOR(leave_balance_adjustments)')
            ->exists();

        if ($hasFractions) {
            throw new RuntimeException(
                'Cannot rollback fractional employee leave balances without data loss.'
            );
        }

        DB::statement(
            'ALTER TABLE employees MODIFY annual_leave_days SMALLINT UNSIGNED NOT NULL DEFAULT 0'
        );

        DB::statement(
            'ALTER TABLE employees MODIFY opening_leave_balance SMALLINT NULL DEFAULT NULL'
        );

        DB::statement(
            'ALTER TABLE employees MODIFY leave_balance_adjustments SMALLINT NOT NULL DEFAULT 0'
        );
    }
};