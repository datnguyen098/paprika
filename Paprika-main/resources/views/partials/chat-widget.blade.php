@php
    $chatBranches = \App\Models\Branch::query()
        ->active()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    $chatSelectedBranch = $selectedBranch ?? null;

    if (! $chatSelectedBranch && isset($branch) && $branch instanceof \App\Models\Branch) {
        $chatSelectedBranch = $branch;
    }

    if (! $chatSelectedBranch && request()->filled('branch')) {
        $branchParam = request('branch');
        $chatSelectedBranch = $chatBranches->first(fn ($branch) => (string) $branch->id === (string) $branchParam || $branch->slug === $branchParam);
    }

@endphp

<div
    class="chat-widget"
    data-chat-widget
    data-start-url="{{ route('chat.start') }}"
    data-branch-id="{{ $chatSelectedBranch?->id }}"
    data-csrf="{{ csrf_token() }}"
>
    <button type="button" class="chat-toggle" data-chat-toggle aria-expanded="false">
        <span class="chat-pulse" aria-hidden="true"></span>
        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
            <path d="M12 3C6.48 3 2 6.92 2 11.75c0 2.76 1.46 5.22 3.75 6.82V22l3.43-1.72c.9.22 1.85.34 2.82.34 5.52 0 10-3.92 10-8.87S17.52 3 12 3Zm-3.2 9.8a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Zm3.2 0a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Zm3.2 0a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Z" />
        </svg>
        <span>{{ __('site.chat.toggle') }}</span>
    </button>

    <section class="chat-panel hidden" data-chat-panel aria-label="{{ __('site.chat.panel_aria') }}">
        <header class="chat-panel-head">
            <div>
                <p class="text-sm font-bold">{{ setting('restaurant_name', 'Paprika') }}</p>
                <p class="text-xs text-emerald-50/80">{{ __('site.chat.subtitle') }}</p>
            </div>
            <button type="button" data-chat-close class="chat-close" aria-label="{{ __('site.chat.close') }}">×</button>
        </header>

        <div class="chat-messages" data-chat-messages>
            <div class="chat-bubble admin">
                {{ __('site.chat.greeting') }}
            </div>
        </div>

        <form class="chat-start-form" data-chat-start-form>
            <div class="grid gap-2">
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                @if ($chatBranches->isNotEmpty())
                    <select name="branch_id" class="chat-input" required aria-label="{{ __('site.chat.branch_aria') }}">
                        <option value="" @selected(! $chatSelectedBranch)>{{ __('site.chat.branch_placeholder') }}</option>
                        @foreach ($chatBranches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $chatSelectedBranch?->id === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                @endif
                <input name="visitor_name" class="chat-input" placeholder="{{ __('site.chat.name_placeholder') }}" autocomplete="name">
                <input type="tel" name="phone" class="chat-input" placeholder="{{ __('site.chat.phone_placeholder') }}" inputmode="tel" autocomplete="tel">
                <textarea name="message" rows="3" class="chat-input" placeholder="{{ __('site.chat.message_placeholder') }}" required></textarea>
            </div>
            <button type="submit" class="chat-submit">{{ __('site.chat.start') }}</button>
            <p class="chat-error hidden" data-chat-error></p>
        </form>

        <form class="chat-send-form hidden" data-chat-send-form>
            <input name="message" class="chat-input" placeholder="{{ __('site.chat.message_input') }}" autocomplete="off" required>
            <button type="submit" class="chat-send-button">{{ __('site.chat.send') }}</button>
        </form>
    </section>
</div>
