<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. attendance_records
        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (! Schema::hasColumn('attendance_records', 'tenant_id')) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tenants')
                        ->nullOnDelete();
                    $table->index('tenant_id');
                }

                if (! Schema::hasColumn('attendance_records', 'company_id')) {
                    $table->foreignId('company_id')
                        ->nullable()
                        ->after('tenant_id')
                        ->constrained('companies')
                        ->cascadeOnDelete();
                    $table->index('company_id');
                }
            });
        }

        // 2. employee_documents
        if (Schema::hasTable('employee_documents')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_documents', 'tenant_id')) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tenants')
                        ->nullOnDelete();
                    $table->index('tenant_id');
                }

                if (! Schema::hasColumn('employee_documents', 'company_id')) {
                    $table->foreignId('company_id')
                        ->nullable()
                        ->after('tenant_id')
                        ->constrained('companies')
                        ->cascadeOnDelete();
                    $table->index('company_id');
                }
            });
        }

        // 3. employee_logs
        if (Schema::hasTable('employee_logs')) {
            Schema::table('employee_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_logs', 'tenant_id')) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tenants')
                        ->nullOnDelete();
                    $table->index('tenant_id');
                }

                if (! Schema::hasColumn('employee_logs', 'company_id')) {
                    $table->foreignId('company_id')
                        ->nullable()
                        ->after('tenant_id')
                        ->constrained('companies')
                        ->cascadeOnDelete();
                    $table->index('company_id');
                }
            });
        }

        // 4. employee_items
        if (Schema::hasTable('employee_items')) {
            Schema::table('employee_items', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_items', 'tenant_id')) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tenants')
                        ->nullOnDelete();
                    $table->index('tenant_id');
                }

                if (! Schema::hasColumn('employee_items', 'company_id')) {
                    $table->foreignId('company_id')
                        ->nullable()
                        ->after('tenant_id')
                        ->constrained('companies')
                        ->cascadeOnDelete();
                    $table->index('company_id');
                }
            });
        }

        // 5. Backfill existente para consistencia total en BD
        $this->backfillSubordinateTables();
    }

    private function backfillSubordinateTables(): void
    {
        // Backfill attendance_records
        if (Schema::hasTable('attendance_records')) {
            DB::table('attendance_records')
                ->whereNull('tenant_id')
                ->orWhereNull('company_id')
                ->orderBy('id')
                ->chunk(200, function ($records) {
                    foreach ($records as $record) {
                        $emp = DB::table('employees')->where('id', $record->employee_id)->first();
                        if ($emp) {
                            $tenantId = $emp->tenant_id ?? DB::table('companies')->where('id', $emp->company_id)->value('tenant_id');
                            DB::table('attendance_records')->where('id', $record->id)->update([
                                'tenant_id' => $tenantId,
                                'company_id' => $emp->company_id,
                            ]);
                        }
                    }
                });
        }

        // Backfill employee_documents
        if (Schema::hasTable('employee_documents')) {
            DB::table('employee_documents')
                ->whereNull('tenant_id')
                ->orWhereNull('company_id')
                ->orderBy('id')
                ->chunk(200, function ($docs) {
                    foreach ($docs as $doc) {
                        $emp = DB::table('employees')->where('id', $doc->employee_id)->first();
                        if ($emp) {
                            $tenantId = $emp->tenant_id ?? DB::table('companies')->where('id', $emp->company_id)->value('tenant_id');
                            DB::table('employee_documents')->where('id', $doc->id)->update([
                                'tenant_id' => $tenantId,
                                'company_id' => $emp->company_id,
                            ]);
                        }
                    }
                });
        }

        // Backfill employee_logs
        if (Schema::hasTable('employee_logs')) {
            DB::table('employee_logs')
                ->whereNull('tenant_id')
                ->orWhereNull('company_id')
                ->orderBy('id')
                ->chunk(200, function ($logs) {
                    foreach ($logs as $log) {
                        $emp = DB::table('employees')->where('id', $log->employee_id)->first();
                        if ($emp) {
                            $tenantId = $emp->tenant_id ?? DB::table('companies')->where('id', $emp->company_id)->value('tenant_id');
                            DB::table('employee_logs')->where('id', $log->id)->update([
                                'tenant_id' => $tenantId,
                                'company_id' => $emp->company_id,
                            ]);
                        }
                    }
                });
        }

        // Backfill employee_items
        if (Schema::hasTable('employee_items')) {
            DB::table('employee_items')
                ->whereNull('tenant_id')
                ->orWhereNull('company_id')
                ->orderBy('id')
                ->chunk(200, function ($items) {
                    foreach ($items as $item) {
                        $emp = DB::table('employees')->where('id', $item->employee_id)->first();
                        if ($emp) {
                            $tenantId = $emp->tenant_id ?? DB::table('companies')->where('id', $emp->company_id)->value('tenant_id');
                            DB::table('employee_items')->where('id', $item->id)->update([
                                'tenant_id' => $tenantId,
                                'company_id' => $emp->company_id,
                            ]);
                        }
                    }
                });
        }

        // Backfill items
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'tenant_id')) {
            DB::table('items')
                ->whereNull('tenant_id')
                ->whereNotNull('company_id')
                ->orderBy('id')
                ->chunk(200, function ($items) {
                    foreach ($items as $item) {
                        $tenantId = DB::table('companies')->where('id', $item->company_id)->value('tenant_id');
                        if ($tenantId) {
                            DB::table('items')->where('id', $item->id)->update(['tenant_id' => $tenantId]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (Schema::hasColumn('attendance_records', 'company_id')) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                }
                if (Schema::hasColumn('attendance_records', 'tenant_id')) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                }
            });
        }

        if (Schema::hasTable('employee_documents')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                if (Schema::hasColumn('employee_documents', 'company_id')) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                }
                if (Schema::hasColumn('employee_documents', 'tenant_id')) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                }
            });
        }

        if (Schema::hasTable('employee_logs')) {
            Schema::table('employee_logs', function (Blueprint $table) {
                if (Schema::hasColumn('employee_logs', 'company_id')) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                }
                if (Schema::hasColumn('employee_logs', 'tenant_id')) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                }
            });
        }

        if (Schema::hasTable('employee_items')) {
            Schema::table('employee_items', function (Blueprint $table) {
                if (Schema::hasColumn('employee_items', 'company_id')) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                }
            });
        }
    }
};
