<?php
/**
 * Downloads: WooCommerce download permissions manager.
 *
 * Every purchase grants 5 full downloads per book. This section shows every
 * granted permission (who, which book, how many downloads used/left) and
 * lets the admin reset a counter or grant extra downloads, plus a
 * per-customer summary of their whole library.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param string $sub '' | 'customers'
 */
function aa_admin_downloads_router( $sub = '' ) {

        /* ---- POST actions: reset counter / grant +5 / verify email --------- */
        $action = isset( $_POST['aa_action'] ) ? sanitize_key( $_POST['aa_action'] ) : '';
        if ( 'verify_email' === $action ) {
                $vuid = absint( $_POST['aa_user_id'] ?? 0 );
                if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_downloads' ) || ! $vuid || ! get_userdata( $vuid ) ) {
                        wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=denied' );
                        exit;
                }
                update_user_meta( $vuid, '_aa_email_verified', '1' );
                delete_user_meta( $vuid, '_aa_email_verify_key' );
                wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=dl_verified' );
                exit;
        }
        if ( $action ) {
                $dl_id  = sanitize_text_field( wp_unslash( $_POST['aa_dl_id'] ?? '' ) );
                if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_downloads' ) || ! $dl_id ) {
                        wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=denied' );
                        exit;
                }

                global $wpdb;
                $table = $wpdb->prefix . 'woocommerce_downloadable_product_permissions';
                $row   = $wpdb->get_row( $wpdb->prepare( "SELECT download_id, product_id, downloads_remaining FROM {$table} WHERE download_id = %s", $dl_id ) );

                if ( $row ) {
                        if ( 'reset' === $action ) {
                                $limit = 5;
                                $product = wc_get_product( (int) $row->product_id );
                                if ( $product && (int) $product->get_download_limit() > 0 ) {
                                        $limit = (int) $product->get_download_limit();
                                }
                                $wpdb->update( $table, array( 'downloads_remaining' => (string) $limit ), array( 'download_id' => $dl_id ), array( '%s' ), array( '%s' ) );
                                wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=dl_reset' );
                                exit;
                        }
                        if ( 'grant' === $action ) {
                                if ( '' === $row->downloads_remaining ) {
                                        wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=dl_unlimited' );
                                        exit;
                                }
                                $new = (int) $row->downloads_remaining + 5;
                                $wpdb->update( $table, array( 'downloads_remaining' => (string) $new ), array( 'download_id' => $dl_id ), array( '%s' ), array( '%s' ) );
                                wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=dl_grant' );
                                exit;
                        }
                }
                wp_safe_redirect( aa_admin_url( 'downloads' ) . '?aa_msg=error' );
                exit;
        }

        if ( 'customers' === $sub ) {
                aa_admin_downloads_customers();
                return;
        }

        aa_admin_downloads_list();
}

function aa_admin_downloads_list() {
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_downloadable_product_permissions';

        $filter_user = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
        $where = $filter_user ? $wpdb->prepare( ' WHERE p.user_id = %d ', $filter_user ) : '';

        $rows = $wpdb->get_results(
                "SELECT p.download_id, p.user_id, p.product_id, p.order_id, p.download_count, p.downloads_remaining, p.access_granted,
                        u.user_login, u.user_email, u.display_name, post.post_title AS book
                FROM {$table} p
                LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id
                LEFT JOIN {$wpdb->posts} post ON post.ID = p.product_id
                {$where}
                ORDER BY p.order_id DESC, post.post_title ASC
                LIMIT 300"
        );

        ob_start();
        ?>
        <div class="c-act" style="margin-bottom:14px">
                <a class="btn ghost <?php echo $filter_user ? '' : 'on'; ?>" href="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>">All permissions</a>
                <a class="btn ghost <?php echo 'customers' === '' ? '' : ''; ?>" href="<?php echo esc_url( aa_admin_url( 'downloads/customers' ) ); ?>">By customer</a>
                <?php if ( $filter_user ) : ?>
                        <span class="pill">Filtered to user #<?php echo (int) $filter_user; ?> — <a href="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>">clear</a></span>
                <?php endif; ?>
        </div>

        <div class="card">
                <p class="muted small" style="margin:0 0 12px">Each purchase includes 5 full downloads per book. When a customer hits 0 they see a “Buy again” button in their library — or you can reset / top-up the counter here.</p>
                <div class="tbl-wrap">
                        <table class="tbl">
                                <thead><tr><th>Customer</th><th>Book</th><th>Order</th><th>Used</th><th>Left</th><th>Granted</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php if ( $rows ) : ?>
                                        <?php foreach ( $rows as $r ) :
                                                $unlim = ( '' === $r->downloads_remaining );
                                                $left  = $unlim ? null : (int) $r->downloads_remaining;
                                                $status = $unlim ? 'unlimited' : ( $left < 1 ? 'exhausted' : 'active' );
                                                ?>
                                                <tr>
                                                        <td>
                                                                <?php echo esc_html( $r->display_name ?: $r->user_login ?: ( 'User #' . $r->user_id ) ); ?>
                                                                <span class="muted small"><?php echo esc_html( $r->user_email ?: 'user #' . $r->user_id ); ?></span>
                                                                <?php $rverified = function_exists( 'aa_email_is_verified' ) ? aa_email_is_verified( (int) $r->user_id ) : true; ?>
                                                                <span class="pill <?php echo $rverified ? 'dl-active' : 'dl-exhausted'; ?>" style="margin-left:6px"><?php echo $rverified ? 'Email verified' : 'Email unverified'; ?></span>
                                                        </td>
                                                        <td><?php echo esc_html( $r->book ?: ( 'Product #' . $r->product_id ) ); ?></td>
                                                        <td class="muted">#<?php echo (int) $r->order_id; ?></td>
                                                        <td><?php echo (int) $r->download_count; ?> / <?php echo $unlim ? '∞' : esc_html( max( (int) $r->download_count + $left, (int) $r->download_count ) ); ?></td>
                                                        <td><?php echo $unlim ? '∞' : (int) $left; ?></td>
                                                        <td class="muted"><?php echo esc_html( $r->access_granted ? mysql2date( 'M j, Y', $r->access_granted ) : '—' ); ?></td>
                                                        <td>
                                                                <span class="pill dl-<?php echo esc_attr( $status ); ?>"><?php
                                                                        echo $unlim ? 'Unlimited' : ( $left < 1 ? 'Exhausted' : 'Active · ' . $left . ' left' );
                                                                ?></span>
                                                        </td>
                                                        <td class="c-act">
                                                                <form method="post" action="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>" style="display:inline">
                                                                        <input type="hidden" name="aa_action" value="reset">
                                                                        <input type="hidden" name="aa_dl_id" value="<?php echo esc_attr( $r->download_id ); ?>">
                                                                        <?php wp_nonce_field( 'aa_downloads' ); ?>
                                                                        <button class="btn sm ghost" type="submit" title="Set counter back to 5">Reset to 5</button>
                                                                </form>
                                                                <form method="post" action="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>" style="display:inline">
                                                                        <input type="hidden" name="aa_action" value="grant">
                                                                        <input type="hidden" name="aa_dl_id" value="<?php echo esc_attr( $r->download_id ); ?>">
                                                                        <?php wp_nonce_field( 'aa_downloads' ); ?>
                                                                        <button class="btn sm ghost" type="submit" title="Grant 5 extra downloads">+5</button>
                                                                </form>
                                                                <?php if ( ! $rverified ) : ?>
                                                                <form method="post" action="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>" style="display:inline">
                                                                        <input type="hidden" name="aa_action" value="verify_email">
                                                                        <input type="hidden" name="aa_user_id" value="<?php echo (int) $r->user_id; ?>">
                                                                        <?php wp_nonce_field( 'aa_downloads' ); ?>
                                                                        <button class="btn sm" type="submit" title="Mark this customer's email as verified (unlocks downloads)">Verify email</button>
                                                                </form>
                                                                <?php endif; ?>
                                                        </td>
                                                </tr>
                                        <?php endforeach; ?>
                                <?php else : ?>
                                        <tr><td colspan="8" class="muted center">No download grants yet — they appear the moment an order is completed.</td></tr>
                                <?php endif; ?>
                                </tbody>
                        </table>
                </div>
        </div>
        <?php
        aa_admin_layout( 'Downloads', ob_get_clean(), 'downloads' );
}

function aa_admin_downloads_customers() {
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_downloadable_product_permissions';

        $rows = $wpdb->get_results(
                "SELECT p.user_id, u.user_login, u.user_email, u.display_name,
                        COUNT(DISTINCT p.product_id) AS books,
                        SUM(p.download_count) AS used,
                        SUM(CASE WHEN p.downloads_remaining = '' THEN 0 ELSE CAST(p.downloads_remaining AS INTEGER) END) AS remaining,
                        MAX(p.access_granted) AS last_grant
                FROM {$table} p
                LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id
                GROUP BY p.user_id, u.user_login, u.user_email, u.display_name
                ORDER BY used DESC
                LIMIT 200"
        );

        ob_start();
        ?>
        <div class="c-act" style="margin-bottom:14px">
                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>">All permissions</a>
                <a class="btn ghost on" href="<?php echo esc_url( aa_admin_url( 'downloads/customers' ) ); ?>">By customer</a>
        </div>
        <div class="tbl-wrap">
                <table class="tbl">
                        <thead><tr><th>Customer</th><th>Books owned</th><th>Downloads used</th><th>Downloads left</th><th>Last grant</th><th></th></tr></thead>
                        <tbody>
                        <?php if ( $rows ) : ?>
                                <?php foreach ( $rows as $r ) : ?>
                                        <tr>
                                                <td>
                                                        <?php echo esc_html( $r->display_name ?: $r->user_login ?: ( 'User #' . $r->user_id ) ); ?>
                                                        <span class="muted small"><?php echo esc_html( $r->user_email ?: 'user #' . $r->user_id ); ?></span>
                                                </td>
                                                <td><?php echo (int) $r->books; ?></td>
                                                <td><?php echo (int) $r->used; ?></td>
                                                <td><?php echo (int) $r->remaining; ?></td>
                                                <td class="muted"><?php echo esc_html( $r->last_grant ? mysql2date( 'M j, Y', $r->last_grant ) : '—' ); ?></td>
                                                <td><a class="btn sm ghost" href="<?php echo esc_url( aa_admin_url( 'downloads' ) . '?user=' . (int) $r->user_id ); ?>">View grants</a></td>
                                        </tr>
                                <?php endforeach; ?>
                        <?php else : ?>
                                <tr><td colspan="6" class="muted center">No customers with download grants yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                </table>
        </div>
        <?php
        aa_admin_layout( 'Downloads · Customers', ob_get_clean(), 'downloads' );
}
