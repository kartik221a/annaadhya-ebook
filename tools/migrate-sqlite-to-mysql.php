<?php
/**
 * SQLite -> MySQL migration for the Annaadhya ebook store.
 *
 * One-time tool: reads the SQLite drop-in database (site/wp-content/database/
 * .ht.<hash>/.ht.sqlite), creates the SAME schema on MySQL/MariaDB through a
 * temporary WordPress install (so core + WooCommerce create every table with
 * the exact right shape), then copies every table row-by-row.
 *
 * Connection: WORDPRESS_DB_HOST / WORDPRESS_DB_USER / WORDPRESS_DB_PASSWORD /
 * WORDPRESS_DB_NAME environment variables (same names wp-config.php uses),
 * defaulting to 127.0.0.1:3307 / wp / wp / wordpress — the local MariaDB that
 * scripts/start_mariadb.sh boots.
 *
 * Run:  WORDPRESS_DB_HOST=127.0.0.1:3307 php tools/migrate-sqlite-to-mysql.php
 *
 * The script is idempotent: re-running replaces the MySQL rows with the
 * current SQLite content.
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

$repo    = dirname( __DIR__ );          // ebook-store/
$site    = $repo . '/site';
$wpcli   = getenv( 'AA_WP_CLI' ) ?: '/home/z/my-project/bin/wp';
// wp-cli subprocess: WordPress's wpdb hard-requires ext/mysqli, which the
// interpreter running THIS file may not carry (we only need pdo_* here).
$wpPhp   = getenv( 'AA_WP_PHP' ) ?: '/home/z/my-project/bin-static/bulk/php';
$dbname  = getenv( 'WORDPRESS_DB_NAME' ) ?: 'wordpress';
$dbuser  = getenv( 'WORDPRESS_DB_USER' ) ?: 'wp';
$dbpass  = getenv( 'WORDPRESS_DB_PASSWORD' ) ?: 'wp';
$dbhost  = getenv( 'WORDPRESS_DB_HOST' ) ?: '127.0.0.1:3307';

if ( strpos( $dbhost, ':' ) !== false ) {
        list( $mhost, $mport ) = explode( ':', $dbhost, 2 );
} else {
        $mhost = $dbhost; $mport = 3306;
}

function aa_mig_fail( $msg ) {
        fwrite( STDERR, "MIGRATION FAILED: $msg\n" );
        exit( 1 );
}

/* ---------------------------------------------------------------- sqlite */

$files = glob( $site . '/wp-content/database/.ht.*/.ht.sqlite' );
if ( ! $files ) { aa_mig_fail( 'no sqlite seed found in wp-content/database/.ht.*/' ); }
$sqlitePath = $files[0];
echo "sqlite seed: $sqlitePath\n";

try {
        $sq = new PDO( 'sqlite:' . $sqlitePath, null, null, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ) );
} catch ( PDOException $e ) { aa_mig_fail( 'sqlite connect: ' . $e->getMessage() ); }

/* ----------------------------------------------------------------- mysql */

try {
        $mx = new PDO( "mysql:host=$mhost;port=$mport;charset=utf8mb4", $dbuser, $dbpass, array(
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT    => 25,
        ) );
} catch ( PDOException $e ) { aa_mig_fail( "mysql connect ($mhost:$mport as $dbuser): " . $e->getMessage() ); }

$mx->exec( "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci" );
$mx->exec( "USE `$dbname`" );
// Shared-hosting style laxness: the sqlite data may contain zero-dates etc.
$mx->exec( "SET SESSION sql_mode = ''" );
echo "mysql: connected to $dbname on $mhost:$mport\n";

/* ------------------------------------------- schema via a temporary WP install */

$dbphp   = $site . '/wp-content/db.php';
$dbphpBk = tempnam( sys_get_temp_dir(), 'aa-db-php-' ) . '.php';
$hidden  = false;
if ( file_exists( $dbphp ) ) {
        rename( $dbphp, $dbphpBk );
        $hidden = true;
        echo "drop-in db.php temporarily hidden so WP boots on MySQL\n";
}

function aa_mig_run( $cmd ) {
        exec( $cmd . ' 2>&1', $out, $rc );
        return array( $rc, implode( "\n", $out ) );
}

try {
        list( $rc, $out ) = aa_mig_run(
                'WORDPRESS_DB_HOST=' . escapeshellarg( $dbhost ) . ' WORDPRESS_DB_NAME=' . escapeshellarg( $dbname )
                . ' WORDPRESS_DB_USER=' . escapeshellarg( $dbuser ) . ' WORDPRESS_DB_PASSWORD=' . escapeshellarg( $dbpass )
                . ' ' . escapeshellarg( $wpPhp ) . ' ' . escapeshellarg( $wpcli ) . ' --path=' . escapeshellarg( $site )
                . ' core is-installed'
        );
        if ( 0 === $rc ) {
                echo "schema: wordpress tables already exist, skipping core install\n";
        } else {
                list( $rc, $out ) = aa_mig_run(
                        'WORDPRESS_DB_HOST=' . escapeshellarg( $dbhost ) . ' WORDPRESS_DB_NAME=' . escapeshellarg( $dbname )
                        . ' WORDPRESS_DB_USER=' . escapeshellarg( $dbuser ) . ' WORDPRESS_DB_PASSWORD=' . escapeshellarg( $dbpass )
                        . ' ' . escapeshellarg( $wpPhp ) . ' ' . escapeshellarg( $wpcli ) . ' --path=' . escapeshellarg( $site )
                        . ' core install --url=http://localhost:8080 --title=Annaadhya --admin_user=admin --admin_password=admin --admin_email=admin@annaadhya.test --skip-email'
                );
                if ( 0 !== $rc ) { aa_mig_fail( "wp core install rc=$rc:\n$out" ); }
                echo "schema: wp core install done (core tables created)\n";
        }

        // WooCommerce owns ~40 tables (HPOS orders, download permissions,
        // action scheduler...). Activating it creates the whole set.
        list( $rc, $out ) = aa_mig_run(
                'WORDPRESS_DB_HOST=' . escapeshellarg( $dbhost ) . ' WORDPRESS_DB_NAME=' . escapeshellarg( $dbname )
                . ' WORDPRESS_DB_USER=' . escapeshellarg( $dbuser ) . ' WORDPRESS_DB_PASSWORD=' . escapeshellarg( $dbpass )
                . ' ' . escapeshellarg( $wpPhp ) . ' ' . escapeshellarg( $wpcli ) . ' --path=' . escapeshellarg( $site )
                . ' plugin activate woocommerce'
        );
        if ( 0 !== $rc ) { aa_mig_fail( "wp plugin activate woocommerce rc=$rc:\n$out" ); }
        echo "schema: woocommerce tables created\n";

        // The store keeps orders in the HPOS tables (wp_wc_orders*) - switch
        // HPOS on and let WC create its full table set, like production does.
        list( $rc, $out ) = aa_mig_run(
                'WORDPRESS_DB_HOST=' . escapeshellarg( $dbhost ) . ' WORDPRESS_DB_NAME=' . escapeshellarg( $dbname )
                . ' WORDPRESS_DB_USER=' . escapeshellarg( $dbuser ) . ' WORDPRESS_DB_PASSWORD=' . escapeshellarg( $dbpass )
                . ' ' . escapeshellarg( $wpPhp ) . ' ' . escapeshellarg( $wpcli ) . ' --path=' . escapeshellarg( $site )
                . ' eval "update_option( \'woocommerce_custom_orders_table_enabled\', \'yes\' ); if ( class_exists( \'WC_Install\' ) ) { WC_Install::create_tables(); } echo \'HPOS-TABLES-OK\';"'
        );
        if ( 0 !== $rc || strpos( $out, 'HPOS-TABLES-OK' ) === false ) { aa_mig_fail( "HPOS table creation rc=$rc:\n$out" ); }
        echo "schema: HPOS order tables created\n";
} finally {
        if ( $hidden ) {
                rename( $dbphpBk, $dbphp );
                echo "drop-in db.php restored\n";
        }
}

/* ------------------------------------------------------------- row copy */

$skipTables = array( 'sqlite_sequence' );
$sqlTables  = array();
foreach ( $sq->query( "SELECT name FROM sqlite_master WHERE type='table'" ) as $row ) {
        $name = $row['name'];
        if ( in_array( $name, $skipTables, true ) || strpos( $name, '_wp_sqlite' ) === 0 ) { continue; }
        $sqlTables[] = $name;
}
sort( $sqlTables );

$myTables = array();
foreach ( $mx->query( 'SHOW TABLES' ) as $row ) { $myTables[] = reset( $row ); }
$mySet = array_flip( $myTables );

$missing = array();
foreach ( $sqlTables as $t ) {
        if ( ! isset( $mySet[ $t ] ) ) { $missing[] = $t; }
}

/* Stray tables (from removed plugins or legacy drop-in bookkeeping):
 * empty ones are skipped; populated ones are recreated on MySQL from their
 * translated SQLite schema so no data is lost. */
foreach ( $missing as $t ) {
        $rows = (int) $sq->query( "SELECT COUNT(*) FROM `$t`" )->fetchColumn();
        if ( 0 === $rows ) {
            $sqlTables = array_values( array_diff( $sqlTables, array( $t ) ) );
            echo "stray table $t is empty on sqlite side - skipped\n";
                continue;
        }
        $create = '';
        foreach ( $sq->query( "SELECT sql FROM sqlite_master WHERE type='table' AND name=" . $sq->quote( $t ) ) as $r ) { $create = $r['sql']; }
        if ( ! $create ) { aa_mig_fail( "table $t missing on mysql and has no sqlite schema" ); }
        // Mechanical SQLite -> MySQL translation (data types + autoincrement).
        $create = preg_replace( '/\s+INTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT/i', ' BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', $create );
        $create = preg_replace( '/\bINTEGER\b/i', 'BIGINT', $create );
        $create = preg_replace( '/\bBLOB\b/i', 'LONGBLOB', $create );
        $create = preg_replace( '/\bTEXT\b/i', 'TEXT', $create );
        $create = str_replace( 'AUTOINCREMENT', '', $create );
        try {
                $mx->exec( $create );
                $mySet[ $t ] = true;
                echo "created missing table $t on mysql from sqlite schema ($rows rows to copy)\n";
        } catch ( PDOException $e ) {
                aa_mig_fail( "cannot recreate table $t on mysql: " . $e->getMessage() . "\n" . $create );
        }
}

foreach ( $sqlTables as $t ) {
        if ( ! isset( $mySet[ $t ] ) ) { aa_mig_fail( "table missing on mysql side (activate the owning plugins!): $t" ); }
}

echo 'copying ' . count( $sqlTables ) . " tables...\n";
$mx->exec( 'SET FOREIGN_KEY_CHECKS = 0' );
$mx->exec( 'SET UNIQUE_CHECKS = 0' );

$copied = array();
foreach ( $sqlTables as $table ) {
        // columns both sides have (sqlite may carry drop-in bookkeeping columns)
        $sqCols = array();
        foreach ( $sq->query( "PRAGMA table_info(`$table`)" ) as $c ) { $sqCols[] = $c['name']; }
        $mxCols = array();
        foreach ( $mx->query( "SHOW COLUMNS FROM `$table`" ) as $c ) { $mxCols[] = $c['Field']; }
        $cols = array_values( array_intersect( $sqCols, $mxCols ) );
        if ( ! $cols ) { echo "  $table: no common columns, skipped\n"; continue; }

        $count = (int) $sq->query( "SELECT COUNT(*) FROM `$table`" )->fetchColumn();
        $mx->exec( "DELETE FROM `$table`" );
        if ( 0 === $count ) { $copied[ $table ] = 0; echo "  $table: 0 rows\n"; continue; }

        $colList = '`' . implode( '`, `', $cols ) . '`';
        $ph      = implode( ', ', array_fill( 0, count( $cols ), '?' ) );
        $stmt    = $mx->prepare( "INSERT INTO `$table` ($colList) VALUES ($ph)" );

        $sel = $sq->prepare( "SELECT $colList FROM `$table`" );
        $sel->execute();
        $done = 0;
        do {
                $mx->beginTransaction();
                $rows = $sel->fetchAll( PDO::FETCH_ASSOC );
                foreach ( $rows as $r ) {
                        $vals = array();
                        foreach ( $cols as $c ) { $vals[] = isset( $r[ $c ] ) ? $r[ $c ] : null; }
                        $stmt->execute( $vals );
                        $done++;
                }
                $mx->commit();
        } while ( count( $rows ) > 0 );

        $copied[ $table ] = $done;
        echo "  $table: $done rows\n";
}

$mx->exec( 'SET FOREIGN_KEY_CHECKS = 1' );
$mx->exec( 'SET UNIQUE_CHECKS = 1' );

/* ---------------------------------------------------------------- sanity */

$sanity = array(
        'posts'     => "SELECT COUNT(*) FROM wp_posts WHERE post_type='product' AND post_status='publish'",
        'options'   => 'SELECT COUNT(*) FROM wp_options',
        'users'     => 'SELECT COUNT(*) FROM wp_users',
);
echo "sanity:\n";
foreach ( $sanity as $label => $q ) {
        echo '  ' . $label . ': ' . $mx->query( $q )->fetchColumn() . "\n";
}

// Transient caches only carry stale paths (e.g. the retired sqlite plugin)
// and regenerate themselves on the next request - purge them so production
// dumps and boots start clean.
$purged = $mx->exec(
        "DELETE FROM wp_options WHERE option_name LIKE '\\_transient\\_%' OR option_name LIKE '\\_site\\_transient\\_%' OR option_name = 'recently_activated' OR option_name = 'wc_installing'"
);
echo "transients purged: " . ( $purged === false ? 0 : $purged ) . " rows\n";

$total = array_sum( $copied );
echo "Migration complete: " . count( $copied ) . " tables, $total rows copied from sqlite to mysql.\n";
