<?php
/**
 * Plugin Name: Annaadhya Store Tweaks (must-use)
 * Description: Stitch "Obsidian & Gold" storefront plumbing: canvas renderer for all core pages (home, shop, search, product, cart, checkout, account, library, about, contact, legal), [aa_books] product grid, [aa_product_single], [aa_library], [aa_contact_form], wishlist (account endpoint + AJAX), newsletter + contact AJAX handlers, cart badge.
 * Version:     2.0.0
 * Author:      Annaadhya
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ------------------------------------------------------------------------
 * Canvas renderer
 *
 * Storefront pages are authored as self-contained Elementor "html" widgets
 * (tools/set_stitch_pages.php + tools/build_pages_v2.php) and printed through
 * a minimal canvas shell of our own, bypassing Astra/Woo archive chrome.
 * ---------------------------------------------------------------------- */

add_filter( 'template_include', 'aa_canvas_for_storefront', 99 );

function aa_canvas_for_storefront( $tpl ) {
        if ( is_admin() || ! is_main_query() ) { return $tpl; }

        if ( function_exists( 'wc_get_page_id' ) ) {
                $front = (int) get_option( 'page_on_front' );
                if ( $front && is_page( $front ) ) {
                        aa_render_canvas( $front );
                }

                $shop = (int) wc_get_page_id( 'shop' );
                if ( $shop > 0 && is_shop() ) {
                        aa_render_canvas( $shop );
                }
                // Site search (?s=) renders through the shop canvas — its [aa_books]
                // grid reads the search term, so results keep the Stitch shell.
                if ( $shop > 0 && is_search() && '' !== get_search_query( false ) ) {
                        aa_render_canvas( $shop );
                }

                $cart_id = (int) wc_get_page_id( 'cart' );
                if ( $cart_id > 0 && is_cart() ) {
                        aa_render_canvas( $cart_id );
                }

                // [woocommerce_checkout] itself renders order-pay + order-received views.
                $co = (int) wc_get_page_id( 'checkout' );
                if ( $co > 0 && is_checkout() ) {
                        aa_render_canvas( $co );
                }

                $acc = (int) wc_get_page_id( 'myaccount' );
                if ( $acc > 0 && is_account_page() ) {
                        aa_render_canvas( $acc );
                }

                // Every single-product URL renders through one hidden template page
                // whose [aa_product_single] shortcode reads the queried product.
                $pd = (int) get_option( 'aa_product_page', 0 );
                if ( $pd > 0 && is_product() ) {
                        $GLOBALS['aa_current_product'] = get_queried_object_id();
                        aa_render_canvas( $pd );
                }
        }

        // Any normal page flagged with _aa_canvas renders through our shell.
        if ( is_page() ) {
                $pid = get_queried_object_id();
                if ( $pid && get_post_meta( $pid, '_aa_canvas', true ) ) {
                        aa_render_canvas( $pid );
                }
        }

        return $tpl;
}

/** Print a minimal canvas shell + the Elementor content of one page. */
function aa_render_canvas( $post_id ) {
        if ( ! get_post( $post_id ) ) { return; }
        if ( ! class_exists( '\Elementor\Plugin' ) ) { return; }

        status_header( 200 );
        nocache_headers();

        echo '<!DOCTYPE html><html ';
        language_attributes();
        echo '><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        wp_head();
        echo '</head><body ';
        body_class( 'aa-canvas' );
        echo '>';
        wp_body_open();
        echo '<div class="elementor elementor-' . esc_attr( $post_id ) . '">';

        // get_builder_content() (not _for_display) — the display variant bails out
        // with an "avoid recursion" guard when get_the_ID() equals the rendered ID,
        // which is exactly our case (we render the queried page itself).
        echo \Elementor\Plugin::$instance->frontend->get_builder_content( $post_id, false );

        echo '</div>';
        wp_footer();
        echo '</body></html>';
        exit;
}

/* ------------------------------------------------------------------------
 * Cart badge plumbing for the custom Stitch header.
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_add_to_cart_fragments', 'aa_cart_count_fragment' );

function aa_cart_count_fragment( $fragments ) {
        $count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
        $hidden = $count < 1 ? ' hidden' : '';
        $fragments['[data-aa-cartcount]'] = '<span class="aa-cartcount" data-aa-cartcount' . $hidden . '>' . esc_html( $count ) . '</span>';
        return $fragments;
}

add_action( 'wc_ajax_aa_cart_count', 'aa_cart_count_endpoint' );

function aa_cart_count_endpoint() {
        $count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
        wp_send_json( array( 'count' => $count ) );
}

/* ------------------------------------------------------------------------
 * [aa_books limit="6" category="" paginate="no"]
 * Stitch-style book cards from live WooCommerce products.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_books', 'aa_books_shortcode' );

function aa_books_shortcode( $atts ) {
        $a = shortcode_atts(
                array(
                        'limit'    => '6',
                        'category' => '',
                        'paginate' => 'no',
                        'orderby'  => 'menu_order title',
                        'order'    => 'ASC',
                        'exclude'  => '',
                ),
                $atts,
                'aa_books'
        );

        $args = array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'posts_per_page'      => (int) $a['limit'],
                'orderby'             => $a['orderby'],
                'order'               => $a['order'],
                'ignore_sticky_posts' => true,
                'no_found_rows'       => ( $a['paginate'] !== 'yes' ),
        );

        if ( 'yes' === $a['paginate'] ) {
                $args['paged'] = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
        }

        $s = isset( $_GET['book'] ) ? sanitize_text_field( wp_unslash( $_GET['book'] ) ) : '';
        if ( '' === $s && isset( $_GET['s'] ) ) {
                $s = sanitize_text_field( wp_unslash( $_GET['s'] ) );
        }
        if ( '' !== $s ) {
                $args['s'] = $s;
        }

        if ( '' !== $a['category'] ) {
                $args['tax_query'] = array(
                        array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'slug',
                                'terms'    => array_map( 'trim', explode( ',', $a['category'] ) ),
                        ),
                );
        }

        $exclude = array_filter( array_map( 'absint', explode( ',', $a['exclude'] ) ) );
        if ( $exclude ) {
                $args['post__not_in'] = $exclude;
        }

        $q = new WP_Query( $args );

        $cards = array();
        while ( $q->have_posts() ) {
                $q->the_post();
                $cards[] = aa_book_card( get_the_ID() );
        }
        wp_reset_postdata();

        if ( empty( $cards ) ) {
                $empty = '' === $s
                        ? 'No books on the shelf yet — new titles are added regularly.'
                        : 'No books matched your search. Try a different keyword.';
                return '<div class="aa-empty">' . esc_html( $empty ) . '</div>';
        }

        $html  = '<div class="aa-books"><ul class="aa-products">';
        $html .= implode( '', $cards );
        $html .= '</ul>';

        if ( 'yes' === $a['paginate'] && $q->max_num_pages > 1 ) {
                $links = paginate_links(
                        array(
                                'total'     => $q->max_num_pages,
                                'current'   => max( 1, (int) $args['paged'] ),
                                'mid_size'  => 1,
                                'format'    => '?paged=%#%',
                                'prev_text' => '<span class="aa-ms">arrow_back</span><span>Previous</span>',
                                'next_text' => '<span>Next</span><span class="aa-ms">arrow_forward</span>',
                                'type'      => 'list',
                        )
                );
                if ( $links ) {
                        $html .= '<nav class="aa-pager-wrap"><p class="aa-pager-info">Page <strong>' . esc_html( (int) $args['paged'] ) . '</strong> of <strong>' . esc_html( (int) $q->max_num_pages ) . '</strong></p>' . $links . '</nav>';
                }
        }

        $html .= '</div>';
        return $html;
}

/** Wishlist ids for the current user (empty array when guest). */
function aa_get_wishlist() {
        if ( ! is_user_logged_in() ) { return array(); }
        $wl = get_user_meta( get_current_user_id(), '_aa_wishlist', true );
        return is_array( $wl ) ? array_map( 'absint', $wl ) : array();
}

/** Render one Stitch-style book card for a product. */
function aa_book_card( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) { return ''; }

        $permalink = get_permalink( $product_id );
        $name      = get_the_title( $product_id );
        $img       = get_the_post_thumbnail_url( $product_id, 'large' );
        if ( ! $img ) { $img = wc_placeholder_img_src(); }

        $cat_terms = get_the_terms( $product_id, 'product_cat' );
        $cat       = '';
        if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
                foreach ( $cat_terms as $t ) {
                        if ( 'uncategorized' !== $t->slug ) { $cat = $t->name; break; }
                }
                if ( '' === $cat ) { $cat = $cat_terms[0]->name; }
        }

        $author = (string) get_post_meta( $product_id, '_aa_author', true );
        $badge  = (string) get_post_meta( $product_id, '_aa_badge', true );
        $format = (string) get_post_meta( $product_id, '_aa_format', true );
        if ( '' === $format ) { $format = 'PDF'; }
        $rating = (string) get_post_meta( $product_id, '_aa_rating', true );

        $regular = (float) $product->get_regular_price( 'edit' );
        $active  = (float) $product->get_price( 'edit' );
        $on_sale = $product->is_on_sale( 'edit' ) && $regular > 0 && $active < $regular;
        $save    = $on_sale ? round( ( 1 - $active / $regular ) * 100 ) : 0;

        if ( $on_sale ) {
                $price_html = '<del>' . wp_kses_post( wc_price( $regular ) ) . '</del><ins>' . wp_kses_post( wc_price( $active ) ) . '</ins>';
                $save_html  = 'Save ' . $save . '%';
        } else {
                $price_html = '<span class="aa-only">' . wp_kses_post( wc_price( $active ) ) . '</span>';
                $save_html  = '';
        }

        $stars = '';
        for ( $i = 0; $i < 5; $i++ ) { $stars .= '<span class="aa-ms aa-star">star</span>'; }
        $rate_row = $rating
                ? '<div class="aa-stars">' . $stars . '<span class="aa-rate">' . esc_html( $rating ) . '/5</span></div>'
                : '';

        $add_url  = add_query_arg( 'add-to-cart', $product_id, home_url( '/' ) );
        $cart_cls = $product->supports( 'ajax_add_to_cart' ) ? 'add_to_cart_button ajax_add_to_cart' : 'add_to_cart_button';
        $in_wish  = in_array( $product_id, aa_get_wishlist(), true );

        ob_start();
        ?>
<li class="aa-card" data-price="<?php echo esc_attr( $active ); ?>" data-title="<?php echo esc_attr( $name ); ?>">
        <div class="aa-media">
                <a class="aa-media-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $name ); ?>">
                        <img class="aa-cover" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?> ebook cover">
                </a>
                <button type="button" class="aa-wishbtn<?php echo $in_wish ? ' aa-on' : ''; ?>" data-id="<?php echo esc_attr( $product_id ); ?>" aria-label="Save to wishlist"><span class="aa-ms">favorite</span></button>
                <span class="aa-fmt"><?php echo esc_html( $format ); ?></span>
                <?php if ( $badge ) : ?>
                        <span class="aa-badge"><?php echo esc_html( $badge ); ?></span>
                <?php elseif ( $on_sale && $save >= 40 ) : ?>
                        <span class="aa-badge aa-badge-sale">SAVE <?php echo esc_html( $save ); ?>%</span>
                <?php endif; ?>
        </div>
        <div class="aa-meta">
                <span class="aa-mcat"><?php echo esc_html( strtoupper( $cat ) ); ?></span>
                <?php if ( $author ) : ?><span class="aa-mauth"><?php echo esc_html( $author ); ?></span><?php endif; ?>
        </div>
        <h3 class="aa-title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $name ); ?></a></h3>
        <?php echo $rate_row; // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <div class="aa-foot">
                <div class="aa-pricing">
                        <span class="aa-now"><?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                        <?php if ( $save_html ) : ?><span class="aa-save"><?php echo esc_html( $save_html ); ?></span><?php endif; ?>
                </div>
                <a class="aa-add <?php echo esc_attr( $cart_cls ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-quantity="1" href="<?php echo esc_url( $add_url ); ?>">
                        <span class="aa-ms">shopping_cart</span><span>Add to cart</span>
                </a>
        </div>
</li>
        <?php
        return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * Wishlist: AJAX toggle + My Account endpoint.
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_aa_wishlist_toggle', 'aa_wishlist_toggle_ajax' );
add_action( 'wp_ajax_nopriv_aa_wishlist_toggle', 'aa_wishlist_toggle_ajax' );

function aa_wishlist_toggle_ajax() {
        if ( ! is_user_logged_in() ) {
                wp_send_json( array( 'status' => 'login' ) );
        }
        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id || 'product' !== get_post_type( $id ) ) {
                wp_send_json( array( 'status' => 'error' ), 400 );
        }
        $uid = get_current_user_id();
        $wl  = aa_get_wishlist();
        $on  = true;
        $key = array_search( $id, $wl, true );
        if ( false !== $key ) {
                unset( $wl[ $key ] );
                $wl = array_values( $wl );
                $on = false;
        } else {
                $wl[] = $id;
        }
        update_user_meta( $uid, '_aa_wishlist', $wl );
        wp_send_json( array( 'status' => 'ok', 'on' => $on, 'count' => count( $wl ) ) );
}

add_action( 'init', 'aa_register_wishlist_endpoint' );

function aa_register_wishlist_endpoint() {
        add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES );
}

add_filter( 'woocommerce_account_menu_items', 'aa_wishlist_menu_item' );

function aa_wishlist_menu_item( $items ) {
        if ( isset( $items['downloads'] ) ) {
                $new = array();
                foreach ( $items as $k => $v ) {
                        $new[ $k ] = $v;
                        if ( 'downloads' === $k ) { $new['wishlist'] = __( 'Wishlist', 'aa' ); }
                }
                return $new;
        }
        $items['wishlist'] = __( 'Wishlist', 'aa' );
        return $items;
}

add_action( 'woocommerce_account_wishlist_endpoint', 'aa_wishlist_endpoint_content' );

function aa_wishlist_endpoint_content() {
        $wl = aa_get_wishlist();
        if ( empty( $wl ) ) {
                echo '<div class="aa-account-empty"><span class="aa-ms">favorite</span><h3>Nothing saved yet</h3><p>Tap the heart on any book cover to keep it here for later.</p><a class="aa-btn-gold" href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">Browse the Bookshelf</a></div>';
                return;
        }
        $q = new WP_Query(
                array(
                        'post_type'           => 'product',
                        'post_status'         => 'publish',
                        'post__in'            => $wl,
                        'orderby'             => 'post__in',
                        'posts_per_page'      => count( $wl ),
                        'ignore_sticky_posts' => true,
                )
        );
        echo '<div class="aa-books aa-wishlist-grid"><ul class="aa-products">';
        while ( $q->have_posts() ) {
                $q->the_post();
                echo aa_book_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
        }
        wp_reset_postdata();
        echo '</ul></div>';
}

/* ------------------------------------------------------------------------
 * [aa_product_single] — full product detail layout for the canvas shell.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_product_single', 'aa_product_single_shortcode' );

function aa_product_single_shortcode() {
        $pid = isset( $GLOBALS['aa_current_product'] ) ? (int) $GLOBALS['aa_current_product'] : (int) get_queried_object_id();
        $product = wc_get_product( $pid );
        if ( ! $product ) {
                return '<div class="aa-empty">Book not found.</div>';
        }

        $name    = get_the_title( $pid );
        $img     = get_the_post_thumbnail_url( $pid, 'full' );
        if ( ! $img ) { $img = wc_placeholder_img_src(); }

        $author  = (string) get_post_meta( $pid, '_aa_author', true );
        $format  = (string) get_post_meta( $pid, '_aa_format', true );
        if ( '' === $format ) { $format = 'PDF'; }
        $rating  = (string) get_post_meta( $pid, '_aa_rating', true );
        $pages   = (string) get_post_meta( $pid, '_aa_pages', true );
        $lang    = (string) get_post_meta( $pid, '_aa_lang', true );
        if ( '' === $lang ) { $lang = 'English'; }
        $badge   = (string) get_post_meta( $pid, '_aa_badge', true );

        $cat_terms = get_the_terms( $pid, 'product_cat' );
        $cat       = '';
        if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
                foreach ( $cat_terms as $t ) {
                        if ( 'uncategorized' !== $t->slug ) { $cat = $t->name; break; }
                }
                if ( '' === $cat ) { $cat = $cat_terms[0]->name; }
        }

        $regular = (float) $product->get_regular_price( 'edit' );
        $active  = (float) $product->get_price( 'edit' );
        $on_sale = $product->is_on_sale( 'edit' ) && $regular > 0 && $active < $regular;
        $save    = $on_sale ? round( ( 1 - $active / $regular ) * 100 ) : 0;

        if ( $on_sale ) {
                $price_html = '<del>' . wp_kses_post( wc_price( $regular ) ) . '</del><ins>' . wp_kses_post( wc_price( $active ) ) . '</ins>';
                $save_html  = '<span class="aa-savepill">Save ' . $save . '%</span>';
        } else {
                $price_html = '<span class="aa-now">' . wp_kses_post( wc_price( $active ) ) . '</span>';
                $save_html  = '';
        }

        $add_url  = add_query_arg( 'add-to-cart', $pid, home_url( '/' ) );
        $cart_cls = $product->supports( 'ajax_add_to_cart' ) ? 'add_to_cart_button ajax_add_to_cart' : 'add_to_cart_button';

        // Demo store: the bundled PDF doubles as the free sample chapter.
        $sample_url = '';
        $downloads  = $product->get_downloads();
        if ( $downloads ) {
                $dl         = reset( $downloads );
                $sample_url = $dl->get_file();
        }
        $in_wish = in_array( $pid, aa_get_wishlist(), true );

        $stars = '';
        for ( $i = 0; $i < 5; $i++ ) { $stars .= '<span class="aa-ms aa-star">star</span>'; }

        // Reviews (Woo comments on the product).
        $reviews = get_comments( array( 'post_id' => $pid, 'status' => 'approve', 'post_type' => 'product', 'number' => 10 ) );
        $rev_html = '';
        if ( $reviews ) {
                foreach ( $reviews as $rv ) {
                        $rev_html .= '<div class="aa-review"><div class="aa-review-top"><span class="aa-av">' . esc_html( strtoupper( substr( $rv->comment_author, 0, 1 ) ) ) . '</span><div><strong>' . esc_html( $rv->comment_author ) . '</strong><div class="aa-stars aa-stars-sm">' . $stars . '</div></div><span class="aa-when">' . esc_html( human_time_diff( strtotime( $rv->comment_date_gmt ) ) . ' ago' ) . '</span></div><p>' . esc_html( wp_strip_all_tags( $rv->comment_content ) ) . '</p></div>';
                }
        } else {
                $rev_html = '<div class="aa-review aa-review-none"><span class="aa-ms">rate_review</span><p>No reviews yet — be the first to share your reading experience.</p></div>';
        }

        // Related books: same category first, newest to fill.
        $related = array();
        $rel_q   = new WP_Query(
                array(
                        'post_type'           => 'product',
                        'post_status'         => 'publish',
                        'posts_per_page'      => 8,
                        'post__not_in'        => array( $pid ),
                        'ignore_sticky_posts' => true,
                        'no_found_rows'       => true,
                        'orderby'             => 'rand',
                )
        );
        while ( $rel_q->have_posts() ) {
                $rel_q->the_post();
                if ( count( $related ) < 4 ) { $related[] = get_the_ID(); }
        }
        wp_reset_postdata();
        $rel_html = '';
        foreach ( $related as $rid ) { $rel_html .= aa_book_card( $rid ); }

        $description = apply_filters( 'the_content', get_post_field( 'post_content', $pid ) );
        if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
                $description = '<p>' . esc_html( $name ) . ' — a carefully edited Annaadhya ebook, delivered as an instantly downloadable PDF optimized for phones, tablets, Kindle and iPads. Your purchase includes lifetime access and free future updates.</p>';
        }

        ob_start();
        ?>
<section class="aa-pdp">
        <div class="aa-wrap aa-pdp-grid">
                <div class="aa-pdp-media">
                        <div class="aa-pdp-coverwrap"><img class="aa-pdp-cover" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?> ebook cover"></div>
                </div>
                <div class="aa-pdp-info">
                        <nav class="aa-crumb"><a href="/">Home</a><span class="aa-ms">chevron_right</span><a href="/shop/">Shop</a><span class="aa-ms">chevron_right</span><span class="aa-cur"><?php echo esc_html( $cat ? $cat : 'Ebook' ); ?></span></nav>
                        <?php if ( $badge ) : ?><span class="aa-badge aa-badge-lg"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
                        <h1 class="aa-pdp-title"><?php echo esc_html( $name ); ?></h1>
                        <?php if ( $author ) : ?><p class="aa-pdp-author">by <strong><?php echo esc_html( $author ); ?></strong></p><?php endif; ?>
                        <div class="aa-pdp-rate">
                                <?php if ( $rating ) : ?>
                                        <div class="aa-stars"><?php echo $stars; // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="aa-rate"><?php echo esc_html( $rating ); ?>/5</span></div>
                                <?php endif; ?>
                                <span class="aa-dot">&middot;</span><span class="aa-fmtchip"><?php echo esc_html( $format ); ?></span>
                        </div>
                        <div class="aa-pdp-pricebox">
                                <div class="aa-pdp-price"><?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $save_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                                <div class="aa-pdp-cta">
                                        <a class="aa-btn-gold <?php echo esc_attr( $cart_cls ); ?>" data-product_id="<?php echo esc_attr( $pid ); ?>" data-quantity="1" href="<?php echo esc_url( $add_url ); ?>"><span class="aa-ms">shopping_cart</span><span>Add to cart</span></a>
                                        <?php if ( $sample_url ) : ?><a class="aa-btn-ghost" href="<?php echo esc_url( $sample_url ); ?>" target="_blank" rel="noopener"><span class="aa-ms">menu_book</span><span>Read sample</span></a><?php endif; ?>
                                        <button type="button" class="aa-wishbtn aa-wishbtn-lg<?php echo $in_wish ? ' aa-on' : ''; ?>" data-id="<?php echo esc_attr( $pid ); ?>" aria-label="Save to wishlist"><span class="aa-ms">favorite</span></button>
                                </div>
                                <ul class="aa-pdp-trust">
                                        <li><span class="aa-ms">bolt</span>Instant PDF download after checkout</li>
                                        <li><span class="aa-ms">devices</span>Reads beautifully on phone, tablet &amp; Kindle</li>
                                        <li><span class="aa-ms">update</span>Lifetime access &amp; free updates</li>
                                </ul>
                        </div>
                </div>
        </div>
        <div class="aa-wrap aa-pdp-body">
                <div class="aa-pdp-desc">
                        <h2>About this ebook</h2>
                        <div class="aa-prose"><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                </div>
                <aside class="aa-pdp-side">
                        <h3>Book details</h3>
                        <ul>
                                <li><span>Format</span><strong><?php echo esc_html( $format ); ?></strong></li>
                                <?php if ( $pages ) : ?><li><span>Pages</span><strong><?php echo esc_html( $pages ); ?></strong></li><?php endif; ?>
                                <li><span>Language</span><strong><?php echo esc_html( $lang ); ?></strong></li>
                                <?php if ( $cat ) : ?><li><span>Category</span><strong><?php echo esc_html( $cat ); ?></strong></li><?php endif; ?>
                                <li><span>Delivery</span><strong>Instant download</strong></li>
                                <li><span>Publisher</span><strong>Annaadhya Ebooks</strong></li>
                        </ul>
                </aside>
        </div>
        <div class="aa-wrap aa-pdp-reviews">
                <h2>Reader reviews <?php if ( $rating ) : ?><span class="aa-ratepill"><span class="aa-ms">star</span><?php echo esc_html( $rating ); ?>/5</span><?php endif; ?></h2>
                <div class="aa-review-list"><?php echo $rev_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
        </div>
        <?php if ( $rel_html ) : ?>
        <div class="aa-pdp-related">
                <div class="aa-wrap">
                        <h2>You may also like</h2>
                        <div class="aa-books"><ul class="aa-products"><?php echo $rel_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></ul></div>
                </div>
        </div>
        <?php endif; ?>
</section>
        <?php
        return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * [aa_library] — customer's purchased ebooks + purchase history.
 * ---------------------------------------------------------------------- */

add_shortcode( 'aa_library', 'aa_library_shortcode' );

function aa_library_shortcode() {
        if ( ! is_user_logged_in() ) {
                ob_start();
                ?>
<section class="aa-lib">
        <div class="aa-wrap">
                <div class="aa-lib-gate">
                        <span class="aa-ms">lock</span>
                        <h2>Your library awaits</h2>
                        <p>Sign in with the account you used at checkout to read, download and re-download every ebook you own — anytime, on any device.</p>
                        <div class="aa-lib-gate-btns">
                                <a class="aa-btn-gold" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>"><span class="aa-ms">login</span><span>Sign in</span></a>
                                <a class="aa-btn-ghost" href="/shop/"><span>Browse the Bookshelf</span></a>
                        </div>
                </div>
        </div>
</section>
                <?php
                return ob_get_clean();
        }

        $uid       = get_current_user_id();
        $downloads = function_exists( 'wc_get_customer_available_downloads' ) ? wc_get_customer_available_downloads( $uid ) : array();
        $orders    = function_exists( 'wc_get_orders' ) ? wc_get_orders( array( 'customer' => $uid, 'limit' => 24 ) ) : array();

        ob_start();
        ?>
<section class="aa-lib">
        <div class="aa-wrap">
                <?php if ( $downloads ) : ?>
                <div class="aa-lib-grid">
                        <?php foreach ( $downloads as $dl ) :
                                $rem = $dl['downloads_remaining'];
                                $rem_text = ( '' === $rem || (int) $rem < 0 )
                                        ? 'Unlimited re-downloads'
                                        : ( (int) $rem === 1 ? '1 download left' : ( (int) $rem ) . ' downloads left' );
                                ?>
                        <div class="aa-lib-item">
                                <span class="aa-ms">picture_as_pdf</span>
                                <div class="aa-lib-item-info">
                                        <strong><?php echo esc_html( $dl['product_name'] ); ?></strong>
                                        <span><?php echo esc_html( $dl['download_name'] ); ?> &middot; <?php echo esc_html( $rem_text ); ?></span>
                                </div>
                                <a class="aa-btn-gold" href="<?php echo esc_url( $dl['download_url'] ); ?>"><span class="aa-ms">download</span><span>Download</span></a>
                        </div>
                        <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="aa-lib-gate aa-lib-gate-lite">
                        <span class="aa-ms">auto_stories</span>
                        <h2>No books yet</h2>
                        <p>Every ebook you purchase lands here as an instant, re-downloadable PDF.</p>
                        <a class="aa-btn-gold" href="/shop/"><span>Find your first read</span><span class="aa-ms">arrow_forward</span></a>
                </div>
                <?php endif; ?>

                <h2 class="aa-lib-h2">Purchase history</h2>
                <?php if ( $orders ) : ?>
                <div class="aa-lib-tablewrap">
                <table class="aa-lib-table">
                        <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ( $orders as $order ) : ?>
                        <tr>
                                <td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a></td>
                                <td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y' ) : '' ); ?></td>
                                <td><?php echo esc_html( count( $order->get_items() ) ); ?> item<?php echo count( $order->get_items() ) === 1 ? '' : 's'; ?></td>
                                <td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                                <td><span class="aa-status aa-status-<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( ucfirst( $order->get_status() ) ); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                </table>
                </div>
                <?php else : ?>
                <p class="aa-lib-none">No orders yet — your purchase history will appear here after checkout.</p>
                <?php endif; ?>
        </div>
</section>
        <?php
        return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * Contact message CPT + [aa_contact_form].
 * ---------------------------------------------------------------------- */

add_action( 'init', 'aa_register_message_cpt' );

function aa_register_message_cpt() {
        register_post_type(
                'aa_message',
                array(
                        'labels'       => array(
                                'name'          => 'Contact Messages',
                                'singular_name' => 'Contact Message',
                        ),
                        'public'       => false,
                        'show_ui'      => false,
                        'show_in_rest' => false,
                        'supports'     => array( 'title', 'editor', 'custom-fields' ),
                        'capability_type' => 'post',
                )
        );
}

add_shortcode( 'aa_contact_form', 'aa_contact_form_shortcode' );

function aa_contact_form_shortcode() {
        ob_start();
        ?>
<form class="aa-cform" data-aa-cform novalidate>
        <input type="text" name="aa_hp" value="" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0">
        <div class="aa-cform-grid">
                <label class="aa-field"><span>Your name *</span><input type="text" name="name" placeholder="Ananya Sharma" required></label>
                <label class="aa-field"><span>Email address *</span><input type="email" name="email" placeholder="you@example.com" required></label>
        </div>
        <label class="aa-field"><span>How can we help?</span>
                <select name="topic">
                        <option value="Order support">Order support</option>
                        <option value="Download help">Download help</option>
                        <option value="Refund request">Refund request</option>
                        <option value="Author submission">Author submission</option>
                        <option value="Other">Something else</option>
                </select>
        </label>
        <label class="aa-field"><span>Message *</span><textarea name="message" rows="6" placeholder="Tell us what happened — include your order number for order-related questions." required></textarea></label>
        <p class="aa-cform-done" data-aa-cform-done hidden><span class="aa-ms">check_circle</span>Message received — our support team replies within one working day.</p>
        <p class="aa-cform-err" data-aa-cform-err hidden></p>
        <button class="aa-btn-gold" type="submit"><span class="aa-ms">send</span><span>Send message</span></button>
</form>
<script>
(function(){
        var f=document.querySelector("[data-aa-cform]");
        if(!f)return;
        f.addEventListener("submit",function(e){
                e.preventDefault();
                var done=f.querySelector("[data-aa-cform-done]"),err=f.querySelector("[data-aa-cform-err]");
                if(done)done.hidden=true;if(err)err.hidden=true;
                var fd=new FormData(f);fd.append("action","aa_contact_submit");
                fetch("/wp-admin/admin-ajax.php",{method:"POST",body:fd})
                .then(function(r){return r.json()})
                .then(function(d){
                        if(d&&d.status==="ok"){
                                f.reset();
                                if(done)done.hidden=false;
                                if(window.aaToast)window.aaToast("Message sent!");
                        }else{
                                if(err){err.hidden=false;err.textContent=(d&&d.msg)||"Please check the form and try again."}
                        }
                }).catch(function(){if(err){err.hidden=false;err.textContent="Network error — please try again."}});
        });
})();
</script>
        <?php
        return ob_get_clean();
}

/* Public AJAX: contact + newsletter (honeypot-guarded, no nonce needed for guests). */

add_action( 'wp_ajax_aa_contact_submit', 'aa_contact_submit_ajax' );
add_action( 'wp_ajax_nopriv_aa_contact_submit', 'aa_contact_submit_ajax' );

function aa_contact_submit_ajax() {
        $hp = isset( $_POST['aa_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['aa_hp'] ) ) : '';
        if ( '' !== $hp ) { wp_send_json( array( 'status' => 'ok' ) ); }

        $name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $topic = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : 'Other';
        $msg   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        $topics = array( 'Order support', 'Download help', 'Refund request', 'Author submission', 'Other' );
        if ( ! in_array( $topic, $topics, true ) ) { $topic = 'Other'; }

        if ( '' === $name || '' === $msg || ! is_email( $email ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Please fill your name, a valid email and a short message.' ), 400 );
        }

        $post_id = wp_insert_post(
                array(
                        'post_type'    => 'aa_message',
                        'post_title'   => '[' . $topic . '] ' . $name,
                        'post_content' => $msg,
                        'post_status'  => 'publish',
                ),
                true
        );
        if ( is_wp_error( $post_id ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Could not save your message. Please try again.' ), 500 );
        }
        update_post_meta( $post_id, '_aa_msg_name', $name );
        update_post_meta( $post_id, '_aa_msg_email', $email );
        update_post_meta( $post_id, '_aa_msg_topic', $topic );
        update_post_meta( $post_id, '_aa_msg_read', 0 );
        wp_send_json( array( 'status' => 'ok' ) );
}

add_action( 'wp_ajax_aa_newsletter_submit', 'aa_newsletter_submit_ajax' );
add_action( 'wp_ajax_nopriv_aa_newsletter_submit', 'aa_newsletter_submit_ajax' );

function aa_newsletter_submit_ajax() {
        $hp = isset( $_POST['hp'] ) ? sanitize_text_field( wp_unslash( $_POST['hp'] ) ) : '';
        if ( '' !== $hp ) { wp_send_json( array( 'status' => 'ok' ) ); }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
                wp_send_json( array( 'status' => 'error', 'msg' => 'Please enter a valid email address.' ), 400 );
        }
        $subs = get_option( 'aa_subscribers', array() );
        if ( ! is_array( $subs ) ) { $subs = array(); }
        foreach ( $subs as $s ) {
                if ( isset( $s['email'] ) && 0 === strcasecmp( $s['email'], $email ) ) {
                        wp_send_json( array( 'status' => 'ok', 'dup' => true ) );
                }
        }
        $subs[] = array( 'email' => $email, 'time' => time() );
        update_option( 'aa_subscribers', $subs, false );
        wp_send_json( array( 'status' => 'ok' ) );
}
