# 2Performant for WooCommerce (Performant Edition)

A lightweight, high-performance integration for the **2Performant** affiliate network. This plugin provides a
comprehensive suite of tools for advertisers to track conversions, generate product feeds, and optimize the shopping
experience for affiliate-driven traffic.

---

## 🚀 Features

### 1. Advanced Tracking & Attribution

* **First-Party Tracking (Big Bear)**: Implements modern JS-based attribution to ensure high tracking accuracy and
  bypass common ad-blocker restrictions.
* **Standard Iframe Tracking**: Includes a fallback sale-check iframe on the order completion page for traditional
  conversion validation.
* **Accurate Totals**: Automatically calculates sale values by excluding taxes and shipping costs to ensure precise
  commission reporting.

### 2. Automated Product Feed

* **Dynamic CSV Generation**: Automatically generates a 2Performant-compatible product feed at the `/twoo-feed/`
  endpoint.
* **Rich Product Data**: Includes product titles, prices (regular vs. sale), categories, subcategories, brand
  attributes, and gallery images.
* **Output & Performance**: Feeds are generated on-the-fly with clean, tag-free descriptions.

### 3. Traffic Optimization

* **Smart Content Hiding**: Hide specific UI elements (like discount bars or newsletters) for users arriving via
  affiliate links using `postMessage` validation.
* **Automated SEO Protection**:
    * Automatically adds `noindex` meta tags to landing pages containing 2Performant parameters to prevent duplicate
      content issues.
    * Injects specific `Disallow` rules into your `robots.txt` to keep affiliate-specific parameters out of search
      engine indexes.

### 4. Technical Excellence

* **HPOS Compatible**: Fully declared compatibility with WooCommerce High-Performance Order Storage.
* **Performance First**: Scripts are deferred or enqueued in the footer to ensure zero impact on your Core Web Vitals.
* **Developer Friendly**: Includes filters (e.g., `twoo_settings`) for easy extension.

---

## 🛠 Installation

1. Upload the plugin folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WooCommerce > Settings > 2Performant**.

## ⚙️ Configuration

To start tracking, enter the following details in the settings tab:

* **Campaign Unique**: Your unique campaign identifier from the 2Performant dashboard.
* **Confirm Code**: Your transaction confirmation ID.
* **Big Bear ID**: The ID found in your 1st party tracking code (usually part of the `attr-2p.com` URL).
* **CSS Classes**: (Optional) Comma-separated classes (e.g., `.promo-banner, #top-bar`) to hide from affiliate traffic.

---

## 📄 License

This project is licensed under the **GPLv2 or later**.