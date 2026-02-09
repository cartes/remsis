@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))
        ->map(fn($n) => mb_substr($n, 0, 1))
        ->take(2)
        ->join('');
    $role = $user->roles->first()?->name ?? 'User';
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="flex items-center gap-3 p-1 hover:bg-gray-50 rounded-2xl transition-all group">
        <div class="text-right hidden sm:block">
            <p class="text-xs font-black text-slate-800 leading-none">{{ $user->name }}</p>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mt-0.5">{{ ucfirst($role) }}</p>
        </div>
        <div
            class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-sm shadow-sm group-hover:shadow-indigo-100 transition-all">
            {{ $initials }}
        </div>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
        style="display: none;">

        <div class="p-4 border-b border-gray-50 bg-[#F8F9FB]/50">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Account</p>
            <p class="text-sm font-bold text-slate-800 truncate">{{ $user->email }}</p>
        </div>

        <div class="p-2">
            <a href="{{ route('profile.edit') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
                <i class="fa-solid fa-user-gear opacity-50"></i>
                Edit Profile
            </a>
            <a href="#"
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
                <i class="fa-solid fa-clock-rotate-left opacity-50"></i>
                Activity Log
            </a>
        </div>

        <div class="p-2 border-t border-gray-50">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold text-rose-500 hover:bg-rose-50 transition-all">
                    <i class="fa-solid fa-right-from-bracket opacity-50"></i>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
