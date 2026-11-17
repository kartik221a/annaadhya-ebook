<?php
/**
 * Annaadhya: one-shot SQLite -> MySQL data migration.
 *
 * Reads the site's SQLite database (the drop-in DB used until now) and imports
 * every table, row and index into the MySQL database configured via
 * WORDPRESS_DB_* / DB_* environment variables (see wp-config.php).
 *
 * The SQLite file is ONLY read - it is never modified or deleted, so it keeps
 * working as a seed snapshot for fresh clones of this repository.
 *
 * Safe to run any number of times:
 *   - If MySQL already has the WordPress tables, this is a no-op.
 *   - If something fails midway, nothing is archived and the SQLite site keeps working.
 *
 * Run inside the web container (docker compose exec web php tools/migrate-sqlite-to-mysql.php)
 * or from the entrypoint on every boot - it exits in milliseconds when there is nothing to do.
 *
 * Flags:
 *   --wipe   Drop all existing wp_* tables in MySQL first (re-migrate from scratch).
 *   --quiet  Only print warnings and the final summary.
 */

if ( PHP_SAPI !== 'cli' ) {
        die( "CLI only\n" );
}

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

$quiet      = in_array( '--quiet', $argv, true );
$wipe       = in_array( '--wipe', $argv, true );
/* Site root = the directory that holds wp-config.php. In the web container the
 * layout is /var/www/html/tools + /var/www/html/wp-content; in this repo the
 * site lives one level deeper (site/), so probe both. */
$repo_root = null;
foreach ( array( dirname( __DIR__ ), dirname( __DIR__ ) . '/site' ) as $candidate ) {
        if ( is_file( $candidate . '/wp-config.php' ) ) {
                $repo_root = $candidate;
                break;
        }
}
if ( ! $repo_root ) {
        $repo_root = dirname( __DIR__ );
}
$log        = static function ( string $msg ) use ( $quiet ) {
        if ( ! $quiet ) {
                echo '[migrate] ' . $msg . "\n";
        }
};
$warn       = static function ( string $msg ) {
        echo '[migrate] WARNING: ' . $msg . "\n";
};

/* -------------------------------------------------------------------------
 * 1. Resolve the MySQL target connection.
 * ------------------------------------------------------------------------- */

$db_host   = getenv( 'WORDPRESS_DB_HOST' )   ?: getenv( 'DB_HOST' )   ?: 'db';
$db_name   = getenv( 'WORDPRESS_DB_NAME' )   ?: getenv( 'DB_NAME' )   ?: 'wordpress';
$db_user   = getenv( 'WORDPRESS_DB_USER' )   ?: getenv( 'DB_USER' )   ?: 'wp';
$db_pass   = getenv( 'WORDPRESS_DB_PASSWORD' ) ?: getenv( 'DB_PASSWORD' ) ?: 'wp';

$mysql_port = 3306;
if ( preg_match( '/^(.*):(\d{1,5})$/', $db_host, $m ) ) {
        $db_host    = $m[1];
        $mysql_port = (int) $m[2];
}

/* -------------------------------------------------------------------------
 * 2. Locate the SQLite database file.
 * ------------------------------------------------------------------------- */

$sqlite_candidates = array(
        $repo_root . '/wp-content/database/.ht.sqlite',
        $repo_root . '/wp-content/database/.ht.*/.ht.sqlite',
);
$sqlite_file = null;
foreach ( $sqlite_candidates as $pattern ) {
        foreach ( glob( $pattern ) ?: array() as $candidate ) {
                if ( is_file( $candidate ) && filesize( $candidate ) > 0 ) {
                        $sqlite_file = $candidate;
                        break 2;
                }
        }
}

/* -------------------------------------------------------------------------
 * 3. Connect to MySQL (retry: the entrypoint may run us while the db
 *    container is still finishing its first-boot init).
 * ------------------------------------------------------------------------- */

/* Read the table prefix from wp-config.php (default wp_). */
$prefix = 'wp_';
$wp_config = $repo_root . '/wp-config.php';
if ( is_file( $wp_config ) ) {
        if ( preg_match( '/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]/', (string) file_get_contents( $wp_config ), $m ) ) {
                $prefix = $m[1];
        }
}
$users_table = $prefix . 'users';

function aa_connect( string $dsn_without_db, ?string $db_name, string $db_user, string $db_pass ): PDO {
        $dsn = $db_name ? "{$dsn_without_db};dbname={$db_name}" : $dsn_without_db;
        try {
                return new PDO( $dsn, $db_user, $db_pass, array(
                        PDO::ATTR_ERRMODE                => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT                => 3,
                        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                ) );
        } catch ( PDOException $e ) {
                /* Unknown database (e.g. hand-created MySQL server): create it. */
                if ( $db_name && false !== strpos( $e->getMessage(), '1049' ) ) {
                        $mysql = new PDO( $dsn_without_db, $db_user, $db_pass, array(
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_TIMEOUT => 3,
                        ) );
                        $mysql->exec( 'CREATE DATABASE IF NOT EXISTS `' . str_replace( '`', '', $db_name ) . "`
                                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci" );
                        $mysql->exec( 'USE `' . str_replace( '`', '', $db_name ) . '`' );
                        return $mysql;
                }
                throw $e;
        }
}

$mysql = null;
$dsn_base = sprintf( 'mysql:host=%s;port=%d;charset=utf8mb4', $db_host, $mysql_port );
for ( $attempt = 1; $attempt <= 30; $attempt++ ) {
        try {
                $mysql = aa_connect( $dsn_base, $db_name, $db_user, $db_pass );
                break;
        } catch ( PDOException $e ) {
                if ( 30 === $attempt ) {
                        $warn( "Cannot connect to MySQL at {$db_host}:{$mysql_port} (db '{$db_name}', user '{$db_user}'): " . $e->getMessage() );
                        exit( 1 );
                }
                if ( 1 === $attempt ) {
                        $log( "MySQL not ready yet at {$db_host}:{$mysql_port}, retrying (up to 60s)..." );
                }
                sleep( 2 );
        }
}
$log( "Connected to MySQL at {$db_host}:{$mysql_port}, database '{$db_name}'." );

/* Relax this session only: WordPress schema has zero-date defaults and the
 * data can contain '0000-00-00 00:00:00' values (Action Scheduler), which
 * MySQL's strict default sql_mode would reject on import. */
$mysql->exec( "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci, sql_mode = '', FOREIGN_KEY_CHECKS = 0" );

$existing_tables = array();
foreach ( $mysql->query( 'SHOW TABLES' )->fetchAll( PDO::FETCH_COLUMN ) as $t ) {
        $existing_tables[ $t ] = true;
}

/* -------------------------------------------------------------------------
 * 4. Short-circuit when MySQL is already migrated.
 * ------------------------------------------------------------------------- */

if ( isset( $existing_tables[ $users_table ] ) && ! $wipe ) {
        $count = (int) $mysql->query( "SELECT COUNT(*) FROM `{$users_table}`" )->fetchColumn();
        $log( "MySQL already migrated ({$users_table}: {$count} users). Nothing to do." );
        exit( 0 );
}

if ( ! empty( $existing_tables ) && ! $wipe ) {
        $sample = implode( ', ', array_slice( array_keys( $existing_tables ), 0, 5 ) );
        $warn( "MySQL database '{$db_name}' is not empty ({$sample}...) but has no '{$users_table}' table." );
        $warn( 'Re-run with --wipe to drop those tables and re-migrate.' );
        exit( 1 );
}

if ( ! $sqlite_file ) {
        $log( 'No SQLite database found under wp-content/database/. WordPress will install fresh on MySQL.' );
        exit( 0 );
}

$log( "Source SQLite file: {$sqlite_file}" );

if ( $wipe ) {
        $log( '--wipe requested: dropping all existing tables in MySQL.' );
        foreach ( array_keys( $existing_tables ) as $t ) {
                $mysql->exec( "DROP TABLE IF EXISTS `{$t}`" );
        }
        $existing_tables = array();
}

/* -------------------------------------------------------------------------
 * 5. Open the SQLite database from a writable temp copy (so any -wal file
 *    can be replayed without touching the original).
 * ------------------------------------------------------------------------- */

$tmp_dir = sys_get_temp_dir() . '/aa-migrate-' . getmypid();
if ( is_dir( $tmp_dir ) ) {
        rrmdir( $tmp_dir );
}
mkdir( $tmp_dir, 0700, true );
foreach ( glob( dirname( $sqlite_file ) . '/.ht.sqlite*' ) as $part ) {
        copy( $part, $tmp_dir . '/' . basename( $part ) );
}
$sqlite = new PDO( 'sqlite:' . $tmp_dir . '/.ht.sqlite', null, null, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
) );
$sqlite->exec( 'PRAGMA busy_timeout = 5000' );

/* -------------------------------------------------------------------------
 * 6. Build the MySQL DDL from the metadata the SQLite drop-in preserved.
 *    (wp-content/_wp_sqlite_mysql_information_schema_* mirrors the original
 *    MySQL information_schema, including varchar lengths, defaults,
 *    auto_increment flags and full index definitions.)
 * ------------------------------------------------------------------------- */

$real_tables = array();
foreach ( $sqlite->query( "SELECT name FROM sqlite_master WHERE type='table'" ) as $row ) {
        /* Drop the drop-in's own metadata tables and sqlite_sequence.
         * Filtered in PHP: SQLite's LIKE has no default escape char, so a
         * backslash-escaped pattern would silently match nothing. */
        $name = $row['name'];
        if ( 'sqlite_sequence' === $name || str_starts_with( $name, '_wp_sqlite' ) ) {
                continue;
        }
        $real_tables[] = $name;
}
sort( $real_tables );

$meta_cols = array();
foreach ( $sqlite->query( 'SELECT * FROM _wp_sqlite_mysql_information_schema_columns ORDER BY TABLE_NAME, ORDINAL_POSITION' ) as $c ) {
        $meta_cols[ $c['TABLE_NAME'] ][] = $c;
}
$meta_tables = array();
foreach ( $sqlite->query( 'SELECT * FROM _wp_sqlite_mysql_information_schema_tables' ) as $t ) {
        $meta_tables[ $t['TABLE_NAME'] ] = $t;
}
$meta_stats = array();
foreach ( $sqlite->query( 'SELECT * FROM _wp_sqlite_mysql_information_schema_statistics ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX' ) as $s ) {
        $meta_stats[ $s['TABLE_NAME'] ][ $s['INDEX_NAME'] ][] = $s;
}
$auto_increment_meta = array();
foreach ( $sqlite->query( 'SELECT name, seq FROM sqlite_sequence' ) as $s ) {
        $auto_increment_meta[ $s['name'] ] = (int) $s['seq'];
}

$mysql->exec( 'USE `' . str_replace( '`', '', $db_name ) . '`' );

$text_types = array( 'text', 'longtext', 'mediumtext', 'tinytext', 'blob', 'longblob', 'mediumblob', 'tinyblob', 'json' );

foreach ( $real_tables as $table ) {
        if ( empty( $meta_cols[ $table ] ) ) {
                $warn( "No column metadata for table '{$table}' - skipping it (site may recreate it automatically)." );
                continue;
        }
        $lines = array();
        $pk_cols = array();

        /* Columns */
        foreach ( $meta_cols[ $table ] as $col ) {
                $line = '`' . $col['COLUMN_NAME'] . '` ' . strtoupper( $col['COLUMN_TYPE'] );

                if ( 'NO' === $col['IS_NULLABLE'] ) {
                        $line .= ' NOT NULL';
                }
                if ( null !== $col['COLUMN_DEFAULT'] ) {
                        $default = $col['COLUMN_DEFAULT'];
                        if ( strcasecmp( $default, 'CURRENT_TIMESTAMP' ) === 0 ) {
                                $line .= ' DEFAULT CURRENT_TIMESTAMP';
                        } else {
                                $line .= " DEFAULT '" . addcslashes( $default, "\\'" ) . "'";
                        }
                }
                if ( false !== stripos( (string) $col['EXTRA'], 'auto_increment' ) ) {
                        $line .= ' AUTO_INCREMENT';
                }
                if ( preg_match( '/on\s+update\s+current_timestamp(\(\))?/i', (string) $col['EXTRA'] ) ) {
                        $line .= ' ON UPDATE CURRENT_TIMESTAMP';
                }
                if ( false !== stripos( (string) $col['EXTRA'], 'auto_increment' ) ) {
                        $pk_cols = array( $col['COLUMN_NAME'] ); // auto_increment implies single-column PK
                }
                $lines[] = $line;
        }

        /* Indexes (PRIMARY key + secondary keys, with prefix lengths) */
        if ( ! empty( $meta_stats[ $table ] ) ) {
                foreach ( $meta_stats[ $table ] as $index_name => $parts ) {
                        $cols = array();
                        foreach ( $parts as $p ) {
                                $segment = '`' . $p['COLUMN_NAME'] . '`';
                                $type    = '';
                                foreach ( $meta_cols[ $table ] as $c ) {
                                        if ( $c['COLUMN_NAME'] === $p['COLUMN_NAME'] ) {
                                                $type = strtolower( explode( '(', (string) $c['COLUMN_TYPE'] )[0] );
                                                break;
                                        }
                                }
                                $prefix_len = $p['SUB_PART'];
                                if ( in_array( $type, $text_types, true ) && empty( $prefix_len ) ) {
                                        $prefix_len = 191;
                                }
                                if ( $prefix_len ) {
                                        $segment .= "({$prefix_len})";
                                }
                                $cols[] = $segment;
                        }
                        if ( 'PRIMARY' === $index_name ) {
                                $lines[] = 'PRIMARY KEY (' . implode( ', ', $cols ) . ')';
                                if ( empty( $pk_cols ) ) {
                                        $pk_cols = array_map( static fn( $c ) => trim( $c, '`' ), $cols );
                                }
                        } elseif ( ! ( (int) $parts[0]['NON_UNIQUE'] ) ) {
                                $lines[] = 'UNIQUE KEY `' . $index_name . '` (' . implode( ', ', $cols ) . ')';
                        } else {
                                $lines[] = 'KEY `' . $index_name . '` (' . implode( ', ', $cols ) . ')';
                        }
                }
        }

        $collation = $meta_tables[ $table ]['TABLE_COLLATION'] ?? 'utf8mb4_unicode_520_ci';
        $create    = "CREATE TABLE `{$table}` (\n  " . implode( ",\n  ", $lines )
                . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$collation}";

        create_table_with_fallback( $mysql, $create, $table, $log );
        $log( "Created table {$table} (" . count( $meta_cols[ $table ] ) . ' columns)' );
}

/* -------------------------------------------------------------------------
 * 7. Copy the data, table by table.
 * ------------------------------------------------------------------------- */

$log( 'Importing data...' );
$total_rows = 0;
$row_counts = array();

foreach ( $real_tables as $table ) {
        if ( empty( $meta_cols[ $table ] ) ) {
                continue;
        }
        $columns   = array_map( static fn( $c ) => $c['COLUMN_NAME'], $meta_cols[ $table ] );
        $col_list  = implode( ', ', array_map( static fn( $c ) => '`' . $c . '`', $columns ) );
        $placeholders = implode( ', ', array_fill( 0, count( $columns ), '?' ) );

        $stmt = $mysql->prepare( "INSERT INTO `{$table}` ({$col_list}) VALUES ({$placeholders})" );

        $rows = 0;
        $mysql->beginTransaction();
        foreach ( $sqlite->query( "SELECT * FROM \"{$table}\"" ) as $row ) {
                $values = array();
                foreach ( $columns as $column ) {
                        $value = $row[ $column ];
                        if ( is_resource( $value ) ) {
                                $value = stream_get_contents( $value );
                        }
                        $values[] = $value;
                }
                $stmt->execute( $values );
                $rows++;
        }
        $mysql->commit();

        $row_counts[ $table ] = $rows;
        $total_rows          += $rows;
        if ( $rows > 0 ) {
                $log( sprintf( '  %-44s %6d rows', $table, $rows ) );
        }
}

/* -------------------------------------------------------------------------
 * 8. Restore AUTO_INCREMENT counters so re-used/deleted IDs keep their gaps.
 * ------------------------------------------------------------------------- */

foreach ( $real_tables as $table ) {
        if ( empty( $meta_cols[ $table ] ) ) {
                continue;
        }
        $has_auto = false;
        foreach ( $meta_cols[ $table ] as $c ) {
                if ( false !== stripos( (string) $c['EXTRA'], 'auto_increment' ) ) {
                        $has_auto = true;
                        break;
                }
        }
        if ( ! $has_auto ) {
                continue;
        }
        $seq   = $auto_increment_meta[ $table ] ?? 0;
        $maxid = (int) $mysql->query( "SELECT COALESCE(MAX(`{$meta_cols[ $table ][0]['COLUMN_NAME']}`), 0) FROM `{$table}`" )->fetchColumn();
        $next  = max( $seq, $maxid ) + 1;
        $mysql->exec( "ALTER TABLE `{$table}` AUTO_INCREMENT = {$next}" );
}

/* -------------------------------------------------------------------------
 * 9. Verify: row counts must match exactly.
 * ------------------------------------------------------------------------- */

$mismatch = 0;
foreach ( $real_tables as $table ) {
        if ( empty( $meta_cols[ $table ] ) ) {
                continue;
        }
        $mysql_count = (int) $mysql->query( "SELECT COUNT(*) FROM `{$table}`" )->fetchColumn();
        if ( $mysql_count !== ( $row_counts[ $table ] ?? 0 ) ) {
                $warn( "Row count mismatch for {$table}: sqlite=" . ( $row_counts[ $table ] ?? 0 ) . " mysql={$mysql_count}" );
                $mismatch++;
        }
}
if ( $mismatch ) {
        $warn( "{$mismatch} table(s) mismatched - MySQL kept as-is, SQLite site untouched. Fix and re-run." );
        exit( 1 );
}

rrmdir( $tmp_dir );

/* -------------------------------------------------------------------------
 * 10. Done.
 * ------------------------------------------------------------------------- */

$log( '' );
$log( '==============================================' );
$log( "Migration complete: {$total_rows} rows across " . count( $row_counts ) . ' tables imported into MySQL.' );
$log( "  MySQL: {$db_host}:{$mysql_port} / db '{$db_name}' / user '{$db_user}'" );
$log( '  The SQLite file was left untouched (it stays as the seed snapshot in git).' );
$log( '  Next boot WordPress will serve from MySQL.' );
$log( '==============================================' );
exit( 0 );

/* ---------------------------------------------------------------------- */

function create_table_with_fallback( PDO $mysql, string $create, string $table, callable $log ): void {
        try {
                $mysql->exec( $create );
                return;
        } catch ( PDOException $e ) {
                // e.g. SQLSTATE[42000] 1067 Invalid default value (zero dates under strict sql_mode)
                if ( false === strpos( $e->getMessage(), '1067' ) ) {
                        throw $e;
                }
        }
        $log( "  ({$table}: zero-date defaults rejected, retrying without them)" );
        $stripped = preg_replace( "/\s+DEFAULT\s+'0000-00-00(\s+00:00:00)?'/i", '', $create );
        $mysql->exec( $stripped );
}

function rrmdir( string $dir ): void {
        foreach ( glob( $dir . '/*' ) ?: array() as $item ) {
                is_dir( $item ) ? rrmdir( $item ) : unlink( $item );
        }
        @rmdir( $dir );
}
