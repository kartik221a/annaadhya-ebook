# Annaadhya eBooks — WordPress ebook store (dev preview)

A complete, portable WordPress + WooCommerce ebook store. The **entire site lives in
this repository as plain files** — WordPress core, plugins, theme, media, and the
SQLite database — so `git pull` always gives you the latest state, ready to run.

## Quick start (Docker — recommended)

```bash
git pull
docker compose up
```

Then open **http://localhost:8080** in your browser. That's it — no database setup,
no MySQL, no config. The stack is WordPress running on SQLite via the official
[SQLite Database Integration](https://wordpress.org/plugins/sqlite-database-integration/)
plugin.

Stop with `Ctrl+C`. To rebuild after pulling changes: `git pull && docker compose up`.

## Quick start (no Docker — plain PHP)

Any PHP 8.x with the `pdo_sqlite` extension works:

```bash
git pull
php -S 127.0.0.1:8080 -t site router.php
```

Open **http://127.0.0.1:8080**.

## Admin login (wp-admin)

| | |
|---|---|
| URL | http://localhost:8080/wp-admin |
| Username | `kartik` |
| Password | `Annaadhya!2026` |

**These credentials are for the local demo only.** Change them before anything
touching production.

## What's inside

| Path | Purpose |
|---|---|
| `site/` | The full WordPress installation (core + plugins + theme + SQLite DB) |
| `docker-compose.yml` + `docker/apache.conf` | One-command preview stack (PHP 8.3 + Apache) |
| `router.php` | Runner for the no-Docker PHP built-in server option |
| `tools/build_content.sh` | wp-cli script that generated pages, menu, products |
| `tools/make_samples.py` | Python script that generated the sample covers + PDFs |
| `tools/set_elementor_home.php` | wp-cli script that builds the Home page as an Elementor page |
| `site/wp-content/plugins/annaadhya-admin/` | Custom admin panel plugin (see below) |

## Custom admin panel — `/admin`

No need to touch wp-admin for day-to-day work. Open **http://localhost:8080/admin**
and sign in with your WordPress account (locally: `kartik` / `Annaadhya!2026`).

- **Books** — list / search / create / edit / delete. Upload a cover image and the
  PDF directly (PDF is stored in the protected `woocommerce_uploads` folder and
  delivered automatically after checkout). Price + sale price, categories,
  description, Draft vs Published.
- **Categories** — create / edit / delete product categories.
- **Orders** — order list and detail view (items, totals, customer, status).
- **Dashboard** — book/category/order counts, paid revenue, recent orders.

The panel is a self-contained WordPress plugin (`annaadhya-admin`): custom routes
via rewrite rules, WP-session auth (users with `manage_woocommerce` only), nonces
on every action, no external dependencies. Deactivate it any time to get the
default WordPress behaviour back.

## Store configuration (already done)

- **WooCommerce** active, currency **INR**, pretty permalinks
- Store pages: Shop, Cart, Checkout, My account, Refund & Returns, Privacy Policy
- Site pages: **Home** (static front page with featured products), About, Contact
- 4 product categories: Technology, Business, Self-Help, Fiction
- 6 **sample** ebook products — virtual + downloadable, each with a generated cover
  (`site/wp-content/uploads/...`) and a sample PDF delivered as the secure download
  file (`site/wp-content/uploads/woocommerce_uploads/`)
- Theme: **Astra** (free, WooCommerce-friendly)
- Page builder: **Elementor Free** (nothing paid) — the Home page is built as a real
  Elementor page: each section is an editable "HTML" widget with self-contained
  HTML/CSS, plus native WooCommerce `[products]` shortcodes so the featured grid
  always stays in sync with the store. Rebuild it anytime with:
  `wp eval-file tools/set_elementor_home.php`

The 6 products and all copy are **demo placeholders** so you can test the complete
flow: browse → add to cart → checkout → instant PDF download. Replace products,
covers, PDFs, and page text with real content before launch.

## Rebuilding / regenerating content

The two scripts in `tools/` recreate everything from scratch (they assume a fresh
WordPress install; paths are relative to the repo root). They are reference
implementations — incremental edits are normally done via wp-admin or wp-cli.

## Production deploy path (later)

This dev copy runs on SQLite; the Hostinger production site runs MySQL. When it's
time to launch there are two clean routes:

1. **API provisioning (preferred):** Hostinger support whitelists our builder IP
   (no DNS changes at all), then content is recreated on the live site via
   wp-cli / REST API — same scripts, different target.
2. **Manual migration:** export content from this site (wp-admin → Tools →
   Export) and import into the production site, or use a migration plugin
   (All-in-One WP Migration / Duplicator) from a machine that can reach the site.
3. In both cases: update the site URL (`wp search-replace`), change the admin
   password, switch the store to real payment gateway keys.

## Repository notes

- The SQLite database (`site/wp-content/database/`) is tracked in git on purpose —
  it IS the site. Commit after making changes so the next `git pull` sees them.
- Nothing here is committed to the production site; this repo is a self-contained
  preview environment.
