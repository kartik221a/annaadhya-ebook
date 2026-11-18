<?php
/**
 * Annaadhya: first-boot store seeding for MySQL.
 *
 * Seeds an empty (or "Hello world!") MySQL database from the shipped,
 * verified dump `hostable/annaadhya-ebooks.sql`. Runs automatically from the
 * web container's entrypoint on every boot and exits in milliseconds when
 * there is nothing to do.
 *
 * Semantics (same protection rules as the original sqlite migrator):
 *   - No-op when the seed marker (wp_options.aa_seed_migrated) is present.
 *   - Re-imports when the database is empty OR when it only contains a
 *     default WordPress install (1 admin, 0 products — the "Hello world!"
 *     trap: an install that ran on an empty DB before the import happened).
 *   - Protects real store data: no-op with a warning when the DB already
 *     holds users AND products but no marker. Pass --wipe to re-seed anyway.
 *
 * Run:  php tools/first-boot-import.php [--wipe] [--quiet]
 * The dump lives at <repo>/hostable/annaadhya-ebooks.sql (in the container:
 * mounted at /var/www/hostable/annaadhya-ebooks.sql).
 */

if ( PHP_SAPI !== 'cli' ) {
        die( "CLI only\n" );
}

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

$quiet = in_array( '--quiet', $argv, true );
$wipe  = in_array( '--wipe', $argv, true );

$log  = static function ( string $msg ) use ( $quiet ) {
        if ( ! $quiet ) { echo '[seed] ' . $msg . "\n"; }
};
$warn = static function ( string $msg ) {
        echo '[seed] WARNING: ' . $msg . "\n";
};

/* Site root = the directory holding wp-config.php (site/ in this repo,
 * /var/www/html in the container). The dump sits beside it in hostable/. */
$repo_root = null;
foreach ( array( dirname( __DIR__ ) . '/site', dirname( __DIR__ ), getcwd() ) as $candidate ) {
        if ( is_file( $candidate . '/wp-config.php' ) ) {
                $repo_root = $candidate;
                break;
        }
}
if ( ! $repo_root ) {
        $repo_root = dirname( __DIR__ ) . '/site';
}

$dump_candidates = array(
        dirname( $repo_root ) . '/hostable/annaadhya-ebooks.sql',
        dirname( $repo_root ) . '/hostable/../hostable/annaadhya-ebooks.sql',
        '/var/www/hostable/annaadhya-ebooks.sql',
        dirname( $repo_root ) . '/annaadhya-ebooks.sql',
);
$dump_file = null;
foreach ( $dump_candidates as $candidate ) {
        if ( is_file( $candidate ) && filesize( $candidate ) > 1024 ) {
                $dump_file = $candidate;
                break;
        }
}
if ( ! $dump_file ) {
        $warn( 'No seed dump found (looked for hostable/annaadhya-ebooks.sql). Booting an empty WordPress instead.' );
        exit( 0 );
}

/* --- target connection (env-aware, same names wp-config.php honours) --- */
$db_host = getenv( 'WORDPRESS_DB_HOST' ) ?: getenv( 'DB_HOST' ) ?: 'db';
$db_name = getenv( 'WORDPRESS_DB_NAME' ) ?: getenv( 'DB_NAME' ) ?: 'wordpress';
$db_user = getenv( 'WORDPRESS_DB_USER' ) ?: getenv( 'DB_USER' ) ?: 'wp';
$db_pass = getenv( 'WORDPRESS_DB_PASSWORD' ) ?: getenv( 'DB_PASSWORD' ) ?: 'wp';

$port = 3306;
if ( preg_match( '/^(.*):(\d{1,5})$/', $db_host, $m ) ) {
        $db_host = $m[1];
        $port    = (int) $m[2];
}

$prefix = 'wp_';
if ( preg_match( '/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]/', (string) file_get_contents( $repo_root . '/wp-config.php' ), $mm ) ) {
        $prefix = $mm[1];
}
$users_table = $prefix . 'users';

/* Retry: the entrypoint may run us while the db container is still finishing
 * its first-boot init. */
$mysql = null;
for ( $attempt = 1; $attempt <= 30; $attempt++ ) {
        try {
                $mysql = new PDO( "mysql:host={$db_host};port={$port};charset=utf8mb4", $db_user, $db_pass, array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 3,
                ) );
                break;
        } catch ( PDOException $e ) {
                if ( 30 === $attempt ) {
                        $warn( "Cannot connect to MySQL at {$db_host}:{$port}: " . $e->getMessage() );
                        exit( 1 );
                }
                if ( 1 === $attempt ) {
                        $log( "MySQL not ready at {$db_host}:{$port}, retrying (up to 60s)..." );
                }
                sleep( 2 );
        }
}
$log( "Connected to MySQL at {$db_host}:{$port}." );

$mysql->exec( "CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci" );
$mysql->exec( "USE `{$db_name}`" );
$mysql->exec( "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci, sql_mode = '', FOREIGN_KEY_CHECKS = 0" );

$existing_tables = array();
foreach ( $mysql->query( 'SHOW TABLES' )->fetchAll( PDO::FETCH_COLUMN ) as $t ) {
        $existing_tables[ $t ] = true;
}

/* ------------------------------- decide -------------------------------- */

if ( isset( $existing_tables[ $users_table ] ) && ! $wipe ) {
        $marker_exists = false;
        if ( isset( $existing_tables[ $prefix . 'options' ] ) ) {
                $marker_exists = (int) $mysql->query(
                        "SELECT COUNT(*) FROM `{$prefix}options` WHERE option_name = 'aa_seed_migrated'"
                )->fetchColumn() > 0;
        }
        if ( $marker_exists ) {
                $count = (int) $mysql->query( "SELECT COUNT(*) FROM `{$users_table}`" )->fetchColumn();
                $log( "MySQL already carries the store seed ({$users_table}: {$count} users, marker present). Nothing to do." );
                exit( 0 );
        }

        $user_count    = (int) $mysql->query( "SELECT COUNT(*) FROM `{$users_table}`" )->fetchColumn();
        $product_count = 0;
        if ( isset( $existing_tables[ $prefix . 'posts' ] ) ) {
                $product_count = (int) $mysql->query(
                        "SELECT COUNT(*) FROM `{$prefix}posts` WHERE post_type = 'product' AND post_status = 'publish'"
                )->fetchColumn();
        }

        if ( $user_count > 0 && $product_count > 0 ) {
                $warn( "Database has {$user_count} users and {$product_count} products but no seed marker -" );
                $warn( 'this looks like REAL store data, so nothing was overwritten.' );
                $warn( 'Run with --wipe to re-seed from hostable/annaadhya-ebooks.sql on purpose.' );
                exit( 0 );
        }

        if ( $user_count > 0 ) {
                $log( "{$users_table} has {$user_count} user(s) but 0 products - this is a default WordPress" );
                $log( 'install on an empty database (the "Hello world!" state). Re-seeding the store.' );
        } else {
                $log( 'Database is empty - importing the store seed.' );
        }
}

if ( $wipe && ! empty( $existing_tables ) ) {
        $log( '--wipe requested: dropping all existing tables in MySQL.' );
        foreach ( array_keys( $existing_tables ) as $t ) {
                $mysql->exec( "DROP TABLE IF EXISTS `{$t}`" );
        }
}

/* ------------------------------- import -------------------------------- */

$log( "Importing seed: {$dump_file}" );
$sql = (string) file_get_contents( $dump_file );
// The dump is phpMyAdmin-safe (no DEFINER/LOCK/CREATE DATABASE). mysqldump
// writes one INSERT per line with newlines inside values escaped as \n, so
// splitting on a semicolon followed by a real line break is safe; chunks may
// START with `--` comment lines, which MySQL executes without complaint.
$stmts = preg_split( '/;[\r\n]+/', $sql );
$done = 0;
foreach ( $stmts as $stmt ) {
        $stmt = trim( $stmt );
        if ( '' === $stmt ) { continue; }
        // Skip chunks that are pure comments (no SQL at all).
        $check = trim( preg_replace( '/^--.*$/m', '', $stmt ) );
        if ( '' === $check ) { continue; }
        try {
                $mysql->exec( $stmt );
                $done++;
        } catch ( PDOException $e ) {
                $warn( 'statement failed (' . substr( $e->getMessage(), 0, 120 ) . ') — first 80 chars: ' . substr( preg_replace( '/\s+/', ' ', $stmt ), 0, 80 ) );
        }
}
$log( "dump executed: {$done} statements" );

$marker = $mysql->prepare( "INSERT INTO `{$prefix}options` (option_name, option_value, autoload) VALUES ('aa_seed_migrated', ?, 'no')" );
$marker->execute( array( gmdate( 'Y-m-d H:i:s' ) ) );

$mysql->exec( "DELETE FROM `{$prefix}options` WHERE option_name = '_site_transient_update_plugins'" );

$users    = (int) $mysql->query( "SELECT COUNT(*) FROM `{$users_table}`" )->fetchColumn();
$products = (int) $mysql->query( "SELECT COUNT(*) FROM `{$prefix}posts` WHERE post_type = 'product' AND post_status = 'publish'" )->fetchColumn();

$log( '' );
$log( '==============================================' );
$log( "Seed import complete: {$products} products, {$users} users now in MySQL." );
$log( "  MySQL: {$db_host}:{$port} / db '{$db_name}' / user '{$db_user}'" );
$log( '  Next boot WordPress will serve the full store from MySQL.' );
$log( '==============================================' );
exit( 0 );
