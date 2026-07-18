<?php
/**
 * Plugin Name:       Milan's Shipping Icons For Woo
 * Requires Plugins:  woocommerce
 * Description:       Displays custom courier/shipping logos next to shipping methods on Cart, Checkout pages and emails using native WordPress safety standards.
 * Plugin URI:        https://github.com/milangru/Milan-s-Shipping-Icons-For-Woo
 * Version:           1.1.0
 * Author:            Milan Grujić
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       milans-shipping-icons-woo
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MSIW_VERSION', '1.0.0' );

// Safely load the frontend rendering logic.
$msiw_frontend_file = plugin_dir_path( __FILE__ ) . 'milans-shipping-icons-woo-frontend.php';
if ( file_exists( $msiw_frontend_file ) ) {
    require_once $msiw_frontend_file;
}

// 1. Add a custom tab to WooCommerce Settings.
add_filter( 'woocommerce_settings_tabs_array', 'msiw_add_settings_tab', 50 );
function msiw_add_settings_tab( $settings_tabs ) {
    $settings_tabs['msiw_settings_tab'] = __( 'Shipping Icons', 'milans-shipping-icons-woo' );
    return $settings_tabs;
}

// 2. Render the admin settings tab content.
add_action( 'woocommerce_settings_tabs_msiw_settings_tab', 'msiw_settings_tab_content' );
function msiw_settings_tab_content() {
    $saved_icons    = get_option( 'msiw_custom_icons', array() );
    $shipping_zones = WC_Shipping_Zones::get_zones();

    // Add the "Rest of the World" / default zone (zone_id 0), which get_zones() excludes.
    $shipping_zones[] = array(
        'zone_id' => 0,
    );
    ?>
    <div class="msiw-settings-wrap">
        <h2><?php esc_html_e( 'Shipping Method Icons Configuration', 'milans-shipping-icons-woo' ); ?></h2>
        <p><?php esc_html_e( 'Choose logos that will be displayed next to each shipping method name during Checkout and on the Cart page.', 'milans-shipping-icons-woo' ); ?></p>

        <!-- Native WP nonce field for security. -->
        <?php wp_nonce_field( 'msiw_save_shipping_icons_action', 'msiw_shipping_icons_nonce_field' ); ?>

        <table class="wp-list-table widefat fixed striped msiw-icons-table">
            <thead>
                <tr>
                    <th class="msiw-col-zone"><?php esc_html_e( 'Shipping Zone', 'milans-shipping-icons-woo' ); ?></th>
                    <th class="msiw-col-method"><?php esc_html_e( 'Shipping Method (ID)', 'milans-shipping-icons-woo' ); ?></th>
                    <th class="msiw-col-icon"><?php esc_html_e( 'Icon / Logo', 'milans-shipping-icons-woo' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $has_methods = false;

                foreach ( $shipping_zones as $zone_data ) {
                    // Every zone (including zone_id 0) is loaded exactly once here.
                    $zone    = new WC_Shipping_Zone( $zone_data['zone_id'] );
                    $methods = $zone->get_shipping_methods();

                    if ( empty( $methods ) ) {
                        continue;
                    }

                    $has_methods = true;

                    foreach ( $methods as $method ) {
                        $method_key       = $method->id . ':' . $method->instance_id;
                        $current_value    = isset( $saved_icons[ $method_key ] ) ? $saved_icons[ $method_key ] : '';
                        $has_image_class  = empty( $current_value ) ? 'msiw-preview-hidden' : 'msiw-preview-visible';
                        $has_text_class   = empty( $current_value ) ? 'msiw-placeholder-visible' : 'msiw-placeholder-hidden';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $zone->get_zone_name() ); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo esc_html( $method->get_title() ); ?></strong><br>
                                <small class="msiw-method-id-subtext"><?php esc_html_e( 'ID:', 'milans-shipping-icons-woo' ); ?> <?php echo esc_html( $method_key ); ?></small>
                            </td>
                            <td>
                                <div class="msiw-icon-row">
                                    <div class="msiw-img-preview-container">
                                        <img class="msiw-icon-preview <?php echo esc_attr( $has_image_class ); ?>" src="<?php echo esc_url( $current_value ); ?>" />
                                        <span class="msiw-no-img-placeholder <?php echo esc_attr( $has_text_class ); ?>"><?php esc_html_e( 'No image', 'milans-shipping-icons-woo' ); ?></span>
                                    </div>

                                    <input type="text"
                                           name="shipping_icons[<?php echo esc_attr( $method_key ); ?>]"
                                           class="msiw-icon-url msiw-wc-native-field msiw-input-hidden"
                                           value="<?php echo esc_url( $current_value ); ?>" />

                                    <button type="button" class="button button-secondary msiw-upload-icon-btn"><?php esc_html_e( 'Choose Image', 'milans-shipping-icons-woo' ); ?></button>
                                    <button type="button" class="button button-link msiw-delete-icon-btn <?php echo esc_attr( $has_image_class ); ?>"><?php esc_html_e( 'Remove', 'milans-shipping-icons-woo' ); ?></button>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                }

                if ( ! $has_methods ) {
                    ?>
                    <tr>
                        <td colspan="3" class="msiw-no-methods-found">
                            <?php esc_html_e( 'No active shipping methods found.', 'milans-shipping-icons-woo' ); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// 3. Handle saving options via the native WooCommerce settings processor.
add_action( 'woocommerce_update_options_msiw_settings_tab', 'msiw_save_settings' );
function msiw_save_settings() {
    // Verify nonce first.
    if ( ! isset( $_POST['msiw_shipping_icons_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['msiw_shipping_icons_nonce_field'] ) ), 'msiw_save_shipping_icons_action' ) ) {
        wp_die( esc_html__( 'Security check failed. Please try again.', 'milans-shipping-icons-woo' ) );
    }

    if ( isset( $_POST['shipping_icons'] ) && is_array( $_POST['shipping_icons'] ) ) {
        // Unslash and sanitize in the same call so each array value is escaped as a URL.
        $saved_icons = array_map( 'esc_url_raw', wp_unslash( $_POST['shipping_icons'] ) );

        update_option( 'msiw_custom_icons', $saved_icons );
        msiw_mirror_to_instance_settings( $saved_icons );
    }
}

// 5. Add a "Shipping Icon" field directly inside each shipping method's own settings
//    modal (Shipping Zones screen), in addition to the central table above. Both write
//    to the same option (msiw_custom_icons), kept in sync via msiw_sync_instance_to_central()
//    and msiw_mirror_to_instance_settings() below, so editing either one updates the other.
//
//    Registered dynamically for every shipping method WooCommerce knows about (built-in
//    and any custom method class from other plugins), instead of listing method IDs by
//    hand, so third-party shipping methods get the field automatically.
add_filter( 'woocommerce_shipping_methods', 'msiw_register_instance_icon_field_for_all_methods', 20 );
function msiw_register_instance_icon_field_for_all_methods( $methods ) {
    foreach ( array_keys( $methods ) as $method_id ) {
        add_filter( 'woocommerce_shipping_instance_form_fields_' . $method_id, 'msiw_add_instance_icon_field' );
    }
    return $methods;
}
function msiw_add_instance_icon_field( $fields ) {
    $fields['msiw_icon_url'] = array(
        'title'       => __( 'Shipping Icon', 'milans-shipping-icons-woo' ),
        'type'        => 'text',
        'description' => __( 'Icon shown next to this method at checkout. Use the Choose Image button below the field.', 'milans-shipping-icons-woo' ),
        'desc_tip'    => false,
        'default'     => '',
        'css'         => 'display:none;', // Hidden; the JS-rendered media picker below replaces it visually.
    );
    return $fields;
}

// 6. Whenever a shipping method's own instance settings are saved (i.e. the merchant used
//    the field added above instead of the central table), mirror the value into the shared
//    msiw_custom_icons option so the central table stays in sync.
add_action( 'updated_option', 'msiw_sync_instance_to_central', 10, 3 );
function msiw_sync_instance_to_central( $option, $old_value, $value ) {
    msiw_maybe_sync_instance_option( $option, $value );
}

add_action( 'added_option', 'msiw_sync_instance_to_central_on_add', 10, 2 );
function msiw_sync_instance_to_central_on_add( $option, $value ) {
    msiw_maybe_sync_instance_option( $option, $value );
}

function msiw_maybe_sync_instance_option( $option, $value ) {
    if ( ! preg_match( '/^woocommerce_(.+)_(\d+)_settings$/', $option, $matches ) ) {
        return;
    }

    if ( ! is_array( $value ) || ! array_key_exists( 'msiw_icon_url', $value ) ) {
        return;
    }

    $method_key  = $matches[1] . ':' . $matches[2];
    $saved_icons = get_option( 'msiw_custom_icons', array() );
    $new_icon    = esc_url_raw( $value['msiw_icon_url'] );

    if ( empty( $new_icon ) ) {
        if ( ! isset( $saved_icons[ $method_key ] ) ) {
            return; // Already absent, nothing to do.
        }
        unset( $saved_icons[ $method_key ] );
    } else {
        if ( isset( $saved_icons[ $method_key ] ) && $saved_icons[ $method_key ] === $new_icon ) {
            return; // Already in sync, avoid an unnecessary write.
        }
        $saved_icons[ $method_key ] = $new_icon;
    }

    update_option( 'msiw_custom_icons', $saved_icons );
}

// 7. The reverse direction: when the central table is saved, also mirror each icon into
//    that method's own instance settings option, so its per-method field shows the
//    up-to-date value the next time its modal is opened.
function msiw_mirror_to_instance_settings( $saved_icons ) {
    foreach ( $saved_icons as $method_key => $icon_url ) {
        $parts = explode( ':', $method_key, 2 );
        if ( 2 !== count( $parts ) ) {
            continue;
        }

        list( $method_id, $instance_id ) = $parts;
        $option_name    = 'woocommerce_' . $method_id . '_' . $instance_id . '_settings';
        $instance_settings = get_option( $option_name, array() );

        if ( ! is_array( $instance_settings ) ) {
            continue;
        }

        if ( isset( $instance_settings['msiw_icon_url'] ) && $instance_settings['msiw_icon_url'] === $icon_url ) {
            continue; // Already in sync.
        }

        $instance_settings['msiw_icon_url'] = $icon_url;
        update_option( $option_name, $instance_settings );
    }
}


add_action( 'admin_enqueue_scripts', 'msiw_admin_assets' );
function msiw_admin_assets( $hook ) {
    if ( 'woocommerce_page_wc-settings' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'msiw-admin-css',
        plugin_dir_url( __FILE__ ) . 'assets/css/shipping-icons-admin.css',
        array(),
        MSIW_VERSION
    );

    wp_enqueue_media();

    add_action( 'admin_footer', 'msiw_admin_footer_script' );
}

function msiw_admin_footer_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Turns the plain (hidden) "Shipping Icon" text field inside a shipping
            // method's own settings modal into the same preview + upload UI used by the
            // central table, reusing the same classes so the click handlers below work
            // for both without duplication.
            // WooCommerce's own admin script for the Free Shipping method treats every
            // field that comes after the "requires" dropdown as conditional (like
            // "Minimum order amount"), and hides it via an inline display:none — even
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

                [label.get(0), fieldset.get(0)].forEach(function(el) {
                    if (!el || el.msiwVisibilityGuarded) {
                        return;
                    }
                    el.msiwVisibilityGuarded = true;

                    var guard = new MutationObserver(function() {
                        if (el.style.display === 'none') {
                            el.style.display = '';
                        }
                    });
                    guard.observe(el, { attributes: true, attributeFilter: ['style'] });
                });
            }

            function msiwEnhanceInstanceFields(context) {
                var scope = context ? $(context) : $(document);

                scope.find('input[id$="_msiw_icon_url"]').each(function() {
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
                                '<span class="msiw-no-img-placeholder ' + hasTextClass + '"><?php echo esc_js( __( 'No image', 'milans-shipping-icons-woo' ) ); ?></span>' +
                            '</div>' +
                            '<button type="button" class="button button-secondary msiw-upload-icon-btn"><?php echo esc_js( __( 'Choose Image', 'milans-shipping-icons-woo' ) ); ?></button>' +
                            '<button type="button" class="button button-link msiw-delete-icon-btn ' + hasImageClass + '"><?php echo esc_js( __( 'Remove', 'milans-shipping-icons-woo' ) ); ?></button>' +
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
                var modalObserver = new MutationObserver(function() {
                    msiwEnhanceInstanceFields();
                });
                modalObserver.observe(document.body, { childList: true, subtree: true });
            }

            msiwEnhanceInstanceFields();

            // Delegated so these also work on rows added later (instance modal),
            // not just the rows present in the central table at page load.
            $(document).on('click', '.msiw-upload-icon-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var row = button.closest('.msiw-icon-row');
                var input = row.find('.msiw-icon-url');
                var preview = row.find('.msiw-icon-preview');
                var placeholder = row.find('.msiw-no-img-placeholder');
                var deleteBtn = row.find('.msiw-delete-icon-btn');

                var uploader = wp.media({
                    title: '<?php echo esc_js( __( 'Choose Shipping Icon', 'milans-shipping-icons-woo' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Use this image', 'milans-shipping-icons-woo' ) ); ?>' },
                    multiple: false
                }).on('select', function() {
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

            $(document).on('click', '.msiw-delete-icon-btn', function(e) {
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
    </script>
    <?php
}