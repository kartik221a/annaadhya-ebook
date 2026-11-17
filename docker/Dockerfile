# Web image for the Annaadhya ebook store.
# The stock php:8.3-apache image ships ONLY the SQLite/PDO-sqlite drivers -
# WordPress running on MySQL needs mysqli, and the one-shot migration script
# (tools/migrate-sqlite-to-mysql.php) needs pdo_mysql.
FROM php:8.3-apache

# mysqli: wpdb's MySQL driver (required by WordPress).
# pdo_mysql: used by tools/migrate-sqlite-to-mysql.php.
# opcache: free first-line performance boost.
RUN docker-php-ext-install mysqli pdo_mysql opcache
