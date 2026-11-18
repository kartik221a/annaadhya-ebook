# Annaadhya eBooks — WordPress ebook store (dev preview)

A complete, portable WordPress + WooCommerce ebook store. The **entire site lives in
this repository as plain files** — WordPress core, plugins, theme, media, and a
SQLite seed snapshot of the store's content (auto-imported into MySQL on first
boot) — so `git pull` always gives you the latest state, ready to run.

## Database: MySQL 8.4 (was SQLite)

The stack now runs **WordPress on MySQL 8.4** (the `db` service in
`docker-compose.yml`, data in the `db_data` volume). The old SQLite drop-in is
gone; the tracked SQLite file under `site/wp-content/database/` is now only a
**seed snapshot**:

- On the first boot the web container runs `tools/migrate-sqlite-to-mysql.php`
  automatically — it rebuilds every table with its original MySQL schema
  (varchar lengths, indexes, auto-increment counters) from the metadata the
  SQLite drop-in preserved, imports all rows, and verifies the row counts.
- Every later boot re-runs it as a fast no-op ("already migrated").
- The seed file is never modified or deleted, so a fresh clone always boots
  with the full store. To re-import it from scratch:
  `docker compose exec web php tools/migrate-sqlite-to-mysql.php --wipe`.
- **Content changes made via wp-admin / wp-cli now live in the `db_data`
  volume**, not in git. To snapshot them, dump the database and commit it:
  `docker compose exec db sh -c 'mysqldump -uwp -pwp wordpress' > db-backup.sql`.

Useful commands:

| | |
|---|---|
| Open a MySQL shell | `docker compose exec db mysql -uwp -pwp wordpress` |
| Backup to a file | `docker compose exec db sh -c 'mysqldump -uwp -pwp wordpress' > db-backup.sql` |
| Restore a backup | `docker compose exec -i db sh -c 'mysql -uwp -pwp wordpress' < db-backup.sql` |
| Reset to the seed | `docker compose down -v` (drops the volume) then `docker compose up` |

## Quick start (Docker — recommended)

```bash
git pull
docker compose up
```

Then open **http://localhost:8080** in your browser. That's it — no database
setup, no config. First `up` builds the web image (stock PHP 8.3 + Apache, plus
`mysqli`/`pdo_mysql`/`opcache`) and starts MySQL 8.4 — give it a minute or two.
When the log shows `[migrate] Migration complete`, the store's data has landed
in MySQL and the site is being served from it. **The site is host-portable**:
URLs follow whatever hostname you open it with (localhost, a LAN IP, a tunnel
or the real domain), so `/web-admin`, links and assets always resolve — no
search-replace needed when the domain changes.

Stop with `Ctrl+C`. To rebuild after pulling changes: `git pull && docker compose up`.

### Troubleshooting: "chmod: Operation not permitted" on startup

Files that Apache created in `wp-content/uploads` during a previous run (e.g.
Elementor regenerated CSS) belong to the container's internal user, so the
one-time permission fix can't change them. This is **harmless** — newer builds
boot anyway with a `[entrypoint] NOTE`. To clean it up permanently (optional):

```bash
sudo chown -R $USER:$USER wp-content/uploads
docker compose up
```

## Quick start (no Docker — plain PHP)

Any PHP 8.x with the `mysqli` and `pdo_mysql` extensions works, but you need a
MySQL server (5.7+ / 8.x / MariaDB 10.4+) and point the site at it first:

```bash
git pull
export WORDPRESS_DB_HOST=127.0.0.1:3306
export WORDPRESS_DB_NAME=wordpress WORDPRESS_DB_USER=wp WORDPRESS_DB_PASSWORD=wp
# one-time: create the db/user, then import the seed snapshot:
php tools/migrate-sqlite-to-mysql.php
# run the site:
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
| `site/` | The full WordPress installation (core + plugins + theme + SQLite seed) |
| `docker-compose.yml` + `docker/` | One-command stack: web (PHP 8.3 + Apache + mysqli) and `db` (MySQL 8.4) |
| `router.php` | Runner for the no-Docker PHP built-in server option |
| `tools/migrate-sqlite-to-mysql.php` | One-shot SQLite → MySQL import (runs automatically on first boot) |
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
template through one hidden page (`[aa_product_single]`), wishlist hearts live
on every book card (stored per-user, shown in the My Account → Wishlist tab).
**Every category archive renders through a hidden "Category Archive" page** too:
`[aa_category_hero]` prints a Stitch hero per category — Technology's terminal
window, Business's playbook-metrics chart, Self-Help's focus rings, Fiction's
mountain-and-book vignette (ported 1:1 from the four Stitch category screens,
project `7746485506472124034`) — and `[aa_books]` automatically follows the
queried category, so new categories get the same design out of the box.
Rebuild every page with:
`wp eval 'require "tools/build_pages_v2.php";'`
(v3 subset for auth/library/checkout/delivery-policy: `wp eval 'require "tools/build_pages_v3.php";'`;
category pages: `wp eval 'require "tools/build_category_pages.php";'`;
home+shop only: `wp eval 'require "tools/set_stitch_pages.php";'` — all of them
pull in `tools/stitch_lib.php`, the shared token/nav/footer library).
The builders impersonate an
admin automatically —
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

## Sign-in options (Google + email/password + verified emails)

- `/my-account/` renders a Stitch-designed auth card: **Continue with Google** pill,
  “or continue with email” divider, sign-in + register tabs (AJAX, nonce-guarded).
- **Every email/password sign-up now receives a confirmation email** (branded
  dark/gold). The account can browse immediately; **downloads stay locked until
  the address is verified** — Library and My Account show an amber
  “Verify your email to unlock downloads” banner with a resend button, and a
  download attempt before verification redirects to the library with that banner.
  `/verify-email/?uid=&key=` is the one-click confirmation (and signs you in).
- **Firebase Authentication — recommended (no SMTP server, no Google Cloud
  Console, no meaningful limits)**: both sign-in methods run on Firebase's own
  OAuth client and Firebase sends the verification + password-reset mails
  itself. Free tier: 50k monthly actives with no per-user charge, 1,000
  verification mails/day. One-time console setup (console.firebase.google.com,
  free, no card): create project → **Authentication → Get started** → enable
  **Email/Password** and **Google** → Settings → Authorized domains keeps
  `localhost` (any port) for dev, add your real domain when you go live →
  Project settings → General → Your apps → Web app (`</>`) → copy the config.
  Then add two constants to `wp-config.php`:
  ```php
  define( 'AA_FB_API_KEY', 'AIza…' );        // Web API key (public by design)
  define( 'AA_FB_PROJECT_ID', 'annaadhya' ); // Firebase project id
  ```
  The auth card switches to Firebase automatically: Google opens a popup,
  sign-ups get Firebase's verification mail, "Forgot password?" sends a
  Firebase reset mail. The browser hands the Firebase ID token to
  `admin-ajax.php` (`aa-firebase-auth.php` verifies the RS256 signature against
  Google's public certs plus every Firebase claim), which creates/logs in the
  WordPress customer and syncs the verified flag that gates downloads. Google
  users arrive pre-verified; email users stay gated until they click the mail
  link — the amber banner's Resend button goes through Firebase, and the
  profile page auto-syncs + unlocks the moment they return from it. Until the
  constants exist the card keeps the built-in flow below — nothing breaks.
- **Native Google sign-in without Firebase**: `aa-auth-plus.php` also ships a
  minimal OAuth2/OIDC client configured through constants — either your own
  Google OAuth client (creating an OAuth 2.0 Client ID is free, no billing
  account needed) or any OIDC provider:
  ```php
  // Option A — native Google:
  define( 'AA_GOOGLE_CLIENT_ID', '...apps.googleusercontent.com' );
  define( 'AA_GOOGLE_CLIENT_SECRET', '...' );
  // Option B — any OpenID Connect provider (discovery-based). Note: Auth0's
  // free tier now requires a credit card for social login/custom domains, so
  // the Firebase path above is the cleanest free option.
  define( 'AA_OAUTH_ISSUER', 'https://your-tenant.eu.auth0.com/' );
  define( 'AA_OAUTH_CLIENT_ID', '...' );
  define( 'AA_OAUTH_CLIENT_SECRET', '...' );
  ```
  The callback URL to register is `<home_url>/oauth/callback`; the sign-in
  route is `/oauth/start`. OAuth users are email-verified automatically.
  Until constants are set the Google button shows a friendly toast instead of
  a broken flow, and email sign-in keeps working.
- **Outgoing email needs SMTP for the built-in flow** (the PHP `mail()`
  fallback rarely works on dev/cheap hosts). Free options that work with the
  bundled `AA_SMTP_*` constants: **Brevo** (300 mails/day free, no card) or a
  Gmail app password.
  ```php
  define( 'AA_SMTP_HOST', 'smtp-relay.brevo.com' );
  define( 'AA_SMTP_PORT', 587 );
  define( 'AA_SMTP_USER', '...' );  define( 'AA_SMTP_PASS', '...' );
  define( 'AA_SMTP_SECURE', 'tls' );
  define( 'AA_SMTP_FROM', 'store@yourdomain.com' );
  define( 'AA_SMTP_FROM_NAME', 'Annaadhya Ebooks' );
  ```
  While SMTP is missing the register card says so plainly, and **web-admin →
  Downloads → “Verify email”** lets you verify a customer manually.
- Email/password registration is open (min 8 chars); accounts get the Woo
  `customer` role. `wp-login.php` (lost-password flow) is branded dark/gold.

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

## Production deploy path (Hostinger) — READY

Everything needed to go live lives in [`hostable/`](hostable/README.md):

- `hostable/annaadhya-ebooks-site.zip` — the entire website (production
  `wp-config.php` inside, nothing SQLite-related), ready to upload & extract
  into `public_html`
- `hostable/annaadhya-ebooks.sql` — the complete MySQL database (all products,
  users, orders, settings) for a phpMyAdmin import
- `hostable/README.md` — the step-by-step Hostinger deployment guide
  (upload → extract → DB credentials → import → URL fix → Firebase constants)

Both artifacts are **verified end-to-end**: the dump was re-imported into an
empty MySQL database and the extracted zip booted from it renders the full
storefront (products, product pages, my-account, library) — never the default
"Hello world!" (which is what an *empty* database gives you).

The SQLite→MySQL conversion itself is reproducible:
`php tools/migrate-sqlite-to-mysql.php` rebuilds the exact MySQL schema from
the metadata the SQLite drop-in preserved and copies every row. It is also
what the Docker entrypoint runs on boot — and it now re-imports the seed
instead of no-oping when it finds a WordPress install that ran on an empty
database (the "Hello world" trap).

## Repository notes

- The SQLite seed (`site/wp-content/database/`) is tracked in git on purpose —
  fresh clones get the full store on first boot. It is only read by the
  migrator, never written. Runtime content changes live in the MySQL volume;
  snapshot them with the mysqldump command from the Database section.
- Nothing here is committed to the production site; this repo is a self-contained
  preview environment.
