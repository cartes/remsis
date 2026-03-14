<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('monthly_ipc_values')
            ->orderBy('id')
            ->get()
            ->each(function (object $record): void {
                $normalized = round((float) $record->value, 1);
                $normalized = $normalized == 0.0 ? 0.0 : $normalized;

                DB::table('monthly_ipc_values')
                    ->where('id', $record->id)
                    ->update([
                        'value' => number_format($normalized, 4, '.', ''),
                    ]);
            });

        $ipc = DB::table('legal_parameters')->where('key', 'ipc_value')->first();

        if ($ipc) {
            $normalized = round((float) $ipc->value, 1);
            $normalized = $normalized == 0.0 ? 0.0 : $normalized;

            DB::table('legal_parameters')
                ->where('key', 'ipc_value')
                ->update([
                    'value' => number_format($normalized, 1, '.', ''),
                ]);
        }
    }

    public function down(): void
    {
    }
};
