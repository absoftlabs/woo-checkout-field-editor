=== Checkout Field Editor (Bangladesh Ready) for WooCommerce ===
Contributors: absoftlab
Donate link: https://absoftlab.com
Tags: woocommerce, checkout, field editor, bangladesh, upazila
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Flexible WooCommerce checkout field editor: enable/require, reorder, set widths, plus Bangladesh District & Sub-district dropdowns.

== Description ==

**Checkout Field Editor for WooCommerce (Bangladesh Ready)** lets you fully control WooCommerce checkout fields:

- Separate tabs for **Billing**, **Shipping**, **Order Notes**, **Bangladesh Fields**, and **Tools**
- Enable/disable any core field
- Mark fields **required/optional**
- **Drag-and-drop** ordering (priorities auto-update)
- **State** field control (`billing_state`, `shipping_state`)
- **Width** per field: **Half** (two columns) or **Full** (full width)
- **Bangladesh extras**:
  - District (all 64 districts) and Sub-district (Upazila) dropdowns
  - Show only when Country = Bangladesh (optional)
  - Validate against dataset (optional)
  - Allow custom Sub-district (optional)
- **Self-healing fields**: if another theme/plugin removes a core field (e.g. `billing_phone`), this plugin restores it when enabled
- Import/Export full settings as JSON

Author: [absoftlab](https://absoftlab.com)

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WP admin.
2. Activate the plugin.
3. Go to **WooCommerce → Field Editor (BD)** to configure.

== Frequently Asked Questions ==

= Phone field isn’t visible on checkout =
If enabled in the plugin, it’s restored even when removed by other code. If still hidden, a checkout customizer may override later. Temporarily disable other checkout plugins to confirm.

= Can I show BD fields only when Country is BD? =
Yes. In **Bangladesh Fields**, choose **BD only**.

= Can I make a field full width? =
Yes. Each field has **Width** (Half/Full). Half alternates left/right to form 2-column rows.

= Where is the dataset? =
`assets/data/bd_geo.json` — a JSON map of `"District": ["Upazila 1", "Upazila 2", ...]`.

== Screenshots ==

1. Admin dashboard – tabs & cards
2. Billing Fields – drag-and-drop + width controls
3. Bangladesh Fields – district/upazila settings
4. Checkout – BD district & sub-district dropdowns

== Changelog ==

= 1.3.0 =
* Field **Width** control (Half/Full)
* Layout engine for 2-column rows by priority

= 1.2.0 =
* Separate **Billing** and **Shipping** tabs
* Added **State** field controls
* Deep-merge defaults so new fields appear on old installs

= 1.1.0 =
* Drag-and-drop ordering for core fields

= 1.0.0 =
* Initial release: enable/disable/require fields, BD district & sub-district, import/export

== Upgrade Notice ==

= 1.3.0 =
Adds width control + improved layout. Review your field widths after update.
