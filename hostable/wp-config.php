<?php
# ============================================================
# Annaadhya eBooks - PRODUCTION wp-config.php
# ============================================================
# 1. hPanel > Databases > create a MySQL database, note:
#      database name, user, password, host (usually "localhost")
# 2. Fill in the four YOUR_* values below.
# 3. Import hostable/annaadhya-ebooks.sql in phpMyAdmin.
# 4. Upload the site zip into public_html (or the site root) and extract.
# 5. Visit your domain - the store comes up as-is.
# ============================================================

define( 'DB_NAME', 'YOUR_DATABASE_NAME' );
define( 'DB_USER', 'YOUR_DATABASE_USER' );
define( 'DB_PASSWORD', 'YOUR_DATABASE_PASSWORD' );
define( 'DB_HOST', 'YOUR_DATABASE_HOST' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         'x!k0B9f8WlqV2nZc7jTgR3mYp5dUeHsA1oQiCwXvNbM' );
define( 'SECURE_AUTH_KEY',  'Lg7tJ2xWq9mZcVbN8sKdR4fYhP6uE3oAiXwQnCjMvTb' );
define( 'LOGGED_IN_KEY',    'Qw9eR5tY3uI8oP2aS7dF1gH6jK4lZ0xCvBnM5qW2eE' );
define( 'NONCE_KEY',        'Zx8cV7bN6mQ5wE4rT3yU2iO1pA9sD8fG7hJ6kL5mC4v' );
define( 'AUTH_SALT',        'B1nM2cV3xZ4lK5jH6gF7dS8aP9oI0uY1tR2eW3qE4rT' );
define( 'SECURE_AUTH_SALT', 'T4rE3wQ2q1P0o9I8u7Y6t5R4e3W2q1E0r9T8y7U6i5O' );
define( 'LOGGED_IN_SALT',   'O5i4U3y2T1r0E9w8Q7q6P5o4i3u2y1t0r9e8w7q6p5o' );
define( 'NONCE_SALT',       'P4o3i2u1y0t9r8e7w6q5p4o3i2u1y0t9r8e7w6q5p4o' );

$table_prefix = 'wp_';

# --- Book delivery emails -----------------------------------------------
# Every paid order emails the PDF to the buyer. Shared hosts send PHP mail()
# out of the box; for better deliverability define an SMTP account (Brevo has
# a free tier that works great):
# define( 'AA_SMTP_HOST', 'smtp-relay.brevo.com' );
# define( 'AA_SMTP_PORT', 587 );
# define( 'AA_SMTP_USER', 'your-smtp-login' );
# define( 'AA_SMTP_PASS', 'your-smtp-key' );
# define( 'AA_SMTP_SECURE', 'tls' );
# define( 'AA_SMTP_FROM', 'store@yourdomain.com' );
# define( 'AA_SMTP_FROM_NAME', 'Annaadhya Ebooks' );

# Portable asset URLs - keeps the store rendering correctly on any domain
# without editing the database (leave this block as-is).
if ( ! defined( 'WP_CONTENT_URL' ) && ! empty( $_SERVER['HTTP_HOST'] )
        && preg_match( '/^[a-z0-9.\-]+(:[0-9]{1,5})?$/i', (string) $_SERVER['HTTP_HOST'] ) ) {
        $__aa_https = false;
        if ( isset( $_SERVER['HTTPS'] ) && 'on' === strtolower( (string) $_SERVER['HTTPS'] ) ) {
                $__aa_https = true;
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
                $__aa_https = true;
        }
        define( 'WP_CONTENT_URL', ( $__aa_https ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . '/wp-content' );
}

define( 'WP_DEBUG', false );
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'DISALLOW_FILE_EDIT', true );

if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
