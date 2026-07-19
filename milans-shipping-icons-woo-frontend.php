<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Load the external CSS file for the frontend.
add_action( 'wp_enqueue_scripts', 'msiw_enqueue_frontend_styles', 99 ); // High priority so it loads after theme/WooCommerce styles.
function msiw_enqueue_frontend_styles() {
    // No is_checkout()/is_cart() restriction here so it also works with page builders and custom templates.
    wp_enqueue_style(
        'msiw-frontend-css',
        plugin_dir_url( __FILE__ ) . 'assets/css/shipping-icons-frontend.css',
        array(),
        MSIW_VERSION
    );
}

// 2. Classic Cart & Checkout: prepend the icon to the shipping method label.
add_filter( 'woocommerce_cart_shipping_method_full_label', 'msiw_display_icon_checkout', 10, 2 );
function msiw_display_icon_checkout( $label, $method ) {
    $saved_icons = get_option( 'msiw_custom_icons', array() );
    $method_key  = $method->id . ':' . $method->instance_id;

    if ( ! empty( $saved_icons[ $method_key ] ) ) {
        $image_url = $saved_icons[ $method_key ];

        $image_html = sprintf(
            '<img src="%s" alt="%s" class="shipping-method-icon wc-shipping-icon-%s" />',
            esc_url( $image_url ),
            esc_attr( $method->label ),
            esc_attr( $method->id )
        );

        $label = $image_html . $label;
    }

    return $label;
}

// 3. JS fallback for the Cart and Checkout blocks (covers both radio buttons and static text).
add_action( 'wp_enqueue_scripts', 'msiw_blocks_js' );
function msiw_blocks_js() {
    if ( ! is_checkout() && ! is_cart() ) {
        return;
    }

    $saved_icons = get_option( 'msiw_custom_icons', array() );
    if ( empty( $saved_icons ) || ! is_array( $saved_icons ) ) {
        return;
    }

    wp_enqueue_script(
        'msiw-frontend-js',
        plugin_dir_url( __FILE__ ) . 'assets/js/shipping-icons-frontend.js',
        array( 'jquery' ),
        MSIW_VERSION,
        true
    );

    wp_localize_script(
        'msiw-frontend-js',
        'msiwFrontendData',
        array(
            'iconMap'         => $saved_icons,
            'storeApiCartUrl' => rest_url( 'wc/store/v1/cart' ),
        )
    );
}

// 4. Prepend the icon to the shipping method on the Thank You page and in WooCommerce emails.
// Both contexts render through the same order shipping display filter, so one hook covers both.
add_filter( 'woocommerce_order_shipping_to_display', 'msiw_display_icon_order', 10, 2 );
function msiw_display_icon_order( $shipping, $order ) {
    $saved_icons      = get_option( 'msiw_custom_icons', array() );
    $shipping_methods = $order->get_shipping_methods();

    if ( ! empty( $shipping_methods ) ) {
        $shipping_method = reset( $shipping_methods );
        $method_key      = $shipping_method->get_method_id() . ':' . $shipping_method->get_instance_id();

        if ( ! empty( $saved_icons[ $method_key ] ) ) {
            $image_url = $saved_icons[ $method_key ];

            // Inline styles are required here (not just the enqueued CSS class) because most
            // email clients strip <style> tags and external stylesheets.
            $image_html = sprintf(
                '<img src="%s" class="shipping-method-icon" alt="" style="vertical-align:middle;max-height:20px;width:auto;margin-right:6px;margin-left:2px;" />',
                esc_url( $image_url )
            );

            // $shipping sometimes looks like "$12.00 <small>via Fedex</small>" (older WC
            // versions), so prepending to the whole string would put the icon before the
            // price, not the courier name. Only insert here if the name actually appears in
            // this string. In newer WooCommerce versions the method name lives in the row's
            // label instead (handled by msiw_add_icon_to_order_totals_row() below), so if it's
            // not found here, leave $shipping untouched rather than guessing a fallback spot.
            $method_name = $shipping_method->get_name();

            if ( $method_name && false !== strpos( $shipping, $method_name ) ) {
                $shipping = str_replace( $method_name, $image_html . $method_name, $shipping );
            }
        }
    }

    return $shipping;
}

// 4b. Some email templates (e.g. the admin "New order" notification) render shipping as a
// "Shipping:" / price row in the order totals table, built from WC_Order::get_order_item_totals(),
// rather than through get_shipping_to_display() above. That table can put the method name in
// either the row's label or its value depending on the template, so check both and fall back
// to prepending the icon to the price if the name isn't found in either.
add_filter( 'woocommerce_get_order_item_totals', 'msiw_add_icon_to_order_totals_row', 10, 3 );
function msiw_add_icon_to_order_totals_row( $total_rows, $order, $tax_display ) {
    if ( empty( $total_rows['shipping'] ) ) {
        return $total_rows;
    }

    $shipping_methods = $order->get_shipping_methods();
    if ( empty( $shipping_methods ) ) {
        return $total_rows;
    }

    $shipping_method = reset( $shipping_methods );
    $method_key      = $shipping_method->get_method_id() . ':' . $shipping_method->get_instance_id();
    $saved_icons     = get_option( 'msiw_custom_icons', array() );

    if ( empty( $saved_icons[ $method_key ] ) ) {
        return $total_rows;
    }

    $image_url  = $saved_icons[ $method_key ];
    $image_html = sprintf(
        '<img src="%s" alt="" style="vertical-align:middle;max-height:16px;width:auto;margin-right:4px;" />',
        esc_url( $image_url )
    );

    $label = isset( $total_rows['shipping']['label'] ) ? $total_rows['shipping']['label'] : '';
    $value = isset( $total_rows['shipping']['value'] ) ? $total_rows['shipping']['value'] : '';

    // Avoid inserting twice if woocommerce_order_shipping_to_display already added it to $value.
    if ( false !== strpos( $value, $image_url ) || false !== strpos( $label, $image_url ) ) {
        return $total_rows;
    }

    $method_name = $shipping_method->get_name();

    if ( $method_name && $label && false !== strpos( $label, $method_name ) ) {
        $total_rows['shipping']['label'] = str_replace( $method_name, $image_html . $method_name, $label );
    } elseif ( $method_name && $value && false !== strpos( $value, $method_name ) ) {
        $total_rows['shipping']['value'] = str_replace( $method_name, $image_html . $method_name, $value );
    } elseif ( $value ) {
        // Fallback: name isn't in either column as text; still show the icon rather than
        // dropping it, placed before the price.
        $total_rows['shipping']['value'] = $image_html . $value;
    }

    return $total_rows;
}