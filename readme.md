# Milan's Shipping Icons For Woo

Contributors: milangru

Donate link: https://paypal.me/milangru79

Tags: woocommerce, shipping, icons, checkout, cart

Requires at least: 6.0

Tested up to: 7.0

Requires PHP: 7.4

Stable tag: 1.1.1

License: GPLv2 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce plugin that displays a custom courier/shipping logo next to each shipping method on the Cart page, Checkout page, order confirmation, and order emails.

## Features

- Assign a custom icon/logo to each shipping method, per shipping zone
- Two places to set an icon, always kept in sync:
    - A central table: **WooCommerce → Settings → Shipping Icons**
    - A "Shipping Icon" field inside each shipping method's own settings screen (Shipping Zones), registered dynamically for any method WooCommerce or a third-party plugin knows about
- Works with both the WooCommerce Cart & Checkout **blocks** and the classic (shortcode-based) templates
- Icons shown in:
    - Shipping Options list at Checkout
    - Order Summary panel (Checkout)
    - Cart Totals panel (Cart page)
    - Thank You / order confirmation page
    - WooCommerce order emails (customer and admin notifications)
- Uses the native WordPress media library for icon selection
- Optional review notice shown after 30 completed orders, fully translatable and dismissible

## Requirements

- WordPress 6.0+
- WooCommerce (active)
- PHP 7.4+

## Installation

1. Copy the plugin folder into `wp-content/plugins/`, or upload the zip via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin.
3. Go to **WooCommerce → Settings → Shipping Icons**, or open any method under **WooCommerce → Settings → Shipping → Shipping zones**.
4. Set an icon for each shipping method and save.

## How it works

### Admin settings

There are two UIs for the same underlying data:

1. **Central table** (`WooCommerce → Settings → Shipping Icons`) — loops through every shipping zone and lists every configured method in one place.
2. **Per-method field** — a "Shipping Icon" field is added directly inside each method's own settings modal, via `woocommerce_shipping_instance_form_fields_{method_id}`, registered dynamically for every method ID returned by the `woocommerce_shipping_methods` filter (so third-party shipping method plugins get the field automatically, with no hardcoded list).

Both write to the same storage: WooCommerce's own per-instance settings option (`woocommerce_{method_id}_{instance_id}_settings`). Saving from the central table also mirrors the value into that option; saving from the per-method field is picked up by a hook on `updated_option`/`added_option` that mirrors it back into the central table's option. Either UI stays current no matter which one was used to make the change.

Before any of this runs, the submitted `method_id:instance_id` key is checked against the shipping methods that actually exist across all zones, so a crafted form submission can't be used to create or overwrite an unrelated `woocommerce_*_settings` option.

A known WooCommerce quirk: the built-in **Free Shipping** method has its own admin script that hides every field positioned after the "Free shipping requires" dropdown by default (treating them all as conditional, like "Minimum order amount"). The plugin detects when this happens to the icon field specifically and forces it back to visible, including re-forcing it if WooCommerce's script re-hides it again after a "requires" change.

### Frontend / Cart & Checkout blocks

Since there's no PHP filter for the Cart & Checkout blocks, a small JS layer (enqueued via `wp_enqueue_script()`, data passed through `wp_localize_script()`) detects the shipping method the customer has selected and injects the matching icon:

- On Checkout, it reads the exact rate ID from the checked radio button (authoritative, unaffected by custom method titles).
- On the Cart page, which has no radio selector, it queries the WooCommerce Store API (`/wp-json/wc/store/v1/cart`) to find which rate is currently selected.
- A text-matching fallback (matching the method ID against rendered text) is used only when neither of the above is available.

### Classic templates, Thank You page, and emails

- Classic Cart/Checkout: `woocommerce_cart_shipping_method_full_label` filter, using the exact method instance object passed by the hook.
- Thank You page / emails: `woocommerce_order_shipping_to_display` and `woocommerce_get_order_item_totals` filters, since different WooCommerce versions/templates put the shipping method's name in different places (the row's value vs. its label).

### Review notice

`includes/class-review-notice.php` shows a dismissible admin notice once 30 orders have been completed while the plugin was active (order completion is counted internally, once per order). The notice offers "Sure, happy to leave a review" (opens WordPress.org in a new tab and records the dismissal in the background via `fetch()`, without reloading the current tab), "Remind me later" (snoozes for 7 days), and "No, thanks". All strings use the plugin's text domain and are translation-ready.

## Known limitations

- WooCommerce's newer **Local Pickup** tab (`Settings → Shipping → Pickup Locations`) stores pickup locations separately from shipping zone method instances, using a different data model entirely. This is **not currently supported** — only the classic, zone-based Local Pickup method (listed alongside Flat Rate inside a Shipping Zone) works.
- Depending on the WooCommerce version/template, the icon placement next to the shipping method name in emails/Thank You page may not always land in the exact same spot, since the method name isn't always rendered in a predictable, filterable location.

## File structure

```
milans-shipping-icons-for-woo/
├── milans-shipping-icons-woo.php            # Main plugin file, admin settings screen + per-method field
├── milans-shipping-icons-woo-frontend.php    # Frontend/blocks rendering logic
├── assets/
│   ├── css/
│   │   ├── shipping-icons-admin.css
│   │   └── shipping-icons-frontend.css
│   └── js/
│       ├── shipping-icons-admin.js
│       └── shipping-icons-frontend.js
├── includes/
│   ├── class-review-notice.php               # "Leave a review" admin notice (shown after 30 completed orders)
│   ├── css/
│   │   └── review-notice.css
│   └── js/
│       └── review-notice.js
├── readme.txt                                # WordPress.org readme
└── readme.md                                 # This file
```

## Changelog

### 1.1.1

- Security: the admin settings-save handler now validates every "method:instance" key from the submitted form against the shipping methods that actually exist, before it's used to build or write to a `woocommerce_*_settings` option.
- Security: nonce and action values read from `$_GET`/`$_POST` are now unslashed before sanitizing, per WordPress coding standards.
- All inline `<script>` blocks were replaced with properly enqueued, versioned JS files, with dynamic data passed via `wp_localize_script()` instead of being printed directly into the page.
- Fixed the plugin's text domain, which didn't match the plugin slug, so translations can load correctly.
- Added an optional, translatable review notice shown after 30 completed orders (`includes/class-review-notice.php`).

### 1.1.0

- Added a "Shipping Icon" field directly inside each shipping method's own settings screen (Shipping Zones), in addition to the central table, kept in two-way sync with it.
- Field is registered dynamically for every shipping method WooCommerce knows about, including third-party method classes, with no hardcoded list.
- Fixed a WooCommerce Free Shipping quirk where its own admin script was hiding the icon field along with other conditional fields.
- Improved icon placement in order emails and on the Thank You page for WooCommerce versions/templates where the courier name appears in the totals row's label rather than next to the price.
- Cleaned up the plugin header.

### 1.0.0

- Initial release.
- Central settings table + per-method settings field, two-way synced.
- Cart/Checkout blocks support via Store API + radio ID matching.
- Classic template, Thank You page, and email icon display.

## License

GPLv2 or later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
