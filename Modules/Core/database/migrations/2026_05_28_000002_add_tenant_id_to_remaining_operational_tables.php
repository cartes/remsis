<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'payrolls',
            'payroll_periods',
            'cost_centers',
            'administrative_day_requests',
            'employee_administrative_day_balances',
            'administrative_day_usages',
            'freelancers',
            'freelancer_receipts',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (! Schema::hasColumn($tableName, 'tenant_id')) {
                        $table->foreignId('tenant_id')
                            ->nullable()
                            ->after('id')
                            ->constrained('tenants')
                            ->nullOnDelete();
                        $table->index('tenant_id');
                    }
                });
            }
        }

        $this->backfillTenantId($tables);
    }

    private function backfillTenantId(array $tables): void
    {
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id') && Schema::hasColumn($tableName, 'company_id')) {
                DB::table($tableName)
                    ->whereNull('tenant_id')
                    ->whereNotNull('company_id')
                    ->orderBy('id')
                    ->chunk(200, function ($records) use ($tableName) {
                        foreach ($records as $record) {
                            $tenantId = DB::table('companies')->where('id', $record->company_id)->value('tenant_id');
                            if ($tenantId) {
                                DB::table($tableName)->where('id', $record->id)->update([
                                    'tenant_id' => $tenantId,
                                ]);
                            }
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'payrolls',
            'payroll_periods',
            'cost_centers',
            'administrative_day_requests',
            'employee_administrative_day_balances',
            'administrative_day_usages',
            'freelancers',
            'freelancer_receipts',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
