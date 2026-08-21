const toggle = document.querySelector('.mobile-nav-toggle');
const mobileNav = document.querySelector('#mobile-nav');

if (toggle && mobileNav) {
    const closeMobileNav = () => {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', window.__t?.menu_open || 'Mở menu');
        mobileNav.classList.remove('is-open');
        document.body.classList.remove('mobile-nav-open');

        window.setTimeout(() => {
            if (toggle.getAttribute('aria-expanded') === 'false') {
                mobileNav.hidden = true;
            }
        }, 220);
    };

    toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';

        if (expanded) {
            closeMobileNav();
            return;
        }

        mobileNav.hidden = false;
        window.requestAnimationFrame(() => {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', window.__t?.menu_close || 'Đóng menu');
            mobileNav.classList.add('is-open');
            document.body.classList.add('mobile-nav-open');
        });
    });

    mobileNav.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMobileNav));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeMobileNav();
        }
    });
}

const stickyHeader = document.querySelector('header.sticky');

if (stickyHeader) {
    const updateHeaderShadow = () => stickyHeader.classList.toggle('is-scrolled', window.scrollY > 12);
    updateHeaderShadow();
    window.addEventListener('scroll', updateHeaderShadow, { passive: true });
}

document.querySelectorAll('[data-admin-tabs]').forEach((tabs) => {
    const buttons = Array.from(tabs.querySelectorAll('[data-admin-tab]'));
    const panels = Array.from(tabs.querySelectorAll('[data-admin-tab-panel]'));

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.adminTab;

            buttons.forEach((item) => item.classList.toggle('is-active', item === button));
            panels.forEach((panel) => {
                const isActive = panel.dataset.adminTabPanel === target;
                panel.hidden = !isActive;
                panel.classList.toggle('is-active', isActive);
            });
        });
    });

    tabs.querySelectorAll('[data-copy-translation]').forEach((copyButton) => {
        copyButton.addEventListener('click', () => {
            tabs.querySelectorAll('[data-admin-tab-panel].is-active [data-copy-field]').forEach((target) => {
                const source = findFieldByName(tabs, target.dataset.copyField);

                if (source && !target.value) {
                    target.value = source.value || source.textContent || '';
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        });
    });
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const findFieldByName = (scope, name) => {
    const candidates = scope.querySelectorAll(`input[name], textarea[name], select[name]`);
    for (const el of candidates) {
        if (el.getAttribute('name') === name) {
            return el;
        }
    }
    return null;
};

const setDeepLStatus = (element, message, type = 'info') => {
    if (!element) {
        return;
    }

    element.hidden = false;
    element.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-800', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800', 'border-slate-200', 'bg-slate-50', 'text-slate-700');

    const classes = {
        error: ['border-red-200', 'bg-red-50', 'text-red-800'],
        success: ['border-emerald-200', 'bg-emerald-50', 'text-emerald-800'],
        info: ['border-slate-200', 'bg-slate-50', 'text-slate-700'],
    };

    element.classList.add(...(classes[type] || classes.info));
    element.textContent = message;
};

const postJson = async (url, payload = {}) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok || data.ok === false) {
        throw new Error(data.message || (window.__t?.request_failed || 'Không xử lý được yêu cầu.'));
    }

    return data;
};

const currentTranslationProviderLabel = () => {
    const select = document.querySelector('[data-translation-provider]');

    if (!select) {
        return 'provider dịch';
    }

    return select.value === 'microsoft' ? 'Microsoft Translator' : 'DeepL';
};

document.querySelectorAll('[data-deepl-translate]').forEach((button) => {
    button.addEventListener('click', async () => {
        const tabs = button.closest('[data-admin-tabs]');
        const status = tabs?.querySelector('[data-deepl-inline-status]');
        const url = button.dataset.deeplUrl;
        const fields = {};
        const fieldElements = {};

        tabs?.querySelectorAll('[data-admin-tab-panel].is-active [data-copy-field]').forEach((target) => {
            if (target.name.endsWith('[slug]')) {
                return;
            }

            const sourceName = target.dataset.copyField;
            const form = button.closest('form');
            const source = findFieldByName(form || document, sourceName);
            const value = source?.value || source?.textContent || '';

            if (value.trim()) {
                fields[target.name] = value;
                fieldElements[target.name] = target;
            }
        });

        if (!url || Object.keys(fields).length === 0) {
            setDeepLStatus(status, 'Không có nội dung tiếng Việt để dịch.', 'error');
            return;
        }

        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = 'Đang dịch...';
        setDeepLStatus(status, `Đang gửi nội dung sang ${currentTranslationProviderLabel()}...`, 'info');

        try {
            const activeTab = tabs?.querySelector('[data-admin-tab].is-active');
            const targetLocale = activeTab?.dataset?.adminTab || 'en';
            const data = await postJson(url, { fields, target_locale: targetLocale });

            Object.entries(data.translations || {}).forEach(([fieldName, value]) => {
                const target = fieldElements[fieldName];

                if (target) {
                    target.value = value || '';
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            const usageText = data.usage?.character_limit
                ? ` Đã dùng ${Number(data.usage.character_count).toLocaleString('vi-VN')} / ${Number(data.usage.character_limit).toLocaleString('vi-VN')} ký tự.`
                : '';

            const localeName = targetLocale === 'el' ? 'Ελληνικά' : 'English';
            setDeepLStatus(status, `Đã dịch xong và đổ vào form ${localeName}.${usageText}`, 'success');
        } catch (error) {
            setDeepLStatus(status, error.message, 'error');
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    });
});

document.querySelectorAll('[data-deepl-test-url], [data-deepl-usage-url]').forEach((button) => {
    button.addEventListener('click', async () => {
        const status = document.querySelector('[data-deepl-status]');
        const isUsage = Boolean(button.dataset.deeplUsageUrl);
        const url = button.dataset.deeplUsageUrl || button.dataset.deeplTestUrl;

        const providerLabel = currentTranslationProviderLabel();

        button.disabled = true;
        setDeepLStatus(status, isUsage ? `Đang kiểm tra quota ${providerLabel}...` : `Đang kiểm tra kết nối ${providerLabel}...`, 'info');

        try {
            const data = isUsage
                ? await fetch(url, { headers: { Accept: 'application/json' } }).then(async (response) => {
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok || json.ok === false) throw new Error(json.message || 'Không đọc được quota.');
                    return json;
                })
                : await postJson(url);

            const usage = data.usage;
            const usageText = usage?.character_limit
                ? ` Đã dùng ${Number(usage.character_count).toLocaleString('vi-VN')} / ${Number(usage.character_limit).toLocaleString('vi-VN')} ký tự.`
                : '';
            const sampleText = data.sample ? ` Mẫu: ${data.sample}` : '';

            setDeepLStatus(status, `${data.message || `Kiểm tra ${providerLabel} thành công.`}${usageText}${sampleText}`, 'success');
        } catch (error) {
            setDeepLStatus(status, error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
});

const translationProviderSelect = document.querySelector('[data-translation-provider]');

if (translationProviderSelect) {
    const updateProviderPanels = () => {
        document.querySelectorAll('[data-provider-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.providerPanel !== translationProviderSelect.value);
        });
    };

    translationProviderSelect.addEventListener('change', updateProviderPanels);
    updateProviderPanels();
}

const trackConversion = (eventName, params = {}) => {
    const payload = {
        page_path: window.location.pathname,
        page_title: document.title,
        ...params,
    };

    if (typeof window.gtag === 'function') {
        window.gtag('event', eventName, payload);
    }

    if (typeof window.fbq === 'function') {
        const facebookEvent = payload.facebook_event || eventName;
        window.fbq('trackCustom', facebookEvent, payload);
    }

    window.dispatchEvent(new CustomEvent('paprika:conversion', {
        detail: { eventName, payload },
    }));
};

document.querySelectorAll('[data-track-event]').forEach((element) => {
    element.addEventListener('click', () => {
        trackConversion(element.dataset.trackEvent, {
            event_category: element.dataset.trackCategory || 'engagement',
            event_label: element.dataset.trackLabel || element.textContent.trim(),
            link_url: element.href || null,
            facebook_event: element.dataset.facebookEvent || undefined,
        });
    });
});

document.querySelectorAll('[data-track-view]').forEach((element) => {
    trackConversion(element.dataset.trackView, {
        event_category: element.dataset.trackCategory || 'view',
        event_label: element.dataset.trackLabel || document.title,
        item_name: element.dataset.itemName || undefined,
        value: element.dataset.value ? Number(element.dataset.value) : undefined,
        currency: element.dataset.currency || undefined,
        facebook_event: element.dataset.facebookEvent || undefined,
    });
});

document.querySelectorAll('form[data-submit-loading]').forEach((form) => {
    form.addEventListener('submit', () => {
        if (!form.checkValidity()) {
            return;
        }

        if (form.dataset.trackSubmit) {
            trackConversion(form.dataset.trackSubmit, {
                event_category: form.dataset.trackCategory || 'form',
                event_label: form.dataset.trackLabel || form.getAttribute('action') || 'submit',
                facebook_event: form.dataset.facebookEvent || undefined,
            });
        }

        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');

        if (!submitButton) {
            return;
        }

        submitButton.disabled = true;
        submitButton.classList.add('is-loading');

        if (submitButton.tagName === 'BUTTON') {
            submitButton.dataset.originalText = submitButton.textContent.trim();
            submitButton.textContent = submitButton.dataset.loadingText || (window.__t?.sending || 'Đang gửi...');
        }
    });
});

const revealTargets = document.querySelectorAll('.section-block, .dish-card, .promo-card, .reason-card, .commitment-card, .home-gallery-card, .gallery-tile, .about-gallery-card, article.group');

if ('IntersectionObserver' in window) {
    revealTargets.forEach((target) => target.classList.add('reveal-ready'));

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.14 });

    revealTargets.forEach((target) => revealObserver.observe(target));
}

document.querySelectorAll('[data-testimonial-slider]').forEach((slider) => {
    const track = slider.querySelector('[data-testimonial-track]');
    const slides = Array.from(slider.querySelectorAll('[data-testimonial-slide]'));
    const dots = Array.from(slider.querySelectorAll('[data-testimonial-dot]'));
    const prevButton = slider.querySelector('[data-testimonial-prev]');
    const nextButton = slider.querySelector('[data-testimonial-next]');
    let current = 0;
    let autoplay = null;

    if (!track || slides.length < 1) {
        return;
    }

    const getStep = () => {
        if (slides.length > 1) {
            return Math.max(1, slides[1].offsetLeft - slides[0].offsetLeft);
        }

        return Math.max(1, slides[0].getBoundingClientRect().width);
    };

    const getMaxIndex = () => {
        const viewportWidth = slider.querySelector('.testimonial-viewport')?.clientWidth || track.clientWidth;
        const visibleCount = Math.max(1, Math.floor(viewportWidth / getStep()));

        return Math.max(0, slides.length - visibleCount);
    };

    const goTo = (index) => {
        const maxIndex = getMaxIndex();
        current = Math.min(Math.max(index, 0), maxIndex);
        track.style.transform = `translateX(-${slides[current].offsetLeft}px)`;

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('is-active', dotIndex === current);
            dot.hidden = dotIndex > maxIndex;
        });
    };

    const stop = () => {
        if (autoplay) {
            window.clearInterval(autoplay);
            autoplay = null;
        }
    };

    const start = () => {
        if (autoplay || slides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        autoplay = window.setInterval(() => {
            const next = current >= getMaxIndex() ? 0 : current + 1;
            goTo(next);
        }, 4500);
    };

    prevButton?.addEventListener('click', () => goTo(current <= 0 ? getMaxIndex() : current - 1));
    nextButton?.addEventListener('click', () => goTo(current >= getMaxIndex() ? 0 : current + 1));
    dots.forEach((dot) => dot.addEventListener('click', () => goTo(Number(dot.dataset.index || 0))));
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);
    window.addEventListener('resize', () => goTo(current), { passive: true });

    goTo(0);
    start();
});

const promoPopup = document.querySelector('[data-promo-popup]');

if (promoPopup) {
    const promoId = promoPopup.dataset.promoId;
    const storageKey = `paprika_promo_seen_${promoId}`;
    const sessionKey = `paprika_promo_dismissed_${promoId}`;
    const showOnce = promoPopup.dataset.showOnce === '1';
    const closeButtons = promoPopup.querySelectorAll('[data-promo-close], [data-promo-action]');
    let openTimer = null;

    const storageGet = (storage, key) => {
        try {
            return storage.getItem(key);
        } catch {
            return null;
        }
    };

    const storageSet = (storage, key, value) => {
        try {
            storage.setItem(key, value);
        } catch {
            // Browser storage can be unavailable in some privacy modes.
        }
    };

    const wasSeen = () => {
        if (storageGet(window.sessionStorage, sessionKey) === '1') return true;
        return showOnce && storageGet(window.localStorage, storageKey) === '1';
    };

    const markSeen = () => {
        storageSet(window.sessionStorage, sessionKey, '1');
        if (showOnce) storageSet(window.localStorage, storageKey, '1');
    };

    const hidePopup = () => {
        promoPopup.hidden = true;
        promoPopup.style.display = 'none';
        promoPopup.classList.add('hidden');
        promoPopup.classList.remove('flex');
    };

    const closePopup = () => {
        if (openTimer) {
            window.clearTimeout(openTimer);
            openTimer = null;
        }

        hidePopup();
        markSeen();
    };

    if (!wasSeen()) {
        openTimer = window.setTimeout(() => {
            promoPopup.hidden = false;
            promoPopup.style.display = '';
            promoPopup.classList.remove('hidden');
            promoPopup.classList.add('flex');
        }, 900);
    } else {
        hidePopup();
    }

    closeButtons.forEach((button) => button.addEventListener('click', (event) => {
        event.stopPropagation();
        if (!button.hasAttribute('data-promo-action')) event.preventDefault();
        closePopup();
    }));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !promoPopup.hidden) {
            closePopup();
        }
    });
}

const reservationDateInput = document.querySelector('[data-reservation-date]');
const reservationTimeInput = document.querySelector('[data-reservation-time]');

if (reservationDateInput && reservationTimeInput) {
    const hint = document.querySelector('[data-reservation-time-hint]');
    const opensAt = reservationTimeInput.dataset.opensAt;
    const closesAt = reservationTimeInput.dataset.closesAt;
    const today = reservationTimeInput.dataset.today;

    const currentTime = () => {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        return `${hours}:${minutes}`;
    };

    const updateReservationTimeLimits = () => {
        let minTime = opensAt;
        let message = window.__t?.reservation_hours?.replace(':opensAt', opensAt)?.replace(':closesAt', closesAt) || `Quán nhận đặt bàn trong khung ${opensAt} - ${closesAt}.`;

        if (reservationDateInput.value === today) {
            minTime = [opensAt, currentTime()].sort().at(-1);
            message = window.__t?.reservation_today_limit?.replace(':minTime', minTime)?.replace(':closesAt', closesAt) || `Hôm nay chỉ nhận giờ từ ${minTime} đến ${closesAt}.`;
        }

        reservationTimeInput.min = minTime;
        reservationTimeInput.max = closesAt;

        if (reservationTimeInput.value && (reservationTimeInput.value < minTime || reservationTimeInput.value > closesAt)) {
            reservationTimeInput.value = '';
        }

        if (hint) {
            hint.textContent = message;
        }
    };

    reservationDateInput.addEventListener('change', updateReservationTimeLimits);
    updateReservationTimeLimits();
}

document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
        const maxKb = Number(document.documentElement.dataset.uploadMaxKb || 10240);
        const maxMb = Math.round(maxKb / 1024);
        const maxBytes = maxKb * 1024;
        const oversizedFiles = Array.from(input.files || []).filter((file) => file.size > maxBytes);
        const existingError = input.parentElement?.querySelector('[data-upload-client-error]');

        existingError?.remove();

        if (!oversizedFiles.length) {
            return;
        }

        input.value = '';

        const error = document.createElement('p');
        error.className = 'form-error';
        error.dataset.uploadClientError = 'true';
        error.textContent = window.__t?.image_too_large?.replace(':maxMb', maxMb) || `Ảnh vượt quá ${maxMb}MB nên chưa thể tải lên. Vui lòng chọn ảnh nhỏ hơn, hệ thống sẽ tự nén sau khi lưu.`;
        input.insertAdjacentElement('afterend', error);
    });
});

document.querySelectorAll('[data-admin-color-picker]').forEach((picker) => {
    const colorPicker = picker.querySelector('[data-color-picker]');
    const colorValue = picker.querySelector('[data-color-value]');
    const colorCode = picker.querySelector('[data-color-code]');
    const swatches = Array.from(picker.querySelectorAll('[data-color-swatch]'));

    const setColor = (color) => {
        if (!/^#[0-9a-fA-F]{6}$/.test(color)) {
            return;
        }

        const normalized = color.toUpperCase();
        colorPicker.value = normalized;
        colorValue.value = normalized;
        colorCode.textContent = normalized;

        swatches.forEach((swatch) => {
            swatch.classList.toggle('is-active', swatch.dataset.colorSwatch.toUpperCase() === normalized);
        });
    };

    colorPicker?.addEventListener('input', () => setColor(colorPicker.value));
    swatches.forEach((swatch) => {
        swatch.addEventListener('click', () => setColor(swatch.dataset.colorSwatch));
    });

    if (colorValue?.value) {
        setColor(colorValue.value);
    }
});

document.querySelectorAll('[data-admin-link-picker]').forEach((picker) => {
    const suggestion = picker.querySelector('[data-link-suggestion]');
    const input = picker.querySelector('input[name="button_link"]');

    suggestion?.addEventListener('change', () => {
        if (suggestion.value && input) {
            input.value = suggestion.value;
            input.focus();
        }
    });
});

const promotionPlacement = document.querySelector('select[name="placement"]');
const popupOnlyFields = document.querySelector('[data-popup-only]');

if (promotionPlacement && popupOnlyFields) {
    const popupCheckbox = popupOnlyFields.querySelector('input[name="show_once"]');
    const updatePopupFields = () => {
        const isPopup = promotionPlacement.value === 'popup';

        popupOnlyFields.classList.toggle('hidden', !isPopup);
        if (popupCheckbox) {
            popupCheckbox.disabled = !isPopup;
        }
    };

    promotionPlacement.addEventListener('change', updatePopupFields);
    updatePopupFields();
}

const chatWidget = document.querySelector('[data-chat-widget]');

if (chatWidget) {
    const toggleButton = chatWidget.querySelector('[data-chat-toggle]');
    const closeButton = chatWidget.querySelector('[data-chat-close]');
    const panel = chatWidget.querySelector('[data-chat-panel]');
    const startForm = chatWidget.querySelector('[data-chat-start-form]');
    const sendForm = chatWidget.querySelector('[data-chat-send-form]');
    const messagesBox = chatWidget.querySelector('[data-chat-messages]');
    const errorBox = chatWidget.querySelector('[data-chat-error]');
    const csrfToken = chatWidget.dataset.csrf;
    const currentBranchId = chatWidget.dataset.branchId || '';
    let chatSessionId = window.sessionStorage.getItem('paprika_chat_session_id');
    let chatBranchId = window.sessionStorage.getItem('paprika_chat_branch_id') || '';
    let pollTimer = null;

    if (chatSessionId && currentBranchId && chatBranchId && currentBranchId !== chatBranchId) {
        window.sessionStorage.removeItem('paprika_chat_session_id');
        window.sessionStorage.removeItem('paprika_chat_branch_id');
        chatSessionId = null;
        chatBranchId = '';
    }

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            },
            credentials: 'same-origin',
            ...options,
        });

        if (!response.ok) {
            throw new Error(window.__t?.chat_send_failed || 'Không gửi được tin nhắn. Bạn thử lại giúp mình nhé.');
        }

        return response.json();
    };

    const renderMessages = (messages) => {
        messagesBox.innerHTML = '';

        messages.forEach((item) => {
            const bubble = document.createElement('div');
            bubble.className = `chat-bubble ${item.sender === 'visitor' ? 'visitor' : 'admin'}`;
            bubble.textContent = item.message;
            messagesBox.appendChild(bubble);
        });

        messagesBox.scrollTop = messagesBox.scrollHeight;
    };

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    const loadMessages = async () => {
        if (!chatSessionId) {
            return;
        }

        try {
            const data = await request(`/chat/${chatSessionId}/messages`);
            renderMessages(data.messages);
            startForm.classList.add('hidden');
            sendForm.classList.remove('hidden');
        } catch {
            window.sessionStorage.removeItem('paprika_chat_session_id');
            window.sessionStorage.removeItem('paprika_chat_branch_id');
            chatSessionId = null;
            chatBranchId = '';
            if (pollTimer) {
                clearInterval(pollTimer);
            }
        }
    };

    const startPolling = () => {
        if (pollTimer || !chatSessionId) {
            return;
        }

        pollTimer = window.setInterval(loadMessages, 6000);
    };

    toggleButton.addEventListener('click', () => {
        const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
        toggleButton.setAttribute('aria-expanded', String(!expanded));
        panel.classList.toggle('hidden', expanded);

        if (!expanded && chatSessionId) {
            trackConversion('open_chat', {
                event_category: 'chat',
                event_label: 'Open chat widget',
                facebook_event: 'OpenChat',
            });
            loadMessages();
            startPolling();
        } else if (!expanded) {
            trackConversion('open_chat', {
                event_category: 'chat',
                event_label: 'Open chat widget',
                facebook_event: 'OpenChat',
            });
        }
    });

    closeButton.addEventListener('click', () => {
        toggleButton.setAttribute('aria-expanded', 'false');
        panel.classList.add('hidden');
    });

    startForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.classList.add('hidden');

        try {
            const formData = new FormData(startForm);
            const data = await request(chatWidget.dataset.startUrl, {
                method: 'POST',
                body: formData,
            });

            chatSessionId = data.session_id;
            chatBranchId = String(data.branch_id || formData.get('branch_id') || '');
            window.sessionStorage.setItem('paprika_chat_session_id', chatSessionId);
            window.sessionStorage.setItem('paprika_chat_branch_id', chatBranchId);
            trackConversion('start_chat', {
                event_category: 'chat',
                event_label: 'Visitor started chat',
                facebook_event: 'StartChat',
            });
            renderMessages(data.messages);
            startForm.classList.add('hidden');
            sendForm.classList.remove('hidden');
            startPolling();
        } catch (error) {
            showError(error.message);
        }
    });

    sendForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!chatSessionId) {
            return;
        }

        const input = sendForm.querySelector('input[name="message"]');
        const message = input.value.trim();

        if (!message) {
            return;
        }

        input.value = '';

        try {
            const data = await request(`/chat/${chatSessionId}/messages`, {
                method: 'POST',
                body: JSON.stringify({ message }),
            });
            renderMessages(data.messages);
        } catch (error) {
            input.value = message;
            showError(error.message);
        }
    });

    if (chatSessionId) {
        startForm.classList.add('hidden');
        sendForm.classList.remove('hidden');
        loadMessages();
        startPolling();
    }
}

const notificationHeader = document.querySelector('[data-admin-notifications-url]');

if (notificationHeader) {
    const url = notificationHeader.dataset.adminNotificationsUrl;
    const bell = document.querySelector('[data-admin-bell]');
    const panel = document.querySelector('[data-admin-notification-panel]');
    const countNode = document.querySelector('[data-admin-notification-count]');
    const listNode = document.querySelector('[data-admin-notification-list]');
    const summaryNode = document.querySelector('[data-admin-notification-summary]');
    const sidebarBadges = document.querySelectorAll('[data-admin-badge]');
    let previousReservationCount = null;
    let previousNewPaidOrderCount = null;

    const updateBadge = (node, count) => {
        if (!node) {
            return;
        }

        node.textContent = count > 99 ? '99+' : String(count);
        node.classList.toggle('hidden', count < 1);
    };

    const escapeNotificationHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const showAdminToast = (message) => {
        const toast = document.createElement('div');
        toast.className = 'admin-live-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        window.setTimeout(() => {
            toast.classList.add('is-hiding');
            window.setTimeout(() => toast.remove(), 220);
        }, 4200);
    };

    const renderNotifications = (data) => {
        const reservationCount = data.counts.reservations || 0;

        if (previousReservationCount !== null && reservationCount > previousReservationCount) {
            showAdminToast(`Có ${reservationCount - previousReservationCount} đơn đặt bàn mới cần xem`);
        }
        const newPaidOrderCount = data.counts.new_paid_orders || 0;
        if (previousNewPaidOrderCount !== null && newPaidOrderCount > previousNewPaidOrderCount) {
            showAdminToast(`Có ${newPaidOrderCount - previousNewPaidOrderCount} đơn thanh toán online chưa xác nhận`);
        }

        previousReservationCount = reservationCount;
        previousNewPaidOrderCount = newPaidOrderCount;

        updateBadge(countNode, data.counts.total || 0);
        summaryNode.textContent = `${data.counts.total || 0} mục cần xem`;

        sidebarBadges.forEach((badge) => {
            updateBadge(badge, data.counts[badge.dataset.adminBadge] || 0);
        });

        if (!data.items.length) {
            listNode.innerHTML = '<p class="px-4 py-5 text-sm text-slate-500">' + (window.__t?.no_notifications || 'Không có thông báo mới.') + '</p>';
            return;
        }

        listNode.innerHTML = data.items.map((item) => `
            <a href="${item.url}" class="admin-notification-item ${item.urgent ? 'is-urgent' : ''}">
                <div class="flex items-center justify-between gap-3">
                    <p class="font-bold text-slate-900">${escapeNotificationHtml(item.title)}</p>
                    <span class="text-xs font-semibold text-slate-400">${escapeNotificationHtml(item.time)}</span>
                </div>
                <p class="mt-1 line-clamp-2 text-sm text-slate-500">${escapeNotificationHtml(item.body)}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold uppercase text-emerald-700">${escapeNotificationHtml(item.type)}</span>
                    ${item.branch ? `<span class="inline-flex rounded-full bg-amber-50 px-2 py-1 text-xs font-bold text-amber-800">${escapeNotificationHtml(item.branch)}</span>` : ''}
                </div>
            </a>
        `).join('');
    };

    const loadNotifications = async () => {
        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (response.ok) {
                renderNotifications(await response.json());
            }
        } catch {
            // Polling should stay quiet; admin can keep working even if one request fails.
        }
    };

    bell?.addEventListener('click', () => {
        const expanded = bell.getAttribute('aria-expanded') === 'true';
        bell.setAttribute('aria-expanded', String(!expanded));
        panel?.classList.toggle('hidden', expanded);
        if (!expanded) {
            loadNotifications();
        }
    });

    document.addEventListener('click', (event) => {
        if (!panel || !bell || panel.classList.contains('hidden')) {
            return;
        }

        if (!panel.contains(event.target) && !bell.contains(event.target)) {
            bell.setAttribute('aria-expanded', 'false');
            panel.classList.add('hidden');
        }
    });

    loadNotifications();
    window.setInterval(loadNotifications, 8000);
}

const adminChatMessages = document.querySelector('[data-admin-chat-messages]');

if (adminChatMessages) {
    const url = adminChatMessages.dataset.adminChatUrl;
    let lastRendered = '';

    const escapeHtml = (value) => value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderAdminChat = (messages) => {
        const html = messages.map((item) => {
            const isAdmin = item.sender === 'admin';
            return `
                <div class="${isAdmin ? 'ml-auto bg-emerald-700 text-white' : 'bg-slate-100 text-slate-800'} max-w-[82%] rounded-2xl px-4 py-3 text-sm leading-6">
                    <p class="text-xs font-bold opacity-75">${escapeHtml(item.sender_name)} - ${item.created_at}</p>
                    <p class="mt-1 whitespace-pre-line">${escapeHtml(item.message)}</p>
                </div>
            `;
        }).join('');

        if (html !== lastRendered) {
            adminChatMessages.innerHTML = html;
            adminChatMessages.scrollTop = adminChatMessages.scrollHeight;
            lastRendered = html;
        }
    };

    const loadAdminChat = async () => {
        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                renderAdminChat(data.messages);
            }
        } catch {
            // Silent retry on next polling tick.
        }
    };

    loadAdminChat();
    window.setInterval(loadAdminChat, 5000);
}

document.querySelectorAll('[data-locale-switcher]').forEach((switcher) => {
    const toggle = switcher.querySelector('[data-locale-toggle]');
    const menu = switcher.querySelector('[data-locale-menu]');

    if (!toggle || !menu) {
        return;
    }

    const closeMenu = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = !menu.hidden;

        document.querySelectorAll('[data-locale-menu]').forEach((other) => {
            if (other !== menu) {
                other.hidden = true;
                other.closest('[data-locale-switcher]')
                    ?.querySelector('[data-locale-toggle]')
                    ?.setAttribute('aria-expanded', 'false');
            }
        });

        if (isOpen) {
            closeMenu();
        } else {
            menu.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        }
    });

    document.addEventListener('click', (event) => {
        if (!switcher.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
});
