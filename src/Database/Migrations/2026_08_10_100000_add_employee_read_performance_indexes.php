<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('employees', 'idx_employees_company_status_deleted_id', ['saas_company_id', 'status', 'deleted_at', 'id']);
        $this->addIndexIfMissing('employees', 'idx_employees_company_branch_status_deleted_id', ['saas_company_id', 'branch_id', 'status', 'deleted_at', 'id']);
        $this->addIndexIfMissing('employees', 'idx_employees_company_status_hired_deleted', ['saas_company_id', 'status', 'hired_at', 'deleted_at']);
        $this->addIndexIfMissing('employees', 'idx_employees_company_mobile_status_deleted', ['saas_company_id', 'mobile', 'status', 'deleted_at']);
        $this->addIndexIfMissing('employees', 'idx_employees_company_email_work_status_deleted', ['saas_company_id', 'email_work', 'status', 'deleted_at']);
        $this->addIndexIfMissing('employees', 'idx_employees_company_email_personal_status_deleted', ['saas_company_id', 'email_personal', 'status', 'deleted_at']);

        $this->addIndexIfMissing('employee_documents', 'idx_employee_documents_employee_type_deleted', ['employee_id', 'type', 'deleted_at']);
        $this->addIndexIfMissing('employee_status_logs', 'idx_employee_status_logs_employee_created', ['employee_id', 'created_at']);
        $this->addIndexIfMissing('employee_leave_adjustments', 'idx_employee_leave_adjustments_employee_created', ['employee_id', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('employee_leave_adjustments', 'idx_employee_leave_adjustments_employee_created');
        $this->dropIndexIfExists('employee_status_logs', 'idx_employee_status_logs_employee_created');
        $this->dropIndexIfExists('employee_documents', 'idx_employee_documents_employee_type_deleted');

        $this->dropIndexIfExists('employees', 'idx_employees_company_email_personal_status_deleted');
        $this->dropIndexIfExists('employees', 'idx_employees_company_email_work_status_deleted');
        $this->dropIndexIfExists('employees', 'idx_employees_company_mobile_status_deleted');
        $this->dropIndexIfExists('employees', 'idx_employees_company_status_hired_deleted');
        $this->dropIndexIfExists('employees', 'idx_employees_company_branch_status_deleted_id');
        $this->dropIndexIfExists('employees', 'idx_employees_company_status_deleted_id');
    }

    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropIndex($index);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
