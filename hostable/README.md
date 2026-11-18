# Annaadhya eBooks — Production Package

Everything in this folder is ready to put your store on a live host
(Hostinger, or any host with PHP + MySQL). No building, no zipping —
the two files below are the whole site.

| File | What it is | Where it goes |
| --- | --- | --- |
| `annaadhya-ebooks.sql` | Full MySQL database dump (phpMyAdmin-ready): all products, pages, settings, users | hPanel → **Databases → phpMyAdmin → Import** |
| `annaadhya-ebooks-site.zip` | The complete WordPress site (code, themes, plugins, covers, PDFs) | **Files → File Manager** → upload into `public_html` → Extract |
| `wp-config.php` | Production config template (already inside the zip) | Fill in 4 values (see below) |

## How the store works (no accounts!)

- **Guests buy with just a name + email.** No registration, no passwords,
  no Google login, no email verification.
- **The PDF is delivered by email.** The moment an order is paid, the
  branded email goes out with the PDF file *attached*, plus permanent
  re-download links on the order page.
- **No download limits.** Every book can be re-downloaded unlimited times
  from the delivery email / order page.
- **You manage everything at `/web-admin`** (Books, Categories, Orders,
  Email Delivery with one-click *Resend*, Messages, Subscribers). Log in
  with your WordPress admin account.
- The demo checkout uses **Cash on Delivery** so you can test the whole
  flow end-to-end. When you're ready for real payments, install a gateway
  (Razorpay for UPI/RuPay, or PayPal/Stripe) — the delivery email fires
  automatically the moment any gateway marks an order paid.

## Step-by-step: go live on Hostinger

1. **Create the database**
   hPanel → *Websites → Manage → Databases → Management* → create a MySQL
   database. Note the **database name, user, password, and host** (usually
   `localhost`).

2. **Import the database**
   In the same screen open **phpMyAdmin** → pick your new database →
   **Import** tab → choose `annaadhya-ebooks.sql` → *Go*.
   (The file replaces `wp_*` tables safely — it starts with
   `DROP TABLE IF EXISTS`.)

3. **Upload the site**
   hPanel → *Files → File Manager* → open `public_html` → upload
   `annaadhya-ebooks-site.zip` → right-click → **Extract**.
   The zip's contents should end up directly in `public_html` (so that
   `wp-config.php` sits at `public_html/wp-config.php`). If extraction
   created a folder, move its contents up one level.

4. **Point the site at your database**
   Edit `public_html/wp-config.php` (the zip ships the production template)
   and replace:

   ```php
   define( 'DB_NAME',     'YOUR_DATABASE_NAME' );
   define( 'DB_USER',     'YOUR_DATABASE_USER' );
   define( 'DB_PASSWORD', 'YOUR_DATABASE_PASSWORD' );
   define( 'DB_HOST',     'YOUR_DATABASE_HOST' );
   ```

   with the values from step 1.

5. **Visit your domain** — the storefront comes up immediately.

6. **One-time URL fix** — the database was built on `localhost:8080`.
   Every content URL follows the incoming domain automatically, but run
   this once from your PC (or use the *Better Search Replace* plugin) so
   stored emails/links are production-clean:

   ```
   wp search-replace 'http://localhost:8080' 'https://YOURDOMAIN' --all-tables
   ```

   No WP-CLI on your PC? Install the free *Better Search Replace* plugin,
   search `http://localhost:8080`, replace with your domain, select all
   tables, run. (Preview-hosting links like `https://preview-....space-z.ai`
   can be replaced the same way.)

7. **Set your admin access**
   The admin user in the dump is `admin` with password `admin` —
   **log into `/wp-admin` and change this password right away**
   (Users → Profile → Set new password).

8. **(Recommended) SMTP for the delivery emails**
   PHP `mail()` usually works on Hostinger, but for reliable delivery to
   Gmail/Outlook define an SMTP account at the bottom of `wp-config.php`
   (Brevo's free tier: 300 emails/day):

   ```php
   define( 'AA_SMTP_HOST', 'smtp-relay.brevo.com' );
   define( 'AA_SMTP_PORT', 587 );
   define( 'AA_SMTP_USER', 'your-smtp-login' );
   define( 'AA_SMTP_PASS', 'your-smtp-key' );
   define( 'AA_SMTP_SECURE', 'tls' );
   define( 'AA_SMTP_FROM', 'store@yourdomain.com' );
   define( 'AA_SMTP_FROM_NAME', 'Annaadhya Ebooks' );
   ```

9. **Test the flow**: open the store in an incognito window, add a book,
   checkout as a guest with a real email, pay with COD demo → complete the
   order in `/web-admin` → check the email arrives with the PDF attached.

## Local development (docker compose)

```bash
docker compose up
# store:    http://localhost:8080
# database: mariadb service (db-data volume persists)
```

The same repo runs WordPress on MySQL/MariaDB everywhere now — SQLite has
been fully retired (`tools/migrate-sqlite-to-mysql.php` did the one-time
data migration).
