<?php
/**
 * Categories (= product_cat terms): list, create, edit, delete.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param string $sub   '' | 'new' | '<term-id>'
 * @param string $sub2  'edit' when editing, else ''
 */
function aa_admin_categories_router( $sub = '', $sub2 = '' ) {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                aa_admin_categories_handle_post();
        }

        if ( 'new' === $sub ) {
                aa_admin_category_form( null );
        } elseif ( $sub && 'edit' === $sub2 ) {
                aa_admin_category_form( absint( $sub ) );
        } else {
                aa_admin_categories_list();
        }
}

function aa_admin_categories_handle_post() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'aa_categories' ) ) {
                wp_safe_redirect( aa_admin_url( 'categories' ) . '?aa_msg=denied' );
                exit;
        }
        $action = sanitize_key( $_POST['aa_action'] ?? '' );
        $id     = absint( $_POST['aa_id'] ?? 0 );

        if ( 'delete' === $action && $id ) {
                wp_delete_term( $id, 'product_cat' );
                wp_safe_redirect( aa_admin_url( 'categories' ) . '?aa_msg=category_deleted' );
                exit;
        }

        if ( 'save' !== $action ) {
                wp_safe_redirect( aa_admin_url( 'categories' ) . '?aa_msg=error' );
                exit;
        }

        $name = sanitize_text_field( wp_unslash( $_POST['aa_name'] ?? '' ) );
        $desc = sanitize_textarea_field( wp_unslash( $_POST['aa_desc'] ?? '' ) );
        $slug = sanitize_title( wp_unslash( $_POST['aa_slug'] ?? '' ) );

        if ( '' === $name ) {
                wp_safe_redirect( aa_admin_url( $id ? "categories/$id/edit" : 'categories/new' ) . '?aa_msg=error' );
                exit;
        }

        $args = array( 'name' => $name, 'description' => $desc, 'slug' => $slug ?: sanitize_title( $name ) );

        if ( $id ) {
                $res = wp_update_term( $id, 'product_cat', $args );
                $msg = 'category_updated';
        } else {
                $res = wp_insert_term( $name, 'product_cat', $args );
                $msg = 'category_created';
        }

        if ( is_wp_error( $res ) ) {
                wp_safe_redirect( aa_admin_url( 'categories' ) . '?aa_msg=error' );
                exit;
        }
        wp_safe_redirect( aa_admin_url( 'categories' ) . '?aa_msg=' . $msg );
        exit;
}

function aa_admin_categories_list() {
        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
        if ( is_wp_error( $terms ) ) { $terms = array(); }
        ob_start();
        ?>
        <div class="toolbar">
                <span class="muted"><?php echo count( $terms ); ?> categories</span>
                <a class="btn pri" href="<?php echo esc_url( aa_admin_url( 'categories/new' ) ); ?>"><?php echo aa_admin_icon( 'plus' ); ?> Add category</a>
        </div>
        <div class="tbl-wrap">
                <table class="tbl">
                        <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Books</th><th class="c-act">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ( $terms as $t ) : $count = (int) $t->count; ?>
                                <tr>
                                        <td><a class="rowlink" href="<?php echo esc_url( aa_admin_url( 'categories/' . $t->term_id . '/edit' ) ); ?>"><?php echo esc_html( $t->name ); ?></a></td>
                                        <td class="muted"><?php echo esc_html( $t->slug ); ?></td>
                                        <td class="muted"><?php echo esc_html( $t->description ?: '—' ); ?></td>
                                        <td><?php echo (int) $count; ?></td>
                                        <td class="c-act">
                                                <a class="btn sm ghost" href="<?php echo esc_url( aa_admin_url( 'categories/' . $t->term_id . '/edit' ) ); ?>">Edit</a>
                                                <?php if ( 'uncategorized' !== $t->slug ) : ?>
                                                <form method="post" action="<?php echo esc_url( aa_admin_url( 'categories' ) ); ?>" data-confirm="<?php echo esc_attr( 'Delete category "' . $t->name . '"?' ); ?>">
                                                        <?php wp_nonce_field( 'aa_categories' ); ?>
                                                        <input type="hidden" name="aa_action" value="delete">
                                                        <input type="hidden" name="aa_id" value="<?php echo (int) $t->term_id; ?>">
                                                        <button class="btn sm danger" type="submit">Delete</button>
                                                </form>
                                                <?php endif; ?>
                                        </td>
                                </tr>
                        <?php endforeach; ?>
                        </tbody>
                </table>
        </div>
        <?php
        aa_admin_layout( 'Categories', ob_get_clean(), 'categories' );
}

function aa_admin_category_form( $id ) {
        $term = $id ? get_term( $id, 'product_cat' ) : null;
        if ( $id && ( ! $term || is_wp_error( $term ) ) ) {
                aa_admin_not_found();
                return;
        }
        ob_start();
        ?>
        <form method="post" action="<?php echo esc_url( aa_admin_url( 'categories' ) ); ?>" class="book-form">
                <?php wp_nonce_field( 'aa_categories' ); ?>
                <input type="hidden" name="aa_action" value="save">
                <input type="hidden" name="aa_id" value="<?php echo $term ? (int) $term->term_id : 0; ?>">
                <div class="form-grid">
                        <div class="card">
                                <div class="field">
                                        <label>Name *</label>
                                        <input type="text" name="aa_name" required value="<?php echo $term ? esc_attr( $term->name ) : ''; ?>" placeholder="e.g. Technology">
                                </div>
                                <div class="field">
                                        <label>Description <span class="muted">(optional)</span></label>
                                        <textarea name="aa_desc" rows="3" placeholder="Short line describing the collection..."><?php echo $term ? esc_textarea( $term->description ) : ''; ?></textarea>
                                </div>
                                <div class="field">
                                        <label>Slug <span class="muted">(leave empty to auto-generate)</span></label>
                                        <input type="text" name="aa_slug" value="<?php echo $term ? esc_attr( $term->slug ) : ''; ?>" placeholder="technology">
                                </div>
                                <button class="btn pri" type="submit"><?php echo $term ? 'Save changes' : 'Create category'; ?></button>
                        </div>
                        <div class="card side-card">
                                <h2>Where it appears</h2>
                                <ul class="howto">
                                        <li>Homepage — "Browse by collection" shelf.</li>
                                        <li>Storefront — <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" target="_blank" rel="noopener">/shop/</a> navigation.</li>
                                        <li>Each category gets its own page: <span class="muted">/product-category/&lt;slug&gt;/</span></li>
                                </ul>
                        </div>
                </div>
        </form>
        <?php
        aa_admin_layout( $term ? 'Edit category' : 'Add category', ob_get_clean(), 'categories' );
}
