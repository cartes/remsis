<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-white">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow-sm border-b border-gray-100">
                <div class="max-w-7xl mx-auto py-8 px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    <!-- Toast UI -->
    <div x-data x-show="$store.toast?.show" x-transition.opacity
        class="fixed top-6 right-6 z-[100] max-w-sm w-[90vw] sm:w-[420px]" style="display:none">
        <div class="rounded-lg shadow-lg px-4 py-3 text-sm flex items-start gap-3"
            :class="{
                'bg-green-50 text-green-800 border border-green-200': $store.toast.type === 'success',
                'bg-red-50 text-red-800 border border-red-200': $store.toast.type === 'error',
                'bg-blue-50 text-blue-800 border border-blue-200': $store.toast.type === 'info'
            }"
            role="status" aria-live="polite">
            <div class="pt-0.5">
                <template x-if="$store.toast.type === 'success'"><i class="fas fa-check-circle"></i></template>
                <template x-if="$store.toast.type === 'error'"><i class="fas fa-circle-exclamation"></i></template>
                <template x-if="$store.toast.type === 'info'"><i class="fas fa-circle-info"></i></template>
            </div>
            <div class="whitespace-pre-line" x-text="$store.toast.message"></div>
            <button class="ml-auto opacity-70 hover:opacity-100" @click="$store.toast.hide()">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Toast store + helper -->
    <script>
        (function() {
            function registerStore() {
                Alpine.store('toast', {
                    show: false,
                    message: '',
                    type: 'success',
                    _timer: null,
                    flash(message, type = 'success', timeout = 3200) {
                        this.message = message ?? ''
                        this.type = type
                        this.show = true
                        clearTimeout(this._timer)
                        this._timer = setTimeout(() => this.hide(), timeout)
                    },
                    hide() {
                        this.show = false
                        clearTimeout(this._timer)
                        this._timer = null
                    }
                })
            }
            if (window.Alpine) registerStore()
            else document.addEventListener('alpine:init', registerStore)

            window.toast = function(message, type = 'error', timeout = 7000) {
                try {
                    if (window.Alpine && Alpine.store('toast')) Alpine.store('toast').flash(message, type, timeout)
                    else console.log(`[${type}] ${message}`)
                } catch {
                    console.log(`[${type}] ${message}`)
                }
            }

            window.addEventListener('DOMContentLoaded', () => {
                @if (session('success'))
                    toast(@json(session('success')), 'success');
                @endif
                @if (session('status') === 'password-updated')
                    toast('Contraseña actualizada correctamente.', 'success');
                @endif
                @if (session('status') === 'profile-updated')
                    toast('Perfil actualizado correctamente.', 'success');
                @endif
                @if (session('error'))
                    toast(@json(session('error')), 'error');
                @endif
                @if ($errors->any())
                    toast(@json(implode("\n", $errors->all())), 'error');
                @endif
            });
        })();
    </script>
</body>

</html>
