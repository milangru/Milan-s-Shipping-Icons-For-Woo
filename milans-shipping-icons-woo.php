<?php
/**
 * Plugin Name:       Milan's Shipping Icons For Woo
 * Requires Plugins:  woocommerce
 * Description:       Displays custom courier/shipping logos next to shipping methods on Cart, Checkout pages and emails using native WordPress safety standards.
 * Plugin URI:        https://github.com/milangru/Milan-s-Shipping-Icons-For-Woo
 * Version:           1.1.1
 * Author:            Milan Grujić
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       milans-shipping-icons-for-woo
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MSIW_VERSION', '1.1.1' );

// Safely load the frontend rendering logic.
$msiw_frontend_file = plugin_dir_path( __FILE__ ) . 'milans-shipping-icons-woo-frontend.php';
if ( file_exists( $msiw_frontend_file ) ) {
    require_once $msiw_frontend_file;
}

// Safely load the review notice, shown after 30 completed orders.
$msiw_review_notice_file = plugin_dir_path( __FILE__ ) . 'includes/class-review-notice.php';
if ( file_exists( $msiw_review_notice_file ) ) {
    require_once $msiw_review_notice_file;
    new MSIW_Review_Notice();
}

// 1. Add a custom tab to WooCommerce Settings.
add_filter( 'woocommerce_settings_tabs_array', 'msiw_add_settings_tab', 50 );
function msiw_add_settings_tab( $settings_tabs ) {
    $settings_tabs['msiw_settings_tab'] = __( 'Shipping Icons', 'milans-shipping-icons-for-woo' );
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
        <h2><?php esc_html_e( 'Shipping Method Icons Configuration', 'milans-shipping-icons-for-woo' ); ?></h2>
        <p><?php esc_html_e( 'Choose logos that will be displayed next to each shipping method name during Checkout and on the Cart page.', 'milans-shipping-icons-for-woo' ); ?></p>

        <!-- Native WP nonce field for security. -->
        <?php wp_nonce_field( 'msiw_save_shipping_icons_action', 'msiw_shipping_icons_nonce_field' ); ?>

        <table class="wp-list-table widefat fixed striped msiw-icons-table">
            <thead>
                <tr>
                    <th class="msiw-col-zone"><?php esc_html_e( 'Shipping Zone', 'milans-shipping-icons-for-woo' ); ?></th>
                    <th class="msiw-col-method"><?php esc_html_e( 'Shipping Method (ID)', 'milans-shipping-icons-for-woo' ); ?></th>
                    <th class="msiw-col-icon"><?php esc_html_e( 'Icon / Logo', 'milans-shipping-icons-for-woo' ); ?></th>
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
                                <small class="msiw-method-id-subtext"><?php esc_html_e( 'ID:', 'milans-shipping-icons-for-woo' ); ?> <?php echo esc_html( $method_key ); ?></small>
                            </td>
                            <td>
                                <div class="msiw-icon-row">
                                    <div class="msiw-img-preview-container">
                                        <img class="msiw-icon-preview <?php echo esc_attr( $has_image_class ); ?>" src="<?php echo esc_url( $current_value ); ?>" />
                                        <span class="msiw-no-img-placeholder <?php echo esc_attr( $has_text_class ); ?>"><?php esc_html_e( 'No image', 'milans-shipping-icons-for-woo' ); ?></span>
                                    </div>

                                    <input type="text"
                                           name="shipping_icons[<?php echo esc_attr( $method_key ); ?>]"
                                           class="msiw-icon-url msiw-wc-native-field msiw-input-hidden"
                                           value="<?php echo esc_url( $current_value ); ?>" />

                                    <button type="button" class="button button-secondary msiw-upload-icon-btn"><?php esc_html_e( 'Choose Image', 'milans-shipping-icons-for-woo' ); ?></button>
                                    <button type="button" class="button button-link msiw-delete-icon-btn <?php echo esc_attr( $has_image_class ); ?>"><?php esc_html_e( 'Remove', 'milans-shipping-icons-for-woo' ); ?></button>
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
                            <?php esc_html_e( 'No active shipping methods found.', 'milans-shipping-icons-for-woo' ); ?>
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
        wp_die( esc_html__( 'Security check failed. Please try again.', 'milans-shipping-icons-for-woo' ) );
    }

    if ( isset( $_POST['shipping_icons'] ) && is_array( $_POST['shipping_icons'] ) ) {
        // Unslash and sanitize in the same call so each array value is escaped as a URL.
        $posted_icons = array_map( 'esc_url_raw', wp_unslash( $_POST['shipping_icons'] ) );

        // Only keep entries whose "method_id:instance_id" key belongs to a shipping
        // method instance that actually exists. Without this, a crafted POST key could
        // make msiw_mirror_to_instance_settings() create/overwrite an arbitrary
        // woocommerce_{method}_{instance}_settings option below.
        $valid_keys  = msiw_get_valid_method_keys();
        $saved_icons = array_intersect_key( $posted_icons, $valid_keys );

        update_option( 'msiw_custom_icons', $saved_icons );
        msiw_mirror_to_instance_settings( $saved_icons );
    }
}

// 4. Builds a whitelist of "method_id:instance_id" keys for every shipping method
//    instance that currently exists across all zones (including the default "Rest of
//    the World" zone). Used to validate any method_key coming from user input before
//    it's used to build an option name or written to instance settings.
function msiw_get_valid_method_keys() {
    $valid_keys     = array();
    $shipping_zones = WC_Shipping_Zones::get_zones();
    $shipping_zones[] = array( 'zone_id' => 0 );

    foreach ( $shipping_zones as $zone_data ) {
        $zone    = new WC_Shipping_Zone( $zone_data['zone_id'] );
        $methods = $zone->get_shipping_methods();

        foreach ( $methods as $method ) {
            $valid_keys[ $method->id . ':' . $method->instance_id ] = true;
        }
    }

    return $valid_keys;
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
        'title'       => __( 'Shipping Icon', 'milans-shipping-icons-for-woo' ),
        'type'        => 'text',
        'description' => __( 'Icon shown next to this method at checkout. Use the Choose Image button below the field.', 'milans-shipping-icons-for-woo' ),
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
    $valid_keys = msiw_get_valid_method_keys();

    foreach ( $saved_icons as $method_key => $icon_url ) {
        // Belt-and-suspenders: skip anything that isn't a real, existing method
        // instance, even though msiw_save_settings() already filtered for this.
        if ( ! isset( $valid_keys[ $method_key ] ) ) {
            continue;
        }

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

    wp_enqueue_script(
        'msiw-admin-js',
        plugin_dir_url( __FILE__ ) . 'assets/js/shipping-icons-admin.js',
        array( 'jquery' ),
        MSIW_VERSION,
        true
    );

    wp_localize_script(
        'msiw-admin-js',
        'msiwAdminData',
        array(
            'chooseIconTitle' => __( 'Choose Shipping Icon', 'milans-shipping-icons-for-woo' ),
            'chooseImageText' => __( 'Choose Image', 'milans-shipping-icons-for-woo' ),
            'useImageText'    => __( 'Use this image', 'milans-shipping-icons-for-woo' ),
            'removeText'      => __( 'Remove', 'milans-shipping-icons-for-woo' ),
            'noImageText'     => __( 'No image', 'milans-shipping-icons-for-woo' ),
        )
    );
}

