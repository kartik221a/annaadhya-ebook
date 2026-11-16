<?php
# Local dev configuration — SQLite database (no MySQL server required).
# The SQLite drop-in at wp-content/db.php overrides all DB_* constants below.
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'wp' );
define( 'DB_PASSWORD', 'wp' );
define( 'DB_HOST', 'localhost' );
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

# Portable media/asset URLs. WP core defines WP_CONTENT_URL from the RAW DB
# siteurl during boot — before mu-plugins load — which would pin every
# attachment, theme and plugin asset URL (uploads covers, Elementor CSS,
# WooCommerce placeholder images…) to whatever host is stored in the DB.
# Define it per-request instead so the store renders correctly whether it is
# opened as localhost:8080, a LAN IP (phone testing), a tunnel or a domain.
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
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'DISALLOW_FILE_EDIT', true );

if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
