<?php
/**
 * Dashboard: store stats + recent orders + quick actions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_dashboard() {
        $books   = wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft' ), 'return' => 'objects' ) );
        $book_n  = count( $books );
        $cat_n   = count( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) );
        $orders  = wc_get_orders( array( 'limit' => 6, 'orderby' => 'date', 'order' => 'DESC' ) );
        $paid    = wc_get_orders( array( 'status' => array( 'wc-completed', 'wc-processing' ), 'limit' => -1 ) );
        $revenue = 0;
        foreach ( $paid as $o ) { $revenue += (float) $o->get_total(); }
        $orders_n = count( wc_get_orders( array( 'limit' => -1, 'return' => 'ids' ) ) );

        $sym = get_woocommerce_currency_symbol();
        ob_start();
        ?>
        <div class="stat-cards">
                <div class="card stat"><b><?php echo (int) $book_n; ?></b><span>Books in store</span></div>
                <div class="card stat"><b><?php echo (int) $cat_n; ?></b><span>Categories</span></div>
                <div class="card stat"><b><?php echo (int) $orders_n; ?></b><span>Orders</span></div>
                <div class="card stat"><b><?php echo esc_html( $sym . number_format( $revenue, 2 ) ); ?></b><span>Paid revenue</span></div>
        </div>

        <div class="dash-cols">
                <div class="card">
                        <h2>Quick actions</h2>
                        <div class="quick">
                                <a class="btn pri" href="<?php echo esc_url( aa_admin_url( 'products/new' ) ); ?>"><?php echo aa_admin_icon( 'plus' ); ?> Add a book</a>
                                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'categories/new' ) ); ?>">New category</a>
                                <a class="btn ghost" href="<?php echo esc_url( aa_admin_url( 'orders' ) ); ?>">View orders</a>
                        </div>
                        <h2 class="mt">How it works</h2>
                        <ul class="howto">
                                <li><b>Add a book</b> — give it a title, price, category, a cover image and the PDF file. Customers get the PDF instantly after checkout.</li>
                                <li><b>Draft</b> — save a book as Draft while you prepare it; it only appears in the store once set to Publish.</li>
                                <li><b>Categories</b> — show up on the homepage "Browse by collection" shelf and the shop filters.</li>
                        </ul>
                </div>
                <div class="card">
                        <h2>Recent orders</h2>
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
