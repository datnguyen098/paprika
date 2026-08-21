@php
    $locales = available_locales();
    $current = current_locale();
    $variant = $variant ?? 'desktop';
    $flagMap = [
        'vi' => 'VN',
        'en' => 'EN',
        'el' => 'EL',
    ];
@endphp

@if (count($locales) > 1)
    @if ($variant === 'mobile-floating')
        <div class="md:hidden relative" data-locale-switcher>
            <button type="button"
                data-locale-toggle
                class="flex items-center gap-1 px-2.5 py-2 rounded-full bg-white/95 shadow ring-1 ring-black/10 text-[#064E3B] font-extrabold text-[11px] uppercase tracking-widest hover:bg-white transition"
                aria-label="Đổi ngôn ngữ"
                aria-haspopup="true"
                aria-expanded="false">
                <span class="text-sm leading-none">{{ $flagMap[$current] ?? '🌐' }}</span>
                <span>{{ $locales[$current]['native'] ?? strtoupper($current) }}</span>
            </button>
            <div data-locale-menu hidden class="absolute right-0 mt-2 min-w-[180px] rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 overflow-hidden z-[60]">
                @foreach ($locales as $code => $config)
                    <a href="{{ locale_switch_url($code) }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm font-semibold transition {{ $current === $code ? 'bg-emerald-50 text-[#064E3B]' : 'text-slate-700 hover:bg-slate-50' }}"
                        @if ($current === $code) aria-current="true" @endif>
                        <span class="text-lg leading-none">{{ $flagMap[$code] ?? '🌐' }}</span>
                        <span class="flex-1">{{ $config['name'] }}</span>
                        @if ($current === $code)
                            <svg class="w-4 h-4 text-[#B91C1C]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @elseif ($variant === 'mobile-inline')
        <div class="px-1">
            <span class="block text-[10px] uppercase tracking-wider text-white/60 font-bold mb-2">Ngôn ngữ</span>
            <div class="grid grid-cols-{{ count($locales) }} gap-2">
                @foreach ($locales as $code => $config)
                    <a href="{{ locale_switch_url($code) }}"
                        class="flex items-center justify-center gap-1.5 px-2 py-2.5 rounded-xl text-[11px] uppercase tracking-widest font-bold transition {{ $current === $code ? 'bg-white text-[#064E3B] shadow-md' : 'bg-white/10 text-white/85 hover:bg-white/20' }}"
                        @if ($current === $code) aria-current="true" @endif>
                        <span class="text-sm leading-none">{{ $flagMap[$code] ?? '🌐' }}</span>
                        <span>{{ $config['native'] ?? strtoupper($code) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <div class="relative" data-locale-switcher>
            <button type="button"
                data-locale-toggle
                class="flex items-center gap-1.5 px-3 py-2 rounded-full bg-white/10 hover:bg-white/20 border border-white/15 text-white text-[11px] uppercase tracking-widest font-extrabold transition"
                aria-label="Đổi ngôn ngữ"
                aria-haspopup="true"
                aria-expanded="false">
                <span class="text-sm leading-none">{{ $flagMap[$current] ?? '🌐' }}</span>
                <span>{{ $locales[$current]['native'] ?? strtoupper($current) }}</span>
                <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div data-locale-menu hidden class="absolute right-0 mt-2 min-w-[180px] rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 overflow-hidden z-50">
                @foreach ($locales as $code => $config)
                    <a href="{{ locale_switch_url($code) }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm font-semibold transition {{ $current === $code ? 'bg-emerald-50 text-[#064E3B]' : 'text-slate-700 hover:bg-slate-50' }}"
                        @if ($current === $code) aria-current="true" @endif>
                        <span class="text-lg leading-none">{{ $flagMap[$code] ?? '🌐' }}</span>
                        <span class="flex-1">{{ $config['name'] }}</span>
                        @if ($current === $code)
                            <svg class="w-4 h-4 text-[#B91C1C]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endif

@once
    <script>
        (function () {
            const init = () => {
                document.querySelectorAll('[data-locale-switcher]').forEach((switcher) => {
                    if (switcher.dataset.localeBound === '1') return;
                    switcher.dataset.localeBound = '1';

                    const toggle = switcher.querySelector('[data-locale-toggle]');
                    const menu = switcher.querySelector('[data-locale-menu]');
                    if (!toggle || !menu) return;

                    const close = () => {
                        menu.hidden = true;
                        toggle.setAttribute('aria-expanded', 'false');
                    };

                    toggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const open = !menu.hidden;
                        document.querySelectorAll('[data-locale-menu]').forEach((m) => {
                            if (m !== menu) {
                                m.hidden = true;
                                m.closest('[data-locale-switcher]')
                                    ?.querySelector('[data-locale-toggle]')
                                    ?.setAttribute('aria-expanded', 'false');
                            }
                        });
                        if (open) {
                            close();
                        } else {
                            menu.hidden = false;
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    });

                    document.addEventListener('click', (e) => {
                        if (!switcher.contains(e.target)) close();
                    });

                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') close();
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@endonce
