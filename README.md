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
| `tools/test_webadmin.sh` | End-to-end test suite for the custom admin panel (19 checks) |
| `site/wp-content/mu-plugins/aa-web-admin.php` | Custom admin panel — must-use plugin (see below) |

## Storefront — the 10-page layout

| # | Page | Route | Notes |
|---|------|-------|-------|
| 1 | Homepage | `/` | hero, featured grid, categories, newsletter |
| 2 | Shop | `/shop/` | catalog with search, category chips, client-side sort, pagination |
| 3 | Ebook details | `/product/<slug>/` | cover, price + save %, read-sample, reviews, related books |
| 4 | Cart | `/cart/` | dark-themed Woo cart + totals |
| 5 | Checkout | `/checkout/` | **address-free** (name + email only — it's all digital), UPI/Bank Transfer gateway, order review |
| 6 | My Library | `/library/` | purchased PDFs with **download meters (N of 5 used)**, exhausted → “Buy again”, purchase history |
| 7 | My Account | `/my-account/` | **Google + email/password sign in / register**, profile dashboard, orders, downloads, **Wishlist** tab |
| 8 | About | `/about/` | brand story, mission & vision, publishing philosophy, authors |
| 9 | Contact & Support | `/contact/` | form (saved to admin "Messages"), support desk, FAQ accordion |
| 10 | Legal | `/privacy-policy/` `/terms/` `/refund-policy/` `/digital-delivery-policy/` `/copyright-policy/` | plain-language policies with TL;DR cards |

All pages render through the custom canvas shell (`aa_canvas_for_storefront()` in
`aa-store-tweaks.php`): site search (`?s=`) reuses the Shop canvas, single products
template through one hidden page (`[aa_product_single]`), and wishlist hearts live
on every book card (stored per-user, shown in the My Account → Wishlist tab).
Rebuild every page with:
`wp eval 'require "tools/build_pages_v2.php";'` (v3 subset for auth/library/checkout/delivery-policy:
`tools/build_pages_v3.php`). The builders impersonate an admin automatically —
Elementor sanitizes `_elementor_data` for users without `unfiltered_html` (e.g. plain
`wp eval` as user 0) and would strip the `<style>`/`<script>` tags from every widget.

## Digital delivery & the 5-download rule (live)

- Every product is **virtual + downloadable** with `download_limit = 5` and no expiry:
  5 full PDF downloads per purchase, **the book stays in the account forever**.
- Guest checkout is **off** — purchases always belong to an account, so downloads are
  always preserved in My Library.
- My Library shows a **“N of 5 downloads used”** meter per book; at 0 the card flips to
  **“Downloads used up” + “Buy again”** (one click re-purchases and grants a fresh 5).
- Hitting a used-up download URL directly shows a friendly limit message with a link
  back to the library (`gettext` filter in `aa-store-tweaks.php`).
- Checkout asks for **name + email only** (no address fields, no order notes).
- The order-received screen has a gold **“Open My Library to download”** CTA.

## Sign-in options (Google + email/password)

- `/my-account/` renders a Stitch-designed auth card: **Continue with Google** pill,
  “or continue with email” divider, sign-in + register tabs (AJAX, nonce-guarded).
- Google sign-in is powered by the **Nextend Social Login** plugin (bundled in
  `wp-content/plugins/nextend-facebook-connect`). It goes live the moment the Google
  provider has real OAuth credentials: create an OAuth 2.0 Client in Google Cloud
  Console (type *Web application*), then in **wp-admin → Nextend Social Login → Google**
  paste the Client ID + Secret (NSL shows the exact Authorized redirect URI to register)
  and enable the provider. Until then the storefront button shows a friendly
  “use email meanwhile” toast instead of a broken flow.
- Email/password registration is open (min 8 chars); accounts get the Woo `customer`
  role.

## Custom admin panel — `/web-admin`

No need to touch wp-admin for day-to-day work. Open **http://localhost:8080/web-admin**
and sign in with your WordPress account (locally: `kartik` / `Annaadhya!2026`).
The old `/admin` address also works — it redirects to `/web-admin` instead of
WordPress' own wp-admin.

- **Books** — list / search / create / edit / delete. Upload a cover image and the
  PDF directly (PDF is stored in the protected `woocommerce_uploads` folder and
  delivered automatically after checkout). Price + sale price, categories,
  description, Draft vs Published.
- **Categories** — create / edit / delete product categories.
- **Orders** — order list and detail view (items, totals, customer, status).
- **Downloads** — every download grant across customers: who owns which book, how
  many of the 5 downloads were used/left, grant date, and **Reset to 5** / **+5**
  actions for support cases. A second tab summarizes each customer's library.
- **Messages** — contact-form submissions from the Contact & Support page:
  list, read, reply by email, delete. Unread count on the dashboard.
- **Subscribers** — newsletter list (from the footer subscribe form), remove
  entries, one-click CSV export for your mailing tool.
- **Dashboard** — book/order counts, paid revenue, unread messages, subscriber
  count, recent orders + messages, and quick links to every storefront page.

The panel ships as a **must-use plugin** (`site/wp-content/mu-plugins/`) and
routes through `template_redirect` on the raw request URI — no rewrite rules,
no `flush_rewrite_rules()`, no "is the plugin active" database row. That means
it works identically after every `git pull`, even if your local database has
drifted from the repo (the reason `/admin` used to bounce to wp-admin).
Auth is WordPress-native: any user with `manage_woocommerce` (admins qualify),
a login form powered by `wp_signon`, and a nonce on every action. No external
dependencies. To uninstall, delete the `mu-plugins/aa-web-admin*` files.

Test it any time with `bash tools/test_webadmin.sh` (boots a temporary server
and runs 19 checks: login, redirect, CRUD, uploads, storefront integrity).

## Store configuration (already done)

- **WooCommerce** active, currency **INR**, pretty permalinks
- Store pages: Shop, Cart, Checkout, My account, Refund & Returns, Privacy Policy
- Site pages: **Home** (static front page with featured products), About, Contact
- 4 product categories: Technology, Business, Self-Help, Fiction
- 6 **sample** ebook products — virtual + downloadable, each with a generated cover
  (`site/wp-content/uploads/...`) and a sample PDF delivered as the secure download
  file (`site/wp-content/uploads/woocommerce_uploads/`)
- Theme: **Astra** (free, WooCommerce-friendly)
- Page builder: **Elementor Free** (nothing paid) — Home and Shop are real Elementor
  pages: each section is an editable "HTML" widget with self-contained HTML/CSS,
  plus the custom `[aa_books]` shortcode (mu-plugin `aa-store-tweaks.php`) which
  renders the book cards from live WooCommerce data. Rebuild both pages anytime with:
  `wp eval-file tools/set_stitch_pages.php` (legacy build: `tools/set_elementor_home.php`)
- **Design source: Google Stitch** — the current storefront design ("Obsidian & Gold
  Lit": dark `#0b0f14` surfaces, amber `#f59e0b` accent, Plus Jakarta Sans, Material
  Symbols) was generated with the Stitch MCP (project `7746485506472124034`, screens:
  home `e14436a8...`, shop `ba9c7f19...`) and ported 1:1 into the Elementor sections.
  New design iterations can be generated with `scripts/stitch_mcp.py` (needs a
  Stitch API key in `scripts/stitch.key`, never committed).
- The front page and the shop page render through a custom canvas shell
  (`aa_render_canvas()` in `aa-store-tweaks.php`) so Astra/Woo archive chrome is
  fully replaced — product/category/cart pages still use the classic templates.

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
