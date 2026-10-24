document.addEventListener('DOMContentLoaded', () => {
    const toast = document.querySelector('.toast');
    const showToast = (message) => {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        window.setTimeout(() => toast.classList.remove('show'), 1800);
    };

    const updateCartBadges = (count) => {
        document.querySelectorAll('.bag-button').forEach((cartLink) => {
            let badge = cartLink.querySelector('.cart-badge');
            if (count <= 0) {
                if (badge) badge.remove();
                return;
            }
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'cart-badge';
                cartLink.appendChild(badge);
            }
            badge.textContent = String(count);
        });
    };

    const cartFrame = document.querySelector('[data-cart-background-target]');
    let pendingCartForm = null;
    let cartFrameTimeout = 0;

    const finishBackgroundCart = (success) => {
        if (!pendingCartForm) return;
        const form = pendingCartForm;
        const button = form.querySelector('.card-add-button');
        window.clearTimeout(cartFrameTimeout);
        pendingCartForm = null;

        if (success) {
            if (button) button.textContent = '✓ Added';
            showToast(`${form.dataset.productName || 'Product'} added to cart.`);
        } else {
            if (button) button.textContent = 'Try again';
            showToast('Could not add the product. Please try again.');
        }

        window.setTimeout(() => {
            form.dataset.submitting = 'false';
            if (button) {
                button.disabled = false;
                button.innerHTML = form.dataset.originalLabel || 'Add to cart';
            }
        }, 1200);
    };

    if (cartFrame) {
        cartFrame.addEventListener('load', () => {
            if (!pendingCartForm) return;
            try {
                const framePath = cartFrame.contentWindow.location.pathname;
                if (!framePath.endsWith('/cart.php')) throw new Error('Cart update was rejected');
                const frameBadge = cartFrame.contentDocument.querySelector('.cart-badge');
                const frameCount = frameBadge
                    ? Number(frameBadge.textContent || 0)
                    : Array.from(cartFrame.contentDocument.querySelectorAll('.quantity-control b'))
                        .reduce((total, quantity) => total + Number(quantity.textContent || 0), 0);
                updateCartBadges(frameCount);
                finishBackgroundCart(true);
            } catch (error) {
                finishBackgroundCart(false);
            }
        });
    }

    document.querySelectorAll('.card-add-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!cartFrame || form.dataset.submitting === 'true' || pendingCartForm) {
                event.preventDefault();
                return;
            }

            const button = form.querySelector('.card-add-button');
            form.dataset.submitting = 'true';
            form.dataset.originalLabel = button ? button.innerHTML : 'Add to cart';
            pendingCartForm = form;
            if (button) {
                button.disabled = true;
                button.textContent = 'Adding…';
            }
            cartFrameTimeout = window.setTimeout(() => finishBackgroundCart(false), 8000);
        });
    });

    document.querySelectorAll('.favourite-toggle').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (button.closest('a')) event.preventDefault();
            button.classList.toggle('selected');
            button.textContent = button.classList.contains('selected') ? '♥' : '♡';
            showToast(button.classList.contains('selected') ? 'Saved to your favourites' : 'Removed from favourites');
        });
    });

    document.querySelectorAll('[data-toast]').forEach((button) => {
        button.addEventListener('click', () => showToast(button.dataset.toast));
    });

    const shareButton = document.querySelector('[data-share]');
    if (shareButton) {
        shareButton.addEventListener('click', async () => {
            const data = { title: document.title, text: 'A cosy ComfyNest find', url: window.location.href };
            try {
                if (navigator.share) await navigator.share(data);
                else {
                    await navigator.clipboard.writeText(window.location.href);
                    showToast('Product link copied');
                }
            } catch (error) {
                if (error.name !== 'AbortError') showToast('Share link is ready');
            }
        });
    }

    document.querySelectorAll('.swatch').forEach((swatch) => {
        swatch.addEventListener('click', () => {
            document.querySelectorAll('.swatch').forEach((item) => item.classList.remove('selected'));
            swatch.classList.add('selected');
        });
    });

    document.querySelectorAll('[data-size-option]').forEach((button) => {
        button.addEventListener('click', () => {
            const selectedSize = button.dataset.sizeOption || 'M';
            document.querySelectorAll('[data-size-option]').forEach((item) => {
                const selected = item === button;
                item.classList.toggle('selected', selected);
                item.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            document.querySelectorAll('[data-selected-size]').forEach((input) => {
                input.value = selectedSize;
            });
            const label = document.querySelector('[data-selected-size-label]');
            if (label) label.textContent = selectedSize;
        });
    });

    const galleryMain = document.querySelector('[data-gallery-main]');
    const galleryCount = document.querySelector('[data-image-count]');
    const galleryThumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));
    const galleryStrip = document.querySelector('[data-gallery-strip]');
    const galleryPrev = document.querySelector('[data-gallery-prev]');
    const galleryNext = document.querySelector('[data-gallery-next]');
    const galleryFrame = galleryMain?.closest('.gallery-frame');
    const galleryMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    let activeImage = 0;
    const updateGalleryArrows = () => {
        if (!galleryStrip) return;
        if (galleryPrev) galleryPrev.disabled = galleryStrip.scrollLeft <= 1;
        if (galleryNext) galleryNext.disabled = galleryStrip.scrollLeft >= galleryStrip.scrollWidth - galleryStrip.clientWidth - 1;
    };
    const selectGalleryImage = (index) => {
        if (!galleryMain || !galleryThumbs[index]) return;
        activeImage = index;
        galleryMain.src = galleryThumbs[index].dataset.imageSrc;
        galleryThumbs.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
            thumb.setAttribute('aria-pressed', i === index ? 'true' : 'false');
        });
        if (galleryCount) galleryCount.textContent = `${index + 1} / ${galleryThumbs.length}`;
        if (galleryStrip) {
            const thumb = galleryThumbs[index];
            galleryStrip.scrollTo({ left: Math.max(0, thumb.offsetLeft - (galleryStrip.clientWidth - thumb.offsetWidth) / 2), behavior: galleryMotion });
        }
    };
    galleryThumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => selectGalleryImage(index));
        thumb.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? galleryThumbs.length - 1
                : Math.max(0, Math.min(galleryThumbs.length - 1, index + (event.key === 'ArrowRight' ? 1 : -1)));
            selectGalleryImage(next);
            galleryThumbs[next].focus({ preventScroll: true });
        });
    });
    galleryPrev?.addEventListener('click', () => galleryStrip?.scrollBy({ left: -(galleryStrip.clientWidth + 6), behavior: galleryMotion }));
    galleryNext?.addEventListener('click', () => galleryStrip?.scrollBy({ left: galleryStrip.clientWidth + 6, behavior: galleryMotion }));
    galleryStrip?.addEventListener('scroll', updateGalleryArrows, { passive: true });
    window.addEventListener('resize', updateGalleryArrows);
    updateGalleryArrows();
    if (galleryFrame) {
        let start = null;
        galleryFrame.addEventListener('pointerdown', (event) => {
            if (event.target.closest('button') || !event.isPrimary) return;
            start = { x: event.clientX, y: event.clientY };
        });
        galleryFrame.addEventListener('pointerup', (event) => {
            if (!start) return;
            const dx = event.clientX - start.x;
            const dy = event.clientY - start.y;
            start = null;
            if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.3) {
                selectGalleryImage(Math.max(0, Math.min(galleryThumbs.length - 1, activeImage + (dx < 0 ? 1 : -1))));
            }
        });
        galleryFrame.addEventListener('pointercancel', () => { start = null; });
        galleryMain?.addEventListener('dragstart', event => event.preventDefault());
    }

    document.querySelectorAll('.payment-option input').forEach((radio) => {
        const update = () => {
            document.querySelectorAll('.payment-option').forEach((item) => item.classList.remove('selected'));
            document.querySelectorAll('.payment-option input:checked').forEach((item) => item.closest('.payment-option')?.classList.add('selected'));
            const selectedMethod = document.querySelector('.payment-option input:checked');
            const submitLabel = document.querySelector('[data-payment-submit-label]');
            if (submitLabel && selectedMethod) {
                const labels = { phonepe: 'Open PhonePe', gpay: 'Open Google Pay', paytm: 'Open Paytm', upi: 'Choose UPI app', qr_scan: 'Show payment QR', cod: 'Place COD order' };
                submitLabel.textContent = labels[selectedMethod.value] || 'Pay now';
            }
        };
        radio.addEventListener('change', update);
        update();
    });

    const scrollPrice = document.querySelector('[data-scroll-price]');
    if (scrollPrice) scrollPrice.addEventListener('click', () => document.querySelector('.price-card')?.scrollIntoView({ behavior: 'smooth', block: 'center' }));

    document.querySelectorAll('[data-helpful]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.classList.contains('selected')) return;
            const count = Number(button.dataset.count || 0) + 1;
            button.dataset.count = String(count);
            button.querySelector('b').textContent = String(count);
            button.classList.add('selected');
            showToast('Thanks for your feedback');
        });
    });

    const search = document.querySelector('[data-product-search]');
    if (search) {
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let visible = 0;
            document.querySelectorAll('[data-product-name]').forEach((card) => {
                const matches = !query || card.dataset.productName.includes(query);
                card.hidden = !matches;
                if (matches) visible += 1;
            });
            const empty = document.querySelector('.no-products');
            if (empty) empty.hidden = visible !== 0;
            if (query) document.querySelector('#products')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    const timer = document.querySelector('[data-countdown]');
    if (timer) {
        let seconds = Number(timer.dataset.countdown || 0);
        window.setInterval(() => {
            seconds = Math.max(0, seconds - 1);
            const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            timer.innerHTML = `<span>${h}</span>:<span>${m}</span>:<span>${s}</span>`;
        }, 1000);
    }

    const orderProcessing = document.querySelector('[data-order-processing]');
    if (orderProcessing) {
        let remaining = Math.max(0, Number(orderProcessing.dataset.seconds ?? 18));
        const counter = orderProcessing.querySelector('[data-processing-countdown]');
        const progressBar = orderProcessing.querySelector('.processing-bar i');
        const finaliseForm = orderProcessing.querySelector('[data-processing-finalise]');
        if (progressBar) progressBar.style.animationDuration = `${Math.max(1, remaining)}s`;
        let finished = false;
        const finishProcessing = () => {
            if (finished) return;
            finished = true;
            if (finaliseForm) {
                if (typeof finaliseForm.requestSubmit === 'function') finaliseForm.requestSubmit();
                else finaliseForm.submit();
            }
        };
        const updateProcessing = () => {
            if (counter) counter.textContent = String(remaining);
            if (remaining <= 0) {
                finishProcessing();
                return;
            }
            remaining -= 1;
        };
        updateProcessing();
        const processingTimer = window.setInterval(() => {
            updateProcessing();
            if (finished) window.clearInterval(processingTimer);
        }, 1000);
    }

    const tracking = window.ComfyNestTracking || {};
    const purchase = window.ComfyNestPurchase || null;
    const pageCommerceEvent = tracking.pageCommerceEvent || null;
    const checkoutCommerceEvent = tracking.checkoutCommerceEvent || null;
    let consentChoice = '';
    let pageCommerceEventFired = false;
    try { consentChoice = window.localStorage.getItem('comfynest_tracking_consent') || ''; } catch (error) { consentChoice = ''; }

    const loadScript = (source, id) => new Promise((resolve, reject) => {
        if (id && document.getElementById(id)) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        if (id) script.id = id;
        script.async = true;
        script.src = source;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });

    const initialiseGoogleTag = () => {
        const adsTagId = String(tracking.googleAdsPurchaseSendTo || '').split('/')[0];
        const tagId = String(tracking.googleTagId || adsTagId || '').trim();
        if (!tagId) return Promise.resolve();
        window.dataLayer = window.dataLayer || [];
        if (tagId.startsWith('GTM-')) {
            window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
            return loadScript(`https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(tagId)}`, 'comfynest-google-tag');
        }
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', tagId, { page_title: document.title, page_location: window.location.href });
        if (adsTagId && adsTagId !== tagId) window.gtag('config', adsTagId);
        return loadScript(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(tagId)}`, 'comfynest-google-tag');
    };

    const initialiseMetaPixel = () => {
        const pixelIds = [...new Set([...(tracking.metaPixelIds || []), ...String(tracking.metaPixelId || '').split(/[\s,]+/)].filter(id => /^\d{5,25}$/.test(id)))];
        if (!pixelIds.length || window.fbq) return Promise.resolve();
        const fbq = function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
        fbq.push = fbq;
        fbq.loaded = true;
        fbq.version = '2.0';
        fbq.queue = [];
        window.fbq = fbq;
        window._fbq = fbq;
        pixelIds.forEach(pixelId => fbq('init', pixelId));
        fbq('track', 'PageView');
        return loadScript('https://connect.facebook.net/en_US/fbevents.js', 'comfynest-meta-pixel');
    };

    const normaliseCommercePayload = (payload) => {
        const items = Array.isArray(payload?.items) ? payload.items.map((item) => ({
            item_id: String(item.item_id || ''),
            item_name: String(item.item_name || ''),
            price: Number(item.price || 0),
            quantity: Math.max(1, Number(item.quantity || 1))
        })).filter((item) => item.item_id) : [];
        return {
            currency: String(payload?.currency || 'INR'),
            value: Number(payload?.value || 0),
            items
        };
    };

    const fireCommerceEvent = (googleEvent, metaEvent, payload) => {
        if (consentChoice !== 'accepted') return;
        const ecommerce = normaliseCommercePayload(payload);
        window.dataLayer = window.dataLayer || [];
        if (String(tracking.googleTagId || '').startsWith('GTM-')) {
            window.dataLayer.push({ event: googleEvent, ecommerce });
        } else if (window.gtag) {
            window.gtag('event', googleEvent, ecommerce);
        }
        if (window.fbq) {
            const contents = ecommerce.items.map((item) => ({ id: item.item_id, quantity: item.quantity, item_price: item.price }));
            const metaPayload = {
                content_ids: ecommerce.items.map((item) => item.item_id),
                content_type: 'product',
                contents,
                value: ecommerce.value,
                currency: ecommerce.currency,
                num_items: ecommerce.items.reduce((total, item) => total + item.quantity, 0)
            };
            if (ecommerce.items.length === 1) metaPayload.content_name = ecommerce.items[0].item_name;
            window.fbq('track', metaEvent, metaPayload);
        }
    };

    const firePageCommerceEvent = () => {
        if (pageCommerceEventFired || !pageCommerceEvent) return;
        pageCommerceEventFired = true;
        fireCommerceEvent('view_item', 'ViewContent', pageCommerceEvent);
    };

    document.querySelectorAll('[data-add-to-cart]').forEach((form) => {
        form.addEventListener('submit', () => {
            const price = Number(form.dataset.productPrice || 0);
            fireCommerceEvent('add_to_cart', 'AddToCart', {
                currency: 'INR',
                value: price,
                items: [{
                    item_id: form.dataset.productId || '',
                    item_name: form.dataset.productName || '',
                    price,
                    quantity: 1
                }]
            });
        });
    });

    document.querySelectorAll('[data-initiate-checkout]').forEach((link) => {
        link.addEventListener('click', () => fireCommerceEvent('begin_checkout', 'InitiateCheckout', checkoutCommerceEvent));
    });

    const firePurchaseOnce = () => {
        if (!purchase || !purchase.transaction_id) return;
        const storageKey = `comfynest_purchase_${purchase.transaction_id}`;
        try {
            if (window.localStorage.getItem(storageKey)) return;
            window.localStorage.setItem(storageKey, '1');
        } catch (error) { /* transaction_id still gives analytics providers a deduplication key */ }

        const googlePayload = {
            transaction_id: purchase.transaction_id,
            value: Number(purchase.value || 0),
            currency: purchase.currency || 'INR',
            items: Array.isArray(purchase.items) ? purchase.items : [],
            order_id: purchase.order_id || ''
        };
        window.dataLayer = window.dataLayer || [];
        if (String(tracking.googleTagId || '').startsWith('GTM-')) {
            window.dataLayer.push({ event: 'purchase', ecommerce: googlePayload });
        } else if (window.gtag) {
            window.gtag('event', 'purchase', googlePayload);
        }
        if (window.gtag && tracking.googleAdsPurchaseSendTo) {
            window.gtag('event', 'conversion', {
                send_to: tracking.googleAdsPurchaseSendTo,
                value: googlePayload.value,
                currency: googlePayload.currency,
                transaction_id: googlePayload.transaction_id
            });
        }
        if (window.fbq) {
            window.fbq('track', 'Purchase', {
                value: googlePayload.value,
                currency: googlePayload.currency,
                order_id: googlePayload.order_id,
                transaction_id: googlePayload.transaction_id,
                content_ids: googlePayload.items.map((item) => String(item.item_id || '')).filter(Boolean),
                content_type: 'product',
                contents: googlePayload.items.map((item) => ({ id: item.item_id, quantity: item.quantity, item_price: item.price }))
            }, { eventID: googlePayload.transaction_id });
        }
    };

    const loadGoogleMaps = () => {
        const key = String(tracking.googleMapsApiKey || '').trim();
        if (!key) return Promise.reject(new Error('Maps key is not configured'));
        if (window.google?.maps?.Geocoder) return Promise.resolve();
        return new Promise((resolve, reject) => {
            window.ComfyNestMapsReady = resolve;
            loadScript(`https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&loading=async&callback=ComfyNestMapsReady`, 'comfynest-google-maps').catch(reject);
        });
    };

    const reportApproximateLocation = async (location) => {
        if (!location || !tracking.pageViewId) return;
        const body = new URLSearchParams({
            csrf_token: tracking.csrfToken || '',
            page_view_id: tracking.pageViewId,
            city: location.city || '',
            state: location.state || '',
            country: location.country || '',
            latitude: String(location.latitude ?? ''),
            longitude: String(location.longitude ?? ''),
            source: location.source || ''
        });
        await fetch(tracking.locationEndpoint || 'track-location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body,
            credentials: 'same-origin'
        });
    };

    const fetchFreeIpLocation = async () => {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 3500);
        try {
            const response = await fetch('https://ipwho.is/', {
                headers: { Accept: 'application/json' },
                signal: controller.signal
            });
            if (!response.ok) throw new Error('IP location service unavailable');
            const data = await response.json();
            if (!data?.success || !Number.isFinite(Number(data.latitude)) || !Number.isFinite(Number(data.longitude))) throw new Error('IP location unavailable');
            return {
                city: String(data.city || ''),
                state: String(data.region || ''),
                country: String(data.country || ''),
                latitude: Number(data.latitude),
                longitude: Number(data.longitude),
                source: 'ipwhois'
            };
        } finally {
            window.clearTimeout(timeout);
        }
    };

    const fetchGoogleLocationFallback = () => new Promise((resolve, reject) => {
        if (!tracking.googleMapsApiKey || !navigator.geolocation) {
            reject(new Error('Google Maps fallback unavailable'));
            return;
        }
        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                await loadGoogleMaps();
                const geocoder = new window.google.maps.Geocoder();
                const response = await geocoder.geocode({ location: { lat: position.coords.latitude, lng: position.coords.longitude } });
                const components = response.results?.[0]?.address_components || [];
                const component = (type) => components.find((item) => item.types.includes(type))?.long_name || '';
                resolve({
                    city: component('locality') || component('administrative_area_level_2'),
                    state: component('administrative_area_level_1'),
                    country: component('country'),
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    source: 'google'
                });
            } catch (error) {
                reject(error);
            }
        }, reject, { enableHighAccuracy: false, timeout: 9000, maximumAge: 3600000 });
    });

    const captureApproximateLocation = async () => {
        if (!tracking.locationEnabled || !tracking.pageViewId) return;
        const pageLocationKey = `comfynest_location_${tracking.pageViewId}`;
        const cachedLocationKey = 'comfynest_ip_location_v1';
        let cachedLocation = null;
        try {
            if (window.sessionStorage.getItem(pageLocationKey)) return;
            window.sessionStorage.setItem(pageLocationKey, 'requested');
            cachedLocation = JSON.parse(window.sessionStorage.getItem(cachedLocationKey) || 'null');
        } catch (error) { cachedLocation = null; }

        let location = cachedLocation;
        if (!location || !Number.isFinite(Number(location.latitude)) || !Number.isFinite(Number(location.longitude))) {
            try {
                location = await fetchFreeIpLocation();
            } catch (error) {
                try { location = await fetchGoogleLocationFallback(); } catch (fallbackError) { location = null; }
            }
            if (location) {
                try { window.sessionStorage.setItem(cachedLocationKey, JSON.stringify(location)); } catch (error) { /* session cache is optional */ }
            }
        }
        try { await reportApproximateLocation(location); } catch (error) { /* location analytics is optional */ }
    };

    const activateTracking = async () => {
        await Promise.allSettled([initialiseGoogleTag(), initialiseMetaPixel()]);
        firePageCommerceEvent();
        firePurchaseOnce();
        captureApproximateLocation();
    };

    const consentBanner = document.querySelector('[data-consent-banner]');
    if (consentBanner && !consentChoice) consentBanner.hidden = false;
    document.querySelectorAll('[data-consent]').forEach((button) => {
        button.addEventListener('click', () => {
            consentChoice = button.dataset.consent || 'necessary';
            try { window.localStorage.setItem('comfynest_tracking_consent', consentChoice); } catch (error) { /* choice applies for this page */ }
            if (consentBanner) consentBanner.hidden = true;
            if (consentChoice === 'accepted') activateTracking();
        });
    });
    document.querySelectorAll('[data-privacy-choices]').forEach((button) => {
        button.addEventListener('click', () => {
            try { window.localStorage.removeItem('comfynest_tracking_consent'); } catch (error) { /* show choices anyway */ }
            if (consentBanner) consentBanner.hidden = false;
        });
    });
    if (consentChoice === 'accepted') activateTracking();
});
