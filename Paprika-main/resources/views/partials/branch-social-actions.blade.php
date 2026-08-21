@php
    $phone = $branch?->hotline ?: $branch?->phone;
    $phoneHref = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
    $zaloUrl = $branch?->zalo_url ?: setting('zalo_url');
    $facebookUrl = $branch?->facebook_url ?: setting('facebook_url');
    $reservationUrl = $branch?->slug ? localized_route('reservations.create', ['branch' => $branch->slug]) : localized_route('reservations.create');
@endphp

<div class="branch-social-actions" aria-label="{{ __('site.contact.eyebrow') }}">
    @if ($phoneHref)
        <a href="tel:{{ $phoneHref }}" class="branch-social-action is-call" aria-label="{{ __('site.contact.call') }} {{ $branch?->name }}" data-track-event="click_call" data-track-category="contact" data-track-label="Branch {{ $branch?->name }} call" data-facebook-event="ClickCall">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6.6 10.8c1.4 2.7 3.6 4.9 6.3 6.3l2.1-2.1c.3-.3.8-.4 1.2-.3 1 .3 2.1.5 3.2.5.7 0 1.2.5 1.2 1.2v3.2c0 .7-.5 1.2-1.2 1.2C10.3 20.8 3.2 13.7 3.2 4.6c0-.7.5-1.2 1.2-1.2h3.2c.7 0 1.2.5 1.2 1.2 0 1.1.2 2.2.5 3.2.1.4 0 .9-.3 1.2l-2.4 1.8Z" />
            </svg>
            <span>{{ __('site.contact.call') }}</span>
        </a>
    @endif

    @if ($zaloUrl)
        <a href="{{ $zaloUrl }}" class="branch-social-action" aria-label="Zalo {{ $branch?->name }}" target="_blank" rel="noopener" data-track-event="click_zalo" data-track-category="contact" data-track-label="Branch {{ $branch?->name }} Zalo" data-facebook-event="ClickZalo">
            @include('partials.social-icon', ['name' => 'zalo'])
            <span>Zalo</span>
        </a>
    @endif

    @if ($facebookUrl)
        <a href="{{ $facebookUrl }}" class="branch-social-action" aria-label="Facebook {{ $branch?->name }}" target="_blank" rel="noopener" data-track-event="click_facebook" data-track-category="contact" data-track-label="Branch {{ $branch?->name }} Facebook" data-facebook-event="ClickFacebook">
            @include('partials.social-icon', ['name' => 'facebook'])
            <span>Facebook</span>
        </a>
    @endif

    <a href="{{ $reservationUrl }}" class="branch-social-action is-reservation" aria-label="{{ __('site.contact.book_branch') }}" data-track-event="click_reservation_cta" data-track-category="reservation" data-track-label="Branch {{ $branch?->name }} quick reservation" data-facebook-event="ClickReservation">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 22 6.5v12A2.5 2.5 0 0 1 19.5 21h-15A2.5 2.5 0 0 1 2 18.5v-12A2.5 2.5 0 0 1 4.5 4H6V3a1 1 0 0 1 1-1Zm13 8H4v8.5c0 .28.22.5.5.5h15a.5.5 0 0 0 .5-.5V10Z" />
        </svg>
        <span>{{ __('site.header.nav_booking') }}</span>
    </a>
</div>
