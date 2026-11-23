<?php
/**
 * Dashboard: store stats + recent orders + quick actions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_dashboard() {
        $books  = wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft' ), 'return' => 'objects' ) );
        $book_n = count( $books );

        $cat_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
        $cat_n     = is_wp_error( $cat_terms ) ? 0 : count( $cat_terms );

        $orders  = wc_get_orders( array( 'limit' => 6, 'orderby' => 'date', 'order' => 'DESC' ) );
        $paid    = wc_get_orders( array( 'status' => array( 'wc-completed', 'wc-processing' ), 'limit' => -1 ) );
        $revenue = 0;
        foreach ( $paid as $o ) { $revenue += (float) $o->get_total(); }
        $orders_n = count( wc_get_orders( array( 'limit' => -1, 'return' => 'ids' ) ) );

        $sym = get_woocommerce_currency_symbol();

        // Files that the database still references but the server has lost
        // (cover PNGs / PDFs wiped by a file restore or hosting cleanup).
        // Surfaced loudly so broken covers can never go unnoticed again.
        $broken = array();
        foreach ( $books as $b ) {
                $issues = array();
                if ( aa_cover_file_missing( $b->get_id() ) ) { $issues[] = 'cover image'; }
                foreach ( $b->get_downloads() as $dl ) {
                        $p = aa_admin_download_path( $dl->get_file() );
                        if ( $p && ! file_exists( $p ) ) { $issues[] = 'PDF file'; break; }
                }
                if ( $issues ) { $broken[] = array( 'book' => $b, 'issues' => $issues ); }
        }

        $msg_posts = get_posts( array( 'post_type' => 'aa_message', 'posts_per_page' => 30, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
        $unread_n  = 0;
        foreach ( $msg_posts as $mp ) {
                if ( ! (int) get_post_meta( $mp->ID, '_aa_msg_read', true ) ) { $unread_n++; }
        }
        $subs      = get_option( 'aa_subscribers', array() );
        $subs_n    = is_array( $subs ) ? count( $subs ) : 0;

        global $wpdb;
        $dl_grants = 0;
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'woocommerce_downloadable_product_permissions' ) ) ) {
                $dl_grants = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions" );
        }

        ob_start();
        ?>
        <div class="stat-cards">
                <div class="card stat"><b><?php echo (int) $book_n; ?></b><span>Books in store</span></div>
                <div class="card stat"><b><?php echo (int) $orders_n; ?></b><span>Orders</span></div>
                <div class="card stat"><b><?php echo esc_html( $sym . number_format( $revenue, 2 ) ); ?></b><span>Paid revenue</span></div>
                <div class="card stat"><b><?php echo (int) $unread_n; ?></b><span>Unread messages</span></div>
                <div class="card stat"><b><?php echo (int) $subs_n; ?></b><span>Newsletter subscribers</span></div>
                <div class="card stat"><b><?php echo (int) $dl_grants; ?></b><span>Download grants</span></div>
        </div>

        <?php if ( $broken ) : ?>
        <div class="card aa-fix-card">
                <h2>Files need attention</h2>
                <p class="muted small">These books still have a database record for the file below, but the file itself is missing on the server. Open the book and re-upload it — covers are repaired in place, so the homepage hero and existing links keep working.</p>
                <table class="tbl">
                        <thead><tr><th>Book</th><th>Missing</th><th class="c-act">Action</th></tr></thead>
                        <tbody>
                        <?php foreach ( $broken as $row ) : ?>
                                <tr>
                                        <td><a class="rowlink" href="<?php echo esc_url( aa_admin_url( 'products/' . $row['book']->get_id() . '/edit' ) ); ?>"><?php echo esc_html( $row['book']->get_name() ); ?></a></td>
                                        <td><span class="pill s-on-hold"><?php echo esc_html( implode( ' + ', $row['issues'] ) ); ?></span></td>
                                        <td class="c-act"><a class="btn sm pri" href="<?php echo esc_url( aa_admin_url( 'products/' . $row['book']->get_id() . '/edit' ) ); ?>">Fix now</a></td>
                                </tr>
                        <?php endforeach; ?>
                        </tbody>
                </table>
        </div>
        <?php endif; ?>

        <div class="dash-cols">
                <div class="card">
                        <h2>Quick actions</h2>
                        <div class="quick">
                                <a class="btn pri" href="<?php echo esc_url( aa_admin_url( 'products/new' ) ); ?>"><?php echo aa_admin_icon( 'plus' ); ?> Add a book</a>
                                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'hero' ) ); ?>"><?php echo aa_admin_icon( 'image' ); ?> Change home hero</a>
                                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'orders' ) ); ?>">View orders</a>
                                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'downloads' ) ); ?>">Download grants</a>
                                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'messages' ) ); ?>">Customer messages</a>
                                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'subscribers' ) ); ?>">Subscribers</a>
                        </div>
                        <h2 class="mt">Storefront pages</h2>
                        <ul class="howto">
                                <li><b>Storefront</b> — <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Home</a> · <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" target="_blank" rel="noopener">Shop</a> · <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" target="_blank" rel="noopener">About</a> · <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" target="_blank" rel="noopener">Contact</a>.</li>
                                <li><b>Customer area</b> — <a href="<?php echo esc_url( home_url( '/library/' ) ); ?>" target="_blank" rel="noopener">My Library</a> (downloads + history), <a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" target="_blank" rel="noopener">My Account</a> (profile, orders, wishlist), cart &amp; checkout.</li>
                                <li><b>Legal</b> — <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener">Privacy</a>, <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" target="_blank" rel="noopener">Terms</a>, <a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>" target="_blank" rel="noopener">Refund</a>, <a href="<?php echo esc_url( home_url( '/digital-delivery-policy/' ) ); ?>" target="_blank" rel="noopener">Digital Delivery</a>, <a href="<?php echo esc_url( home_url( '/copyright-policy/' ) ); ?>" target="_blank" rel="noopener">Copyright</a>. Edit any of them via wp-admin → Pages.</li>
                                <li><b>Add a book</b> — title, price, category, cover, PDF. It appears on Home/Shop instantly; customers get the PDF right after checkout.</li>
                        </ul>
                </div>
                <div class="card">
                        <h2>Recent messages</h2>
                        <?php if ( $msg_posts ) : ?>
                        <table class="tbl">
                                <thead><tr><th>From</th><th>Topic</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ( array_slice( $msg_posts, 0, 5 ) as $mp ) : ?>
                                        <tr>
                                                <td><a class="rowlink" href="<?php echo esc_url( aa_admin_url( 'messages/' . $mp->ID ) ); ?>"><?php echo esc_html( get_post_meta( $mp->ID, '_aa_msg_name', true ) ); ?></a></td>
                                                <td class="muted"><?php echo esc_html( get_post_meta( $mp->ID, '_aa_msg_topic', true ) ); ?></td>
                                                <td><span class="pill <?php echo (int) get_post_meta( $mp->ID, '_aa_msg_read', true ) ? 's-completed' : 's-on-hold'; ?>"><?php echo (int) get_post_meta( $mp->ID, '_aa_msg_read', true ) ? 'Handled' : 'New'; ?></span></td>
                                        </tr>
                                <?php endforeach; ?>
                                </tbody>
                        </table>
                        <?php else : ?>
                                <p class="muted">No customer messages yet.</p>
                        <?php endif; ?>
                        <h2 class="mt">Recent orders</h2>
                        <?php if ( $orders ) : ?>
                        <table class="tbl">
                                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ( $orders as $o ) : ?>
                                        <tr>
                                                <td><a class="rowlink" href="<?php echo esc_url( aa_admin_url( 'orders/' . $o->get_id() ) ); ?>">#<?php echo (int) $o->get_id(); ?></a></td>
                                                <td><?php echo esc_html( trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ) ?: 'Guest' ); ?></td>
                                                <td><?php echo esc_html( wp_strip_all_tags( wc_price( $o->get_total() ) ) ); ?></td>
                                                <td><span class="pill s-<?php echo esc_attr( $o->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $o->get_status() ) ); ?></span></td>
                                        </tr>
                                <?php endforeach; ?>
                                </tbody>
                        </table>
                        <?php else : ?>
                                <p class="muted">No orders yet. They will show up here as soon as someone checks out.</p>
                        <?php endif; ?>
                </div>
        </div>
        <?php
        aa_admin_layout( 'Dashboard', ob_get_clean(), 'dashboard' );
}
