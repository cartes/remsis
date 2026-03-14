<x-adminpanel::layouts.master>
    @section('title', 'Banco Central e Indicadores')

    @section('content')
        <div class="max-w-7xl mx-auto space-y-6" x-data="{ showPassword: false }">
            @if (session('success_bcch'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success_bcch') }}
                </div>
            @endif

            @if (session('error_bcch'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 shadow-sm">
                    <i class="fas fa-triangle-exclamation mr-2"></i>{{ session('error_bcch') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-triangle-exclamation mt-0.5"></i>
                        <div>
                            <p class="font-semibold">No se pudo guardar la configuración.</p>
                            <ul class="mt-1 space-y-1 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if (! $todayUf)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-amber-100 p-2 text-amber-700">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-amber-900">La UF del día aún no está almacenada localmente.</p>
                                <p class="mt-1 text-sm text-amber-800">
                                    El sistema intentará sincronizarla automáticamente una vez al día cuando cualquier usuario inicie sesión.
                                    @if ($attemptedToday && $credential?->last_daily_sync_error)
                                        Último error: {{ $credential->last_daily_sync_error }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($showManualSyncTodayButton)
                            <form action="{{ route('settings.bcch.sync_today') }}" method="POST" class="shrink-0">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    <i class="fas fa-rotate-right"></i>
                                    Sincronizar UF de hoy
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Integración BCCH</p>
                                <h3 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Credenciales del Web Service Siete</h3>
                                <p class="mt-1 text-sm text-slate-500">Se almacenan cifradas usando el encriptador de Laravel.</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $credential ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $credential ? 'Configurado' : 'Pendiente' }}
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('settings.bcch.credentials.update') }}" method="POST" class="space-y-6 px-6 py-6">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Usuario API</label>
                                <input
                                    id="username"
                                    name="username"
                                    type="text"
                                    value="{{ old('username', $credential?->username) }}"
                                    class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-200"
                                    placeholder="usuario@empresa.cl"
                                    required
                                >
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Contraseña API</label>
                                <div class="relative">
                                    <input
                                        id="password"
                                        name="password"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-200"
                                        placeholder="{{ $credential ? 'Dejar en blanco para conservar la actual' : 'Ingresa la contraseña del servicio' }}"
                                        {{ $credential ? '' : 'required' }}
                                    >
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 transition hover:text-slate-700"
                                    >
                                        <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-circle-info mt-0.5 text-slate-400"></i>
                                <p>
                                    El scheduler ejecutará el comando <code class="rounded bg-white px-1.5 py-0.5 text-xs text-slate-700">remsis:sync-indicadores</code>
                                    idealmente los días 8 y 9. El IPC mensual y la UF diaria se guardan localmente para que el liquidador consulte sin depender del API en línea.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                <i class="fas fa-save"></i>
                                Guardar credenciales
                            </button>
                        </div>
                    </form>
                </section>

                <section class="grid gap-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Último IPC sincronizado</p>
                        @if ($latestIpc)
                            <div class="mt-3 flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format((float) $latestIpc->value, 1, ',', '.') }}%</p>
                                    <p class="mt-1 text-sm text-slate-500">Periodo {{ $latestIpc->reference_period->translatedFormat('F Y') }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-4 py-3 text-right text-xs font-medium text-slate-500">
                                    <div>Publicado</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-700">{{ optional($latestIpc->published_at)->format('d-m-Y') }}</div>
                                </div>
                            </div>
                        @else
                            <p class="mt-3 text-sm text-slate-500">Todavía no hay IPC sincronizado.</p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Ciclo UF activo</p>
                        @if ($activeCycleSample)
                            <div class="mt-3 space-y-2 text-sm text-slate-600">
                                <p><span class="font-semibold text-slate-900">Inicio:</span> {{ $activeCycleSample->cycle_start_date->format('d-m-Y') }}</p>
                                <p><span class="font-semibold text-slate-900">Término:</span> {{ $activeCycleSample->cycle_end_date->format('d-m-Y') }}</p>
                                <p><span class="font-semibold text-slate-900">Valor más reciente:</span> {{ number_format((float) $activeCycleSample->value, 2, ',', '.') }}</p>
                            </div>
                        @else
                            <p class="mt-3 text-sm text-slate-500">Aún no existen valores UF calculados localmente.</p>
                        @endif

                        <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <p><span class="font-semibold text-slate-900">UF de hoy:</span>
                                {{ $todayUf ? number_format((float) $todayUf->value, 2, ',', '.') : 'Pendiente de sincronización' }}</p>
                            @if ($credential?->last_daily_sync_attempted_at)
                                <p class="mt-1 text-xs text-slate-500">
                                    Último intento automático: {{ $credential->last_daily_sync_attempted_at->timezone('America/Santiago')->format('d-m-Y H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h3 class="text-lg font-bold tracking-tight text-slate-900">Historial IPC</h3>
                        <p class="mt-1 text-sm text-slate-500">Últimos periodos almacenados desde la API del Banco Central.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Periodo</th>
                                    <th class="px-6 py-3">IPC</th>
                                    <th class="px-6 py-3">Publicación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($ipcHistory as $ipc)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $ipc->reference_period->translatedFormat('F Y') }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ number_format((float) $ipc->value, 1, ',', '.') }}%</td>
                                        <td class="px-6 py-4 text-slate-500">{{ optional($ipc->published_at)->format('d-m-Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-sm text-slate-500">No hay registros IPC todavía.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h3 class="text-lg font-bold tracking-tight text-slate-900">UF diaria del mes actual</h3>
                        <p class="mt-1 text-sm text-slate-500">Valores locales disponibles para el mes calendario en curso.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Fecha</th>
                                    <th class="px-6 py-3">UF</th>
                                    <th class="px-6 py-3">Ciclo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($currentMonthUfValues as $uf)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $uf->date->format('d-m-Y') }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ number_format((float) $uf->value, 2, ',', '.') }}</td>
                                        <td class="px-6 py-4 text-slate-500">{{ $uf->cycle_start_date->format('d-m') }} al {{ $uf->cycle_end_date->format('d-m') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-sm text-slate-500">No hay UF diaria calculada para este mes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    @endsection
</x-adminpanel::layouts.master>
