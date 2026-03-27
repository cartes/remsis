        <div x-show="tab === 'documentos'" x-cloak
             x-data="{ isUploadModalOpen: false }">

            {{-- Cabecera --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Carpeta Digital</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Contratos, anexos y comprobantes del colaborador</p>
                </div>
                <button type="button" @click="isUploadModalOpen = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                    {{-- Heroicon: arrow-up-tray --}}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    Subir Documento
                </button>
            </div>

            @forelse ($employee->documents as $document)

                @php
                    $docTypeLabelMap = [
                        'contrato'    => 'Contrato',
                        'anexo'       => 'Anexo',
                        'comprobante' => 'Comprobante',
                        'legal'       => 'Documento Legal',
                        'otro'        => 'Otro',
                    ];
                    $sigBadgeMap = [
                        'sin_firma' => [
                            'label' => 'Sin firma',
                            'class' => 'bg-slate-100 text-slate-500 border-slate-200',
                            'dot'   => 'bg-slate-400',
                        ],
                        'pendiente_colaborador' => [
                            'label' => 'Pendiente Colaborador',
                            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'dot'   => 'bg-amber-500',
                        ],
                        'pendiente_empresa' => [
                            'label' => 'Pendiente Empresa',
                            'class' => 'bg-orange-50 text-orange-700 border-orange-200',
                            'dot'   => 'bg-orange-500',
                        ],
                        'firmado_completamente' => [
                            'label' => 'Firmado',
                            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'dot'   => 'bg-emerald-500',
                        ],
                    ];
                    $sigBadge = $sigBadgeMap[$document->signature_status] ?? $sigBadgeMap['sin_firma'];
                @endphp

                @if ($loop->first)
                {{-- Tabla: abrir sólo en la primera iteración --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Documento</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Fecha de Carga</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Estado de Firma</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                @endif

                            <tr class="hover:bg-slate-50/70 transition-colors group">

                                {{-- Documento --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0 group-hover:bg-red-100 transition-colors">
                                            {{-- Heroicon: document --}}
                                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 leading-tight">{{ $document->title }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                {{ $docTypeLabelMap[$document->document_type] ?? $document->document_type }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Fecha de carga --}}
                                <td class="px-5 py-4 text-slate-500">
                                    {{ $document->created_at->format('d/m/Y') }}
                                </td>

                                {{-- Badge estado firma --}}
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $sigBadge['class'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sigBadge['dot'] }}"></span>
                                        {{ $sigBadge['label'] }}
                                    </span>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-5 py-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        {{-- Descargar --}}
                                        <a href="{{ route('companies.employees.documents.download', [$company, $employee, $document]) }}"
                                            title="Descargar"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:border-slate-900 hover:text-slate-900 transition-all">
                                            {{-- Heroicon: arrow-down-tray --}}
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            Descargar
                                        </a>

                                        {{-- Solicitar Firma --}}
                                        @if (in_array($document->signature_status, ['sin_firma', 'pendiente_colaborador', 'pendiente_empresa']))
                                            <button type="button" title="Solicitar firma"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100 transition-all">
                                                {{-- Heroicon: pencil-square --}}
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                                Solicitar Firma
                                            </button>
                                        @endif

                                        {{-- Eliminar --}}
                                        <form method="POST"
                                            action="{{ route('companies.employees.documents.destroy', [$company, $employee, $document]) }}"
                                            onsubmit="return confirm('¿Eliminar este documento?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                class="inline-flex items-center rounded-lg border border-red-100 bg-red-50 p-1.5 text-red-400 hover:bg-red-100 hover:text-red-600 transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                @if ($loop->last)
                        </tbody>
                    </table>
                </div>
                @endif

            @empty

                {{-- Empty state --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-16 text-center">
                    <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                        {{-- Heroicon: folder-open --}}
                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-600 mb-1">Carpeta digital vacía</h3>
                    <p class="text-sm text-slate-400 max-w-xs mx-auto leading-relaxed">
                        Sube el primer contrato o anexo del colaborador usando el botón <strong>"Subir Documento"</strong>.
                    </p>
                </div>

            @endforelse

            {{-- ── Modal: Subir Documento ──────────────────────────────────── --}}
            <div x-show="isUploadModalOpen"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape.window="isUploadModalOpen = false">

                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                     @click="isUploadModalOpen = false"></div>

                {{-- Panel --}}
                <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-2xl"
                     @click.stop>

                    {{-- Header modal --}}
                    <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">Subir Documento</h3>
                        </div>
                        <button type="button" @click="isUploadModalOpen = false"
                            class="rounded-lg p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Formulario --}}
                    <form method="POST"
                          action="{{ route('companies.employees.documents.store', [$company, $employee]) }}"
                          enctype="multipart/form-data"
                          class="px-6 py-5 space-y-4">
                        @csrf

                        {{-- Título --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Título del documento <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" required
                                placeholder="Ej: Contrato de Trabajo Indefinido"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>

                        {{-- Tipo de documento --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Tipo de documento <span class="text-red-500">*</span>
                            </label>
                            <select name="document_type" required
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                                <option value="">— Selecciona un tipo —</option>
                                <option value="contrato">Contrato</option>
                                <option value="anexo">Anexo</option>
                                <option value="comprobante">Comprobante</option>
                                <option value="legal">Documento Legal</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        {{-- Archivo PDF --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Archivo PDF <span class="text-red-500">*</span>
                            </label>
                            <label class="flex flex-col items-center justify-center w-full rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-4 py-8 cursor-pointer hover:border-blue-400 hover:bg-blue-50/40 transition-colors"
                                   x-data="{ fileName: '' }">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <p class="text-sm text-slate-400 text-center" x-text="fileName || 'Haz clic para seleccionar o arrastra el PDF aquí'"></p>
                                <p class="text-xs text-slate-300 mt-1">Máximo 10 MB · Solo PDF</p>
                                <input type="file" name="file" accept=".pdf" required class="sr-only"
                                    @change="fileName = $event.target.files[0]?.name || ''">
                            </label>
                            @error('file')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Botones --}}
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="isUploadModalOpen = false"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                Subir Documento
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: ÍTEMS                                                      --}}
