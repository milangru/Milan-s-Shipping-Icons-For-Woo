# Milan's Shipping Icons For Woo

Contributors: milangru

Donate link: https://paypal.me/milangru79

Tags: woocommerce, shipping, icons, checkout, cart

Requires at least: 6.0

Tested up to: 7.0

Requires PHP: 7.4

Stable tag: 1.1.0

License: GPLv2 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce plugin that displays a custom courier/shipping logo next to each shipping method on the Cart page, Checkout page, order confirmation, and order emails.

## Features

- Assign a custom icon/logo to each shipping method, per shipping zone
- Works with both the WooCommerce Cart & Checkout **blocks** and the classic (shortcode-based) templates
- Icons shown in:
    - Shipping Options list at Checkout
    - Order Summary panel (Checkout)
    - Cart Totals panel (Cart page)
    - Thank You / order confirmation page
    - WooCommerce order emails
- Simple settings screen: **WooCommerce → Settings → Shipping Icons**
- Uses the native WordPress media library for icon selection

## Requirements

- WordPress 6.0+
- WooCommerce (active)
- PHP 7.4+

## Installation

1. Copy the plugin folder into `wp-content/plugins/`, or upload the zip via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin.
3. Go to **WooCommerce → Settings → Shipping Icons**.
4. Set an icon for each shipping method and save.

## How it works

- On the classic Cart/Checkout templates, icons are added via the `woocommerce_cart_shipping_method_full_label` filter.
- On the Cart & Checkout **blocks**, since there's no equivalent PHP filter, a small JS layer detects the shipping method the customer has selected and injects the matching icon:
    - On Checkout, it reads the exact rate ID from the checked radio button.
    - On the Cart page (which has no radio selector), it queries the WooCommerce Store API (`/wp-json/wc/store/v1/cart`) to find which rate is currently selected.
- On the Thank You page and in order emails, icons are added via the `woocommerce_order_shipping_to_display` filter.

## File structure

```
milans-shipping-icons-woo/
├── milans-shipping-icons-woo.php            # Main plugin file, admin settings screen
├── milans-shipping-icons-woo-frontend.php    # Frontend/blocks rendering logic
├── assets/
│   └── css/
│       ├── shipping-icons-admin.css
│       └── shipping-icons-frontend.css
├── readme.txt                                # WordPress.org readme
└── readme.md                                 # This file
```

## Changelog

### 1.0.0

- Initial release.

## License

GPLv2 or later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
