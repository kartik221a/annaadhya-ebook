<?php
/**
 * Orders: read-only list + detail view.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aa_admin_orders_router() {
	$sub = get_query_var( 'aa_sub' );
	if ( $sub && absint( $sub ) ) {
		aa_admin_order_detail( absint( $sub ) );
		return;
	}

	$orders = wc_get_orders( array( 'limit' => 50, 'orderby' => 'date', 'order' => 'DESC' ) );
	ob_start();
	?>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
			<tbody>
			<?php if ( $orders ) : ?>
				<?php foreach ( $orders as $o ) : ?>
					<tr>
						<td><a class="rowlink" href="<?php echo esc_url( aa_admin_url( 'orders/' . $o->get_id() ) ); ?>">#<?php echo (int) $o->get_id(); ?></a></td>
						<td class="muted"><?php echo esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'M j, Y g:i a' ) : '—' ); ?></td>
						<td>
							<?php echo esc_html( trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ) ?: 'Guest' ); ?>
							<span class="muted small"><?php echo esc_html( $o->get_billing_email() ); ?></span>
						</td>
						<td class="muted"><?php echo count( $o->get_items() ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $o->get_total() ) ); ?></td>
						<td><span class="pill s-<?php echo esc_attr( $o->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $o->get_status() ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6" class="muted center">No orders yet.</td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
	aa_admin_layout( 'Orders', ob_get_clean(), 'orders' );
}

function aa_admin_order_detail( $id ) {
	$order = wc_get_order( $id );
	if ( ! $order ) {
		aa_admin_not_found();
		return;
	}
	ob_start();
	?>
	<div class="order-head">
		<a class="btn sm ghost" href="<?php echo esc_url( aa_admin_url( 'orders' ) ); ?>">&larr; All orders</a>
		<span class="pill s-<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
	</div>

	<div class="dash-cols">
		<div class="card">
			<h2>Items</h2>
			<table class="tbl bare">
				<thead><tr><th>Book</th><th>Qty</th><th>Total</th></tr></thead>
				<tbody>
				<?php foreach ( $order->get_items() as $item ) : ?>
					<tr>
						<td><?php echo esc_html( $item->get_name() ); ?></td>
						<td><?php echo (int) $item->get_quantity(); ?></td>
						<td><?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<h2 class="mt">Totals</h2>
			<table class="tbl bare">
				<tbody>
					<tr><td>Subtotal</td><td><?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?></td></tr>
					<?php if ( (float) $order->get_total_tax() > 0 ) : ?>
					<tr><td>Tax</td><td><?php echo wp_kses_post( wc_price( $order->get_total_tax() ) ); ?></td></tr>
					<?php endif; ?>
					<tr><td><b>Order total</b></td><td><b><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></b></td></tr>
				</tbody>
			</table>
		</div>
		<div class="card">
			<h2>Customer</h2>
			<p class="muted small">
				<?php
				echo esc_html( trim( $order->get_formatted_billing_full_name() ) ?: 'Guest' ) . '<br>';
				echo esc_html( $order->get_billing_email() ) . '<br>';
				echo esc_html( $order->get_billing_phone() );
				?>
			</p>
			<h2 class="mt">Meta</h2>
			<table class="tbl bare">
				<tbody>
					<tr><td>Order ID</td><td>#<?php echo (int) $order->get_id(); ?></td></tr>
					<tr><td>Placed</td><td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y g:i a' ) : '—' ); ?></td></tr>
					<tr><td>Payment</td><td><?php echo esc_html( $order->get_payment_method_title() ?: '—' ); ?></td></tr>
					<tr><td>Key</td><td class="muted"><?php echo esc_html( substr( (string) $order->get_order_key(), 0, 12 ) ); ?>...</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	<?php
	aa_admin_layout( 'Order #' . $order->get_id(), ob_get_clean(), 'orders' );
}
