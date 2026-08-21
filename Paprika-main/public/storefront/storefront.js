(function () {
    'use strict';

    var body = document.body;
    if (!body.classList.contains('storefront')) return;
    var cartDrawerApi = {
        open: function () {},
        close: function () {},
    };

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    ready(function () {
        initPageTransitionLoader();
        initCartDrawer();
        initAjaxCart();
        initMobileMenu();
        initServiceToggle();
        initQtySteppers();
        initFulfillmentPanels();
        initChoiceCards();
        initCustomizer();
        initBookingMap();
        initFlashToast();
        initPromoPopup();
        initScrollReveal();
        initChatWidget();
    });

    function initPageTransitionLoader() {
        var loader = document.querySelector('[data-page-loader]');
        if (!loader) return;

        var showTimer = null;
        var isVisible = false;

        function showLoader() {
            if (isVisible) return;

            isVisible = true;
            loader.hidden = false;
            loader.setAttribute('aria-hidden', 'false');
            body.classList.add('is-page-loading');
        }

        function showLoaderSoon() {
            if (showTimer) window.clearTimeout(showTimer);
            showTimer = window.setTimeout(showLoader, 80);
        }

        function hideLoader() {
            if (showTimer) {
                window.clearTimeout(showTimer);
                showTimer = null;
            }

            isVisible = false;
            loader.hidden = true;
            loader.setAttribute('aria-hidden', 'true');
            body.classList.remove('is-page-loading');
        }

        function isModifiedClick(event) {
            return event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
        }

        function shouldIgnoreLink(link) {
            if (!link || link.target && link.target !== '_self') return true;
            if (link.hasAttribute('download')) return true;
            if (link.closest('[data-open-cart], [data-close-cart], [data-promo-close], [data-chat-widget]')) return true;

            var href = link.getAttribute('href') || '';
            if (!href || href === '#') return true;
            if (/^(mailto:|tel:|sms:|javascript:)/i.test(href)) return true;

            var url;
            try {
                url = new URL(href, window.location.href);
            } catch {
                return true;
            }

            if (url.origin !== window.location.origin) return true;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return true;

            return false;
        }

        function shouldIgnoreForm(form) {
            if (!form) return true;
            if (form.target && form.target !== '_self') return true;
            if (form.matches('[data-ajax-cart-form], [data-chat-start-form], [data-chat-send-form], [data-no-page-loader]')) return true;
            if (form.closest('[data-cart-drawer], [data-chat-widget]')) return true;

            return false;
        }

        document.addEventListener('click', function (event) {
            if (isModifiedClick(event)) return;

            var link = event.target.closest('a[href]');
            if (shouldIgnoreLink(link)) return;

            showLoaderSoon();
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (event.defaultPrevented || shouldIgnoreForm(form)) return;
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;

            showLoaderSoon();
        }, true);

        window.addEventListener('pageshow', hideLoader);
        window.addEventListener('pagehide', function () {
            if (isVisible) return;
            showLoader();
        });
    }

    // Scroll reveal animation
    function initScrollReveal() {
        var reveals = document.querySelectorAll('.reveal-on-scroll');
        if (!reveals.length) return;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });

        reveals.forEach(function(el) {
            observer.observe(el);
        });
    }

    function initCartDrawer() {
        var drawer = document.querySelector('[data-cart-drawer]');
        if (!drawer) return;

        function open() {
            drawer.hidden = false;
            body.style.overflow = 'hidden';
        }

        function close() {
            drawer.hidden = true;
            body.style.overflow = '';
        }

        cartDrawerApi.open = open;
        cartDrawerApi.close = close;

        document.addEventListener('click', function (event) {
            var openButton = event.target.closest('[data-open-cart]');
            if (openButton) {
                event.preventDefault();
                open();
                return;
            }

            if (event.target.closest('[data-close-cart]')) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !drawer.hidden) close();
        });
    }

    function initAjaxCart() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.matches('[data-ajax-cart-form]')) return;

            event.preventDefault();
            submitCartForm(form);
        });
    }

    function submitCartForm(form) {
        if (form.getAttribute('data-ajax-busy') === 'true') return;

        var submitter = form.querySelector('button[type="submit"]');
        var methodInput = form.querySelector('input[name="_method"]');
        var intendedMethod = (methodInput ? methodInput.value : form.getAttribute('method') || 'GET').toUpperCase();
        var shouldOpenCart = intendedMethod === 'POST';
        var shouldCloseCustomizer = form.hasAttribute('data-close-customizer-on-success');
        var formData = new FormData(form);

        form.setAttribute('data-ajax-busy', 'true');
        if (submitter) submitter.disabled = true;
        form.classList.add('opacity-70', 'pointer-events-none');

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        var message = payload.message || Object.values(payload.errors || {})[0]?.[0] || (window.__t?.cart_update_error || 'Unable to update cart.');
                        throw new Error(message);
                    }

                    return payload;
                });
            })
            .then(function (payload) {
                applyCartPayload(payload);
                showToast(payload.message || (window.__t?.cart_updated || 'Cart updated.'));

                if (shouldCloseCustomizer) {
                    var customizer = document.querySelector('[data-customizer]');
                    if (customizer) customizer.hidden = true;
                }

                if (shouldOpenCart) cartDrawerApi.open();
            })
            .catch(function (error) {
                showToast(error.message || (window.__t?.cart_update_error || 'Unable to update cart.'), true);
            })
            .finally(function () {
                form.removeAttribute('data-ajax-busy');
                if (submitter) submitter.disabled = false;
                form.classList.remove('opacity-70', 'pointer-events-none');
            });
    }

    function applyCartPayload(payload) {
        if (!payload) return;

        var drawerContent = document.querySelector('[data-cart-drawer-content]');
        if (drawerContent && payload.drawer_html) {
            drawerContent.innerHTML = payload.drawer_html;
        }

        var cartPageContent = document.querySelector('[data-cart-page-content]');
        if (cartPageContent && payload.cart_page_html) {
            cartPageContent.innerHTML = payload.cart_page_html;
        }

        updateCartBadges(parseInt(payload.count || '0', 10));
    }

    function updateCartBadges(count) {
        document.querySelectorAll('[data-cart-count-badge]').forEach(function (badge) {
            badge.textContent = String(count);
            badge.classList.toggle('hidden', count <= 0);
            badge.classList.toggle('flex', count > 0);
        });
    }

    function showToast(message, isError) {
        if (!message) return;

        document.querySelectorAll('[data-ajax-toast]').forEach(function (toast) {
            toast.remove();
        });

        var toast = document.createElement('div');
        toast.setAttribute('data-ajax-toast', '');
        toast.setAttribute('role', 'status');
        toast.className = 'fixed inset-x-0 top-4 z-55 mx-auto w-[min(28rem,calc(100vw-2rem))] rounded-2xl border bg-white p-4 shadow-2xl animate-slideIn ' + (isError ? 'border-rose-200' : 'border-emerald-200');
        toast.innerHTML = '<div class="flex items-start gap-3"><div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ' + (isError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700') + '"><span class="text-sm font-black">' + (isError ? '!' : '✓') + '</span></div><p class="min-w-0 flex-1 text-sm font-bold leading-5 text-stone-800">' + escapeHtml(message) + '</p><button type="button" class="rounded p-1 text-stone-400 hover:bg-stone-50" aria-label="' + (window.__t?.toast_dismiss || 'Dismiss') + '">×</button></div>';
        body.appendChild(toast);

        var dismiss = toast.querySelector('button');
        var hide = function () { toast.remove(); };
        if (dismiss) dismiss.addEventListener('click', hide);
        setTimeout(hide, 3500);
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function initMobileMenu() {
        var menu = document.querySelector('[data-mobile-menu]');
        var toggle = document.querySelector('[data-mobile-menu-toggle]');
        if (!menu || !toggle) return;

        toggle.addEventListener('click', function () {
            var open = menu.hidden;
            menu.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    function initServiceToggle() {
        var key = 'sf_service_type';
        var stored = sessionStorage.getItem(key) || 'delivery';

        function apply(type) {
            sessionStorage.setItem(key, type);
            document.querySelectorAll('[data-service-toggle] button, [data-service-toggle-mobile] button').forEach(function (btn) {
                var active = btn.getAttribute('data-service') === type;
                if (btn.closest('[data-service-toggle]')) {
                    btn.className = active
                        ? 'px-4 py-1.5 rounded-full text-[10px] uppercase tracking-widest font-bold transition bg-white text-[#064E3B] shadow-md'
                        : 'px-4 py-1.5 rounded-full text-[10px] uppercase tracking-widest font-bold transition text-white/85 hover:text-white';
                } else {
                    btn.className = active
                        ? 'p-3 rounded-xl text-center text-[10px] uppercase tracking-widest font-bold flex flex-col items-center gap-1.5 transition bg-[#B91C1C] text-white'
                        : 'p-3 rounded-xl text-center text-[10px] uppercase tracking-widest font-bold flex flex-col items-center gap-1.5 transition bg-[#064E3B] text-white/85';
                }
            });
            document.querySelectorAll('input[name="fulfillment_method"]').forEach(function (input) {
                if ((type === 'delivery' && input.value === 'delivery') || (type === 'pickup' && input.value === 'pickup')) {
                    input.checked = true;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        document.querySelectorAll('[data-service-toggle] button, [data-service-toggle-mobile] button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                apply(btn.getAttribute('data-service'));
            });
        });

        apply(stored);
    }

    function initQtySteppers() {
        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-qty-action]');
            if (!btn) return;

            var wrap = btn.closest('[data-qty-stepper]');
            if (!wrap) return;

            var form = wrap.closest('form');
            var input = wrap.querySelector('input[type="number"]');
            if (!form || !input) return;

            var min = parseInt(input.getAttribute('min') || '0', 10);
            var max = parseInt(input.getAttribute('max') || '99', 10);
            var delta = btn.getAttribute('data-qty-action') === 'inc' ? 1 : -1;
            input.value = String(Math.min(max, Math.max(min, parseInt(input.value || '0', 10) + delta)));

            if (form.matches('[data-ajax-cart-form]')) {
                submitCartForm(form);
            } else {
                form.requestSubmit();
            }
        });
    }

    function initFulfillmentPanels() {
        document.querySelectorAll('[data-fulfillment-form]').forEach(function (form) {
            var panels = form.querySelectorAll('[data-fulfillment-panel]');
            var radios = form.querySelectorAll('input[name="fulfillment_method"]');
            if (!panels.length || !radios.length) return;

            function sync() {
                var method = form.querySelector('input[name="fulfillment_method"]:checked');
                var value = method ? method.value : 'pickup';
                panels.forEach(function (panel) {
                    var show = panel.getAttribute('data-fulfillment-panel') === value;
                    panel.hidden = !show;
                    panel.querySelectorAll('input, textarea, select').forEach(function (field) {
                        if (show) field.removeAttribute('disabled');
                        else field.setAttribute('disabled', 'disabled');
                    });
                });
            }

            radios.forEach(function (radio) {
                radio.addEventListener('change', sync);
            });
            sync();
        });
    }

    function initChoiceCards() {
        document.querySelectorAll('[data-choice-grid]').forEach(function (grid) {
            grid.querySelectorAll('label').forEach(function (label) {
                var input = label.querySelector('input[type="radio"]');
                if (!input) return;
                var sync = function () {
                    grid.querySelectorAll('label').forEach(function (item) {
                        var checked = item.querySelector('input[type="radio"]')?.checked;
                        item.classList.toggle('ring-2', !!checked);
                        item.classList.toggle('ring-[#064E3B]', !!checked);
                        item.classList.toggle('border-[#064E3B]', !!checked);
                        item.classList.toggle('bg-[#064E3B]/5', !!checked);
                    });
                };
                input.addEventListener('change', sync);
                sync();
            });
        });
    }

    function initCustomizer() {
        var modal = document.querySelector('[data-customizer]');
        if (!modal) return;

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        var nameEls = modal.querySelectorAll('[data-customizer-name]');
        var imageEl = modal.querySelector('[data-customizer-image]');
        var form = modal.querySelector('[data-customizer-form]');
        var priceFromEls = modal.querySelectorAll('[data-customizer-price-from]');
        var optionsContainer = modal.querySelector('[data-customizer-options-container]');
        var qtyInput = modal.querySelector('[data-customizer-qty-input]');
        var qtyLabel = modal.querySelector('[data-customizer-qty-label]');
        var totalEl = modal.querySelector('[data-customizer-total]');
        var totalInline = modal.querySelector('[data-customizer-total-inline]');
        var noteEl = modal.querySelector('[data-customizer-note]');
        var noteInput = modal.querySelector('[data-customizer-note-input]');
        var summaryEl = modal.querySelector('[data-customizer-summary]');
        var optionInputsContainer = modal.querySelector('[data-customizer-option-inputs]');

        var currentOptions = [];
        var basePrice = 0;
        var quantity = 1;

        function setText(nodes, value) {
            nodes.forEach(function (node) {
                node.textContent = value;
            });
        }

        function closeCustomizer() {
            modal.hidden = true;
            body.style.overflow = '';
        }

        function keepModalAnchored() {
            modal.scrollTop = 0;
            window.requestAnimationFrame(function () {
                modal.scrollTop = 0;
                window.setTimeout(function () {
                    modal.scrollTop = 0;
                }, 0);
            });
        }

        function formatEuro(cents) {
            return '€' + (cents / 100).toFixed(2).replace('.', ',');
        }

        function unitPrice() {
            return currentOptions.reduce(function (sum, opt) {
                return sum + (opt.checked ? opt.price : 0);
            }, basePrice);
        }

        function sync() {
            var price = Math.max(0, unitPrice());
            var total = price * quantity;
            if (qtyInput) qtyInput.value = String(quantity);
            if (qtyLabel) qtyLabel.textContent = String(quantity);
            var formatted = formatEuro(total);
            if (totalEl) totalEl.textContent = formatted;
            if (totalInline) totalInline.textContent = formatted;
            if (noteInput && noteEl) noteInput.value = noteEl.value;

            var selectedLabels = currentOptions.filter(function (o) { return o.checked; }).map(function (o) { return o.label; });
            var noteText = noteEl ? noteEl.value.trim() : '';
            if (noteText) selectedLabels.push(noteText);
            if (summaryEl) summaryEl.textContent = selectedLabels.join(' | ') || (summaryEl.getAttribute('data-default') || (window.__t?.standard_recipe || 'Standard recipe'));

            // Sync hidden inputs for form submission
            if (optionInputsContainer) {
                var inputsHtml = '';
                currentOptions.forEach(function (opt) {
                    if (opt.checked) {
                        inputsHtml += '<input type="hidden" name="option_ids[]" value="' + opt.id + '">';
                    }
                });
                optionInputsContainer.innerHTML = inputsHtml;
            }

            // Keep option cards stable: show/hide pre-rendered badges instead of inserting nodes.
            if (optionsContainer) {
                optionsContainer.querySelectorAll('[data-option-label]').forEach(function (label) {
                    var cb = label.querySelector('input[type="checkbox"]');
                    var badge = label.querySelector('.customizer-included-badge');
                    if (badge) {
                        var show = cb && cb.checked && parseInt(cb.getAttribute('data-option-price') || '0', 10) === 0;
                        badge.classList.toggle('hidden', !show);
                    }
                });
            }

            keepModalAnchored();
        }

        function updateQty(delta) {
            quantity = Math.min(99, Math.max(1, quantity + delta));
            sync();
        }

        function renderOptions(optionsJson, dishName, price) {
            var groups;
            try { groups = JSON.parse(optionsJson || '[]'); } catch (e) { groups = []; }
            currentOptions = [];
            basePrice = parseInt(price, 10) || 0;
            quantity = 1;
            if (qtyInput) qtyInput.value = '1';
            if (qtyLabel) qtyLabel.textContent = '1';
            if (noteEl) noteEl.value = '';
            if (noteInput) noteInput.value = '';
            setText(priceFromEls, basePrice > 0 ? 'From ' + formatEuro(basePrice) : '');

            if (groups.length === 0) {
                if (optionsContainer) optionsContainer.innerHTML = '<p class="text-[11px] text-stone-400 text-center">' + (summaryEl && summaryEl.getAttribute('data-default') || (window.__t?.standard_recipe || 'Standard recipe')) + '</p>';
                sync();
                return;
            }

            var html = '';
            groups.forEach(function (group) {
                var isSingle = group.type === 'single';
                var isExclude = group.type === 'exclude';
                var hint = isSingle ? (optionsContainer.getAttribute('data-hint-single') || (window.__t?.pick_one_option || 'Pick one option'))
                    : (isExclude ? (optionsContainer.getAttribute('data-hint-exclude') || (window.__t?.pick_exclude || 'Pick ingredients to exclude'))
                    : (optionsContainer.getAttribute('data-hint-multiple') || (window.__t?.choose_multiple || 'Choose multiple options')));

                html += '<section class="space-y-2.5"><div class="flex items-baseline justify-between gap-3"><div><h3 class="text-[11px] font-black uppercase tracking-[0.14em] text-stone-400">' + escapeHtml(group.name) + '</h3>';
                if (group.desc) html += '<p class="mt-0.5 text-[10px] leading-4 text-stone-400">' + escapeHtml(group.desc) + '</p>';
                html += '</div><span class="shrink-0 text-[9px] font-semibold text-stone-400">' + escapeHtml(hint) + '</span></div>';
                html += '<div class="grid gap-2 ' + (isExclude ? 'grid-cols-2' : '') + '">';

                group.options.forEach(function (opt) {
                    var optId = 'customizer_opt_' + opt.id;
                    var checked = !!opt.default;
                    var checkClass = isSingle ? 'rounded-full' : 'rounded-md';
                    var priceLabel = (opt.price !== 0) ? ((opt.price > 0 ? '+' : '-') + formatEuro(Math.abs(opt.price))) : '';
                    var includedLabel = '<span class="customizer-included-badge ' + (checked && opt.price === 0 ? '' : 'hidden ') + 'shrink-0 rounded-full bg-stone-100 px-2 py-0.5 text-[8px] font-black uppercase text-stone-400">' + (optionsContainer.getAttribute('data-included') || (window.__t?.included || 'Included')) + '</span>';

                    html += '<label class="relative flex min-h-[3.75rem] cursor-pointer items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-2.5 shadow-sm transition hover:border-[#064E3B]/40 sm:min-h-[4.75rem] sm:p-3" data-option-label="' + optId + '">';
                    html += '<span class="flex min-w-0 items-center gap-2.5"><input type="checkbox" class="peer pointer-events-none absolute left-3 top-3 h-px w-px opacity-0" value="' + opt.id + '"' + (checked ? ' checked' : '') + ' data-option-id="' + opt.id + '" data-option-group="' + group.id + '" data-option-type="' + group.type + '" data-option-price="' + opt.price + '" data-option-label="' + escapeHtml(group.name + ': ' + opt.name) + '">';
                    html += '<span class="flex h-5 w-5 shrink-0 items-center justify-center ' + checkClass + ' border border-stone-300 bg-white peer-checked:border-[#064E3B] peer-checked:bg-[#064E3B] peer-checked:text-white transition-colors duration-150"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>';
                    html += '<span class="min-w-0"><span class="block text-xs font-bold text-stone-850">' + escapeHtml(opt.name) + '</span></span></span>';
                    if (opt.price !== 0) {
                        html += '<strong class="shrink-0 font-mono text-xs ' + (opt.price > 0 ? 'text-[#064E3B]' : 'text-[#B91C1C]') + '">' + priceLabel + '</strong>';
                    } else {
                        html += includedLabel;
                    }
                    html += '</label>';

                    currentOptions.push({ id: opt.id, groupId: group.id, type: group.type, price: opt.price, checked: checked, label: group.name + ': ' + opt.name });
                });

                html += '</div></section>';
            });

            if (optionsContainer) optionsContainer.innerHTML = html;

            // Bind option change events
            optionsContainer.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    var optId = parseInt(this.value, 10);
                    var groupId = parseInt(this.getAttribute('data-option-group'), 10);
                    var type = this.getAttribute('data-option-type');
                    var price = parseInt(this.getAttribute('data-option-price'), 10) || 0;
                    var label = this.getAttribute('data-option-label') || '';

                    // Update currentOptions
                    var entry = currentOptions.find(function (o) { return o.id === optId; });
                    if (entry) entry.checked = this.checked;

                    // Single select: uncheck peers
                    if (type === 'single' && this.checked) {
                        optionsContainer.querySelectorAll('input[data-option-group="' + groupId + '"]').forEach(function (peer) {
                            if (peer !== cb) {
                                peer.checked = false;
                                var peerId = parseInt(peer.value, 10);
                                var peerEntry = currentOptions.find(function (o) { return o.id === peerId; });
                                if (peerEntry) peerEntry.checked = false;
                            }
                        });
                    }

                    // Update included badges
                    sync();
                });
            });

            sync();
        }

        document.querySelectorAll('[data-open-customizer]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dishName = btn.getAttribute('data-dish-name') || '';
                var dishImage = btn.getAttribute('data-dish-image') || '';
                var addUrl = btn.getAttribute('data-add-url') || '';
                var dishPrice = btn.getAttribute('data-dish-price') || '0';
                var optionsJson = btn.getAttribute('data-dish-options') || '[]';

                setText(nameEls, dishName);
                if (imageEl) imageEl.src = dishImage;
                if (form) form.action = addUrl;

                renderOptions(optionsJson, dishName, dishPrice);

                modal.hidden = false;
                keepModalAnchored();
                body.style.overflow = 'hidden';
            });
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeCustomizer();
        });

        modal.querySelectorAll('[data-close-customizer]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeCustomizer();
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) closeCustomizer();
        });

        modal.querySelectorAll('[data-customizer-qty]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var delta = btn.getAttribute('data-customizer-qty') === 'inc' ? 1 : -1;
                updateQty(delta);
            });
        });

        if (noteEl) {
            noteEl.addEventListener('input', sync);
        }
    }

    function initBookingMap() {
        var map = document.querySelector('[data-seat-map]');
        if (!map) return;

        map.querySelectorAll('[data-table]').forEach(function (cell) {
            if (cell.disabled) return;
            cell.addEventListener('click', function () {
                map.querySelectorAll('[data-table]:not([disabled])').forEach(function (c) {
                    c.classList.remove('bg-[#B91C1C]', 'ring-2', 'ring-white', 'scale-105');
                    c.classList.add('bg-[#064E3B]', 'text-white/90', 'border', 'border-white/10');
                });
                cell.classList.remove('bg-[#064E3B]');
                cell.classList.add('bg-[#B91C1C]', 'ring-2', 'ring-white', 'scale-105');
            });
        });
    }

    function initFlashToast() {
        var toast = document.querySelector('[data-flash-toast]');
        if (!toast) return;

        var stack = document.querySelector('[data-toast-stack]');
        var dismiss = toast.querySelector('[data-dismiss-toast]');
        var hide = function () {
            if (stack) stack.remove();
            else toast.remove();
        };

        function computeToastSafeArea() {
            try {
                var chat = document.querySelector('[data-chat-widget]');
                var toggle = chat ? chat.querySelector('[data-chat-toggle]') : null;
                if (!toggle) return;

                var rect = toggle.getBoundingClientRect();
                var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                if (!viewportHeight) return;

                var bottomOffset = Math.max(0, Math.round(viewportHeight - rect.top));
                // add a small gap so toast never touches the button
                var safeBottom = bottomOffset + 12;
                document.documentElement.style.setProperty('--sf-chat-safe-bottom', safeBottom + 'px');
            } catch (e) {
                // ignore
            }
        }

        computeToastSafeArea();
        window.addEventListener('resize', computeToastSafeArea);

        if (dismiss) dismiss.addEventListener('click', hide);
        setTimeout(hide, 5000);
    }

    function initPromoPopup() {
        var popup = document.querySelector('[data-promo-popup]');
        if (!popup) return;

        var promoId = popup.getAttribute('data-promo-id') || 'default';
        var showOnce = popup.getAttribute('data-show-once') === '1';
        var storageKey = 'paprika_promo_seen_' + promoId;
        var sessionKey = 'paprika_promo_dismissed_' + promoId;
        var closeButtons = popup.querySelectorAll('[data-promo-close], [data-promo-action]');
        var closeButton = popup.querySelector('article [data-promo-close]');
        var openTimer = null;

        function storageGet(storage, key) {
            try {
                return storage.getItem(key);
            } catch (error) {
                return null;
            }
        }

        function storageSet(storage, key, value) {
            try {
                storage.setItem(key, value);
            } catch (error) {
                // Browser storage may be disabled in private browsing.
            }
        }

        function wasSeen() {
            if (storageGet(window.sessionStorage, sessionKey) === '1') return true;
            return showOnce && storageGet(window.localStorage, storageKey) === '1';
        }

        function markSeen() {
            storageSet(window.sessionStorage, sessionKey, '1');
            if (showOnce) storageSet(window.localStorage, storageKey, '1');
        }

        function hidePopup() {
            popup.hidden = true;
            popup.style.display = 'none';
            popup.classList.add('hidden');
            popup.classList.remove('flex');
            body.style.overflow = '';
        }

        function open() {
            popup.hidden = false;
            popup.style.display = '';
            popup.classList.remove('hidden');
            popup.classList.add('flex');
            popup.classList.add('animate-fadeIn');
            body.style.overflow = 'hidden';
            if (closeButton) closeButton.focus({ preventScroll: true });
        }

        function close() {
            if (openTimer) {
                window.clearTimeout(openTimer);
                openTimer = null;
            }

            hidePopup();
            markSeen();
        }

        if (!wasSeen()) {
            openTimer = window.setTimeout(open, 850);
        } else {
            hidePopup();
        }

        closeButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                if (!button.hasAttribute('data-promo-action')) event.preventDefault();
                close();
            });
        });

        document.addEventListener('click', function (event) {
            if (!popup.contains(event.target)) return;
            if (event.target.closest('[data-promo-close]')) {
                event.preventDefault();
                close();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !popup.hidden) close();
        });
    }

    // ================================================
    // CHAT WIDGET
    // ================================================
    function initChatWidget() {
        var widget = document.querySelector('[data-chat-widget]');
        if (!widget) return;

        var toggle = widget.querySelector('[data-chat-toggle]');
        var panel = widget.querySelector('[data-chat-panel]');
        var messagesContainer = widget.querySelector('[data-chat-messages]');
        var startForm = widget.querySelector('[data-chat-start-form]');
        var sendForm = widget.querySelector('[data-chat-send-form]');
        var errorEl = widget.querySelector('[data-chat-error]');
        var badge = widget.querySelector('[data-chat-badge]');
        var closeBtn = widget.querySelector('[data-chat-close]');

        var startUrl = widget.getAttribute('data-start-url');
        var messagesUrl = widget.getAttribute('data-messages-url') || '';
        var sendUrl = widget.getAttribute('data-send-url') || '';
        var csrf = widget.getAttribute('data-csrf');
        
        var sessionId = sessionStorage.getItem('chat_session_id') || widget.getAttribute('data-session-id') || '';

        var pollInterval = null;
        var lastMessageId = sessionStorage.getItem('chat_last_message_id') || 0;
        var lastFetchedCount = 0;
        var isOpen = false;

        // Check if we have existing session
        if (sessionId) {
            messagesUrl = '/chat/' + sessionId + '/messages';
            sendUrl = '/chat/' + sessionId + '/messages';
            widget.setAttribute('data-session-id', sessionId);
            widget.setAttribute('data-messages-url', messagesUrl);
            widget.setAttribute('data-send-url', sendUrl);
            
            // Switch to send form using style.display
            if (startForm) startForm.style.display = 'none';
            if (sendForm) sendForm.style.display = '';
            
            // Start background polling immediately
            startBackgroundPolling();
        }

        // Toggle button - OPEN chat panel
        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                isOpen = true;
                panel.style.display = '';
                toggle.setAttribute('aria-expanded', 'true');
                panel.focus();
                scrollToBottom();
                clearBadge();
                
                // Fetch latest messages when opening
                if (sessionId) {
                    fetchMessages(true);
                }
            });
        }

        // Close button - CLOSE chat panel (FIXED)
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                isOpen = false;
                panel.style.display = 'none';
                toggle.setAttribute('aria-expanded', 'false');
            });
        }

        // Click outside to close
        document.addEventListener('click', function (e) {
            if (isOpen && !widget.contains(e.target)) {
                isOpen = false;
                panel.style.display = 'none';
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) {
                isOpen = false;
                panel.style.display = 'none';
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Start chat form
        if (startForm) {
            startForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitStartForm();
            });
        }

        // Send message form
        if (sendForm) {
            var textarea = sendForm.querySelector('textarea[name="message"]');
            
            sendForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitSendMessage();
            });

            // Auto-resize textarea
            if (textarea) {
                textarea.addEventListener('input', function () {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });
            }
        }

        function submitStartForm() {
            if (!startForm) return;
            
            var submitBtn = startForm.querySelector('button[type="submit"]');
            var formData = new FormData(startForm);
            
            // Honey pot check
            if (formData.get('website')) {
                return;
            }

            submitBtn.disabled = true;
            hideError();

            fetch(startUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        var msg = data.message || (data.errors ? Object.values(data.errors)[0][0] : (window.__t?.chat_error || 'Có lỗi xảy ra.'));
                        throw new Error(msg);
                    }
                    return data;
                });
            })
            .then(function (data) {
                // Save session
                sessionId = data.session_id;
                sessionStorage.setItem('chat_session_id', sessionId);
                
                messagesUrl = '/chat/' + sessionId + '/messages';
                sendUrl = '/chat/' + sessionId + '/messages';
                widget.setAttribute('data-session-id', sessionId);
                widget.setAttribute('data-messages-url', messagesUrl);
                widget.setAttribute('data-send-url', sendUrl);

                // Clear existing messages and render new ones
                clearMessages();
                renderMessages(data.messages || []);

                // Switch to send form using style.display
                if (startForm) startForm.style.display = 'none';
                if (sendForm) {
                    sendForm.style.display = '';
                    sendForm.querySelector('textarea[name="message"]').focus();
                }

                // Start background polling
                startBackgroundPolling();
                scrollToBottom();
            })
            .catch(function (error) {
                showError(error.message || (window.__t?.chat_error_retry || 'Có lỗi xảy ra. Vui lòng thử lại.'));
            })
            .finally(function () {
                submitBtn.disabled = false;
            });
        }

        function submitSendMessage() {
            if (!sendForm) return;
            
            var textarea = sendForm.querySelector('textarea[name="message"]');
            var submitBtn = sendForm.querySelector('button[type="submit"]');
            var message = textarea.value.trim();
            
            if (!message) return;

            submitBtn.disabled = true;

            var formData = new FormData();
            formData.append('message', message);

            fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(data.message || (window.__t?.chat_error || 'Có lỗi xảy ra.'));
                    }
                    return data;
                });
            })
            .then(function (data) {
                renderMessages(data.messages || []);
                textarea.value = '';
                textarea.style.height = 'auto';
                scrollToBottom();
            })
            .catch(function (error) {
                showError(error.message || (window.__t?.chat_error || 'Có lỗi xảy ra.'));
            })
            .finally(function () {
                submitBtn.disabled = false;
                textarea.focus();
            });
        }

        // Background polling - runs ALWAYS when session exists, even when closed
        function startBackgroundPolling() {
            if (pollInterval) return; // Already running
            
            // Fetch immediately
            fetchMessages(false);
            
            // Then poll every 3 seconds
            pollInterval = window.setInterval(function() {
                fetchMessages(false);
            }, 3000);
        }

        function fetchMessages(scrollAfter) {
            if (!messagesUrl) return;

            fetch(messagesUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(function (response) {
                if (!response.ok) {
                    // Session might be invalid
                    if (response.status === 403) {
                        clearSession();
                    }
                    return null;
                }
                return response.json();
            })
            .then(function (data) {
                if (data && data.messages) {
                    var newMessages = data.messages.filter(function(msg) {
                        return parseInt(msg.id) > lastMessageId;
                    });
                    
                    if (newMessages.length > 0) {
                        renderMessages(newMessages);
                        
                        // Save last message ID
                        var latestMsg = data.messages[data.messages.length - 1];
                        lastMessageId = latestMsg.id;
                        sessionStorage.setItem('chat_last_message_id', lastMessageId);
                        
                        // Play sound and show badge if chat is closed
                        if (!isOpen) {
                            playNotificationSound();
                            showBadge(newMessages.length);
                        }
                        
                        if (scrollAfter || isOpen) {
                            scrollToBottom();
                        }
                    }
                }
            })
            .catch(function () {
                // Silently fail polling
            });
        }

        function renderMessages(messages) {
            if (!messagesContainer || !messages.length) return;

            messages.forEach(function (msg) {
                // Check if message already exists
                if (messagesContainer.querySelector('[data-message-id="' + msg.id + '"]')) {
                    return;
                }
                
                var isVisitor = msg.sender === 'visitor';
                var msgHtml = '<div class="sf-chat-message sf-chat-message-' + (isVisitor ? 'visitor' : 'admin') + '" data-message-id="' + msg.id + '">' +
                    '<div class="sf-chat-bubble sf-chat-bubble-' + (isVisitor ? 'visitor' : 'admin') + '"><p>' + escapeHtml(msg.message) + '</p></div>' +
                    '<span class="sf-chat-time">' + msg.created_at + '</span></div>';

                messagesContainer.insertAdjacentHTML('beforeend', msgHtml);
            });
        }

        function clearMessages() {
            if (messagesContainer) {
                // Keep date divider and greeting, remove other messages
                var messages = messagesContainer.querySelectorAll('.sf-chat-message');
                messages.forEach(function(m) { m.remove(); });
            }
        }

        function clearSession() {
            sessionId = '';
            messagesUrl = '';
            sendUrl = '';
            lastMessageId = 0;
            sessionStorage.removeItem('chat_session_id');
            sessionStorage.removeItem('chat_last_message_id');
            stopPolling();
            
            // Switch to start form using style.display
            if (startForm) startForm.style.display = '';
            if (sendForm) sendForm.style.display = 'none';
        }

        function scrollToBottom() {
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        function showError(message) {
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = '';
            }
        }

        function hideError() {
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }

        function clearBadge() {
            if (badge) {
                badge.style.display = 'none';
                badge.textContent = '';
            }
        }

        function showBadge(count) {
            if (badge) {
                var current = parseInt(badge.textContent) || 0;
                var total = current + count;
                badge.textContent = total > 9 ? '9+' : total;
                badge.style.display = '';
                
                // Animate badge
                badge.style.animation = 'none';
                badge.offsetHeight; // Trigger reflow
                badge.style.animation = 'sf-chat-badge-pop 0.3s ease';
            }
        }

        function stopPolling() {
            if (pollInterval) {
                window.clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        function playNotificationSound() {
            // Simple beep using Web Audio API
            try {
                var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                var oscillator = audioCtx.createOscillator();
                var gainNode = audioCtx.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gainNode.gain.value = 0.1;
                
                oscillator.start();
                oscillator.stop(audioCtx.currentTime + 0.1);
            } catch (e) {
                // Audio not supported, silent fail
            }
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    }
})();
