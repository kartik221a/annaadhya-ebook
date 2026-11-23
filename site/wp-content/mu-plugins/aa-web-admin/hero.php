<?php
/**
 * Home Hero: the admin picks ONE published book from a searchable list and
 * the home page hero shows that book — the cover image, the price and the
 * "shop now" link are all pulled from the WooCommerce product automatically.
 * There is nothing to upload and nothing to copy: selecting the book IS the
 * whole configuration. "Restore default" brings the bundled hero design back.
 *
 * Storage: option aa_hero = array( 'product_id' => N ). Rendered by
 * aa_home_hero_featured() in aa-store-tweaks.php (swap is home-page only).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_hero_router() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                aa_admin_hero_handle_post(); // redirects + exits on every branch
        }
        aa_admin_hero_page();
}

function aa_admin_hero_current_id() {
        $hero = get_option( 'aa_hero', array() );
        return ( is_array( $hero ) && isset( $hero['product_id'] ) ) ? absint( $hero['product_id'] ) : 0;
}

/** Clean price display: plain text + minimal HTML (no WC accessibility clutter). */
function aa_admin_hero_price( $product ) {
        $now = (float) $product->get_price( 'edit' );
        $reg = (float) $product->get_regular_price( 'edit' );
        if ( $now <= 0 ) { return array( 'text' => wp_strip_all_tags( $product->get_price_html() ), 'html' => $product->get_price_html() ); }
        $now_h = wp_strip_all_tags( wc_price( $now ) );
        if ( $reg > 0 && $now < $reg ) {
                return array(
                        'text' => $now_h . ' (was ' . wp_strip_all_tags( wc_price( $reg ) ) . ')',
                        'html' => '<ins>' . wc_price( $now ) . '</ins> <del>' . wc_price( $reg ) . '</del>',
                );
        }
        return array( 'text' => $now_h, 'html' => wc_price( $now ) );
}

function aa_admin_hero_row( $product, $current_pid ) {
        $pid   = $product->get_id();
        $name  = $product->get_name();

        $cover = aa_cover_url( $pid, 'medium' );

        $author = (string) get_post_meta( $pid, '_aa_author', true );
        $price  = aa_admin_hero_price( $product );

        $sub = array();
        if ( '' !== $author ) { $sub[] = $author; }
        if ( $price['text'] ) { $sub[] = $price['text']; }
        $sub = $sub ? implode( ' · ', $sub ) : '—';

        return '<label class="aa-hero-book" data-title="' . esc_attr( $name ) . '" data-price="' . esc_attr( $price['html'] ) . '" data-cover="' . esc_attr( $cover ) . '" data-link="' . esc_attr( get_permalink( $pid ) ) . '">'
                . '<input type="radio" name="aa_product" value="' . (int) $pid . '" required ' . checked( $pid, $current_pid, false ) . '>'
                . '<img src="' . esc_url( $cover ) . '" alt="' . esc_attr( $name ) . ' cover">'
                . '<span class="aa-hero-bt"><b>' . esc_html( $name ) . '</b><span>' . esc_html( $sub ) . '</span></span>'
                . '</label>';
}

function aa_admin_hero_page() {
        if ( ! function_exists( 'wc_get_product' ) ) {
                aa_admin_layout( 'Home Hero', '<div class="card"><p class="muted">WooCommerce is not active — the hero picker needs it.</p></div>', 'hero' );
                return;
        }

        $current_pid = aa_admin_hero_current_id();
        $current     = $current_pid ? wc_get_product( $current_pid ) : null;
        if ( $current && 'publish' !== $current->get_status() ) { $current = null; }

        $books = wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) );
        usort( $books, function ( $a, $b ) { return strcasecmp( $a->get_name(), $b->get_name() ); } );

        // Preview defaults to the book that is live right now (JS updates it live).
        $p_pid   = $current ? $current->get_id() : 0;
        $p_name  = $current ? $current->get_name() : '';
        $p_cover = $p_pid ? aa_cover_url( $p_pid, 'medium' ) : '';
        $p_price = $current ? aa_admin_hero_price( $current ) : array( 'text' => '', 'html' => '' );
        $p_price = $p_price['html'];
        $p_link  = $p_pid ? get_permalink( $p_pid ) : '';

        ob_start();
        ?>
        <div class="dash-cols">
                <div class="card">
                        <h2>Feature a book from the store</h2>
                        <p class="muted small" style="margin-top:-6px;margin-bottom:14px">Pick <b>one</b> book below. The cover image, the price and the link on the home page hero are pulled from that book automatically — there is nothing to upload.</p>
                        <form method="post" action="<?php echo esc_url( aa_admin_url( 'hero' ) ); ?>">
                                <?php wp_nonce_field( 'aa_hero' ); ?>
                                <input type="hidden" name="aa_action" value="book">
                                <label class="search" style="max-width:none;width:100%;margin-bottom:14px">
                                        <?php echo aa_admin_icon( 'search' ); ?>
                                        <input type="text" id="aa-hero-search" placeholder="Search books by name or author…" autocomplete="off">
                                </label>
                                <div class="aa-hero-list" id="aa-hero-list">
                                        <?php foreach ( $books as $b ) { echo aa_admin_hero_row( $b, $current_pid ); } // phpcs:ignore ?>
                                        <div class="aa-hero-none">No book matches that search.</div>
                                </div>
                                <?php if ( ! $books ) : ?>
                                        <p class="muted small">No published books yet — add one under Books and it will appear here.</p>
                                <?php endif; ?>
                                <button class="btn pri" style="margin-top:14px" type="submit"><?php echo aa_admin_icon( 'image' ); ?> Save hero book</button>
                        </form>
                </div>
                <div style="display:flex;flex-direction:column;gap:16px">
                        <div class="card" id="aa-hero-preview">
                                <h2>Live preview</h2>
                                <div class="aa-prev" <?php echo $p_pid ? '' : 'style="display:none"'; ?>>
                                        <img class="aa-prev-img" src="<?php echo esc_url( $p_cover ); ?>" alt="<?php echo esc_attr( $p_name ); ?> cover">
                                        <div class="aa-prev-meta">
                                                <b class="aa-prev-title"><?php echo esc_html( $p_name ); ?></b>
                                                <div class="aa-prev-price"><?php echo wp_kses_post( $p_price ); ?></div>
                                                <a class="aa-prev-link" href="<?php echo esc_url( $p_link ); ?>" target="_blank" rel="noopener">View book page</a>
                                        </div>
                                </div>
                                <p class="muted aa-prev-empty" <?php echo $p_pid ? 'style="display:none"' : ''; ?>>The original bundled hero design is live. Pick a book on the left — this preview updates as you browse the list.</p>
                        </div>
                        <div class="card">
                                <h2>Currently live on the home page</h2>
                                <?php if ( $current ) : ?>
                                        <p class="muted" style="margin:0 0 4px">Featuring:</p>
                                        <p style="margin:0 0 4px"><b><?php echo esc_html( $current->get_name() ); ?></b></p>
                                        <p class="muted small" style="margin:0 0 14px">Its cover, price and product link appear in the hero automatically. Customers clicking the hero land on this book.</p>
                                        <form method="post" action="<?php echo esc_url( aa_admin_url( 'hero' ) ); ?>" data-confirm="Restore the original bundled hero? Your featured book will stop showing until you feature it again.">
                                                <?php wp_nonce_field( 'aa_hero' ); ?>
                                                <input type="hidden" name="aa_action" value="reset">
                                                <button class="btn ghost" type="submit">Restore the default hero</button>
                                        </form>
                                <?php else : ?>
                                        <p class="muted small" style="margin:0">The bundled default hero design is live — the big “Read Better. Grow Faster.” banner with the featured-book panel. Pick a book above to replace it; the panel’s cover, price and link will follow that book.</p>
                                <?php endif; ?>
                        </div>
                </div>
        </div>
        <?php
        aa_admin_layout( 'Home Hero', ob_get_clean(), 'hero' );
}

function aa_admin_hero_handle_post() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_hero' ) ) {
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=denied' );
                exit;
        }

        $action = sanitize_key( $_POST['aa_action'] ?? '' );

        if ( 'reset' === $action ) {
                delete_option( 'aa_hero' );
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_reset' );
                exit;
        }

        if ( 'book' === $action ) {
                $pid     = absint( $_POST['aa_product'] ?? 0 );
                $product = $pid ? wc_get_product( $pid ) : null;

                if ( ! $product || 'publish' !== $product->get_status() ) {
                        wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_error' );
                        exit;
                }

                update_option( 'aa_hero', array( 'product_id' => $pid ), false );
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_book_saved' );
                exit;
        }

        wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=error' );
        exit;
}
