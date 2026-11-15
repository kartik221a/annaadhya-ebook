<?php
/**
 * One-time store data for the Stitch storefront: sale prices + card extras.
 * Run:  wp eval-file tools/set_book_data.php
 */

$books = array(
	13 => array( 'regular' => 499, 'sale' => 299, 'author' => 'R. Senthil',      'rating' => '4.9', 'format' => 'PDF + EPUB',        'badge' => 'BESTSELLER' ),
	15 => array( 'regular' => 399, 'sale' => 199, 'author' => 'Ishita Rao',      'rating' => '4.8', 'format' => 'PDF FORMAT',        'badge' => 'POPULAR' ),
	17 => array( 'regular' => 449, 'sale' => 249, 'author' => 'Dr. Ananya Iyer', 'rating' => '4.9', 'format' => 'PDF + AUDIO NOTES', 'badge' => 'EDITORS PICK' ),
	19 => array( 'regular' => 299, 'sale' => 149, 'author' => 'Arya Shakara',    'rating' => '4.8', 'format' => 'PDF NOVEL',         'badge' => 'BESTSELLER' ),
	21 => array( 'regular' => 599, 'sale' => 349, 'author' => 'Tanmay Bhatia',   'rating' => '4.9', 'format' => 'PDF + NOTEBOOK',    'badge' => 'HOT PICK' ),
	23 => array( 'regular' => 699, 'sale' => 399, 'author' => 'Arjun Desai',     'rating' => '4.8', 'format' => 'PDF + TEMPLATES',   'badge' => 'NEW' ),
);

foreach ( $books as $id => $b ) {
	$product = wc_get_product( $id );
	if ( ! $product ) { echo "MISSING product $id\n"; continue; }
	$product->set_regular_price( (string) $b['regular'] );
	$product->set_sale_price( (string) $b['sale'] );
	$product->save();
	update_post_meta( $id, '_aa_author', $b['author'] );
	update_post_meta( $id, '_aa_rating', $b['rating'] );
	update_post_meta( $id, '_aa_format', $b['format'] );
	update_post_meta( $id, '_aa_badge', $b['badge'] );
	echo "OK $id " . get_the_title( $id ) . " {$b['sale']}/{$b['regular']}\n";
}
echo "BOOK-DATA-OK\n";
