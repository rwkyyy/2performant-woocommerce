# 2Performant / Business League for WooCommerce

Connect your WooCommerce store to the **2Performant** and **Business League** affiliate network. This plugin
handles advertiser-side conversion tracking, generates a product feed for the network, and cleans up affiliate
traffic so your store reports accurate sales and stays out of the search index where it should.

It supports both tracking methods 2Performant offers — the legacy sale-check iframe and the newer first-party
"Big Bear" attribution — and works with WooCommerce High-Performance Order Storage (HPOS).

**Website and documentation (Romanian):** https://rwkyyy.github.io/2performant-woocommerce/

## Requirements

- WordPress with WooCommerce 4.2 or newer (tested up to WooCommerce 11.1)
- A 2Performant / Business League advertiser account
- Your campaign credentials from the [iframe tracking](https://businessleague.2performant.com/advertiser/attribution/iframe_tracking#installCode)
  and [Big Bear attribution](https://businessleague.2performant.com/advertiser/attribution/big_bear_attribution#section_0) pages

## Features

### Conversion tracking

- **Big Bear first-party tracking.** Loads the `attr-2p.com` click and sale scripts and builds the order payload
  (`window.tpOrder`) on the thank-you page, so attribution survives ad blockers and third-party cookie limits.
- **Iframe sale-check.** Prints the classic 2Performant sale-check pixel on order completion as a fallback for the
  first-party method.
- **Net sale values.** Sale amounts exclude tax and shipping, so the commissionable value you report matches what
  2Performant expects.

### VAT override

For stores that run **without** WooCommerce taxes configured, prices still include VAT that WooCommerce can't strip.
Set a flat VAT rate in the settings and it's removed from the values reported to 2Performant (both iframe and Big
Bear). When WooCommerce taxes are enabled, the override is ignored and the native tax data is used instead.

### Product feed

- **CSV feed** served at `/twoo-feed/`, generated on demand.
- **Rich data** per product: title, description, price, sale/old price, full category path, images (featured +
  gallery), stock status, brand, and a GTIN derived from the SKU when it fits the network's 9–13 character rule.
- **Optional filtering.** Restrict the feed to selected categories, brands, and/or tags. A product is included if it
  matches any selected term; leave filtering off (or all lists empty) to include every published product.

### Affiliate traffic cleanup

- **Hide elements** (for example a phone number or a discount bar) for visitors arriving through an affiliate link,
  using the network's `postMessage` cookie check.
- **`noindex`** on landing pages that carry 2Performant tracking parameters, so affiliate URLs don't create duplicate
  content.
- **`robots.txt` rules** that disallow the 2Performant parameters from being crawled.

## Installation

1. Copy the plugin folder to `wp-content/plugins/`.
2. Activate it from **Plugins** in the WordPress admin.
3. Open **WooCommerce → Settings → 2Performant**.

## Configuration

All settings live under **WooCommerce → Settings → 2Performant**.

| Setting | What it's for |
| --- | --- |
| Campaign Unique | The `campaign_unique` value from your 2Performant iframe tracking code. |
| Confirm | The `confirm` value from the same tracking code. |
| Big Bear Attribution | The ID from `attr-2p.com/THIS_ID/clc/1.js` in your first-party code. |
| CSS Classes to Hide | Comma-separated selectors to hide from affiliate traffic, e.g. `.promo-banner, #top-bar`. |
| Feed filtering | Enable it, then pick the categories, brands, and/or tags to include in the feed. |
| VAT rate (%) | Used only when WooCommerce taxes are off; strips VAT from reported sale values. `0` disables it. |

The feed URL is shown on the settings screen and is always `/twoo-feed/` on your domain.

## Extending

The settings array is filterable, so you can add or adjust fields from your own code:

```php
add_filter( 'twoo_settings', function ( $settings ) {
    // modify $settings
    return $settings;
} );
```

## Translations

The admin interface is fully translatable (text domain `twoo-performant-uprise`). A **Romanian** (`ro_RO`)
translation ships in `languages/`, along with the `.pot` template.

To add or update a language:

```bash
# regenerate the template after changing any UI string
wp i18n make-pot . languages/twoo-performant-uprise.pot --domain=twoo-performant-uprise --exclude=languages

# start a new locale from the template, then translate and compile
msginit --input=languages/twoo-performant-uprise.pot --locale=xx_XX --output=languages/twoo-performant-uprise-xx_XX.po
msgfmt languages/twoo-performant-uprise-xx_XX.po -o languages/twoo-performant-uprise-xx_XX.mo
```

The feed CSV column headers are a fixed data contract with 2Performant and are intentionally left untranslated.

## License

GPLv3 or later. See [LICENSE](LICENSE).
