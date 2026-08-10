<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('employees', 'idx_employees_company_mobile_status_deleted', ['saas_company_id', 'mobile', 'status', 'deleted_at']);
        $this->addIndexIfMissing('employees', 'idx_employees_company_email_work_status_deleted', ['saas_company_id', 'email_work', 'status', 'deleted_at']);
        $this->addIndexIfMissing('employees', 'idx_employees_company_email_personal_status_deleted', ['saas_company_id', 'email_personal', 'status', 'deleted_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('employees', 'idx_employees_company_email_personal_status_deleted');
        $this->dropIndexIfExists('employees', 'idx_employees_company_email_work_status_deleted');
        $this->dropIndexIfExists('employees', 'idx_employees_company_mobile_status_deleted');
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
