<?php
/**
 * Rebuild the Home page (post 24) as a full Elementor page.
 * Every section is a self-contained Elementor "html" widget:
 * custom HTML + inline CSS + WooCommerce shortcodes (all free).
 *
 * Run:  wp eval-file scripts/set_elementor_home.php
 */

$page_id = 24;

$hero_html = <<<'HTML'
<style>
.aa-hero{background:#0b1020;background-image:radial-gradient(52rem 30rem at 85% -10%,rgba(124,58,237,.28),transparent 60%),radial-gradient(40rem 26rem at -10% 110%,rgba(79,70,229,.22),transparent 60%);color:#fff;padding:84px 24px 92px;overflow:hidden;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw)}
.aa-wrap{max-width:1140px;margin:0 auto}
.aa-hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:48px;align-items:center}
.aa-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(124,58,237,.16);border:1px solid rgba(167,139,250,.35);color:#c4b5fd;font-size:13px;font-weight:600;letter-spacing:.4px;padding:7px 14px;border-radius:999px}
.aa-badge i{width:7px;height:7px;border-radius:50%;background:#34d399;box-shadow:0 0 8px #34d399}
body{overflow-x:hidden}
.aa-hero h1{color:#fff !important;font-size:clamp(36px,4.6vw,56px);line-height:1.12;font-weight:800;margin:20px 0 16px;letter-spacing:-.5px}
.aa-hero h1 em{font-style:normal;background:linear-gradient(92deg,#a78bfa,#5eead4);-webkit-background-clip:text;background-clip:text;color:transparent}
.aa-hero p.aa-sub{font-size:17px;line-height:1.7;color:#94a3b8;max-width:520px;margin:0 0 30px}
.aa-cta{display:flex;gap:14px;flex-wrap:wrap;margin:0 0 40px}
.aa-btn{display:inline-flex;align-items:center;gap:8px;padding:14px 26px;border-radius:12px;font-weight:700;font-size:15px;text-decoration:none;transition:transform .18s ease,box-shadow .18s ease}
.aa-btn.primary{background:linear-gradient(95deg,#6d28d9,#4f46e5);color:#fff;box-shadow:0 12px 30px rgba(79,70,229,.4)}
.aa-btn.primary:hover{transform:translateY(-2px);box-shadow:0 16px 36px rgba(79,70,229,.5)}
.aa-btn.ghost{border:1px solid rgba(255,255,255,.25);color:#e2e8f0}
.aa-btn.ghost:hover{border-color:rgba(255,255,255,.5)}
.aa-stats{display:flex;gap:0;border-top:1px solid rgba(255,255,255,.12);padding-top:24px;max-width:520px}
.aa-stats div{flex:1}
.aa-stats div + div{border-left:1px solid rgba(255,255,255,.12);padding-left:22px;margin-left:22px}
.aa-stats b{display:block;font-size:24px;font-weight:800;color:#fff}
.aa-stats span{font-size:13px;color:#818cf8}
.aa-shelf{position:relative;height:470px}
.aa-shelf:before{content:"";position:absolute;inset:8% 12%;background:radial-gradient(closest-side,rgba(109,40,217,.45),transparent);filter:blur(28px);border-radius:50%}
.aa-shelf img{position:absolute;border-radius:10px;box-shadow:0 30px 60px rgba(2,6,23,.65),0 4px 14px rgba(2,6,23,.5);width:190px;height:auto}
.aa-shelf .c-left{left:2%;top:16%;transform:rotate(-10deg) scale(.92);z-index:1}
.aa-shelf .c-right{right:2%;top:16%;transform:rotate(10deg) scale(.92);z-index:1}
.aa-shelf .c-center{left:50%;top:4%;transform:translateX(-50%);width:250px;z-index:2;animation:aa-float 5.5s ease-in-out infinite}
.aa-chip{position:absolute;z-index:3;background:#fff;color:#0f172a;font-weight:700;font-size:13px;padding:7px 13px;border-radius:999px;box-shadow:0 8px 20px rgba(2,6,23,.4)}
.aa-chip.a{top:2%;left:16%}
.aa-chip.b{bottom:16%;right:8%;background:#34d399;color:#052e1a}
@keyframes aa-float{0%,100%{margin-top:0}50%{margin-top:-14px}}
@media (max-width:900px){.aa-hero{padding:56px 20px 64px}.aa-hero-grid{grid-template-columns:1fr;gap:56px}.aa-shelf{height:400px;max-width:420px;margin:0 auto;width:100%}}
</style>
<section class="aa-hero">
  <div class="aa-wrap">
    <div class="aa-hero-grid">
      <div>
        <span class="aa-badge"><i></i>Instant PDF delivery · priced in ₹</span>
        <h1>Every great idea,<br><em>one download away.</em></h1>
        <p class="aa-sub">Hand-picked ebooks on technology, business and self-growth. Buy once, download instantly, keep the PDF forever — no subscriptions, no waiting for delivery.</p>
        <div class="aa-cta">
          <a class="aa-btn primary" href="/shop/">Browse the bookstore
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
          <a class="aa-btn ghost" href="/about/">About Annaadhya</a>
        </div>
        <div class="aa-stats">
          <div><b>6</b><span>Curated titles</span></div>
          <div><b>4</b><span>Collections</span></div>
          <div><b>₹149</b><span>Starting price</span></div>
        </div>
      </div>
      <div class="aa-shelf" aria-hidden="true">
        <img class="c-left" src="/wp-content/uploads/2026/11/echoes-himalayas-200x300.png" alt="">
        <img class="c-right" src="/wp-content/uploads/2026/11/ai-for-everyone-200x300.png" alt="">
        <a href="/product/wordpress-mastery/"><img class="c-center" src="/wp-content/uploads/2026/11/wordpress-mastery.png" alt="WordPress Mastery ebook cover"></a>
        <span class="aa-chip a">Bestseller</span>
        <span class="aa-chip b">Instant PDF</span>
      </div>
    </div>
  </div>
</section>
HTML;

$featured_html = <<<'HTML'
<style>
.aa-feat{background:#f8fafc;padding:76px 24px 84px;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw)}
.aa-feat .aa-wrap{max-width:1140px;margin:0 auto}
.aa-feat-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:36px}
.aa-feat-head h2{font-size:clamp(26px,3vw,34px);font-weight:800;color:#0f172a;letter-spacing:-.4px;margin:0 0 6px}
.aa-feat-head p{margin:0;color:#64748b;font-size:15px}
.aa-feat-all{color:#4f46e5;font-weight:700;font-size:15px;text-decoration:none;white-space:nowrap}
.aa-feat-all:hover{text-decoration:underline}
.aa-feat ul.products{display:grid !important;grid-template-columns:repeat(3,1fr) !important;gap:26px;margin:0;padding:0;list-style:none}
.aa-feat ul.products:before,.aa-feat ul.products:after{display:none}
.aa-feat li.product{float:none !important;width:100% !important;margin:0 !important;background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:14px 14px 20px;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;overflow:hidden}
.aa-feat li.product:hover{transform:translateY(-5px);box-shadow:0 22px 44px rgba(15,23,42,.12);border-color:#c7d2fe}
.aa-feat li.product img{width:100% !important;height:280px;object-fit:cover;border-radius:12px;margin:0 0 14px !important}
.aa-feat .woocommerce-loop-product__title{font-size:16px !important;font-weight:650;color:#0f172a;line-height:1.45;margin:0 0 8px !important;padding:0 !important}
.aa-feat .price{font-size:18px;font-weight:750;color:#4f46e5;margin:0 0 14px !important;display:block}
.aa-feat del{color:#94a3b8;font-weight:500;opacity:1}
.aa-feat ins{text-decoration:none;background:none}
.aa-feat .button{display:inline-block;background:#4f46e5 !important;color:#fff !important;border-radius:10px !important;padding:11px 18px !important;font-weight:650;font-size:14px !important;text-decoration:none;transition:background .2s ease}
.aa-feat .button:hover{background:#4338ca !important}
.aa-feat span.onsale{background:#7c3aed !important;border-radius:999px !important;padding:5px 12px !important;font-size:12px;font-weight:700;min-height:0;min-width:0;line-height:1.4;top:22px;left:22px !important;margin:0}
@media (max-width:900px){.aa-feat ul.products{grid-template-columns:repeat(2,1fr) !important}}
@media (max-width:620px){.aa-feat ul.products{grid-template-columns:1fr !important}.aa-feat-head{flex-direction:column;align-items:flex-start}}
</style>
<section class="aa-feat">
  <div class="aa-wrap">
    <div class="aa-feat-head">
      <div>
        <h2>Featured eBooks</h2>
        <p>Every title is a digital download — checkout and read in minutes.</p>
      </div>
      <a class="aa-feat-all" href="/shop/">View all books →</a>
    </div>
    [products limit="6" columns="3"]
  </div>
</section>
HTML;

$cats_html = <<<'HTML'
<style>
.aa-cats{background:#fff;padding:72px 24px}
.aa-cats .aa-wrap{max-width:1140px;margin:0 auto}
.aa-cats h2{font-size:clamp(24px,2.8vw,32px);font-weight:800;color:#0f172a;margin:0 0 8px;letter-spacing:-.4px}
.aa-cats p.aa-lede{color:#64748b;margin:0 0 34px;font-size:15px}
.aa-cats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
.aa-cat{display:block;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:26px 24px;text-decoration:none !important;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
.aa-cat *{text-decoration:none !important}
.aa-cat:hover{transform:translateY(-4px);box-shadow:0 18px 36px rgba(15,23,42,.1);border-color:#c7d2fe}
.aa-cat i{display:inline-flex;width:44px;height:44px;border-radius:12px;align-items:center;justify-content:center;margin-bottom:16px}
.aa-cat h3{font-size:17px;font-weight:700;color:#0f172a;margin:0 0 6px}
.aa-cat span{font-size:14px;color:#64748b;line-height:1.55;display:block}
.aa-t1{background:#eef2ff;color:#4f46e5}
.aa-t2{background:#fef3c7;color:#b45309}
.aa-t3{background:#fce7f3;color:#be185d}
.aa-t4{background:#dcfce7;color:#15803d}
@media (max-width:900px){.aa-cats-grid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:560px){.aa-cats-grid{grid-template-columns:1fr}}
</style>
<section class="aa-cats">
  <div class="aa-wrap">
    <h2>Browse by collection</h2>
    <p class="aa-lede">Four shelves, one obsession — books you can actually finish and actually use.</p>
    <div class="aa-cats-grid">
      <a class="aa-cat" href="/product-category/technology/">
        <i class="aa-t1"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg></i>
        <h3>Technology</h3><span>WordPress, AI and practical digital skills for builders.</span>
      </a>
      <a class="aa-cat" href="/product-category/business/">
        <i class="aa-t2"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></i>
        <h3>Business</h3><span>Side hustles, launches and money that doesn't need luck.</span>
      </a>
      <a class="aa-cat" href="/product-category/self-help/">
        <i class="aa-t3"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/></svg></i>
        <h3>Self-Help</h3><span>Focus, habits and discipline in an age of noise.</span>
      </a>
      <a class="aa-cat" href="/product-category/fiction/">
        <i class="aa-t4"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4h7a3 3 0 0 1 3 3v13a2.5 2.5 0 0 0-2.5-2.5H2z"/><path d="M22 4h-7a3 3 0 0 0-3 3v13a2.5 2.5 0 0 1 2.5-2.5H22z"/></svg></i>
        <h3>Fiction</h3><span>Stories that travel far — starting from the Himalayas.</span>
      </a>
    </div>
  </div>
</section>
HTML;

$why_html = <<<'HTML'
<style>
.aa-why{background:#0f172a;color:#fff;padding:72px 24px;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw)}
.aa-why .aa-wrap{max-width:1140px;margin:0 auto}
.aa-why-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px}
.aa-why-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:30px 28px}
.aa-why-card i{display:inline-flex;width:46px;height:46px;border-radius:12px;background:rgba(139,92,246,.18);border:1px solid rgba(139,92,246,.4);color:#c4b5fd;align-items:center;justify-content:center;margin-bottom:18px}
.aa-why-card h3{font-size:18px;font-weight:700;margin:0 0 8px;color:#fff}
.aa-why-card p{margin:0;color:#94a3b8;font-size:15px;line-height:1.7}
@media (max-width:820px){.aa-why-grid{grid-template-columns:1fr}}
</style>
<section class="aa-why">
  <div class="aa-wrap">
    <div class="aa-why-grid">
      <div class="aa-why-card">
        <i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4 14h6l-1 8 9-12h-6z"/></svg></i>
        <h3>Instant delivery</h3>
        <p>Your PDF lands in your account and inbox seconds after checkout. No shipping, no queues, no waiting for the postman.</p>
      </div>
      <div class="aa-why-card">
        <i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><rect x="4" y="17" width="16" height="4" rx="1"/></svg></i>
        <h3>Read anywhere</h3>
        <p>Download once and keep it forever — on your phone, laptop or tablet. Re-download anytime from your account page.</p>
      </div>
      <div class="aa-why-card">
        <i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></i>
        <h3>Secure checkout</h3>
        <p>Powered by WooCommerce with India-friendly payments — UPI, cards and net-banking through the gateway you choose.</p>
      </div>
    </div>
  </div>
</section>
HTML;

$cta_html = <<<'HTML'
<style>
.aa-cta-band{background:linear-gradient(95deg,#4f46e5 0%,#7c3aed 60%,#9333ea 100%);color:#fff;padding:72px 24px;text-align:center;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw)}
.aa-cta-band .aa-wrap{max-width:760px;margin:0 auto}
.aa-cta-band h2{font-size:clamp(26px,3.4vw,38px);font-weight:800;margin:0 0 12px;letter-spacing:-.4px}
.aa-cta-band p{color:rgba(255,255,255,.85);font-size:16px;line-height:1.7;margin:0 0 30px}
.aa-cta-band .aa-btn-w{display:inline-block;background:#fff;color:#4338ca;font-weight:750;font-size:15px;padding:14px 30px;border-radius:12px;text-decoration:none;box-shadow:0 14px 34px rgba(2,6,23,.28);transition:transform .18s ease}
.aa-cta-band .aa-btn-w:hover{transform:translateY(-2px)}
</style>
<section class="aa-cta-band">
  <div class="aa-wrap">
    <h2>Start reading in the next two minutes.</h2>
    <p>Pick a book, check out, and it's on your device — that's the whole process.</p>
    <a class="aa-btn-w" href="/shop/">Browse the bookstore</a>
  </div>
</section>
HTML;

$sections = array( $hero_html, $featured_html, $cats_html, $why_html, $cta_html );

function aa_section( $html, $i ) {
        return array(
                'id'       => sprintf( 'aa%06x', 0x100000 + $i ),
                'elType'   => 'section',
                'settings' => array(
                        'gap'             => 'no',
                        'structure'       => '10',
                        'stretch_section' => 'section-stretched',
                        'padding'         => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
                ),
                'elements' => array(
                        array(
                                'id'       => sprintf( 'ab%06x', 0x100000 + $i ),
                                'elType'   => 'column',
                                'settings' => array( '_column_size' => 100, '_inline_size' => null ),
                                'elements' => array(
                                        array(
                                                'id'         => sprintf( 'ac%06x', 0x100000 + $i ),
                                                'elType'     => 'widget',
                                                'widgetType' => 'html',
                                                'settings'   => array( 'html' => $html ),
                                                'elements'   => array(),
                                        ),
                                ),
                        ),
                ),
        );
}

$data = array();
foreach ( $sections as $i => $h ) { $data[] = aa_section( $h, $i ); }

update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $page_id, '_elementor_page_template', 'elementor_header_footer' );
update_post_meta( $page_id, '_elementor_version', '4.4.0' );
update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
update_post_meta( $page_id, 'site-post-title', 'disabled' );

// Elementor caches rendered element HTML in postmeta; clear it on every rebuild.
delete_post_meta( $page_id, '_elementor_element_cache' );
delete_post_meta( $page_id, '_elementor_css' );
delete_post_meta( $page_id, '_elementor_page_assets' );

echo 'HOME-ELEMENTOR-OK sections=' . count( $data ) . "\n";
