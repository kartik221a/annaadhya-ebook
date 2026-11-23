<?php
/**
 * Books (= WooCommerce simple products): list, create, edit, delete.
 * Uploads: cover image -> media library; PDF -> protected woocommerce_uploads dir.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param string $sub   '' | 'new' | '<book-id>'
 * @param string $sub2  'edit' when editing, else ''
 */
function aa_admin_products_router( $sub = '', $sub2 = '' ) {
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		aa_admin_products_handle_post();
	}

	if ( 'new' === $sub ) {
		aa_admin_product_form( null );
	} elseif ( $sub && 'edit' === $sub2 ) {
		aa_admin_product_form( absint( $sub ) );
	} else {
		aa_admin_products_list();
	}
}

function aa_admin_products_handle_post() {
	$action = sanitize_key( $_POST['aa_action'] ?? '' );
	$id     = absint( $_POST['aa_id'] ?? 0 );

	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_products' ) ) {
		wp_safe_redirect( aa_admin_url( 'products' ) . '?aa_msg=denied' );
		exit;
	}

	if ( 'delete' === $action && $id ) {
		$product = wc_get_product( $id );
		if ( $product ) { $product->delete( true ); }
		wp_safe_redirect( aa_admin_url( 'products' ) . '?aa_msg=product_deleted' );
		exit;
	}

	if ( 'save' !== $action ) {
		wp_safe_redirect( aa_admin_url( 'products' ) . '?aa_msg=error' );
		exit;
	}

	// ---- Validate + collect fields ------------------------------------
	$title  = sanitize_text_field( wp_unslash( $_POST['aa_title'] ?? '' ) );
	$price  = number_format( (float) str_replace( ',', '', (string) ( $_POST['aa_price'] ?? 0 ) ), 2, '.', '' );
	$sale   = trim( (string) ( $_POST['aa_sale'] ?? '' ) );
	$sale   = '' === $sale ? '' : number_format( (float) str_replace( ',', '', $sale ), 2, '.', '' );
	$status = in_array( $_POST['aa_status'] ?? 'draft', array( 'publish', 'draft' ), true ) ? $_POST['aa_status'] : 'draft';
	$desc   = wp_kses_post( wp_unslash( $_POST['aa_desc'] ?? '' ) );
	$short  = sanitize_textarea_field( wp_unslash( $_POST['aa_short'] ?? '' ) );
	$cats   = isset( $_POST['aa_cats'] ) ? array_map( 'absint', (array) $_POST['aa_cats'] ) : array();

	if ( '' === $title || (float) $price <= 0 ) {
		wp_safe_redirect( aa_admin_url( $id ? "products/$id/edit" : 'products/new' ) . '?aa_msg=error' );
		exit;
	}

	// ---- Uploads -------------------------------------------------------
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$cover_id    = 0;
	$cover_error = '';

	if ( ! empty( $_FILES['aa_cover']['name'] ) && file_exists( $_FILES['aa_cover']['tmp_name'] ) ) {

		/**
		 * Repair-in-place: the book already has a cover attachment whose
		 * FILE vanished from the server (uploads overwritten by a file
		 * restore, hosting cleanup, ...) while the database record and
		 * every URL referencing it survived. Re-writing the new upload
		 * onto the SAME attachment keeps the image id and the file URL
		 * stable, so things that reference it directly (Elementor hero
		 * image, cached pages) resume working without re-picking.
		 */
		$old_img_id = 0;
		if ( $id ) {
			$old = wc_get_product( $id );
			if ( $old && $old->get_image_id() ) { $old_img_id = (int) $old->get_image_id(); }
		}

		$old_file   = ( $old_img_id && get_post( $old_img_id ) ) ? get_attached_file( $old_img_id ) : '';
		$can_repair = $old_file && ! file_exists( $old_file );
		$repaired   = false;

		if ( $can_repair ) {
			$info = @getimagesize( $_FILES['aa_cover']['tmp_name'] );
			if ( $info && wp_mkdir_p( dirname( $old_file ) ) && @copy( $_FILES['aa_cover']['tmp_name'], $old_file ) ) {
				@chmod( $old_file, 0644 );
				clearstatcache();
				update_attached_file( $old_img_id, $old_file );
				wp_update_attachment_metadata( $old_img_id, wp_generate_attachment_metadata( $old_img_id, $old_file ) );
				$cover_id = $old_img_id;
				$repaired = true;
			}
		}

		if ( ! $repaired ) {
			$cover_id = (int) media_handle_upload( 'aa_cover', 0 );
			if ( is_wp_error( $cover_id ) ) {
				$cover_error = $cover_id->get_error_message();
				$cover_id    = 0;
			}
		}
	}

	$pdf_path = '';
	if ( ! empty( $_FILES['aa_pdf']['name'] ) && ! empty( $_FILES['aa_pdf']['tmp_name'] ) ) {
		$protected_dir = WP_CONTENT_DIR . '/uploads/woocommerce_uploads';
		wp_mkdir_p( $protected_dir );
		$up = wp_handle_upload( $_FILES['aa_pdf'], array( 'test_form' => false, 'mimes' => array( 'pdf' => 'application/pdf' ) ) );
		if ( empty( $up['error'] ) && ! empty( $up['file'] ) ) {
			$pdf_path = $protected_dir . '/' . sanitize_file_name( $title ) . '-' . wp_generate_password( 8, false ) . '.pdf';
			if ( ! @rename( $up['file'], $pdf_path ) ) {
				$pdf_path = $up['file'];
			}
		}
	}

	// ---- Save ----------------------------------------------------------
	if ( $id ) {
		$product = wc_get_product( $id );
		if ( ! $product ) {
			wp_safe_redirect( aa_admin_url( 'products' ) . '?aa_msg=error' );
			exit;
		}
	} else {
		$product = new WC_Product_Simple();
	}

	$product->set_name( $title );
	$product->set_regular_price( $price );
	$product->set_sale_price( $sale );
	$product->set_status( $status );
	$product->set_description( $desc );
	$product->set_short_description( $short );
	$product->set_virtual( true );
	$product->set_downloadable( true );
	$product->set_sold_individually( true );
	$product->set_category_ids( $cats );
	if ( $cover_id ) { $product->set_image_id( $cover_id ); }
	if ( $pdf_path ) {
		$dl = new WC_Product_Download();
		$dl->set_id( 'aa-pdf-' . substr( md5( $pdf_path ), 0, 10 ) );
		$dl->set_name( 'eBook (PDF)' );
		$dl->set_file( $pdf_path );
		$product->set_downloads( array( $dl ) );
	}

	$pid = $product->save();

	// The book itself saved; a failed cover upload must not read as a full success.
	$msg = ( $cover_error && ! $cover_id ) ? 'cover_failed' : ( $id ? 'product_updated' : 'product_created' );

	wp_safe_redirect( aa_admin_url( 'products/' . $pid . '/edit' ) . '?aa_msg=' . $msg );
	exit;
}

/** Resolve a WC download file value (absolute path, wp-content/... or URL) to a local path. */
function aa_admin_download_path( $file ) {
	$file = (string) $file;
	if ( '' === $file || wp_is_stream( $file ) ) { return ''; }

	if ( 0 === strpos( $file, 'http://' ) || 0 === strpos( $file, 'https://' ) ) {
		$base = home_url( '/' );
		if ( 0 !== strpos( $file, $base ) ) { return ''; } // off-site file: nothing to check locally
		$file = wp_normalize_path( ABSPATH ) . str_replace( $base, '', $file );
	}

	$file = wp_normalize_path( ltrim( $file, '/' ) );
	if ( 0 === strpos( $file, 'wp-content/' ) ) {
		return wp_normalize_path( WP_CONTENT_DIR ) . '/' . substr( $file, strlen( 'wp-content/' ) );
	}

	return wp_normalize_path( WP_CONTENT_DIR ) . '/' . $file;
}

function aa_admin_products_list() {
	$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
	$paged  = max( 1, absint( get_query_var( 'paged' ) ) );
	$per    = 12;

	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => $per,
		'paged'          => $paged,
		's'              => $search,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	ob_start();
	?>
	<div class="toolbar">
		<form class="search" method="get" action="<?php echo esc_url( aa_admin_url( 'products' ) ); ?>">
			<?php echo aa_admin_icon( 'search' ); ?>
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search books...">
		</form>
		<a class="btn pri" href="<?php echo esc_url( aa_admin_url( 'products/new' ) ); ?>"><?php echo aa_admin_icon( 'plus' ); ?> Add book</a>
	</div>

	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th class="c-img">Cover</th><th>Title</th><th>Categories</th><th>Price</th><th>Status</th><th class="c-act">Actions</th></tr></thead>
			<tbody>
			<?php if ( $q->have_posts() ) : ?>
				<?php
				foreach ( $q->posts as $post ) :
					$p    = wc_get_product( $post->ID );
					if ( ! $p ) continue;
					// Only show a thumbnail when the attachment file really exists -- a
					// deleted/lost file would otherwise render a broken <img>.
					$img  = '';
					$img_id = (int) $p->get_image_id();
					if ( $img_id ) {
						$img_file = get_attached_file( $img_id );
						if ( $img_file && file_exists( $img_file ) ) {
							$img = wp_get_attachment_image_url( $img_id, 'thumbnail' );
						}
					}
					$cats = implode( ', ', wp_list_pluck( $p->get_category_ids() ? get_terms( array( 'taxonomy' => 'product_cat', 'include' => $p->get_category_ids(), 'hide_empty' => false ) ) : array(), 'name' ) );
					?>
					<tr>
						<td class="c-img">
							<?php if ( $img ) : ?>
								<img class="thumb" src="<?php echo esc_url( $img ); ?>" alt="">
							<?php else : ?>
								<span class="thumb ph"><?php echo esc_html( mb_substr( $p->get_name(), 0, 1 ) ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<a class="rowlink" href="<?php echo esc_url( aa_admin_url( 'products/' . $p->get_id() . '/edit' ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
							<span class="muted small"><?php echo esc_html( $p->get_slug() ); ?></span>
						</td>
						<td class="muted"><?php echo esc_html( $cats ?: '—' ); ?></td>
						<td><?php echo wp_kses_post( $p->get_price_html() ); ?></td>
						<td><span class="pill s-<?php echo esc_attr( $p->get_status() === 'publish' ? 'publish' : 'draft' ); ?>"><?php echo $p->get_status() === 'publish' ? 'Published' : 'Draft'; ?></span></td>
						<td class="c-act">
							<a class="btn sm ghost" href="<?php echo esc_url( aa_admin_url( 'products/' . $p->get_id() . '/edit' ) ); ?>">Edit</a>
							<form method="post" action="<?php echo esc_url( aa_admin_url( 'products' ) ); ?>" data-confirm="<?php echo esc_attr( 'Delete "' . $p->get_name() . '" permanently?' ); ?>">
								<?php wp_nonce_field( 'aa_products' ); ?>
								<input type="hidden" name="aa_action" value="delete">
								<input type="hidden" name="aa_id" value="<?php echo (int) $p->get_id(); ?>">
								<button class="btn sm danger" type="submit">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6" class="muted center">No books found. Click "Add book" to create the first one.</td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $q->max_num_pages > 1 ) : ?>
	<div class="pager">
		<?php
		echo paginate_links( array(
			'base'      => aa_admin_url( 'products' ) . '%_%',
			'format'    => '?paged=%#%',
			'current'   => $paged,
			'total'     => $q->max_num_pages,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
		) );
		?>
	</div>
	<?php endif; ?>
	<?php
	aa_admin_layout( 'Books', ob_get_clean(), 'products' );
}

function aa_admin_product_form( $id ) {
	$product = $id ? wc_get_product( $id ) : null;
	if ( $id && ! $product ) {
		aa_admin_not_found();
		return;
	}
	ob_start();
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) { $terms = array(); }
	$sel   = $product ? $product->get_category_ids() : array();
	// Cover: only trust the URL when the file is really on disk.
	$cover = '';
	$cover_missing = false;
	if ( $product && $product->get_image_id() ) {
		$img_id   = (int) $product->get_image_id();
		$img_file = get_attached_file( $img_id );
		if ( $img_file && file_exists( $img_file ) ) {
			$cover = wp_get_attachment_image_url( $img_id, 'medium' );
		} else {
			$cover_missing = true;
		}
	}

	$dls = $product ? $product->get_downloads() : array();
	$pdf_missing = false;
	if ( $dls ) {
		$pdf_file = aa_admin_download_path( reset( $dls )->get_file() );
		$pdf_missing = ( $pdf_file && ! file_exists( $pdf_file ) );
	}
	?>
	<form method="post" action="<?php echo esc_url( aa_admin_url( 'products' ) ); ?>" enctype="multipart/form-data" class="book-form">
		<?php wp_nonce_field( 'aa_products' ); ?>
		<input type="hidden" name="aa_action" value="save">
		<input type="hidden" name="aa_id" value="<?php echo $product ? (int) $product->get_id() : 0; ?>">

		<div class="form-grid">
			<div class="card">
				<div class="field">
					<label>Book title *</label>
					<input type="text" name="aa_title" required value="<?php echo $product ? esc_attr( $product->get_name() ) : ''; ?>" placeholder="e.g. WordPress Mastery: Build Sites That Sell">
				</div>
				<div class="field">
					<label>Short description <span class="muted">(shown under the title on cards)</span></label>
					<textarea name="aa_short" rows="2" placeholder="One line that sells the book..."><?php echo $product ? esc_textarea( $product->get_short_description() ) : ''; ?></textarea>
				</div>
				<div class="field">
					<label>Full description</label>
					<textarea name="aa_desc" rows="7" placeholder="What is inside, who it is for, chapters..."><?php echo $product ? esc_textarea( $product->get_description() ) : ''; ?></textarea>
				</div>
				<div class="field">
					<label>Categories</label>
					<div class="cat-checks">
						<?php foreach ( $terms as $t ) : ?>
							<label class="check"><input type="checkbox" name="aa_cats[]" value="<?php echo (int) $t->term_id; ?>" <?php checked( in_array( $t->term_id, $sel, true ) ); ?>> <?php echo esc_html( $t->name ); ?></label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div>
				<div class="card side-card">
					<div class="field-row">
						<div class="field">
							<label>Price (₹) *</label>
							<input type="number" step="0.01" min="0" name="aa_price" required value="<?php echo $product ? esc_attr( $product->get_regular_price() ) : ''; ?>">
						</div>
						<div class="field">
							<label>Sale price (₹)</label>
							<input type="number" step="0.01" min="0" name="aa_sale" value="<?php echo $product ? esc_attr( $product->get_sale_price() ) : ''; ?>">
						</div>
					</div>
					<div class="field">
						<label>Status</label>
						<select name="aa_status">
							<option value="publish" <?php selected( $product ? $product->get_status() : 'draft', 'publish' ); ?>>Published</option>
							<option value="draft" <?php selected( $product ? $product->get_status() : 'draft', 'draft' ); ?>>Draft</option>
						</select>
					</div>
					<button class="btn pri wide" type="submit"><?php echo $product ? 'Save changes' : 'Add book'; ?></button>
				</div>

				<div class="card side-card">
					<label>Cover image <?php if ( $cover ) : ?><span class="muted">(replaces current)</span><?php endif; ?></label>
					<?php if ( $cover_missing ) : ?>
						<p class="fileline warn">Cover file missing on the server — upload a replacement below. Re-uploading repairs the existing image in place, so the homepage hero and old links keep working.</p>
					<?php elseif ( $cover ) : ?><img class="cover-preview" src="<?php echo esc_url( $cover ); ?>" alt="Current cover"><?php endif; ?>
					<input type="file" name="aa_cover" accept="image/*">
					<p class="muted small">Portrait works best (600x900). JPG / PNG / WebP.</p>
				</div>

				<div class="card side-card">
					<label>PDF file <?php if ( $dls ) : ?><span class="muted">(replaces current)</span><?php endif; ?></label>
					<?php if ( $dls ) : ?>
						<p class="fileline">Current: <?php echo esc_html( basename( reset( $dls )->get_file() ) ); ?></p>
						<?php if ( $pdf_missing ) : ?>
							<p class="fileline warn">PDF file missing on the server — upload it again or customers will not be able to download.</p>
						<?php endif; ?>
					<?php else : ?>
						<p class="muted small">No PDF attached yet — customers need one before they can download.</p>
					<?php endif; ?>
					<input type="file" name="aa_pdf" accept="application/pdf">
					<p class="muted small">Stored in the protected downloads folder; delivered automatically after checkout.</p>
				</div>
			</div>
		</div>
	</form>
	<?php
	aa_admin_layout( $product ? 'Edit book' : 'Add book', ob_get_clean(), 'products' );
}
