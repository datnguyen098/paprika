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
    class="sf-chat-widget"
    data-chat-widget
    data-start-url="{{ route('chat.start') }}"
    data-messages-url="{{ isset($chatSession) ? route('chat.messages', $chatSession) : '' }}"
    data-send-url="{{ isset($chatSession) ? route('chat.send', $chatSession) : '' }}"
    data-session-id="{{ isset($chatSession) ? $chatSession->public_id : '' }}"
    data-branch-id="{{ $chatSelectedBranch?->id }}"
    data-csrf="{{ csrf_token() }}"
>
    {{-- Toggle Button --}}
    <button 
        type="button" 
        class="sf-chat-toggle" 
        data-chat-toggle 
        aria-expanded="false"
        aria-label="{{ __('site.chat.toggle_aria') }}"
    >
        <span class="sf-chat-pulse" aria-hidden="true"></span>
        <svg class="sf-chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <span class="sf-chat-toggle-text">{{ __('site.chat.toggle') }}</span>
        <span class="sf-chat-badge" data-chat-badge style="display: none;"></span>
    </button>

    {{-- Chat Panel - hidden by default --}}
    <div class="sf-chat-panel" data-chat-panel aria-label="{{ __('site.chat.panel_aria') }}" style="display: none;">
        {{-- Header --}}
        <header class="sf-chat-header">
            <div class="sf-chat-header-info">
                <div class="sf-chat-avatar">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div>
                    <p class="sf-chat-brand">{{ setting('restaurant_name', 'Paprika') }}</p>
                    <p class="sf-chat-status">
                        <span class="sf-chat-status-dot"></span>
                        {{ __('site.chat.online') }}
                    </p>
                </div>
            </div>
            <button type="button" data-chat-close class="sf-chat-close" aria-label="{{ __('site.chat.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </header>

        {{-- Messages Container --}}
        <div class="sf-chat-messages" data-chat-messages>
            <div class="sf-chat-date-divider">
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="sf-chat-message sf-chat-message-admin">
                <div class="sf-chat-bubble sf-chat-bubble-admin">
                    <p>{{ __('site.chat.greeting') }}</p>
                </div>
                <span class="sf-chat-time">{{ now()->format('H:i') }}</span>
            </div>
        </div>

        {{-- Start Form (first time) --}}
        <form class="sf-chat-form sf-chat-start-form" data-chat-start-form>
            <div class="sf-chat-form-intro">
                <p>{{ __('site.chat.start_prompt') }}</p>
            </div>
            
            @if ($chatBranches->isNotEmpty())
                <div class="sf-chat-field">
                    <select name="branch_id" class="sf-chat-input" required aria-label="{{ __('site.chat.branch_aria') }}">
                        <option value="">{{ __('site.chat.branch_placeholder') }}</option>
                        @foreach ($chatBranches as $branch)
                            <option value="{{ $branch->id }}" @selected($chatBranches->count() === 1)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            <div class="sf-chat-field">
                <input 
                    type="text" 
                    name="visitor_name" 
                    class="sf-chat-input" 
                    placeholder="{{ __('site.chat.name_placeholder') }}" 
                    autocomplete="name"
                    maxlength="120"
                >
            </div>
            
            <div class="sf-chat-field">
                <input 
                    type="tel" 
                    name="phone" 
                    class="sf-chat-input" 
                    placeholder="{{ __('site.chat.phone_placeholder') }}" 
                    inputmode="tel"
                    autocomplete="tel"
                    maxlength="20"
                >
            </div>
            
            <div class="sf-chat-field">
                <textarea 
                    name="message" 
                    class="sf-chat-input sf-chat-textarea" 
                    placeholder="{{ __('site.chat.message_placeholder') }}" 
                    rows="3"
                    required
                    maxlength="1200"
                ></textarea>
            </div>
            
            <div class="sf-chat-field">
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="sf-chat-honey" aria-hidden="true">
            </div>
            
            <button type="submit" class="sf-chat-submit">
                <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
                {{ __('site.chat.start') }}
            </button>
            
            <p class="sf-chat-error" data-chat-error style="display: none;"></p>
        </form>

        {{-- Send Form (after session started) - hidden by default --}}
        <form class="sf-chat-form sf-chat-send-form" data-chat-send-form style="display: none;">
            <div class="sf-chat-input-group">
                <textarea 
                    name="message" 
                    class="sf-chat-input sf-chat-textarea" 
                    placeholder="{{ __('site.chat.message_input') }}" 
                    rows="1"
                    autocomplete="off"
                    required
                    maxlength="1200"
                ></textarea>
                <button type="submit" class="sf-chat-send-btn" aria-label="{{ __('site.chat.send') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </form>
        
        {{-- Typing indicator (for admin typing) --}}
        <div class="sf-chat-typing hidden" data-chat-typing>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>
