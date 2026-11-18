# Annaadhya eBooks — production package (Hostinger-ready)

This folder contains everything needed to put the store live on Hostinger
(or any cPanel/shared host with a MySQL database and phpMyAdmin):

| File | What it is |
| --- | --- |
| `annaadhya-ebooks-site.zip` (~80 MB) | The **entire website** — WordPress core, themes, plugins, mu-plugins, uploads (covers + downloadable PDFs), `.htaccess` and a production `wp-config.php`. The SQLite database, the SQLite drop-in (`wp-content/db.php`) and the SQLite plugin are deliberately **not** included: production runs on MySQL. |
| `annaadhya-ebooks.sql` (~2 MB) | The **complete database** — all 54 `wp_*` tables with every product (6), user (5), order, review and setting. Import it with phpMyAdmin. |
| `wp-config.php` | Copy of the production config inside the zip (for reference / manual installs). |
| `README.md` | This guide. |

> **Why did the site show "Hello world!" before?** That is WordPress's default
> post on an **empty database**. Uploading the files alone gives WordPress a
> blank MySQL database (or the installer's one-click sample site). Importing
> `annaadhya-ebooks.sql` is what puts the store (products, pages, users) in
> the database.

---

## Deploy on Hostinger, step by step

### Step 1 — Create the WordPress site
hPanel → **Websites** → *Add website* → **WordPress** (auto-install).
- Pick your domain, PHP 8.2 or newer.
- Let the installer finish; it creates the MySQL database **and** a
  `wp-config.php` with the correct credentials.
- Ignore the sample content it creates — the import in Step 4 replaces it.

### Step 2 — Upload the zip
hPanel → **Files** → **File Manager** → open `public_html` (or
`~/domains/YOURDOMAIN/public_html`) → *Upload* → choose
`annaadhya-ebooks-site.zip`.
- After the upload, right-click the zip → **Extract** → extract **into
  `public_html`**, confirming **Replace/Overwrite** for every conflict.
- If the File Manager refuses an 80 MB upload, upload via **FTP/SFTP**
  (credentials in hPanel → FTP accounts) and extract on the server, or
  extract locally and upload the folder.
- Do **not** upload `wp-config.php` separately — it is already inside the zip.

### Step 3 — Point wp-config.php at your database
Open `public_html/wp-config.php` in File Manager (Edit) and set the three
lines from hPanel → **Databases** → *MySQL databases*:

```php
define( 'DB_NAME',     'u123456789_annaadhya' );   // your database name
define( 'DB_USER',     'u123456789_kartik' );      // your database user
define( 'DB_PASSWORD', 'the-password-you-set' );
```

*Alternative:* keep the installer-generated `wp-config.php` (its credentials
already work) and copy only the **"Annaadhya constants"** block from the
zip's `wp-config.php` into it.

### Step 4 — Import the database with phpMyAdmin
hPanel → **Databases** → *MySQL databases* → **Enter phpMyAdmin** on your
database → **Import** tab → choose `annaadhya-ebooks.sql` → **Go**
(~2 MB, well under the upload limit).

The file contains `DROP TABLE IF EXISTS` + `CREATE TABLE` + `INSERT` for all
`wp_*` tables, so it can be re-imported any time; it replaces the current
contents. It needs no special privileges (no `DEFINER`, no `LOCK TABLES`,
no `CREATE DATABASE`).

### Step 5 — Fix the site address (30 seconds)
The database still carries the development address `http://localhost:8080`.
In phpMyAdmin, open the **SQL** tab on your database and run (use **your**
domain):

```sql
UPDATE wp_options SET option_value = 'https://YOURDOMAIN.com'
WHERE option_name IN ('siteurl','home');
```

### Step 6 — Replace the remaining localhost URLs
All other content URLs (product links inside Elementor pages, WooCommerce
settings) also point at `localhost:8080`. The safe fix rewrites serialized
PHP data correctly:

**With SSH (Hostinger includes WP-CLI):**
```bash
cd ~/domains/YOURDOMAIN.com/public_html
wp search-replace 'http://localhost:8080' 'https://YOURDOMAIN.com' --all-tables
```

**Without SSH:** install the free **Better Search Replace** plugin
(Plugins → Add New), run
`http://localhost:8080` → `https://YOURDOMAIN.com` over all tables as a
*dry run* first, then for real.

> Tip: the production `wp-config.php` also defines `WP_CONTENT_URL`
> per-request, so every theme/plugin/upload asset already follows whatever
> domain the visitor uses — this step mainly cleans post content and
> WooCommerce references.

### Step 7 — Enable Firebase sign-in (optional but recommended)
Edit `wp-config.php` and paste your Firebase project values:

```php
define( 'AA_FB_API_KEY', 'AIza...your-web-api-key...' );
define( 'AA_FB_PROJECT_ID', 'annaadhya' );
```

Left empty, Firebase login is disabled and the store falls back to the
standard WordPress login form — nothing breaks.

### Step 8 — Flush permalinks and log in
1. `https://YOURDOMAIN.com/wp-admin/` → log in as **kartik** (same
   username/password as on the local copy).
2. Settings → **Permalinks** → click **Save** once (rewrites the rules).
3. If any page styling looks off: Elementor → Tools → **Regenerate CSS**,
   then reload.

### Step 9 — Post-deploy checklist
- [ ] Home page shows the hero + book grid (not "Hello world!")
- [ ] `/shop/` lists all 6 books, covers load
- [ ] A product page opens; **Add to cart** works
- [ ] Checkout completes and the **download link** works (PDF opens)
- [ ] Registration/login works (Firebase, once Step 7 is done)
- [ ] Admin → Downloads manager shows purchased files
- [ ] `https://` padlock is active (Hostinger issues SSL automatically)

---

## Security notes
- The salts/keys in the shipped `wp-config.php` were also committed to this
  repository. After the site is live, generate fresh ones at
  `https://api.wordpress.org/secret-key/1.1/salt/` and replace the eight
  `*_KEY` / `*_SALT` lines (everyone gets logged out once — that is fine).
- `DISALLOW_FILE_EDIT` is already enabled in the production config.

## Local development vs production
| | Local (Docker) | Production (this package) |
| --- | --- | --- |
| Database | SQLite (drop-in, no server needed) | MySQL via the `.sql` import |
| Config | `site/wp-config.php` (dev) | production `wp-config.php` in the zip |
| Data source | `site/wp-content/database/.ht.*/*.ht.sqlite` | `hostable/annaadhya-ebooks.sql` |

If you add new products locally later, the database export can be rebuilt
with `php tools/migrate-sqlite-to-mysql.php` (SQLite → any MySQL/MariaDB
server) — ask the bot for a fresh `hostable/` package when that happens.
