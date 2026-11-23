<?php
/**
 * Home Page Hero: change the featured book / image in the store's home hero.
 * Stored in option aa_hero (mode + product_id / image_url); the canvas
 * renderer in aa-store-tweaks.php re-renders the hero panel at render time —
 * no page editing, no Elementor fiddling.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_hero_router() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                aa_admin_hero_handle_post();
        }

        $o       = get_option( 'aa_hero', array() );
        if ( ! is_array( $o ) ) { $o = array(); }
        $mode    = isset( $o['mode'] ) ? (string) $o['mode'] : '';
        $pid     = isset( $o['product_id'] ) ? (int) $o['product_id'] : 0;
        $img_url = isset( $o['image_url'] ) ? (string) $o['image_url'] : '';

        $products = function_exists( 'wc_get_products' )
                ? wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'return' => 'objects', 'orderby' => 'title', 'order' => 'ASC' ) )
                : array();

        $state_label = 'Bundled design (default)';
        $prev_img    = '';
        if ( 'product' === $mode && $pid ) {
                $p = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : false;
                if ( $p ) {
                        $prev_img    = get_the_post_thumbnail_url( $pid, 'medium' );
                        $state_label = 'Featured book: ' . $p->get_name();
                }
        } elseif ( 'image' === $mode && '' !== $img_url ) {
                $prev_img    = $img_url;
                $state_label = 'Custom image';
        }

        ob_start();
        ?>
        <div class="dash-cols" style="margin-bottom:18px">
                <div class="card stat"><b style="font-size:15px"><?php echo esc_html( $state_label ); ?></b><span>What the home hero shows right now</span></div>
                <div class="card" style="display:flex;align-items:center;gap:12px">
                        <a class="btn pri" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php echo aa_admin_icon( 'store' ); ?> View home page</a>
                        <span class="muted small">Changes apply instantly — open it to see the hero.</span>
                </div>
        </div>

        <div class="dash-cols">
                <div class="card">
                        <h2>Feature a book from the store</h2>
                        <p class="muted small">The hero panel shows this book's cover, title, author and price, and links to its page.</p>
                        <form method="post" action="<?php echo esc_url( aa_admin_url( 'hero' ) ); ?>">
                                <?php wp_nonce_field( 'aa_hero' ); ?>
                                <input type="hidden" name="aa_action" value="book">
                                <label class="field">
                                        <span>Book</span>
                                        <select name="aa_product" required>
                                                <?php foreach ( $products as $p ) : ?>
                                                        <option value="<?php echo (int) $p->get_id(); ?>" <?php selected( 'product' === $mode && $pid === (int) $p->get_id() ); ?>><?php echo esc_html( $p->get_name() ); ?></option>
                                                <?php endforeach; ?>
                                        </select>
                                </label>
                                <button class="btn pri" type="submit">Feature this book</button>
                        </form>
                </div>

                <div class="card">
                        <h2>Or put any image in the hero</h2>
                        <p class="muted small">Upload whatever you want the hero panel to show — the caption becomes a generic "Featured this week".</p>
                        <?php if ( 'image' === $mode && $prev_img ) : ?>
                                <div class="hero-preview"><img src="<?php echo esc_url( $img_url ); ?>" alt="Current custom hero image"></div>
                        <?php endif; ?>
                        <form class="hero-form" method="post" action="<?php echo esc_url( aa_admin_url( 'hero' ) ); ?>" enctype="multipart/form-data">
                                <?php wp_nonce_field( 'aa_hero' ); ?>
                                <input type="hidden" name="aa_action" value="upload">
                                <label class="field">
                                        <span>Image file</span>
                                        <input type="file" name="aa_hero_file" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" required>
                                </label>
                                <div class="hero-hints">
                                        <p><b>Works best:</b> a portrait-ish cover image (JPG, PNG, WebP or GIF, up to 8&nbsp;MB).</p>
                                        <p><b>Applies instantly</b> — no page editing needed. Feature a book again any time to switch back.</p>
                                </div>
                                <button class="btn pri" type="submit">Save hero image</button>
                        </form>
                </div>
        </div>

        <div class="card" style="margin-top:18px">
                <h2>Start over</h2>
                <p class="muted small">Restore the bundled hero design that ships with the store (the default "Digital Discipline" panel).</p>
                <form method="post" action="<?php echo esc_url( aa_admin_url( 'hero' ) ); ?>" data-confirm="Restore the bundled hero design?">
                        <?php wp_nonce_field( 'aa_hero' ); ?>
                        <input type="hidden" name="aa_action" value="reset">
                        <button class="btn ghost sm" type="submit" <?php echo '' === $mode ? 'disabled' : ''; ?>>Reset to bundled design</button>
                </form>
        </div>
        <?php
        aa_admin_layout( 'Home Page Hero', ob_get_clean(), 'hero' );
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
                $pid = isset( $_POST['aa_product'] ) ? absint( $_POST['aa_product'] ) : 0;
                $p   = $pid && function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : false;
                if ( ! $p || 'publish' !== $p->get_status() ) {
                        wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=error' );
                        exit;
                }
                update_option( 'aa_hero', array( 'mode' => 'product', 'product_id' => $pid, 'image_url' => '' ), false );
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_book_saved' );
                exit;
        }

        if ( 'upload' !== $action ) {
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=error' );
                exit;
        }

        if ( empty( $_FILES['aa_hero_file'] ) || ! isset( $_FILES['aa_hero_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['aa_hero_file']['tmp_name'] ) ) {
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_nofile' );
                exit;
        }

        $file    = $_FILES['aa_hero_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $allowed = array( 'jpg', 'jpeg', 'png', 'webp', 'gif' );

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
        $ext   = strtolower( (string) $check['ext'] );

        if ( ! $ext || ! in_array( $ext, $allowed, true ) ) {
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_badfile' );
                exit;
        }
        if ( ! isset( $file['size'] ) || $file['size'] < 1 || $file['size'] > 8388608 ) {
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_toolarge' );
                exit;
        }

        $moved = wp_handle_upload( $file, array( 'test_form' => false ) );
        if ( ! is_array( $moved ) || empty( $moved['url'] ) ) {
                wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=error' );
                exit;
        }

        // Register it in the media library so WP manages the file (also visible under Media in wp-admin).
        $attach_id = wp_insert_attachment(
                array(
                        'post_mime_type' => $moved['type'],
                        'post_title'     => sanitize_file_name( pathinfo( $moved['file'], PATHINFO_FILENAME ) ),
                        'post_status'    => 'inherit',
                ),
                $moved['file']
        );
        if ( $attach_id && ! is_wp_error( $attach_id ) ) {
                $meta = wp_generate_attachment_metadata( $attach_id, $moved['file'] );
                if ( $meta && ! is_wp_error( $meta ) ) { wp_update_attachment_metadata( $attach_id, $meta ); }
        }

        update_option( 'aa_hero', array( 'mode' => 'image', 'product_id' => 0, 'image_url' => esc_url_raw( $moved['url'] ) ), false );
        wp_safe_redirect( aa_admin_url( 'hero' ) . '?aa_msg=hero_saved' );
        exit;
}
