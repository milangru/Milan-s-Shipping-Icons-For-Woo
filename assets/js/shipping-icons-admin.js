jQuery(document).ready(function ($) {
    var msiwI18n = (typeof msiwAdminData !== 'undefined') ? msiwAdminData : {
        chooseIconTitle: 'Choose Shipping Icon',
        useImageText: 'Use this image',
        noImageText: 'No image'
    };

    // Turns the plain (hidden) "Shipping Icon" text field inside a shipping
    // method's own settings modal into the same preview + upload UI used by the
    // central table, reusing the same classes so the click handlers below work
    // for both without duplication.
    // WooCommerce's own admin script for the Free Shipping method treats every
    // field that comes after the "requires" dropdown as conditional (like
    // "Minimum order amount"), and hides it via an inline display:none -- even
    // though our field has nothing to do with that setting. This forces our
    // field's label and fieldset to stay visible, and keeps re-forcing it if
    // WooCommerce's script re-hides it again (e.g. when "requires" changes).
    function msiwForceFieldVisible(input) {
        var id = input.attr('id');
        if (!id) {
            return;
        }

        var label = $('label[for="' + id + '"]');
        var fieldset = input.closest('fieldset');

        function unhide(el) {
            if (el.length && el.css('display') === 'none') {
                el.css('display', '');
            }
        }

        unhide(label);
        unhide(fieldset);

        if (typeof MutationObserver === 'undefined') {
            return;
        }

        [label.get(0), fieldset.get(0)].forEach(function (el) {
            if (!el || el.msiwVisibilityGuarded) {
                return;
            }
            el.msiwVisibilityGuarded = true;

            var guard = new MutationObserver(function () {
                if (el.style.display === 'none') {
                    el.style.display = '';
                }
            });
            guard.observe(el, { attributes: true, attributeFilter: ['style'] });
        });
    }

    function msiwEnhanceInstanceFields(context) {
        var scope = context ? $(context) : $(document);

        scope.find('input[id$="_msiw_icon_url"]').each(function () {
            var input = $(this);

            if (input.data('msiwEnhanced')) {
                return;
            }
            input.data('msiwEnhanced', true);
            input.addClass('msiw-icon-url msiw-input-hidden');

            var currentValue = input.val();
            var hasImageClass = currentValue ? 'msiw-preview-visible' : 'msiw-preview-hidden';
            var hasTextClass = currentValue ? 'msiw-placeholder-hidden' : 'msiw-placeholder-visible';

            var row = $(
                '<div class="msiw-icon-row msiw-instance-icon-row">' +
                    '<div class="msiw-img-preview-container">' +
                        '<img class="msiw-icon-preview ' + hasImageClass + '" src="' + currentValue + '" />' +
                        '<span class="msiw-no-img-placeholder ' + hasTextClass + '">' + msiwI18n.noImageText + '</span>' +
                    '</div>' +
                    '<button type="button" class="button button-secondary msiw-upload-icon-btn">' + msiwI18n.chooseImageText + '</button>' +
                    '<button type="button" class="button button-link msiw-delete-icon-btn ' + hasImageClass + '">' + msiwI18n.removeText + '</button>' +
                '</div>'
            );

            var marker = $('<span></span>');
            input.before(marker);
            input.detach();
            row.append(input);
            marker.replaceWith(row);

            msiwForceFieldVisible(input);
        });
    }

    // The shipping method modal is injected into the DOM after the merchant
    // clicks a method row (it isn't present on initial page load), so watch for it
    // instead of only enhancing once on ready.
    if (typeof MutationObserver !== 'undefined') {
        var modalObserver = new MutationObserver(function () {
            msiwEnhanceInstanceFields();
        });
        modalObserver.observe(document.body, { childList: true, subtree: true });
    }

    msiwEnhanceInstanceFields();

    // Delegated so these also work on rows added later (instance modal),
    // not just the rows present in the central table at page load.
    $(document).on('click', '.msiw-upload-icon-btn', function (e) {
        e.preventDefault();
        var button = $(this);
        var row = button.closest('.msiw-icon-row');
        var input = row.find('.msiw-icon-url');
        var preview = row.find('.msiw-icon-preview');
        var placeholder = row.find('.msiw-no-img-placeholder');
        var deleteBtn = row.find('.msiw-delete-icon-btn');

        var uploader = wp.media({
            title: msiwI18n.chooseIconTitle,
            button: { text: msiwI18n.useImageText },
            multiple: false
        }).on('select', function () {
            var attachment = uploader.state().get('selection').first().toJSON();
            input.val(attachment.url).trigger('change');

            preview.attr('src', attachment.url)
                   .removeClass('msiw-preview-hidden')
                   .addClass('msiw-preview-visible');

            placeholder.removeClass('msiw-placeholder-visible')
                       .addClass('msiw-placeholder-hidden');

            deleteBtn.removeClass('msiw-preview-hidden')
                     .addClass('msiw-preview-visible');
        }).open();
    });

    $(document).on('click', '.msiw-delete-icon-btn', function (e) {
        e.preventDefault();
        var button = $(this);
        var row = button.closest('.msiw-icon-row');
        var input = row.find('.msiw-icon-url');
        var preview = row.find('.msiw-icon-preview');
        var placeholder = row.find('.msiw-no-img-placeholder');

        input.val('').trigger('change');

        preview.removeClass('msiw-preview-visible')
               .addClass('msiw-preview-hidden')
               .attr('src', '');

        placeholder.removeClass('msiw-placeholder-hidden')
                   .addClass('msiw-placeholder-visible');

        button.removeClass('msiw-preview-visible')
              .addClass('msiw-preview-hidden');
    });
});
