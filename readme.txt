=== Milan's Shipping Icons For Woo ===
Contributors: milangru
Donate link: https://paypal.me/milangru79
Tags: woocommerce, shipping, icons, checkout, cart
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce

Displays custom courier/shipping logos next to shipping methods on Cart, Checkout pages and emails using native WordPress safety standards.

== Description ==

Milan's Shipping Icons For Woo lets you assign a custom logo or icon to each of your WooCommerce shipping methods (Flat Rate, Free Shipping, Local Pickup, or any custom courier method), so customers see a familiar courier logo instead of plain text.

Icons are shown in:

* The Shipping Options list on the Checkout page (Cart & Checkout blocks)
* The Order Summary panel on Checkout
* The Cart Totals panel on the Cart page
* The classic (shortcode-based) Cart and Checkout templates
* The Thank You / order confirmation page
* WooCommerce order emails (customer and admin notifications)

= Features =

* Two convenient places to set an icon, always kept in sync:
    * A central table under WooCommerce → Settings → Shipping Icons, listing every method across every shipping zone
    * A "Shipping Icon" field directly inside each shipping method's own settings screen (Shipping Zones), for any method WooCommerce or a third-party plugin registers
* Uses the native WordPress media library to choose icons
* Works with both the block-based (Cart & Checkout blocks) and classic WooCommerce templates
* Reads the WooCommerce Store API to correctly detect the selected shipping method on the Cart page, where no method selector is otherwise present
* No page builder or theme dependency

= How it works =

Each shipping method (per shipping zone) gets its own icon. Once saved, the plugin automatically matches the selected shipping method to its icon and displays it wherever that method's name appears to the customer, on both the classic and block-based templates.

= Known limitations =

* WooCommerce's newer "Local Pickup" tab (Settings → Shipping → Pickup Locations) stores pickup locations separately from shipping zone method instances and is not currently supported. The classic, zone-based Local Pickup method (the one listed alongside Flat Rate inside a Shipping Zone) is fully supported.
* In some WooCommerce versions/templates, the shipping method name shown in order emails and on the Thank You page is not distinguishable from surrounding text with enough reliability for the icon to always be placed immediately next to it; in that case the icon may appear elsewhere in the same row rather than being omitted.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/milans-shipping-icons-for-woo` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Make sure WooCommerce is installed and active.
4. Go to WooCommerce → Settings → Shipping Icons, **or** open any shipping method under WooCommerce → Settings → Shipping → Shipping zones.
5. Choose an icon for each shipping method and save.

== Frequently Asked Questions ==

= Does this work with the new Cart and Checkout blocks? =

Yes. The plugin supports both the block-based Cart/Checkout and the classic shortcode-based templates.

= Does this require WooCommerce? =

Yes, WooCommerce must be installed and active.

= Will icons show up in order emails? =

Yes, the shipping method icon is shown in WooCommerce order emails as well as on the Thank You page.

= If I set an icon in the central table, does it also show up in the method's own settings screen? =

Yes. Both places read and write the same underlying setting, so updating either one updates the other immediately.

= Does this support WooCommerce's new "Local Pickup" (Pickup Locations) tab? =

Not currently. That feature stores pickup locations in a separate system from shipping zone method instances. The classic, zone-based Local Pickup method is fully supported.

== Screenshots ==

1. Shipping Icons settings screen under WooCommerce → Settings
2. "Shipping Icon" field inside a shipping method's own settings screen
3. Icons displayed next to shipping methods at Checkout
4. Icon displayed in the Cart Totals panel

== Changelog ==

= 1.1.1 =
* Security: the admin settings-save handler now validates every "method:instance" key from the submitted form against the shipping methods that actually exist, before it's used to build or write to a `woocommerce_*_settings` option.
* Security: nonce and action values read from `$_GET`/`$_POST` are now unslashed before sanitizing, per WordPress coding standards.
* All inline `<script>` blocks were replaced with properly enqueued, versioned JS files (`wp_enqueue_script()` / `wp_register_script()`), with dynamic data passed via `wp_localize_script()` instead of being printed directly into the page.
* Fixed the plugin's text domain, which didn't match the plugin slug, so translations can load correctly.
* Added an optional review notice (`WooCommerce → Settings` and other admin screens) shown once the plugin has helped process 30 completed orders, with "Remind me later" and "No, thanks" options. Fully translatable and dismissible; can be safely ignored.

= 1.1.0 =
* Added a "Shipping Icon" field directly inside each shipping method's own settings screen (Shipping Zones), in addition to the central table, kept in two-way sync with it.
* Field is registered dynamically for every shipping method WooCommerce knows about, including third-party method classes, with no hardcoded list.
* Fixed a WooCommerce Free Shipping quirk where its own admin script was hiding the icon field along with other conditional fields.
* Improved icon placement in order emails and on the Thank You page for WooCommerce versions/templates where the courier name appears in the totals row's label rather than next to the price.
* Cleaned up the plugin header.

= 1.0.0 =
* Initial release.
* Central "Shipping Icons" settings table listing all shipping zones and methods.
* Icon display on the classic Cart/Checkout templates, Cart & Checkout blocks, Thank You page, and order emails.
* WooCommerce Store API lookup to correctly detect the selected method on the Cart page.

== Upgrade Notice ==

= 1.1.1 =
Security hardening for the settings-save handler, inline scripts replaced with properly enqueued assets, and a corrected text domain. Recommended update.

= 1.1.0 =
Adds a per-method icon field (synced with the central table) and fixes several display issues. Recommended update.

= 1.0.0 =
Initial release.
