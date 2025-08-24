# Woo Checkout Field Editor (Bangladesh Ready)

Modern, clean & flexible WooCommerce checkout field editor. Toggle, require, reorder, and size fields — with first-class support for **Bangladesh District & Sub-district** dropdowns.

> **Author:** [absoftlab](https://absoftlab.com)  
> **Folder/Slug:** abb-wcfe-bd  
> **License:** GPL-2.0-or-later

---

## ✨ Features

- **Separate tabs** for **Billing**, **Shipping**, **Order Notes**, **Bangladesh Fields**, and **Tools**.
- **Enable/Disable** any core checkout field.
- **Required/Optional** toggle for every field.
- **Drag-and-drop ordering** (priorities update automatically).
- **State field control**: manage `billing_state` and `shipping_state` like other fields.
- **Width control** per field: **Half** (two columns) or **Full** (full width).
- **Bangladesh extras**:
  - **District** dropdown (all 64 districts)
  - **Sub-district (Upazila)** dropdown (linked to district)
  - **Show only when Country = Bangladesh** (optional)
  - **Validate against dataset** (optional)
  - **Allow custom Sub-district** (optional)
- **Self-healing fields**: if another theme/plugin removes a core field (e.g. `billing_phone`), this plugin restores it when enabled.
- **Import/Export** full settings as JSON.

---

## 📦 Installation

1. Place in `wp-content/plugins/abb-wcfe-bd`
2. Ensure the main plugin folder name is **abb-wcfe-bd**.
3. Activate **Woo Checkout Field Editor (Bangladesh Ready)** from **Plugins** in wp-admin.
4. Go to **WooCommerce → Field Editor (BD)**.

**Requirements:**

- WordPress 5.8+
- WooCommerce 5.0+ (works with modern 7/8/9+ too)
- PHP 7.4+ (PHP 8.x recommended)

---

## 🧭 Admin UI Overview

- **Billing Fields**: Toggle/require, set width (Half/Full), drag to reorder.
- **Shipping Fields**: Same controls as Billing.
- **Order Notes**: Manage the `order_comments` field.
- **Bangladesh Fields**:
  - Enable/disable the BD suite
  - Choose placement (**Billing** or **Shipping**)
  - Country condition (**Always show** / **BD only**)
  - District/Sub-district: enable, required, custom upazila, validate from list
- **Tools**:
  - **Export** current settings (read-only JSON)
  - **Import**: paste JSON and save

---

## 🇧🇩 Bangladesh Dataset

- File: `assets/data/bd_geo.json`
- Structure: a JSON map of `"District": ["Upazila 1", "Upazila 2", ...]`.

You can update this file to adjust spellings or add missing upazilas. Validation and dropdowns will automatically reflect your changes.

---

## 🧩 How it Works (Technical)

- Hooks into `woocommerce_checkout_fields` to apply your toggles, labels, required flags, priorities, and widths.
- **Widths** use native Woo CSS classes:
  - **Full** → `form-row-wide`
  - **Half** → alternates `form-row-first` / `form-row-last` by priority order.
- When **enabled** but **missing** (e.g., removed by theme), a field is **recreated** from a safe blueprint.
- BD fields are injected into the chosen section (billing or shipping) with optional validation against the dataset.

**Primary option key:** `abb_wcfe_settings_v1` (array)

**Example (excerpt):**

```json
{
  "core_fields": {
    "billing_phone": {
      "enabled": true,
      "required": true,
      "priority": 40,
      "label": "Phone",
      "width": "full"
    }
  },
  "bd_fields": {
    "enabled": true,
    "placement": "billing",
    "country_condition": "any",
    "district": { "enabled": true, "required": true, "label": "District" },
    "subdistrict": { "enabled": true, "required": true, "label": "Sub-district" },
    "allow_custom_subdistrict": false,
    "validate_from_list": true
  }
}
```

---

## 🛠️ Programmatic Usage (Developers)

Set or override settings in a small plugin or mu-plugin:

```php
add_action('init', function () {
    $opt = get_option('abb_wcfe_settings_v1', []);
    // Force full width for phone:
    $opt['core_fields']['billing_phone']['width'] = 'full';
    // Move email up:
    $opt['core_fields']['billing_email']['priority'] = 45;
    update_option('abb_wcfe_settings_v1', $opt);
});
```

You can still use core WooCommerce filters to fine-tune specific fields:

```php
add_filter('woocommerce_checkout_fields', function($fields){
    // Example: add a custom class to billing_company:
    if (isset($fields['billing']['billing_company'])) {
        $fields['billing']['billing_company']['class'][] = 'my-company-class';
    }
    return $fields;
}, 30);
```

---

## 🧪 Troubleshooting

**Phone field not showing**

- This plugin restores `billing_phone` if enabled in the admin.
- If still hidden, a checkout customizer might be overriding later. Try increasing this plugin’s filter priority or temporarily disabling the conflicting plugin.

**BD dropdowns not appearing**

- Check **Bangladesh Fields → Enable BD fields**.
- If **BD only** is selected, ensure the chosen checkout country is **Bangladesh (BD)**.
- Confirm `assets/data/bd_geo.json` exists and is valid JSON.

**Layout looks off**

- Some themes override Woo classes. Width logic uses `form-row-first`, `form-row-last`, `form-row-wide`.
- Verify your theme supports these or add CSS to align.

---

## 📸 Screenshots (placeholders)

1. **Admin dashboard** – tabs & cards
2. **Billing Fields** – drag-and-drop + width controls
3. **Bangladesh Fields** – district/upazila settings
4. **Checkout** – BD district & sub-district dropdowns

> Add images under `assets/` and reference them here as needed.

---

## 🗺️ Roadmap

- Preset layouts (Minimal / Standard / Full)
- Conditional logic (show/hide based on field values)
- Per-role field visibility
- Import/export to file upload/download
- Translations (i18n) & POT file

---

## 🤝 Contributing

PRs and issues are welcome!

1. Fork the repo
2. Create a feature branch
3. Commit with clear messages
4. Open a PR explaining the change & testing steps

---

## 🔒 Security

If you discover a vulnerability, please open a private issue or contact the author directly via [absoftlab.com](https://absoftlab.com).

---

## 📝 Changelog

- **1.3.0**
  - Field **Width** control (Half/Full)
  - Layout engine for 2-column rows by priority
- **1.2.0**
  - Split **Billing** and **Shipping** into separate tabs
  - Added **State** field controls
  - Deep-merge defaults so new fields appear on old installs
- **1.1.0**
  - Drag-and-drop ordering for core fields
- **1.0.0**
  - Initial release: enable/disable/require fields, BD district & sub-district, import/export

---

## 📄 License

This project is licensed under the **GPL-2.0-