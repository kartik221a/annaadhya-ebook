<?php
/**
 * Plugin Name: Annaadhya Store Tweaks (must-use)
 * Description: [aa_books] shortcode renders the Stitch "Obsidian & Gold" book card (cover, format chip, badge, category, author, rating, sale price, add to cart) from live WooCommerce products. Optional product meta powers the extras: _aa_author, _aa_badge, _aa_format, _aa_rating.
 * Version:     1.0.0
 * Author:      Annaadhya
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the storefront pages (front page + WooCommerce shop page) inside an
 * Elementor-canvas shell of our own. Both are normally hijacked by archive-ish
 * templates (Astra front-page handling / WooCommerce archive-product), and
 * Elementor's own canvas print callback runs the WP loop — which on the shop
 * archive would print product excerpts instead of the page content. So we
 * print the Elementor builder content for the fixed page ID ourselves.
 */
add_filter( 'template_include', 'aa_canvas_for_storefront', 99 );

function aa_canvas_for_storefront( $tpl ) {
        $front = (int) get_option( 'page_on_front' );
        if ( is_main_query() && $front && is_page( $front ) ) {
                aa_render_canvas( $front );
        }
        if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 && is_shop() ) {
                aa_render_canvas( (int) wc_get_page_id( 'shop' ) );
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

/**
 * Cart badge plumbing for the custom Stitch header:
 * - a WooCommerce fragment so wc-add-to-cart.js updates [data-aa-cartcount]
 *   instantly after every AJAX "add to cart";
 * - a wc-ajax endpoint for the initial badge value on page load
 *   (the Store API cart is a separate guest store from the WC session).
 */
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

/**
 * [aa_books limit="6" category="" paginate="no"]
 * Renders a <ul class="aa-products"> grid of Stitch-style book cards.
 * Honors ?s= search term when present (used by the Shop page search box).
 */
add_shortcode( 'aa_books', 'aa_books_shortcode' );

function aa_books_shortcode( $atts ) {
        $a = shortcode_atts(
                array(
                        'limit'    => '6',
                        'category' => '',
                        'paginate' => 'no',
                        'orderby'  => 'menu_order title',
                        'order'    => 'ASC',
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

/** Render one Stitch-style book card for a product. */
function aa_book_card( $product_id ) {
        $product   = wc_get_product( $product_id );
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

        // Price block.
        if ( $on_sale ) {
                $price_html = '<del>' . wp_kses_post( wc_price( $regular ) ) . '</del><ins>' . wp_kses_post( wc_price( $active ) ) . '</ins>';
                $save_html  = 'Save ' . $save . '%';
        } else {
                $price_html = '<span class="aa-only">' . wp_kses_post( wc_price( $active ) ) . '</span>';
                $save_html  = '';
        }

        // Rating row (static demo rating from meta; omitted when unset).
        $stars = '';
        for ( $i = 0; $i < 5; $i++ ) { $stars .= '<span class="aa-ms aa-star">star</span>'; }
        $rate_row = $rating
                ? '<div class="aa-stars">' . $stars . '<span class="aa-rate">' . esc_html( $rating ) . '/5</span></div>'
                : '';

        $add_url  = add_query_arg( 'add-to-cart', $product_id, home_url( '/' ) );
        $cart_cls = $product->supports( 'ajax_add_to_cart' ) ? 'add_to_cart_button ajax_add_to_cart' : 'add_to_cart_button';

        ob_start();
        ?>
<li class="aa-card" data-price="<?php echo esc_attr( $active ); ?>" data-title="<?php echo esc_attr( $name ); ?>">
        <div class="aa-media">
                <a class="aa-media-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $name ); ?>">
                        <img class="aa-cover" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?> ebook cover">
                </a>
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
