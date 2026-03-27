        <div x-show="tab === 'liquidaciones'" x-cloak>

            {{-- Cabecera --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Historial de Liquidaciones
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Registro de sueldos procesados del colaborador</p>
                </div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ $employee->payrolls->count() }}
                    {{ $employee->payrolls->count() === 1 ? 'liquidación' : 'liquidaciones' }}
                </span>
            </div>

            @if ($employee->payrolls->isEmpty())

                {{-- Empty state --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-16 text-center">
                    <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-600 mb-1">Sin liquidaciones históricas</h3>
                    <p class="text-sm text-slate-400 max-w-xs mx-auto leading-relaxed">
                        Este colaborador aún no tiene liquidaciones históricas procesadas.
                    </p>
                </div>
            @else
                {{-- Tabla de liquidaciones --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Período</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Total Haberes</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Total Descuentos</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sueldo Líquido</th>
                                <th
                                    class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Estado</th>
                                <th
                                    class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php
                                $meses = [
                                    1 => 'Enero',
                                    2 => 'Febrero',
                                    3 => 'Marzo',
                                    4 => 'Abril',
                                    5 => 'Mayo',
                                    6 => 'Junio',
                                    7 => 'Julio',
                                    8 => 'Agosto',
                                    9 => 'Septiembre',
                                    10 => 'Octubre',
                                    11 => 'Noviembre',
                                    12 => 'Diciembre',
                                ];
                                $statusMap = [
                                    'pending' => [
                                        'label' => 'Pendiente',
                                        'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    ],
                                    'processed' => [
                                        'label' => 'Procesado',
                                        'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    ],
                                    'paid' => [
                                        'label' => 'Pagado',
                                        'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    ],
                                    'cancelled' => [
                                        'label' => 'Anulado',
                                        'class' => 'bg-red-50 text-red-600 border-red-200',
                                    ],
                                ];
                            @endphp

                            @foreach ($employee->payrolls as $payroll)
                                @php
                                    $badge = $statusMap[$payroll->status] ?? $statusMap['pending'];
                                    $haberes =
                                        $payroll->total_earnings > 0
                                            ? $payroll->total_earnings
                                            : $payroll->gross_salary;
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors group">

                                    {{-- Período --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 0 -2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                                                </svg>
                                            </div>
                                            <button type="button"
                                                @click="selectedPayslip = {{ json_encode($payroll) }}; isPayslipModalOpen = true"
                                                class="text-left group/btn">
                                                <p
                                                    class="font-semibold text-blue-600 group-hover/btn:text-blue-800 transition-colors">
                                                    {{ $meses[$payroll->period_month] ?? '—' }}
                                                    {{ $payroll->period_year }}
                                                </p>
                                                @if ($payroll->payment_date)
                                                    <p class="text-xs text-slate-400 mt-0.5">
                                                        Pagado el
                                                        {{ \Carbon\Carbon::parse($payroll->payment_date)->format('d/m/Y') }}
                                                    </p>
                                                @endif
                                            </button>
                                        </div>
                                    </td>

                                    {{-- Total Haberes --}}
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-semibold text-slate-800">
                                            $&nbsp;{{ number_format($haberes, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Total Descuentos --}}
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-semibold text-red-600">
                                            $&nbsp;{{ number_format($payroll->total_deductions, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Sueldo Líquido --}}
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-bold text-slate-900 text-base">
                                            $&nbsp;{{ number_format($payroll->net_salary, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Estado badge --}}
                                    <td class="px-5 py-4 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('companies.employees.payrolls.pdf', [$company, $employee, $payroll]) }}"
                                            title="Descargar liquidación PDF"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:border-slate-900 hover:text-slate-900 transition-all group-hover:shadow-md">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            PDF
                                        </a>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                        {{-- Resumen totales al pie --}}
                        @php
                            $totalHaberes = $employee->payrolls->sum(
                                fn($p) => $p->total_earnings > 0 ? $p->total_earnings : $p->gross_salary,
                            );
                            $totalDescuentos = $employee->payrolls->sum('total_deductions');
                            $totalLiquido = $employee->payrolls->sum('net_salary');
                        @endphp
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr>
                                <td class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Totales acumulados
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-slate-700">
                                    $&nbsp;{{ number_format($totalHaberes, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-red-600">
                                    $&nbsp;{{ number_format($totalDescuentos, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-slate-900">
                                    $&nbsp;{{ number_format($totalLiquido, 0, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>

            @endif

        </div>

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: DOCUMENTOS                                                  --}}
