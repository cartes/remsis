<x-adminpanel::layouts.master>
    @section('title', 'Parámetros Legales')

    @section('content')
        <div class="max-w-7xl mx-auto">
            @if (session('success_legal'))
                <div class="mb-6 bg-green-50 text-green-700 border border-green-200 px-4 py-3 rounded-lg flex items-center gap-2 shadow-sm"
                    role="alert">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span class="font-medium">{{ session('success_legal') }}</span>
                </div>
            @endif

            <form action="{{ route('legal_parameters.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">

                    {{-- Indicadores Económicos --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-7">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                                <i class="fas fa-chart-line text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg">Indicadores Económicos</h3>
                                <p class="text-xs text-gray-500">Valores referenciales monetarios</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($legalParameters as $param)
                                @if (in_array($param->key, ['monthly_minimum_wage', 'utm_value']))
                                    <div>
                                        <label for="{{ $param->key }}"
                                            class="block text-xs font-bold text-gray-600 uppercase mb-1 tracking-wide">
                                            {{ $param->label }}
                                        </label>
                                        <div class="relative">
                                            <input type="text" name="{{ $param->key }}" id="{{ $param->key }}"
                                                value="{{ $param->value }}"
                                                class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2.5 bg-gray-50 focus:bg-white transition-colors border"
                                                placeholder="0.00">
                                            @if (str_contains(strtolower($param->label), '%'))
                                                <div
                                                    class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-400 text-sm font-bold">%</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="border-t border-gray-100 bg-slate-50/80 px-6 py-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Indicadores sincronizados</p>
                                    <h4 class="mt-1 text-base font-bold text-slate-900">IPC y UF desde Banco Central</h4>
                                    <p class="mt-1 text-sm text-slate-500">Se muestran aquí como referencia y se administran desde la integración BCCH.</p>
                                </div>

                                <a href="{{ route('settings.bcch') }}"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100">
                                    <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                                    Ir a Banco Central
                                </a>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">IPC vigente</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                                        {{ $latestSyncedIpc ? number_format((float) $latestSyncedIpc->value, 1, ',', '.') . '%' : 'Sin sincronización' }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($latestSyncedIpc)
                                            Periodo {{ $latestSyncedIpc->reference_period->translatedFormat('F Y') }} · publicado {{ optional($latestSyncedIpc->published_at)->format('d-m-Y') }}
                                        @else
                                            Aún no hay IPC sincronizado desde Banco Central.
                                        @endif
                                    </p>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">UF vigente</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                                        {{ $latestSyncedUf ? number_format((float) $latestSyncedUf->value, 2, ',', '.') : 'Sin sincronización' }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($latestSyncedUf)
                                            Valor local al {{ $latestSyncedUf->date->format('d-m-Y') }} · ciclo {{ $latestSyncedUf->cycle_start_date->format('d-m') }} al {{ $latestSyncedUf->cycle_end_date->format('d-m') }}
                                        @else
                                            Aún no hay UF diaria sincronizada desde Banco Central.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if (! $latestSyncedIpc || ! $latestSyncedUf)
                                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-circle-info mt-0.5 text-amber-500"></i>
                                        <p>
                                            Los indicadores todavía no están sincronizados. Configura las credenciales y ejecuta la sincronización en
                                            <a href="{{ route('settings.bcch') }}" class="font-semibold underline underline-offset-2">Banco Central e Indicadores</a>.
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Reglas Laborales --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-5">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="bg-orange-100 text-orange-600 p-2 rounded-lg">
                                <i class="fas fa-briefcase text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg">Reglas Laborales</h3>
                                <p class="text-xs text-gray-500">Jornada y Gratificaciones</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($legalParameters as $param)
                                @if (in_array($param->key, ['weekly_work_hours', 'legal_gratification_percent']))
                                    <div>
                                        <label for="{{ $param->key }}"
                                            class="block text-xs font-bold text-gray-600 uppercase mb-1 tracking-wide">
                                            {{ $param->label }}
                                        </label>
                                        <div class="relative">
                                            <input type="text" name="{{ $param->key }}" id="{{ $param->key }}"
                                                value="{{ $param->value }}"
                                                class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2.5 bg-gray-50 focus:bg-white transition-colors border"
                                                placeholder="0.00">
                                            @if ($param->key == 'weekly_work_hours')
                                                <div
                                                    class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-400 text-xs font-bold">Hrs</span>
                                                </div>
                                            @endif
                                            @if (str_contains(strtolower($param->label), '%'))
                                                <div
                                                    class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-400 text-sm font-bold">%</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Cotizaciones Previsionales --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-12">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="bg-teal-100 text-teal-600 p-2 rounded-lg">
                                <i class="fas fa-hand-holding-usd text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg">Cotizaciones Previsionales</h3>
                                <p class="text-xs text-gray-500">Porcentajes de aporte obligatorio</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($legalParameters as $param)
                                @if (in_array($param->key, [
                                        'default_afp_rate',
                                        'health_insurance_rate',
                                        'sis_rate',
                                        'unemployment_insurance_rate_indefinite',
                                        'mutual_security_rate_min',
                                        'employer_pension_contribution_rate',
                                    ]))
                                    <div class="relative rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <label for="{{ $param->key }}"
                                            class="block text-xs font-bold text-gray-600 uppercase mb-1 tracking-wide">
                                            {{ $param->label }}
                                        </label>
                                        <div class="relative rounded-md shadow-sm">
                                            <input type="text" name="{{ $param->key }}" id="{{ $param->key }}"
                                                value="{{ $param->value }}"
                                                class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-3 bg-white focus:bg-white transition-colors border font-mono text-gray-700"
                                                placeholder="0.00">
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-400 text-sm font-bold">%</span>
                                            </div>
                                        </div>
                                        @if ($param->description)
                                            <p class="mt-1 text-xs text-gray-400">{{ $param->description }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Action Bar --}}
                <div class="mt-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i> Estos valores afectan a todos los cálculos del sistema.
                    </div>
                    <button type="submit"
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg font-bold flex items-center gap-2 transform hover:-translate-y-0.5">
                        <i class="fas fa-save"></i>
                        Guardar Parámetros
                    </button>
                </div>
            </form>
        </div>
    @endsection
</x-adminpanel::layouts.master>
