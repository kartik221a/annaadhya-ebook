<?php
/**
 * Seed demo reviews + pages/language meta for the 6 demo products.
 * Run: wp eval 'require "tools/seed_reviews.php";'
 */

$map = array(
        'wordpress-mastery' => array(
                'pages' => '412', 'lang' => 'English', 'rating' => '4.9',
                'reviews' => array(
                        array( 'Meera Krishnan', 'Bought this on a Sunday, had a client-ready service site live by Wednesday. The chapters on pricing your packages alone are worth the price.' ),
                        array( 'Devansh Gupta', 'Finally a WordPress book that talks about selling, not just installing plugins. Screenshots are current and the checklists are gold.' ),
                        array( 'Farah Sheikh', 'Read it on my phone during commutes - formatting is genuinely good. Would love a companion workbook.' ),
                ),
        ),
        'lean-side-hustle' => array(
                'pages' => '348', 'lang' => 'English', 'rating' => '4.8',
                'reviews' => array(
                        array( 'Rohit Verma', 'The "first 500 customers" framework is the most practical thing I have read this year. Already applied it to my weekend gig.' ),
                        array( 'Anjali Nair', 'Honest numbers, no hustle-culture fluff. Ishita writes like a friend who has actually done it.' ),
                ),
        ),
        'digital-discipline' => array(
                'pages' => '296', 'lang' => 'English', 'rating' => '4.9',
                'reviews' => array(
                        array( 'Karthik Suresh', 'The 21-day reset protocol genuinely reduced my doom-scrolling. Evidence-based but never preachy.' ),
                        array( 'Nikita Bose', 'As a grad student juggling research and social media, the attention audit chapter changed how I plan my day.' ),
                        array( 'Vikram Malhotra', 'Dr. Iyer cites real studies, not Instagram science. The printable worksheets are a nice touch.' ),
                ),
        ),
        'echoes-himalayas' => array(
                'pages' => '386', 'lang' => 'English', 'rating' => '4.8',
                'reviews' => array(
                        array( 'Sanya Kapoor', 'I finished it in two nights. The mountain passages are so vivid I could feel the cold.' ),
                        array( 'Aravind Menon', 'A novel that respects the reader - quiet, steady, and devastating in the last hundred pages.' ),
                ),
        ),
        'ai-for-everyone' => array(
                'pages' => '324', 'lang' => 'English', 'rating' => '4.9',
                'reviews' => array(
                        array( 'Priya Venkatesh', 'Explains AI tools without jargon. My mother ran her first prompt successfully after chapter 3.' ),
                        array( 'Siddharth Joshi', 'The prompt-pattern library is immediately usable at work. Best intro to practical AI I have seen in India.' ),
                        array( 'Tanvi Rathore', 'Bought for the team, everyone from marketing to ops finished it. Instantly useful.' ),
                ),
        ),
        'idea-to-launch' => array(
                'pages' => '368', 'lang' => 'English', 'rating' => '4.8',
                'reviews' => array(
                        array( 'Neha Agarwal', 'Day 9 template got me my first paying user. The 30-day plan is realistic if you follow it properly.' ),
                        array( 'Imran Qureshi', 'Structured, motivating, zero fluff. The weekly reviews kept me honest.' ),
                ),
        ),
);

foreach ( $map as $slug => $data ) {
        $pid = 0;
        $p   = get_page_by_path( $slug, OBJECT, 'product' );
        if ( $p ) { $pid = $p->ID; }
        if ( ! $pid ) {
                echo 'MISS product ' . $slug . "\n";
                continue;
        }
        update_post_meta( $pid, '_aa_pages', $data['pages'] );
        update_post_meta( $pid, '_aa_lang', $data['lang'] );
        update_post_meta( $pid, '_aa_rating', $data['rating'] );

        $existing = get_comments( array( 'post_id' => $pid, 'status' => 'approve', 'post_type' => 'product', 'number' => 1 ) );
        if ( $existing ) { echo 'SKIP reviews exist for ' . $slug . "\n"; continue; }

        $i = 0;
        foreach ( $data['reviews'] as $rv ) {
                $i++;
                wp_insert_comment(
                        array(
                                'comment_post_ID'     => $pid,
                                'comment_author'      => $rv[0],
                                'comment_author_email'=> strtolower( str_replace( ' ', '.', $rv[0] ) ) . '@example.com',
                                'comment_content'     => $rv[1],
                                'comment_type'        => 'review',
                                'comment_approved'    => 1,
                                'comment_date_gmt'    => gmdate( 'Y-m-d H:i:s', time() - ( $i * 5 + 2 ) * 86400 ),
                                'comment_date'        => date( 'Y-m-d H:i:s', time() - ( $i * 5 + 2 ) * 86400 ),
                        )
                );
        }
        echo 'OK reviews for ' . $slug . " (" . $i . ")\n";
}

echo "SEED-OK\n";
