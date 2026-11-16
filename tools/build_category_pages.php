<?php
/**
 * Build the hidden "Category Archive" template page from the Stitch category
 * designs (project 7746485506472124034 — screens Technology, Business,
 * Self-Help, Fiction). Every /product-category/<slug>/ URL renders through
 * this page: the hero comes from [aa_category_hero] (per-category motif
 * ported 1:1 from Stitch), the grid from [aa_books] (follows the queried
 * term automatically).
 *
 * Run:  wp eval 'require "tools/stitch_lib.php"; require "tools/build_category_pages.php";'
 */

require_once __DIR__ . '/stitch_lib.php';

/* ------------------------------------------------------------ hero styles */

$aa_cat_hero = <<<'HTML'
<style>
/* ---- hero band (Stitch: rounded panel, ambient glow, 7/5 split) ---- */
.aa-cath{padding:32px 0 0;background:var(--aa-surface)}
.aa-cath-band{position:relative;border:1px solid var(--aa-line);border-radius:16px;background:var(--aa-low);padding:28px;overflow:hidden}
@media(min-width:768px){.aa-cath-band{padding:44px 48px}}
.aa-cath-glow{position:absolute;top:-180px;right:-120px;width:640px;height:480px;background:radial-gradient(closest-side,rgba(245,158,11,.14),transparent 70%);filter:blur(46px);pointer-events:none}
.aa-cath-grid{position:relative;display:grid;grid-template-columns:7fr 5fr;gap:44px;align-items:center}
@media(max-width:1024px){.aa-cath-grid{grid-template-columns:1fr;gap:40px}}
.aa-cath-crumb{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--aa-dim);margin-bottom:18px}
.aa-cath-crumb a{color:var(--aa-dim)!important;text-decoration:none!important;transition:color .15s}
.aa-cath-crumb a:hover{color:var(--aa-gold)!important}
.aa-cath-crumb em{font-style:normal;color:var(--aa-goldbg);font-weight:600}
.aa-cath-eyebrow{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:999px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);color:var(--aa-goldbg);margin-bottom:18px}
.aa-cath-eyebrow .aa-ms{font-size:15px}
.aa-cath-eyebrow span:last-child{font-size:10.5px;font-weight:800;letter-spacing:.12em}
.aa-cath h1{font-size:clamp(34px,4.6vw,50px);line-height:1.12;font-weight:800;letter-spacing:-.02em;color:var(--aa-tx);margin:0 0 16px}
.aa-cath-gold{background:linear-gradient(92deg,#ffc174,#f59e0b 55%,#ffdf9e);-webkit-background-clip:text;background-clip:text;color:transparent}
.aa-cath-sub{font-size:15.5px;line-height:1.75;color:var(--aa-warm);max-width:600px;margin:0 0 22px}
.aa-cath-pills{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:26px}
.aa-cath-pill{display:inline-flex;align-items:center;gap:7px;padding:8px 13px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line2);font-size:12.5px;font-weight:600;color:#b9c8dc}
.aa-cath-pill .aa-ms{font-size:17px;color:var(--aa-goldbg)}
.aa-cath-cta{margin-bottom:0}
/* ---- motif panel shell ---- */
.aa-cath-art{min-width:0}
.aa-cath-art>div{background:rgba(24,28,33,.92);border:1px solid rgba(160,142,122,.5);border-radius:14px;padding:20px;box-shadow:0 30px 70px rgba(2,6,12,.55);position:relative;overflow:hidden}
.aa-motif-top{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-bottom:12px;border-bottom:1px solid var(--aa-line);margin-bottom:16px}
.aa-motif-dots{display:flex;align-items:center;gap:6px}
.aa-motif-dots i{width:10px;height:10px;border-radius:50%;background:rgba(255,180,171,.7)}
.aa-motif-dots i.g{background:rgba(245,158,11,.75)}
.aa-motif-dots i.s{background:rgba(185,200,220,.5)}
.aa-motif-caps{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:10px;font-weight:700;letter-spacing:.1em;color:#a08e7a;text-transform:uppercase}
.aa-motif-ti{font-size:16px;color:#a08e7a}
/* ---- technology: terminal ---- */
.aa-term{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;color:var(--aa-warm)}
.aa-term-line{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.aa-term-p{color:var(--aa-goldbg);font-weight:700}
.aa-term-out{background:rgba(10,14,19,.85);border:1px solid var(--aa-line);border-radius:9px;padding:12px 14px;display:grid;gap:5px;color:#b9c8dc;margin-bottom:14px}
.aa-term-out p{margin:0;font-size:12px}
.aa-term-out p.g{color:var(--aa-goldbg);font-weight:700}
.aa-term-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
.aa-term-tag{display:inline-flex;align-items:center;gap:6px;color:var(--aa-goldbg);font-weight:700;font-size:12px}
.aa-term-tag .aa-ms{font-size:16px}
.aa-term-chip{font-family:'Plus Jakarta Sans',sans-serif;font-size:9.5px;font-weight:800;letter-spacing:.08em;color:var(--aa-goldbg);background:rgba(245,158,11,.14);border:1px solid rgba(245,158,11,.4);padding:3px 8px;border-radius:6px}
.aa-term-strip{display:flex;align-items:center;justify-content:space-between;gap:10px;border-top:1px solid var(--aa-line);padding-top:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;color:#a08e7a}
.aa-term-strip span{display:inline-flex;align-items:center;gap:6px}
.aa-term-strip .aa-ms{font-size:14px;color:var(--aa-goldbg)}
/* ---- business: playbook metrics ---- */
.aa-biz-stat{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line);margin-bottom:12px}
.aa-biz-ic{width:40px;height:40px;flex:none;border-radius:10px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);display:flex;align-items:center;justify-content:center;color:var(--aa-goldbg)}
.aa-biz-ic .aa-ms{font-size:22px}
.aa-biz-stat-tx{flex:1;min-width:0}
.aa-biz-stat-tx em{display:block;font-style:normal;font-size:11px;color:#a08e7a;margin-bottom:2px}
.aa-biz-stat-tx strong{font-size:14.5px;font-weight:700;color:var(--aa-tx);white-space:nowrap}
.aa-biz-badge{font-size:10px;font-weight:800;letter-spacing:.06em;color:var(--aa-goldbg);background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);padding:3px 8px;border-radius:6px;white-space:nowrap}
.aa-biz-chart{padding:14px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line);margin-bottom:12px}
.aa-biz-chart-h{display:flex;align-items:center;justify-content:space-between;font-size:9.5px;font-weight:800;letter-spacing:.1em;color:#a08e7a;margin-bottom:10px}
.aa-biz-chart-h b{color:var(--aa-goldbg);font-family:ui-monospace,Menlo,monospace;font-weight:700}
.aa-biz-bars{height:64px;display:flex;align-items:flex-end;gap:8px;padding:0 4px}
.aa-biz-bars i{flex:1;background:var(--aa-high2);border-radius:6px 6px 0 0;transition:background .2s}
.aa-biz-bars i:hover{background:rgba(245,158,11,.4)}
.aa-biz-bars i.hot{background:var(--aa-goldbg);box-shadow:0 0 12px rgba(245,158,11,.4)}
.aa-biz-strip{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:11px;color:#a08e7a}
.aa-biz-strip span{display:inline-flex;align-items:center;gap:6px}
.aa-biz-strip .aa-ms{font-size:14px;color:var(--aa-goldbg)}
/* ---- self-help: focus rings ---- */
.aa-sh-wrap{position:relative}
.aa-sh-halo{position:absolute;top:-48px;right:-48px;width:190px;height:190px;border-radius:50%;background:radial-gradient(closest-side,rgba(245,158,11,.16),transparent 70%);filter:blur(30px);pointer-events:none}
.aa-sh-live{width:9px;height:9px;border-radius:50%;background:var(--aa-goldbg);animation:aa-sh-pulse 2.4s ease-in-out infinite}
@keyframes aa-sh-pulse{0%,100%{opacity:1}50%{opacity:.3}}
.aa-sh-art{position:relative;height:172px;border-radius:10px;background:rgba(10,14,19,.85);border:1px solid var(--aa-line);display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:12px}
.aa-ring{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);border-radius:50%}
.aa-ring.r1{width:150px;height:150px;border:1px solid rgba(245,158,11,.22);animation:aa-breath 4s ease-in-out infinite}
.aa-ring.r2{width:114px;height:114px;border:1px solid rgba(245,158,11,.32)}
.aa-ring.r3{width:80px;height:80px;border:1px solid rgba(160,142,122,.55)}
.aa-ring.r4{width:48px;height:48px;border:1px solid rgba(245,158,11,.4);background:rgba(245,158,11,.1)}
@keyframes aa-breath{0%,100%{transform:translate(-50%,-50%) scale(1);opacity:.9}50%{transform:translate(-50%,-50%) scale(1.08);opacity:.5}}
.aa-sh-svg{position:relative;z-index:1;width:92px;height:92px;color:var(--aa-gold)}
.aa-sh-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.aa-sh-stats>div{background:rgba(10,14,19,.8);border:1px solid var(--aa-line);border-radius:9px;padding:10px 12px}
.aa-sh-stats em{display:block;font-style:normal;font-size:10.5px;color:#a08e7a;margin-bottom:2px}
.aa-sh-stats strong{font-size:14px;font-weight:700;color:var(--aa-tx)}
/* ---- fiction: literary journeys ---- */
.aa-fic-ed{font-family:'Plus Jakarta Sans',sans-serif;font-size:9.5px;font-weight:800;letter-spacing:.08em;color:#a08e7a;background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:6px;padding:3px 8px}
.aa-fic-art{position:relative;height:172px;border-radius:10px;background:rgba(10,14,19,.85);border:1px solid var(--aa-line);overflow:hidden;margin-bottom:12px}
.aa-fic-art:before{content:"";position:absolute;inset:0;background:radial-gradient(ellipse at center,rgba(245,158,11,.1),transparent 65%)}
.aa-fic-svg{width:100%;height:100%;display:block}
.aa-fic-node{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:56px;height:56px;border-radius:50%;background:var(--aa-cont);border:1px solid rgba(245,158,11,.45);display:flex;align-items:center;justify-content:center;box-shadow:0 0 25px rgba(245,158,11,.25)}
.aa-fic-node .aa-ms{font-size:28px;color:var(--aa-goldbg);font-variation-settings:'FILL' 1}
.aa-fic-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.aa-fic-stats>div{background:rgba(10,14,19,.8);border:1px solid var(--aa-line);border-radius:9px;padding:10px 12px}
.aa-fic-stats em{display:block;font-style:normal;font-size:10.5px;color:#a08e7a;margin-bottom:2px}
.aa-fic-stats strong{font-size:14px;font-weight:700;color:var(--aa-tx)}
</style>
HTML;

/* ------------------------------------------- toolbar + grid + trust strip */

$aa_cat_grid = <<<'HTML'
<style>
.aa-tool{padding:22px 0 8px;background:var(--aa-surface)}
.aa-tool-in{display:flex;flex-wrap:wrap;align-items:center;gap:16px}
.aa-chipbtn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-tx)!important;font-size:13px;font-weight:700;text-decoration:none!important;white-space:nowrap;transition:border-color .15s,color .15s}
.aa-chipbtn:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
.aa-chipbtn.aa-on{background:var(--aa-goldbg);color:var(--aa-ongold)!important;border-color:transparent}
.aa-tool-r{display:flex;align-items:center;gap:18px;margin-left:auto}
.aa-showing{font-size:13px;color:#a08e7a;display:none}
@media(min-width:640px){.aa-showing{display:block}}
.aa-showing strong{color:var(--aa-tx);font-weight:700}
.aa-tool-sep{width:1px;height:16px;background:var(--aa-line2)}
.aa-sortwrap{display:flex;align-items:center;gap:10px}
.aa-sortwrap label{font-size:11px;font-weight:700;letter-spacing:.08em;color:#a08e7a;text-transform:uppercase;white-space:nowrap;display:none}
@media(min-width:1024px){.aa-sortwrap label{display:inline}}
.aa-sortsel{position:relative}
.aa-sortsel select{appearance:none;-webkit-appearance:none;background:var(--aa-high);border:1px solid var(--aa-line2);border-radius:10px;color:var(--aa-tx);font-size:13px;font-weight:600;padding:10px 34px 10px 14px;cursor:pointer;font-family:inherit;outline:none}
.aa-sortsel select:hover{border-color:var(--aa-gold)}
.aa-sortsel .aa-ms{position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:16px;color:#a08e7a;pointer-events:none}
.aa-shopgrid{padding:26px 0 40px;background:var(--aa-surface)}
.aa-shopgrid .aa-media{aspect-ratio:1/1.38}
.aa-sperks{background:var(--aa-bg);padding:26px 0;border-top:1px solid var(--aa-line)}
.aa-sperks-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;text-align:center}
@media(min-width:768px){.aa-sperks-grid{grid-template-columns:repeat(4,1fr)}}
.aa-sperks-grid>div{display:flex;align-items:center;justify-content:center;gap:9px;font-size:13px;font-weight:600;color:var(--aa-dim)}
.aa-sperks-grid .aa-ms{font-size:20px;color:var(--aa-goldbg)}
</style>
<section class="aa-tool" id="aa-catgrid">
  <div class="aa-wrap">
    <div class="aa-tool-in">
      [aa_category_count]
      <div class="aa-tool-r">
        <span class="aa-tool-sep"></span>
        <div class="aa-sortwrap">
          <label>Sort by:</label>
          <div class="aa-sortsel">
            <select data-aa-sort>
              <option value="featured" selected>Popularity</option>
              <option value="price_asc">Price: Low to High</option>
              <option value="price_desc">Price: High to Low</option>
              <option value="title">Title: A to Z</option>
            </select>
            <span class="aa-ms">expand_more</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="aa-shopgrid aa-shop">
  <div class="aa-wrap">
    [aa_books limit="12" paginate="yes"]
  </div>
</section>
<section class="aa-sperks">
  <div class="aa-wrap">
    <div class="aa-sperks-grid">
      <div><span class="aa-ms">bolt</span><span>Instant PDF download in 10s</span></div>
      <div><span class="aa-ms">lock</span><span>Secure UPI &amp; RuPay checkout</span></div>
      <div><span class="aa-ms">devices</span><span>Formatted for Kindle, iPad &amp; Phone</span></div>
      <div><span class="aa-ms">update</span><span>Lifetime free updates</span></div>
    </div>
  </div>
</section>
HTML;

/* ----------------------------------------------------------------- build */

$cat_sections = array(
        $aa_common_html . aa_nav_html( 'shop' ),
        $aa_cat_hero . '[aa_category_hero]',
        $aa_cat_grid,
        aa_footer_html(),
);

$existing = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'private', 'draft' ), 'name' => 'aa-category-archive', 'numberposts' => 1 ) );
if ( $existing ) {
        $cat_page_id = (int) $existing[0]->ID;
        wp_update_post( array( 'ID' => $cat_page_id, 'post_status' => 'private', 'post_title' => 'Category Archive' ) );
} else {
        $cat_page_id = (int) wp_insert_post(
                array(
                        'post_type'    => 'page',
                        'post_title'   => 'Category Archive',
                        'post_name'    => 'aa-category-archive',
                        'post_status'  => 'private',
                        'comment_status' => 'closed',
                        'ping_status'  => 'closed',
                )
        );
}
if ( ! $cat_page_id ) { wp_die( 'Could not create the Category Archive page' ); }

update_post_meta( $cat_page_id, '_aa_canvas', '1' );
aa_build_page( $cat_page_id, $cat_sections );
update_option( 'aa_category_page', $cat_page_id );

// Category descriptions power the hero subtitle — set the Stitch copy once.
$aa_cat_desc = array(
        'technology' => 'AI, WordPress and the craft of shipping digital products — practical, battle-tested guides for indie builders and software engineers.',
        'business'   => 'Lean side hustles, product launches and the playbooks that actually pay — field notes for founders and operators.',
        'self-help'  => 'Attention, discipline and calm — honest systems for doing deep work in a noisy world.',
        'fiction'    => 'Stories that travel — from Himalayan trails to far shores and quiet rooms, told by voices worth staying up for.',
);
foreach ( $aa_cat_desc as $slug => $desc ) {
        $t = get_term_by( 'slug', $slug, 'product_cat' );
        if ( $t && '' === trim( (string) $t->description ) ) {
                wp_update_term( $t->term_id, 'product_cat', array( 'description' => $desc ) );
        }
}

echo 'CATEGORY-ARCHIVE-OK page=' . $cat_page_id . ' sections=' . count( $cat_sections ) . "\n";
