<?php
/**
 * Plugin Name: Milan's Shipping Icons For Woo
 * Description: Displays custom courier/shipping logos next to shipping methods on Cart, Checkout pages and emails using native WordPress safety standards.
 * Version:     1.0.0
 * Author:      Milan
 * License:     GPL2
 * Text Domain: milans-shipping-icons-woo
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
    }
}

// 4. Load the WP media library, jQuery logic, and the admin stylesheet.
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
            $('.msiw-upload-icon-btn').click(function(e) {
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

            $('.msiw-delete-icon-btn').click(function(e) {
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