@php
    $pendingRequests = $dayRequests->where('status', 'pending');
    $historicalRequests = $dayRequests->where('status', '!=', 'pending');
@endphp

<div class="space-y-8">
    {{-- Solicitudes Pendientes --}}
    <div>
        <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-800">Solicitudes Pendientes</h3>
            <p class="text-sm text-gray-500">Bandeja de entrada para autorizar o rechazar solicitudes de días administrativos.</p>
        </div>

        @if ($pendingRequests->isEmpty())
            <div class="text-center py-10 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
                <i class="fas fa-check-circle text-slate-300 text-3xl mb-2"></i>
                <p class="text-sm font-semibold text-slate-500">No hay solicitudes pendientes</p>
                <p class="text-xs text-slate-400 mt-0.5">Todas las solicitudes han sido resueltas o canceladas.</p>
            </div>
        @else
            <div class="overflow-x-auto border border-slate-150 rounded-xl bg-white shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-3.5">Colaborador</th>
                            <th class="px-6 py-3.5">Fecha Solicitada</th>
                            <th class="px-6 py-3.5">Comentarios</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach ($pendingRequests as $req)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800">{{ $req->employee->full_name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $req->employee->rut }}</div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-700">
                                    {{ \Carbon\Carbon::parse($req->requested_date)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-sm truncate" title="{{ $req->notes }}">
                                    {{ $req->notes ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <form action="{{ route('companies.requests.approve', [$company, $req]) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas APROBAR esta solicitud?');" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                <i class="fas fa-check"></i> Aprobar
                                            </button>
                                        </form>
                                        <button type="button" 
                                                @click="rejectingRequest = { id: {{ $req->id }}, employee_name: '{{ addslashes($req->employee->full_name) }}', date: '{{ \Carbon\Carbon::parse($req->requested_date)->format('d/m/Y') }}' }"
                                                class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                            <i class="fas fa-xmark"></i> Rechazar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <hr class="border-slate-100">

    {{-- Historial de Solicitudes --}}
    <div>
        <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-800">Historial de Solicitudes</h3>
            <p class="text-sm text-gray-500">Registro histórico de solicitudes procesadas.</p>
        </div>

        @if ($historicalRequests->isEmpty())
            <div class="text-center py-10 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
                <p class="text-sm font-semibold text-slate-500">No hay solicitudes en el historial</p>
                <p class="text-xs text-slate-400 mt-0.5">El historial aparecerá aquí a medida que se resuelvan solicitudes.</p>
            </div>
        @else
            <div class="overflow-x-auto border border-slate-150 rounded-xl bg-white shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-3.5">Colaborador</th>
                            <th class="px-6 py-3.5">Fecha Solicitada</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Resolución</th>
                            <th class="px-6 py-3.5">Comentarios Trabajador</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach ($historicalRequests as $req)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800">{{ $req->employee->full_name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $req->employee->rut }}</div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-700">
                                    {{ \Carbon\Carbon::parse($req->requested_date)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($req->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aprobada</span>
                                    @elseif ($req->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Rechazada</span>
                                    @elseif ($req->status === 'cancelled')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">Cancelada</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    @if ($req->status === 'approved')
                                        <div>Autorizado por: {{ $req->resolver?->name ?? 'Sistema' }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $req->resolved_at?->format('d/m/Y H:i') }}</div>
                                    @elseif ($req->status === 'rejected')
                                        <div>Rechazado por: {{ $req->resolver?->name ?? 'Sistema' }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $req->resolved_at?->format('d/m/Y H:i') }}</div>
                                        @if ($req->rejection_reason)
                                            <div class="mt-1 text-rose-600 font-medium italic">Motivo: {{ $req->rejection_reason }}</div>
                                        @endif
                                    @elseif ($req->status === 'cancelled')
                                        Cancelada por el colaborador
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-xs truncate" title="{{ $req->notes }}">
                                    {{ $req->notes ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Rejection Form Modal --}}
    <div x-show="rejectingRequest !== null" 
         class="fixed inset-0 z-[70] flex items-center justify-center p-4" 
         x-cloak>
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="rejectingRequest = null"></div>
        
        {{-- Modal Content --}}
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden relative z-10 p-6 transform transition-all"
             x-show="rejectingRequest !== null"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Rechazar Solicitud</h3>
                <button type="button" @click="rejectingRequest = null" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <p class="text-xs text-slate-500 mb-4">
                ¿Seguro que deseas rechazar la solicitud de día administrativo para <span class="font-bold text-slate-700" x-text="rejectingRequest ? rejectingRequest.employee_name : ''"></span> el día <span class="font-bold text-slate-700" x-text="rejectingRequest ? rejectingRequest.date : ''"></span>?
            </p>

            <form method="POST" x-bind:action="'/companies/{{ $company->id }}/requests/' + (rejectingRequest ? rejectingRequest.id : '') + '/reject'" class="space-y-4">
                @csrf
                <div>
                    <label for="rejection_reason" class="block text-xs font-bold text-slate-500 uppercase mb-1">Motivo del Rechazo</label>
                    <textarea id="rejection_reason" 
                              name="rejection_reason" 
                              required 
                              rows="3" 
                              placeholder="Explique brevemente por qué se rechaza la solicitud..." 
                              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="rejectingRequest = null" 
                            class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200/80 rounded-xl transition-all">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-all shadow-sm">
                        Rechazar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
