jQuery(document).ready(function ($) {
    if (typeof msiwFrontendData === 'undefined') {
        return;
    }

    var iconMap = msiwFrontendData.iconMap || {};
    var storeApiCartUrl = msiwFrontendData.storeApiCartUrl || '';
    var apiSelectedUrl = null;
    var apiFetchTimer = null;

    // Normalizes a method ID like "flat_rate" into a form comparable to
    // rendered label text ("flat rate"), so word-boundary matching works.
    function normalize(str) {
        return str.replace(/[_-]+/g, ' ').trim().toLowerCase();
    }

    // The Cart page often shows only the already-selected shipping method with no
    // radio buttons to read from. The Store API exposes exactly which rate is
    // selected (rate.selected === true), so this is the authoritative source there.
    function refreshFromStoreApi(then) {
        if (!storeApiCartUrl) {
            then();
            return;
        }

        fetch(storeApiCartUrl, { credentials: 'same-origin' })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                apiSelectedUrl = null;

                if (data && Array.isArray(data.shipping_rates)) {
                    data.shipping_rates.forEach(function (pkg) {
                        (pkg.shipping_rates || []).forEach(function (rate) {
                            if (rate.selected && iconMap[rate.rate_id]) {
                                apiSelectedUrl = iconMap[rate.rate_id];
                            }
                        });
                    });
                }

                then();
            })
            .catch(function () {
                then();
            });
    }

    // Debounced trigger so we don't hit the Store API on every single DOM mutation.
    function scheduleStoreApiRefresh() {
        clearTimeout(apiFetchTimer);
        apiFetchTimer = setTimeout(function () {
            refreshFromStoreApi(injectIcons);
        }, 400);
    }

    function injectIcons() {
        // Variant 1: radio buttons (Checkout, and Cart when it shows a rate selector).
        // This matches on the exact rate ID (radio value), so it's always correct
        // regardless of what title the merchant gave the method.
        $('input[type="radio"]').each(function () {
            var radio = $(this);
            var value = radio.val();

            if (value && iconMap[value]) {
                var id = radio.attr('id');
                if (id) {
                    var label = $('label[for="' + id + '"]');
                    if (label.length && !label.find('.blocks-js-shipping-icon').length) {
                        var imgHtml = '<img src="' + iconMap[value] + '" class="blocks-js-shipping-icon" />';
                        label.prepend(imgHtml);
                    }
                }
            }
        });

        // Prefer the exact ID read from a checked radio; if none exists on this page
        // (e.g. the Cart page showing only the selected method), fall back to the
        // Store API result fetched separately.
        var selectedUrl = getSelectedIconUrl() || apiSelectedUrl;

        // Variant 2: static display on the Cart block when only one method exists
        // (no radio button is rendered in that case, so we fall back to matching
        // the method ID against the rendered text).
        if (!selectedUrl) {
            $('.wc-block-components-totals-shipping__val').each(function () {
                injectByTextFallback($(this));
            });
        }

        // Variant 3: the "Order summary" panel on Checkout and the "Cart totals"
        // panel on Cart both render the selected method's name (e.g. "Fedex") via
        // the same shared WooCommerce Blocks component, so one selector covers both.
        $('.wc-block-components-totals-shipping').each(function () {
            if (selectedUrl) {
                injectSelected($(this), selectedUrl);
            } else {
                injectByTextFallback($(this));
            }
        });
    }

    // Reads the currently checked shipping radio button and returns its icon URL,
    // looked up by the exact rate ID rather than by parsing displayed text.
    function getSelectedIconUrl() {
        var url = null;
        $('input[type="radio"]:checked').each(function () {
            var value = $(this).val();
            if (value && iconMap[value]) {
                url = iconMap[value];
                return false;
            }
        });
        return url;
    }

    // A leaf of text counts as a candidate method-name line if it isn't one of the
    // fixed row labels or a currency amount.
    function isNameCandidate(text) {
        if (!text) {
            return false;
        }
        if (text === 'delivery' || text === 'shipping' || text === 'total' || text === 'estimated total') {
            return false;
        }
        if (/^[\d.,\s$€£]+$/.test(text)) {
            return false; // Looks like a price, not a method name.
        }
        return true;
    }

    // Prepends the given icon URL to the first leaf inside the container that looks
    // like a method-name line (used once we already know which icon is correct).
    function injectSelected(container, url) {
        if (!container.length || container.find('.blocks-js-shipping-icon').length) {
            return;
        }

        var target = null;

        container.find('*').addBack().each(function () {
            var el = $(this);

            if (el.children().length > 0) {
                return; // Only consider leaf-ish elements.
            }

            if (isNameCandidate(normalize(el.text()))) {
                target = el;
                return false;
            }
        });

        if (target) {
            var imgHtml = '<img src="' + url + '" class="blocks-js-shipping-icon msiw-icon-summary" />';
            target.prepend(imgHtml);
        }
    }

    // Fallback used only when no radio button is present to read the selection from
    // (e.g. the Cart block with a single available method). Matches by method ID
    // appearing in the rendered text, which only works when the title resembles the ID.
    function injectByTextFallback(container) {
        if (!container.length || container.find('.blocks-js-shipping-icon').length) {
            return;
        }

        var target = null;
        var matchedUrl = null;

        container.find('*').addBack().each(function () {
            var el = $(this);

            if (el.children().length > 0) {
                return;
            }

            var text = normalize(el.text());
            if (!text) {
                return;
            }

            $.each(iconMap, function (key, url) {
                var methodId = normalize(key.split(':')[0]);
                var pattern = new RegExp('\\b' + methodId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b');
                if (pattern.test(text)) {
                    target = el;
                    matchedUrl = url;
                    return false;
                }
            });

            if (target) {
                return false;
            }
        });

        if (target) {
            var imgHtml = '<img src="' + matchedUrl + '" class="blocks-js-shipping-icon msiw-icon-summary" />';
            target.prepend(imgHtml);
        }
    }

    // Watch for React re-renders, since Cart/Checkout blocks redraw on state changes.
    // Each mutation runs the fast DOM-based injection immediately, and also schedules
    // a debounced Store API refresh in case the selected rate changed (relevant on
    // the Cart page, which has no radio buttons to read from directly).
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
            injectIcons();
            scheduleStoreApiRefresh();
        });
        var targetNode = document.body;
        if (targetNode) {
            observer.observe(targetNode, { childList: true, subtree: true });
        }
    }

    setTimeout(injectIcons, 300);
    refreshFromStoreApi(injectIcons);
});
