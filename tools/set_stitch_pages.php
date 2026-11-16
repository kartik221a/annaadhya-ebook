<?php
/**
 * Rebuild Home (24) and Shop (5) from Stitch designs ("Obsidian & Gold Lit").
 * Generated via the Stitch MCP (project 7746485506472124034) and ported to
 * self-contained Elementor "html" widgets: scoped aa-* CSS, WooCommerce data
 * through the [aa_books] shortcode (mu-plugin aa-store-tweaks.php).
 *
 * Run:  wp eval-file tools/set_stitch_pages.php
 */

/* Build as an admin: Elementor registers _elementor_data with a sanitize
 * callback that runs wp_kses over every widget string for users without
 * unfiltered_html — wp-cli runs as user 0, so <style> tags would be stripped
 * from every html widget. Impersonate an administrator for the build. */
if ( ! current_user_can( 'unfiltered_html' ) ) {
        $aa_build_admins = get_users( array( 'role' => 'Administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID', 'order' => 'ASC' ) );
        if ( $aa_build_admins ) { wp_set_current_user( (int) reset( $aa_build_admins ) ); }
}

/* ---------------------------------------------------------------- shared */

$aa_common_html = <<<'HTML'
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
<style>
:root{--aa-bg:#0a0e13;--aa-surface:#101419;--aa-low:#181c21;--aa-cont:#1c2025;--aa-high:#262a30;--aa-high2:#31353b;
--aa-tx:#e0e2ea;--aa-warm:#d8c3ad;--aa-dim:#8b8fa3;--aa-line:rgba(160,142,122,.22);--aa-line2:rgba(160,142,122,.4);
--aa-gold:#ffc174;--aa-goldbg:#f59e0b;--aa-ongold:#472a00;--aa-tert:#ffc08e}
html{scroll-behavior:smooth}
body{background:var(--aa-bg)!important;color:var(--aa-tx)!important;font-family:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif!important;margin:0!important;padding:0!important;overflow-x:hidden}
body.admin-bar .aa-nav{top:32px}
.elementor-widget-html{width:100%}
p,h1,h2,h3,h4{margin:0}
.aa-ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;display:inline-block;line-height:1;text-transform:none;letter-spacing:normal;word-wrap:normal;white-space:nowrap;direction:ltr;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;-webkit-font-smoothing:antialiased;user-select:none}
.aa-wrap{max-width:1280px;margin:0 auto;padding:0 24px}
@media(min-width:768px){.aa-wrap{padding:0 40px}}
.aa-caps{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.aa-caps-gold{color:var(--aa-goldbg)}
.aa-btn-gold{display:inline-flex;align-items:center;gap:8px;background:var(--aa-goldbg);color:var(--aa-ongold)!important;font-weight:700;font-size:14px;padding:13px 24px;border-radius:10px;text-decoration:none!important;transition:filter .18s,transform .18s;cursor:pointer;border:0}
.aa-btn-gold:hover{filter:brightness(1.07);transform:translateY(-1px)}
.aa-btn-gold .aa-ms{font-size:17px}
.aa-btn-ghost{display:inline-flex;align-items:center;gap:8px;background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-tx)!important;font-weight:600;font-size:14px;padding:13px 24px;border-radius:10px;text-decoration:none!important;transition:border-color .18s,color .18s}
.aa-btn-ghost:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
.aa-btn-ghost .aa-ms{font-size:17px}
/* ---- sticky nav ---- */
.aa-nav{position:sticky;top:0;z-index:900;background:rgba(16,20,25,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--aa-line)}
.aa-nav-in{display:flex;align-items:center;justify-content:space-between;height:80px;gap:16px}
.aa-brand{display:flex;align-items:center;gap:10px;text-decoration:none!important}
.aa-brand-ic{width:40px;height:40px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line2);display:flex;align-items:center;justify-content:center;color:var(--aa-goldbg);flex:none}
.aa-brand-ic .aa-ms{font-size:22px}
.aa-brand-name{color:var(--aa-gold);font-weight:700;font-size:20px;letter-spacing:-.02em}
.aa-menu{display:flex;gap:30px}
.aa-menu a{color:var(--aa-warm);text-decoration:none!important;font-size:14px;font-weight:600;padding-bottom:4px;border-bottom:2px solid transparent;transition:color .15s}
.aa-menu a:hover{color:var(--aa-tx)}
.aa-menu a.aa-on{color:var(--aa-gold);border-color:var(--aa-goldbg)}
.aa-nav-r{display:flex;align-items:center;gap:12px}
.aa-nav-search{position:relative;display:none;align-items:center}
@media(min-width:1024px){.aa-nav-search{display:block;width:190px}}
.aa-nav-search input{width:100%;background:var(--aa-bg);border:1px solid var(--aa-line2);border-radius:10px;padding:8px 10px 8px 34px;color:var(--aa-tx);font-size:13px;outline:none;font-family:inherit}
.aa-nav-search input::placeholder{color:#7a7f8f}
.aa-nav-search .aa-ms{position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:18px;color:var(--aa-dim);pointer-events:none}
.aa-cartbtn,.aa-actbtn{position:relative;padding:9px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-warm)!important;text-decoration:none!important;display:flex}
.aa-cartbtn .aa-ms,.aa-actbtn .aa-ms{font-size:20px}
.aa-cartbtn:hover,.aa-actbtn:hover{color:var(--aa-gold)!important;border-color:var(--aa-gold)}
.aa-wishbtn{position:absolute;top:10px;right:10px;z-index:2;width:34px;height:34px;border-radius:50%;background:rgba(10,14,19,.72);backdrop-filter:blur(4px);border:1px solid var(--aa-line2);color:#c9b8a4!important;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;transition:color .15s,border-color .15s,transform .15s}
.aa-wishbtn .aa-ms{font-size:17px}
.aa-wishbtn:hover{color:var(--aa-goldbg)!important;border-color:var(--aa-goldbg);transform:scale(1.06)}
.aa-wishbtn.aa-on{color:var(--aa-goldbg)!important;border-color:var(--aa-goldbg);background:rgba(71,42,0,.55)}
.aa-wishbtn.aa-on .aa-ms{font-variation-settings:'FILL' 1}
.aa-toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%) translateY(16px);z-index:2000;background:var(--aa-high);color:var(--aa-tx);font-size:13px;font-weight:600;border:1px solid var(--aa-goldbg);border-radius:10px;padding:12px 18px;opacity:0;transition:opacity .25s,transform .25s;pointer-events:none;box-shadow:0 12px 32px rgba(0,0,0,.5)}
.aa-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.aa-cartcount{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;border-radius:9px;background:var(--aa-goldbg);color:var(--aa-ongold);font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 4px}
.aa-cartcount[hidden]{display:none}
.aa-nav-cta{display:none!important}
@media(min-width:768px){.aa-nav-cta{display:inline-flex!important;padding:10px 16px}}
.aa-burger{display:flex;background:none;border:0;color:var(--aa-warm);cursor:pointer;padding:6px}
.aa-burger .aa-ms{font-size:26px}
@media(min-width:768px){.aa-burger{display:none}}
.aa-mobile-menu{display:none;border-top:1px solid var(--aa-line);background:var(--aa-surface);padding:10px 24px 18px}
.aa-mobile-menu.open{display:block}
.aa-mobile-menu a{display:block;padding:11px 0;color:var(--aa-tx)!important;text-decoration:none!important;font-weight:600;font-size:15px;border-bottom:1px solid var(--aa-line)}
.aa-mobile-menu a:last-child{border-bottom:0}
@media(max-width:767px){.aa-menu{display:none}}
/* ---- book cards (rendered by [aa_books]) ---- */
.aa-products{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;list-style:none;margin:0;padding:0}
@media(max-width:1024px){.aa-products{grid-template-columns:repeat(2,1fr)}}
@media(max-width:640px){.aa-products{grid-template-columns:1fr}}
.aa-card{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:12px;padding:16px;display:flex;flex-direction:column;transition:border-color .2s,transform .2s,box-shadow .2s;position:relative}
.aa-card:hover{border-color:rgba(245,158,11,.65);transform:translateY(-4px);box-shadow:0 18px 40px rgba(2,6,12,.5)}
.aa-media{position:relative;width:100%;aspect-ratio:3/4;border-radius:10px;overflow:hidden;background:var(--aa-bg);border:1px solid var(--aa-line);margin-bottom:16px}
.aa-media-link{display:block;width:100%;height:100%}
.aa-cover{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;display:block}
.aa-card:hover .aa-cover{transform:scale(1.05)}
.aa-fmt{position:absolute;top:12px;left:12px;background:rgba(10,14,19,.82);backdrop-filter:blur(6px);border:1px solid var(--aa-line2);color:#a08e7a;font-size:10px;font-weight:700;letter-spacing:.06em;padding:4px 8px;border-radius:6px;text-transform:uppercase}
.aa-badge{position:absolute;top:12px;right:52px;background:var(--aa-goldbg);color:var(--aa-ongold);font-size:10px;font-weight:800;letter-spacing:.06em;padding:4px 10px;border-radius:999px;text-transform:uppercase}
.aa-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px}
.aa-mcat{font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--aa-goldbg);text-transform:uppercase}
.aa-mauth{font-size:12px;color:var(--aa-dim);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aa-title{font-size:18px;font-weight:600;line-height:1.4;margin:0 0 10px;color:var(--aa-tx)}
.aa-title a{color:inherit!important;text-decoration:none!important}
.aa-card:hover .aa-title a{color:var(--aa-gold)}
.aa-stars{display:flex;align-items:center;gap:6px;margin-bottom:14px}
.aa-star{font-size:15px;color:var(--aa-goldbg);font-variation-settings:'FILL' 1,'wght' 500}
.aa-rate{font-size:12px;font-weight:700;color:var(--aa-tx)}
.aa-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;border-top:1px solid var(--aa-line);padding-top:14px;margin-top:auto}
.aa-pricing{display:flex;flex-direction:column;gap:2px}
.aa-now{display:flex;align-items:baseline;gap:8px;color:var(--aa-tx);font-size:18px;font-weight:700}
.aa-now del{color:var(--aa-dim);font-size:13px;font-weight:500}
.aa-now ins{text-decoration:none;background:none;color:var(--aa-tx)}
.aa-now .aa-only{font-size:20px;font-weight:800}
.aa-save{font-size:10px;font-weight:800;color:var(--aa-gold)}
.aa-add{display:inline-flex;align-items:center;gap:7px;background:var(--aa-goldbg);color:var(--aa-ongold)!important;font-weight:700;font-size:13px;padding:9px 14px;border-radius:10px;text-decoration:none!important;transition:filter .15s;white-space:nowrap}
.aa-add:hover{filter:brightness(1.08)}
.aa-add .aa-ms{font-size:17px}
.aa-empty{grid-column:1/-1;text-align:center;color:var(--aa-dim);padding:48px 0;font-size:15px}
/* ---- pager ---- */
.aa-pager-wrap{display:flex;flex-direction:column;gap:14px;align-items:center;margin-top:36px}
.aa-pager-info{color:var(--aa-dim);font-size:13px;margin:0}
.aa-pager-wrap ul{display:flex;gap:8px;list-style:none;padding:0;margin:0;flex-wrap:wrap;justify-content:center}
.aa-pager-wrap .page-numbers{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--aa-line2);border-radius:10px;padding:8px 14px;color:#a08e7a!important;font-size:13px;font-weight:600;text-decoration:none!important;background:var(--aa-cont)}
.aa-pager-wrap .page-numbers .aa-ms{font-size:16px}
.aa-pager-wrap .page-numbers.current{background:var(--aa-goldbg);color:var(--aa-ongold)!important;border-color:transparent}
.aa-pager-wrap .page-numbers:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
/* ---- eyebrow headings ---- */
.aa-eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.aa-eyebrow .aa-ms{font-size:20px;color:var(--aa-goldbg)}
</style>
HTML;

/* Sticky top navigation. $active: home|shop|about|contact|library|account. */
function aa_nav_html( $active = '' ) {
        $cls = function ( $key ) use ( $active ) { return $key === $active ? 'aa-on' : ''; };
        return '<header class="aa-nav">
  <div class="aa-wrap aa-nav-in">
    <a class="aa-brand" href="/">
      <span class="aa-brand-ic"><span class="aa-ms">auto_stories</span></span>
      <span class="aa-brand-name">Annaadhya</span>
    </a>
    <nav class="aa-menu">
      <a href="/" class="' . $cls( 'home' ) . '">Home</a>
      <a href="/shop/" class="' . $cls( 'shop' ) . '">Shop</a>
      <a href="/about/" class="' . $cls( 'about' ) . '">About</a>
      <a href="/contact/" class="' . $cls( 'contact' ) . '">Contact</a>
    </nav>
    <div class="aa-nav-r">
      <form class="aa-nav-search" action="/shop/" method="get">
        <span class="aa-ms">search</span>
        <input type="search" name="book" placeholder="Quick search books..." aria-label="Search books">
      </form>
      <a class="aa-actbtn" href="/my-account/" aria-label="My Account">
        <span class="aa-ms">person</span>
      </a>
      <a class="aa-cartbtn" href="/cart/" aria-label="Cart">
        <span class="aa-ms">shopping_bag</span>
        <span class="aa-cartcount" data-aa-cartcount hidden>0</span>
      </a>
      <a class="aa-btn-gold aa-nav-cta" href="/shop/"><span>Browse Books</span><span class="aa-ms">arrow_forward</span></a>
      <button class="aa-burger" type="button" aria-label="Open menu"><span class="aa-ms">menu</span></button>
    </div>
  </div>
  <div class="aa-mobile-menu">
    <a href="/">Home</a>
    <a href="/shop/">Shop</a>
    <a href="/about/">About</a>
    <a href="/contact/">Contact</a>
    <a href="/library/">My Library</a>
    <a href="/my-account/">My Account</a>
    <a href="/cart/">Cart</a>
  </div>
</header>';
}

/* Footer + the page behaviour script (cart badge, category counts, sort, newsletter, wishlist). */
function aa_footer_html() {
        $year = date( 'Y' );
        return '<style>
.aa-footer{background:var(--aa-bg);border-top:1px solid var(--aa-line);padding:64px 0 32px}
.aa-fgrid{display:grid;grid-template-columns:1.8fr 1fr 1fr 1fr 1fr;gap:34px;margin-bottom:48px}
@media(max-width:1024px){.aa-fgrid{grid-template-columns:1fr 1fr 1fr}}
@media(max-width:900px){.aa-fgrid{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.aa-fgrid{grid-template-columns:1fr}}
.aa-fbrand-row{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.aa-fbrand-row .aa-brand-ic{width:34px;height:34px}
.aa-fbrand-row .aa-brand-ic .aa-ms{font-size:19px}
.aa-fbrand-name{color:var(--aa-gold);font-weight:700;font-size:18px}
.aa-fbrand p{color:var(--aa-dim);font-size:14px;line-height:1.75;max-width:380px;margin:14px 0 20px}
.aa-paychips{display:flex;gap:8px;flex-wrap:wrap}
.aa-paychips span{font-size:10px;font-weight:700;letter-spacing:.06em;color:#a08e7a;background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:6px;padding:5px 9px}
.aa-fgrid h4{color:var(--aa-gold);font-size:16px;font-weight:600;margin:0 0 16px}
.aa-fgrid ul{list-style:none;margin:0;padding:0;display:grid;gap:10px}
.aa-fgrid ul a{color:var(--aa-dim)!important;text-decoration:none!important;font-size:14px;transition:color .15s}
.aa-fgrid ul a:hover{color:var(--aa-gold)!important}
.aa-fbottom{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px;border-top:1px solid var(--aa-line);padding-top:28px}
.aa-fsocial{display:flex;gap:10px}
.aa-fsocial a{width:36px;height:36px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line2);display:flex;align-items:center;justify-content:center;color:var(--aa-dim)!important}
.aa-fsocial a:hover{color:var(--aa-gold)!important;border-color:var(--aa-gold)}
.aa-fsocial .aa-ms{font-size:18px}
.aa-fbottom p{color:var(--aa-dim);font-size:13px;margin:0}
.aa-fchips{display:flex;gap:10px;flex-wrap:wrap}
.aa-fchips span{font-size:10px;font-weight:700;letter-spacing:.08em;color:var(--aa-dim);background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:6px;padding:4px 8px}
</style>
<footer class="aa-footer">
  <div class="aa-wrap">
    <div class="aa-fgrid">
      <div class="aa-fbrand">
        <div class="aa-fbrand-row"><span class="aa-brand-ic"><span class="aa-ms">auto_stories</span></span><span class="aa-fbrand-name">Annaadhya</span></div>
        <p>Empowering Indian readers with accessible, high-quality digital knowledge. Instantly downloadable PDFs optimized for phones, tablets and e-readers.</p>
        <div class="aa-paychips"><span>UPI</span><span>RUPAY</span><span>GPAY</span><span>PHONEPE</span></div>
      </div>
      <div>
        <h4>Shop</h4>
        <ul>
          <li><a href="/shop/">Bestsellers</a></li>
          <li><a href="/product-category/technology/">Technology</a></li>
          <li><a href="/product-category/business/">Business</a></li>
          <li><a href="/product-category/self-help/">Self-Help</a></li>
          <li><a href="/product-category/fiction/">Fiction</a></li>
          <li><a href="/shop/">Bundles &amp; Deals</a></li>
        </ul>
      </div>
      <div>
        <h4>Company</h4>
        <ul>
          <li><a href="/about/">About Us</a></li>
          <li><a href="/contact/">Contact Support</a></li>
          <li><a href="/contact/">Author Submissions</a></li>
          <li><a href="/contact/">Affiliate Program</a></li>
        </ul>
      </div>
      <div>
        <h4>Account</h4>
        <ul>
          <li><a href="/library/">My Library</a></li>
          <li><a href="/my-account/">My Account</a></li>
          <li><a href="/my-account/wishlist/">Wishlist</a></li>
          <li><a href="/cart/">Shopping Cart</a></li>
          <li><a href="/web-admin/">Site Admin</a></li>
        </ul>
      </div>
      <div>
        <h4>Legal</h4>
        <ul>
          <li><a href="/privacy-policy/">Privacy Policy</a></li>
          <li><a href="/terms/">Terms &amp; Conditions</a></li>
          <li><a href="/refund-policy/">Refund Policy</a></li>
          <li><a href="/digital-delivery-policy/">Digital Delivery</a></li>
          <li><a href="/copyright-policy/">Copyright Policy</a></li>
        </ul>
      </div>
    </div>
    <div class="aa-fbottom">
      <div class="aa-fsocial">
        <a href="#" aria-label="Instagram"><span class="aa-ms">photo_camera</span></a>
        <a href="#" aria-label="LinkedIn"><span class="aa-ms">work</span></a>
        <a href="#" aria-label="YouTube"><span class="aa-ms">smart_display</span></a>
        <a href="#" aria-label="Telegram"><span class="aa-ms">send</span></a>
      </div>
      <p>&copy; ' . $year . ' Annaadhya Ebooks. All rights reserved. Crafted for Indian readers.</p>
      <div class="aa-fchips"><span>100% SECURE CHECKOUT</span><span>256-BIT ENCRYPTION</span></div>
    </div>
  </div>
  <script>
(function(){
  function refreshCart(){
    fetch("/?wc-ajax=aa_cart_count",{headers:{"Cache-Control":"no-cache"}})
      .then(function(r){return r.ok?r.json():Promise.reject()})
      .then(function(d){var n=d&&d.count||0;
        document.querySelectorAll("[data-aa-cartcount]").forEach(function(b){b.textContent=n;b.hidden=n<1})})
      .catch(function(){});
  }
  refreshCart();
  if(window.jQuery){jQuery(document.body).on("added_to_cart",refreshCart)}
  document.addEventListener("click",function(e){
    var t=e.target.closest?e.target.closest(".add_to_cart_button"):null;
    if(t){setTimeout(refreshCart,1500)}
  },true);
  var burger=document.querySelector(".aa-burger"),mm=document.querySelector(".aa-mobile-menu");
  if(burger&&mm){burger.addEventListener("click",function(){mm.classList.toggle("open")})}
  var sp0=new URLSearchParams(location.search),st0=(sp0.get("book")||sp0.get("s")||"").trim();
  if(st0){
    var tt=document.querySelector("[data-aa-bookshelf-title]"),ts=document.querySelector("[data-aa-bookshelf-sub]");
    if(tt){tt.textContent="";var em=document.createElement("span");em.className="aa-grad";em.textContent="\u201C"+st0+"\u201D";tt.append("Results for ",em)}
    if(ts){ts.textContent="Every ebook matching your search \u2014 instant PDF download, optimized for all devices."}
  }
  fetch("/wp-json/wc/store/v1/products/categories?per_page=30")
    .then(function(r){return r.ok?r.json():Promise.reject()})
    .then(function(cats){
      var all=0;
      (cats||[]).forEach(function(c){
        all+=c.count||0;
        document.querySelectorAll("[data-aa-catcount]").forEach(function(el){
          if(el.getAttribute("data-aa-catcount")===c.slug){
            el.textContent=(c.count||0)+(c.count===1?" Ebook":" Ebooks")
          }
        });
        document.querySelectorAll("[data-aa-catnum]").forEach(function(el){
          if(el.getAttribute("data-aa-catnum")===c.slug){
            el.textContent=c.count||0
          }
        });
      });
      document.querySelectorAll("[data-aa-catnum]").forEach(function(el){
        if(el.getAttribute("data-aa-catnum")==="_all"){
          el.textContent=all
        }
      });
    })
    .catch(function(){});
  var sel=document.querySelector("[data-aa-sort]"),grid=document.querySelector(".aa-shopgrid .aa-products");
  if(sel&&grid){sel.addEventListener("change",function(){
    var items=[].slice.call(grid.querySelectorAll("li.aa-card"));if(!items.length)return;
    var v=sel.value;
    items.sort(function(x,y){
      var px=parseFloat(x.getAttribute("data-price"))||0,py=parseFloat(y.getAttribute("data-price"))||0,
          tx=(x.getAttribute("data-title")||"").toLowerCase(),ty=(y.getAttribute("data-title")||"").toLowerCase();
      if(v==="price_asc")return px-py;
      if(v==="price_desc")return py-px;
      if(v==="title")return tx.localeCompare(ty);
      return 0});
    items.forEach(function(it){grid.appendChild(it)})})}
  var totalEl=document.querySelector("[data-aa-total]");
  if(totalEl){
    var sp=new URLSearchParams(location.search);
    var q=sp.get("book")||sp.get("s")||"";
    fetch("/wp-json/wc/store/v1/products?per_page=1"+(q?"&search="+encodeURIComponent(q):""))
      .then(function(r){var t=r.headers.get("X-WP-Total");return t===null?Promise.reject():t})
      .then(function(total){
        var gridEl=document.querySelector(".aa-shopgrid .aa-products");
        var n=gridEl?gridEl.querySelectorAll("li.aa-card").length:0;
        totalEl.innerHTML="Showing <strong>1&ndash;"+n+"</strong> of <strong>"+total+"</strong> ebooks";
        totalEl.style.display="";
      }).catch(function(){});
  }
  function toast(msg){var t=document.createElement("div");t.className="aa-toast";t.textContent=msg;document.body.appendChild(t);setTimeout(function(){t.classList.add("show")},20);setTimeout(function(){t.classList.remove("show");setTimeout(function(){t.remove()},300)},2600)}
  window.aaToast=toast;
  var nf=document.querySelector("[data-aa-news]");
  if(nf){nf.addEventListener("submit",function(e){e.preventDefault();
    var inp=nf.querySelector("input[type=email]");var fd=new FormData();
    fd.append("action","aa_newsletter_submit");fd.append("email",inp&&inp.value||"");
    var hp=nf.querySelector("[name=aa_hp]");fd.append("hp",hp?hp.value:"");
    fetch("/wp-admin/admin-ajax.php",{method:"POST",body:fd})
      .then(function(r){return r.json()}).then(function(d){
        var done=document.querySelector("[data-aa-news-done]"),err=document.querySelector("[data-aa-news-err]");
        if(d&&d.status==="ok"){if(done)done.hidden=false;if(err)err.hidden=true;if(inp)inp.value="";toast("Subscribed \u2014 welcome aboard!")}
        else{if(err){err.hidden=false;err.textContent=(d&&d.msg)||"Something went wrong. Please try again."}}
      }).catch(function(){var done=document.querySelector("[data-aa-news-done]");if(done)done.hidden=false});
  })}
  document.addEventListener("click",function(e){
    var b=e.target.closest?e.target.closest(".aa-wishbtn"):null;if(!b)return;
    e.preventDefault();e.stopPropagation();
    var fd=new FormData();fd.append("action","aa_wishlist_toggle");fd.append("id",b.getAttribute("data-id")||"");
    fetch("/wp-admin/admin-ajax.php",{method:"POST",body:fd,credentials:"same-origin"})
      .then(function(r){return r.json()})
      .then(function(d){
        if(d&&d.status==="login"){location.href="/my-account/?aa_wish=login";return}
        if(d&&d.status==="ok"){b.classList.toggle("aa-on",d.on);toast(d.on?"Saved to your wishlist":"Removed from wishlist")}
      }).catch(function(){});
  },true);
})();
  </script>
</footer>';
}

/* Elementor section wrapper: one full-width section holding one html widget. */
function aa_section( $html, $i ) {
        return array(
                'id'       => sprintf( 'aa%06x', 0x310000 + $i ),
                'elType'   => 'section',
                'settings' => array(
                        'gap'             => 'no',
                        'structure'       => '10',
                        'stretch_section' => 'section-stretched',
                        'padding'         => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
                ),
                'elements' => array(
                        array(
                                'id'       => sprintf( 'ab%06x', 0x320000 + $i ),
                                'elType'   => 'column',
                                'settings' => array( '_column_size' => 100, '_inline_size' => null ),
                                'elements' => array(
                                        array(
                                                'id'         => sprintf( 'ac%06x', 0x330000 + $i ),
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

function aa_build_page( $page_id, $sections ) {
        $data = array();
        foreach ( $sections as $i => $h ) { $data[] = aa_section( $h, $i ); }

        update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
        update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
        update_post_meta( $page_id, '_elementor_page_template', 'elementor_canvas' );
        update_post_meta( $page_id, '_elementor_version', '4.4.0' );
        update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
        update_post_meta( $page_id, 'site-post-title', 'disabled' );

        delete_post_meta( $page_id, '_elementor_element_cache' );
        delete_post_meta( $page_id, '_elementor_css' );
        delete_post_meta( $page_id, '_elementor_page_assets' );
}

/* ---------------------------------------------------------------- home */

$aa_hero = <<<'HTML'
<style>
.aa-hero{position:relative;padding:64px 0 96px;border-bottom:1px solid var(--aa-line);overflow:hidden;background:var(--aa-surface)}
.aa-hero-glow1{position:absolute;top:-160px;left:50%;transform:translateX(-50%);width:720px;height:520px;background:radial-gradient(closest-side,rgba(245,158,11,.16),transparent 70%);filter:blur(48px);pointer-events:none}
.aa-hero-glow2{position:absolute;top:30%;right:-80px;width:420px;height:420px;background:radial-gradient(closest-side,rgba(245,158,11,.08),transparent 70%);border-radius:50%;filter:blur(90px);pointer-events:none}
.aa-hero-grid{position:relative;display:grid;grid-template-columns:7fr 5fr;gap:48px;align-items:center}
@media(max-width:1024px){.aa-hero-grid{grid-template-columns:1fr;gap:56px}}
.aa-hero-pill{display:inline-flex;align-items:center;gap:10px;padding:7px 14px;border-radius:999px;background:var(--aa-cont);border:1px solid var(--aa-line2)}
.aa-hero-pill i{width:8px;height:8px;border-radius:50%;background:var(--aa-goldbg);animation:aa-pulse 1.8s ease-in-out infinite}
@keyframes aa-pulse{0%,100%{opacity:1}50%{opacity:.35}}
.aa-hero-pill span{font-size:11px;font-weight:800;letter-spacing:.1em;color:var(--aa-goldbg);text-transform:uppercase}
.aa-hero h1{font-size:clamp(38px,5vw,54px);line-height:1.15;font-weight:800;letter-spacing:-.02em;color:var(--aa-tx);margin:22px 0 18px}
.aa-grad{background:linear-gradient(92deg,#ffc174,#f59e0b 55%,#ffdf9e);-webkit-background-clip:text;background-clip:text;color:transparent}
.aa-hero-sub{font-size:16px;line-height:1.75;color:var(--aa-warm);max-width:560px;margin:0 0 32px}
.aa-hero-cta{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:40px}
.aa-hero-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;border-top:1px solid var(--aa-line);padding-top:24px}
@media(max-width:640px){.aa-hero-trust{grid-template-columns:1fr}}
.aa-hero-trust>div{display:flex;align-items:center;gap:12px}
.aa-hero-trust i{width:34px;height:34px;flex:none;border-radius:9px;background:var(--aa-low);border:1px solid var(--aa-line2);display:flex;align-items:center;justify-content:center;color:var(--aa-goldbg)}
.aa-hero-trust i .aa-ms{font-size:18px}
.aa-hero-trust span{font-size:12px;font-weight:600;color:var(--aa-cool,#b9c8dc);color:var(--aa-dim)}
.aa-hero-panel{position:relative;width:100%;max-width:420px;margin:0 auto;aspect-ratio:4/5;border-radius:14px;background:var(--aa-low);border:1px solid var(--aa-line);padding:22px;display:flex;flex-direction:column;box-shadow:0 30px 70px rgba(2,6,12,.55)}
.aa-hero-panel:before{content:"";position:absolute;inset:0;border-radius:14px;background:linear-gradient(135deg,rgba(245,158,11,.1),transparent 45%,rgba(49,53,59,.4));pointer-events:none}
.aa-panel-top{display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--aa-line);padding-bottom:12px;position:relative}
.aa-dot{width:10px;height:10px;border-radius:50%;background:var(--aa-goldbg);flex:none}
.aa-panel-top .aa-caps{color:var(--aa-dim);font-size:10px;flex:1}
.aa-panel-chip{font-size:10px;font-weight:800;letter-spacing:.06em;color:var(--aa-goldbg);background:rgba(245,158,11,.14);border:1px solid rgba(245,158,11,.4);padding:3px 9px;border-radius:6px;text-transform:uppercase;white-space:nowrap}
.aa-panel-book{position:relative;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;padding:18px 0}
.aa-book{position:relative;width:62%;max-width:236px;aspect-ratio:1/1.45;border-radius:10px;overflow:hidden;border:1px solid rgba(160,142,122,.5);box-shadow:0 24px 48px rgba(2,6,12,.6)}
.aa-book img{width:100%;height:100%;object-fit:cover;display:block}
.aa-book-ov{position:absolute;left:0;right:0;bottom:0;padding:26px 12px 10px;background:linear-gradient(to top,rgba(10,14,19,.95) 20%,rgba(10,14,19,.75) 55%,transparent)}
.aa-book-ov .aa-caps{display:block;font-size:9px;color:var(--aa-goldbg);margin-bottom:4px}
.aa-book-ov h4{font-size:15px;font-weight:700;color:var(--aa-tx);line-height:1.25}
.aa-book-ov p{font-size:11px;color:var(--aa-dim);margin-top:2px}
.aa-panel-fact{position:absolute;bottom:6px;right:6px;display:flex;gap:10px;align-items:center;background:rgba(38,42,48,.95);backdrop-filter:blur(8px);border:1px solid var(--aa-line2);border-radius:10px;padding:10px 12px}
.aa-panel-fact>i{width:34px;height:34px;border-radius:8px;background:rgba(245,158,11,.16);border:1px solid rgba(245,158,11,.4);display:flex;align-items:center;justify-content:center;color:var(--aa-goldbg);flex:none}
.aa-panel-fact i .aa-ms{font-size:18px}
.aa-panel-fact .aa-caps{display:block;font-size:9px;color:var(--aa-dim)}
.aa-panel-fact b{display:block;font-size:12px;color:var(--aa-gold);font-weight:700;white-space:nowrap}
.aa-panel-foot{display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--aa-line);padding-top:12px;position:relative}
.aa-panel-foot .aa-caps{font-size:9px;color:var(--aa-dim)}
.aa-panel-price{font-size:13px;font-weight:700;color:var(--aa-goldbg)}
</style>
<section class="aa-hero">
  <div class="aa-hero-glow1"></div>
  <div class="aa-hero-glow2"></div>
  <div class="aa-wrap">
    <div class="aa-hero-grid">
      <div class="aa-hero-l">
        <span class="aa-hero-pill"><i></i><span>Premium PDF Repository &bull; India</span></span>
        <h1>Read Better.<br><span class="aa-grad">Grow Faster.</span></h1>
        <p class="aa-hero-sub">Discover and instantly download premium PDF ebooks written by India&rsquo;s top thinkers, creators, and doers. Optimized for phones, tablets, and e-readers.</p>
        <div class="aa-hero-cta">
          <a class="aa-btn-gold" href="#aa-featured"><span class="aa-ms">bolt</span><span>Shop Bestsellers</span></a>
          <a class="aa-btn-ghost" href="#aa-categories"><span class="aa-ms">category</span><span>Browse Categories</span></a>
        </div>
        <div class="aa-hero-trust">
          <div><i><span class="aa-ms">download</span></i><span>Instant PDF download</span></div>
          <div><i><span class="aa-ms">shield</span></i><span>Secure UPI &amp; card payments</span></div>
          <div><i><span class="aa-ms">all_inclusive</span></i><span>Lifetime access &amp; free updates</span></div>
        </div>
      </div>
      <div class="aa-hero-r">
        <div class="aa-hero-panel">
          <div class="aa-panel-top">
            <span class="aa-dot"></span>
            <span class="aa-caps">Annaadhya Reader Engine v2.4</span>
            <span class="aa-panel-chip">DRM-Free PDF</span>
          </div>
          <div class="aa-panel-book">
            <a class="aa-book" href="/product/digital-discipline/">
              <img src="/wp-content/uploads/2026/11/digital-discipline.png" alt="Digital Discipline ebook cover">
              <div class="aa-book-ov">
                <span class="aa-caps">Trending #1 this week</span>
                <h4>Digital Discipline</h4>
                <p>By Dr. Ananya Iyer</p>
              </div>
            </a>
            <div class="aa-panel-fact">
              <i><span class="aa-ms">picture_as_pdf</span></i>
              <div>
                <span class="aa-caps">Delivered via UPI</span>
                <b>100% Instant Delivery</b>
              </div>
            </div>
          </div>
          <div class="aa-panel-foot">
            <span class="aa-caps">Optimized for iPad &amp; Kindle</span>
            <span class="aa-panel-price">&#8377;249 Only</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
HTML;

$aa_featured = <<<'HTML'
<style>
.aa-feat{padding:112px 0;background:var(--aa-surface)}
@media(max-width:768px){.aa-feat{padding:72px 0}}
.aa-feat-head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:44px}
.aa-feat-head h2{font-size:clamp(26px,3vw,34px);font-weight:600;letter-spacing:-.015em;color:var(--aa-tx)}
.aa-feat-head p{font-size:14px;color:var(--aa-warm);margin-top:6px}
.aa-feat-chips{display:flex;gap:10px;overflow-x:auto;padding-bottom:4px}
.aa-chipbtn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-tx)!important;font-size:13px;font-weight:700;text-decoration:none!important;white-space:nowrap;transition:border-color .15s,color .15s}
.aa-chipbtn:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
.aa-chipbtn.aa-on{background:var(--aa-goldbg);color:var(--aa-ongold)!important;border-color:transparent}
</style>
<section class="aa-feat" id="aa-featured">
  <div class="aa-wrap">
    <div class="aa-feat-head">
      <div>
        <div class="aa-eyebrow"><span class="aa-ms">bookmark_added</span><span class="aa-caps aa-caps-gold">Handpicked Curations</span></div>
        <h2>Featured Ebooks</h2>
        <p>Handpicked bestsellers ready for instant download</p>
      </div>
      <div class="aa-feat-chips">
        <a class="aa-chipbtn aa-on" href="/shop/">All</a>
        <a class="aa-chipbtn" href="/product-category/technology/">Technology</a>
        <a class="aa-chipbtn" href="/product-category/business/">Business</a>
        <a class="aa-chipbtn" href="/product-category/self-help/">Self-Help</a>
        <a class="aa-chipbtn" href="/product-category/fiction/">Fiction</a>
      </div>
    </div>
    [aa_books limit="6"]
  </div>
</section>
HTML;

$aa_categories = <<<'HTML'
<style>
.aa-cats{padding:96px 0;background:var(--aa-bg);border-top:1px solid var(--aa-line);border-bottom:1px solid var(--aa-line)}
@media(max-width:768px){.aa-cats{padding:72px 0}}
.aa-cats-head{text-align:center;max-width:640px;margin:0 auto 56px}
.aa-cats-head .aa-caps{color:var(--aa-goldbg);letter-spacing:.14em}
.aa-cats-head h2{font-size:clamp(26px,3vw,34px);font-weight:600;letter-spacing:-.015em;color:var(--aa-tx);margin:12px 0 12px}
.aa-cats-head p{font-size:16px;color:var(--aa-dim);line-height:1.7}
.aa-cats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
@media(max-width:1024px){.aa-cats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.aa-cats-grid{grid-template-columns:1fr}}
.aa-cat{position:relative;display:flex;flex-direction:column;align-items:flex-start;background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:26px;overflow:hidden;text-decoration:none!important;transition:border-color .2s,transform .2s}
.aa-cat:before{content:"";position:absolute;top:-36px;right:-36px;width:110px;height:110px;border-radius:50%;background:rgba(245,158,11,.09);filter:blur(22px);transition:background .2s}
.aa-cat:hover{border-color:rgba(245,158,11,.8);transform:translateY(-3px)}
.aa-cat-ic{width:50px;height:50px;border-radius:12px;background:var(--aa-cont);border:1px solid var(--aa-line2);display:flex;align-items:center;justify-content:center;color:var(--aa-goldbg);margin-bottom:18px;position:relative}
.aa-cat-ic .aa-ms{font-size:26px}
.aa-cat-row{display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;margin-bottom:8px;position:relative}
.aa-cat-row h3{font-size:18px;font-weight:700;color:var(--aa-tx);transition:color .15s}
.aa-cat:hover .aa-cat-row h3{color:var(--aa-gold)}
.aa-cat-count{font-size:10px;font-weight:700;letter-spacing:.06em;color:var(--aa-dim);background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:6px;padding:4px 8px;text-transform:uppercase;white-space:nowrap}
.aa-cat p{font-size:14px;color:var(--aa-dim);line-height:1.65;margin-bottom:22px;position:relative}
.aa-cat-go{display:inline-flex;align-items:center;gap:7px;font-size:14px;font-weight:600;color:var(--aa-goldbg);position:relative;transition:gap .2s}
.aa-cat:hover .aa-cat-go{gap:12px}
.aa-cat-go .aa-ms{font-size:16px}
</style>
<section class="aa-cats" id="aa-categories">
  <div class="aa-wrap">
    <div class="aa-cats-head">
      <span class="aa-caps aa-caps-gold">Disciplines &amp; Genres</span>
      <h2>Browse by Category</h2>
      <p>Find curated reads across your favorite domains</p>
    </div>
    <div class="aa-cats-grid">
      <a class="aa-cat" href="/product-category/technology/">
        <span class="aa-cat-ic"><span class="aa-ms">terminal</span></span>
        <span class="aa-cat-row"><h3>Technology</h3><span class="aa-cat-count" data-aa-catcount="technology">&nbsp;</span></span>
        <p>WordPress, AI and practical digital skills for builders</p>
        <span class="aa-cat-go"><span>Explore category</span><span class="aa-ms">arrow_forward</span></span>
      </a>
      <a class="aa-cat" href="/product-category/business/">
        <span class="aa-cat-ic"><span class="aa-ms">trending_up</span></span>
        <span class="aa-cat-row"><h3>Business</h3><span class="aa-cat-count" data-aa-catcount="business">&nbsp;</span></span>
        <p>Startups, side hustles, scaling and money skills</p>
        <span class="aa-cat-go"><span>Explore category</span><span class="aa-ms">arrow_forward</span></span>
      </a>
      <a class="aa-cat" href="/product-category/self-help/">
        <span class="aa-cat-ic"><span class="aa-ms">psychology</span></span>
        <span class="aa-cat-row"><h3>Self-Help</h3><span class="aa-cat-count" data-aa-catcount="self-help">&nbsp;</span></span>
        <p>Focus, habits, productivity and modern living</p>
        <span class="aa-cat-go"><span>Explore category</span><span class="aa-ms">arrow_forward</span></span>
      </a>
      <a class="aa-cat" href="/product-category/fiction/">
        <span class="aa-cat-ic"><span class="aa-ms">history_edu</span></span>
        <span class="aa-cat-row"><h3>Fiction</h3><span class="aa-cat-count" data-aa-catcount="fiction">&nbsp;</span></span>
        <p>Gripping contemporary stories and timeless lore</p>
        <span class="aa-cat-go"><span>Explore category</span><span class="aa-ms">arrow_forward</span></span>
      </a>
    </div>
  </div>
</section>
HTML;

$aa_perks = <<<'HTML'
<style>
.aa-perks{padding:112px 0;background:var(--aa-surface)}
@media(max-width:768px){.aa-perks{padding:72px 0}}
.aa-perks-head{text-align:center;max-width:640px;margin:0 auto 56px}
.aa-perks-head .aa-caps{color:var(--aa-goldbg);letter-spacing:.14em}
.aa-perks-head h2{font-size:clamp(26px,3vw,34px);font-weight:600;letter-spacing:-.015em;color:var(--aa-tx);margin-top:12px}
.aa-perks-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
@media(max-width:1024px){.aa-perks-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.aa-perks-grid{grid-template-columns:1fr}}
.aa-perk{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:26px;transition:border-color .2s}
.aa-perk:hover{border-color:rgba(245,158,11,.5)}
.aa-perk-ic{width:50px;height:50px;border-radius:12px;background:var(--aa-cont);border:1px solid var(--aa-line2);display:flex;align-items:center;justify-content:center;color:var(--aa-goldbg);margin-bottom:18px}
.aa-perk-ic .aa-ms{font-size:26px}
.aa-perk h3{font-size:18px;font-weight:700;color:var(--aa-tx);margin-bottom:10px}
.aa-perk p{font-size:14px;color:var(--aa-dim);line-height:1.7;margin:0}
</style>
<section class="aa-perks">
  <div class="aa-wrap">
    <div class="aa-perks-head">
      <span class="aa-caps aa-caps-gold">Built for Frictionless Knowledge</span>
      <h2>Why Readers Love Annaadhya</h2>
    </div>
    <div class="aa-perks-grid">
      <div class="aa-perk">
        <span class="aa-perk-ic"><span class="aa-ms">bolt</span></span>
        <h3>Instant download</h3>
        <p>Get PDF delivery to your email and account within 10 seconds of checkout.</p>
      </div>
      <div class="aa-perk">
        <span class="aa-perk-ic"><span class="aa-ms">verified_user</span></span>
        <h3>Secure payments</h3>
        <p>Pay seamlessly via UPI (GPay, PhonePe, Paytm), net-banking, and cards.</p>
      </div>
      <div class="aa-perk">
        <span class="aa-perk-ic"><span class="aa-ms">verified</span></span>
        <h3>Quality checked</h3>
        <p>Meticulously formatted, reader-friendly typography for every device.</p>
      </div>
      <div class="aa-perk">
        <span class="aa-perk-ic"><span class="aa-ms">support_agent</span></span>
        <h3>Friendly support</h3>
        <p>A dedicated support team ready to help with any download or reading query.</p>
      </div>
    </div>
  </div>
</section>
HTML;

$aa_newsletter = <<<'HTML'
<style>
.aa-news{padding:56px 0 96px;background:var(--aa-surface)}
.aa-news-band{position:relative;border-radius:18px;background:linear-gradient(180deg,var(--aa-cont),var(--aa-low));border:1px solid rgba(245,158,11,.45);padding:64px 32px;overflow:hidden}
.aa-news-glow{position:absolute;right:-96px;bottom:-96px;width:380px;height:380px;background:radial-gradient(closest-side,rgba(245,158,11,.22),transparent 70%);filter:blur(40px);pointer-events:none}
.aa-news-glow2{position:absolute;left:-80px;top:-80px;width:320px;height:320px;border-radius:50%;background:rgba(245,158,11,.08);filter:blur(50px);pointer-events:none}
.aa-news-in{position:relative;max-width:720px;margin:0 auto;text-align:center}
.aa-news-pill{display:inline-flex;align-items:center;gap:8px;padding:6px 13px;border-radius:999px;background:var(--aa-bg);border:1px solid var(--aa-line2);margin-bottom:22px}
.aa-news-pill .aa-ms{font-size:15px;color:var(--aa-goldbg)}
.aa-news-pill span{font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--aa-goldbg);text-transform:uppercase}
.aa-news h2{font-size:clamp(26px,3.2vw,36px);font-weight:600;letter-spacing:-.015em;color:var(--aa-tx);margin-bottom:14px}
.aa-news p.aa-news-sub{font-size:16px;color:var(--aa-warm);line-height:1.75;margin:0 auto 32px;max-width:560px}
.aa-news-form{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;max-width:480px;margin:0 auto 16px}
.aa-news-field{position:relative;flex:1;min-width:220px;text-align:left}
.aa-news-field .aa-ms{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:18px;color:#8b8fa3}
.aa-news-field input{width:100%;background:var(--aa-bg);border:1px solid rgba(160,142,122,.5);border-radius:10px;padding:14px 16px 14px 42px;color:var(--aa-tx);font-size:14px;outline:none;font-family:inherit}
.aa-news-field input:focus{border-color:var(--aa-goldbg)}
.aa-news-field input::placeholder{color:#7a7f8f}
.aa-news-note{display:flex;align-items:center;justify-content:center;gap:7px;font-size:12px;color:var(--aa-dim)}
.aa-news-note .aa-ms{font-size:14px;color:var(--aa-goldbg)}
.aa-news-done{margin:0 0 16px;font-size:14px;font-weight:600;color:var(--aa-tert);text-align:center}
.aa-news-done[hidden]{display:none}
</style>
<section class="aa-news">
  <div class="aa-wrap">
    <div class="aa-news-band">
      <div class="aa-news-glow"></div>
      <div class="aa-news-glow2"></div>
      <div class="aa-news-in">
        <span class="aa-news-pill"><span class="aa-ms">mail</span><span>Exclusive Samples &amp; Discounts</span></span>
        <h2>Get free sample chapters</h2>
        <p class="aa-news-sub">Join thousands of avid Indian readers. Receive curated weekly summaries, author interviews, and exclusive discounts straight to your inbox.</p>
        <p class="aa-news-done" data-aa-news-done hidden>You&rsquo;re on the list &mdash; check your inbox for a welcome note.</p>
        <p class="aa-news-done" data-aa-news-err hidden style="color:#ff9d8a"></p>
        <form class="aa-news-form" data-aa-news>
          <input type="text" name="aa_hp" value="" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0">
          <div class="aa-news-field">
            <span class="aa-ms">alternate_email</span>
            <input type="email" name="email" placeholder="Enter your email address" required>
          </div>
          <button class="aa-btn-gold" type="submit">Subscribe Free</button>
        </form>
        <p class="aa-news-note"><span class="aa-ms">lock</span><span>Zero spam. Unsubscribe anytime with 1 click.</span></p>
      </div>
    </div>
  </div>
</section>
HTML;

/* ---------------------------------------------------------------- shop */

$aa_shop_head = <<<'HTML'
<style>
.aa-shead{position:relative;padding:48px 0 56px;border-bottom:1px solid var(--aa-line);background:var(--aa-bg);overflow:hidden}
.aa-shead-glow{position:absolute;top:50%;left:25%;transform:translate(-50%,-50%);width:520px;height:260px;background:radial-gradient(closest-side,rgba(245,158,11,.1),transparent 70%);filter:blur(46px);pointer-events:none}
.aa-shead-in{position:relative}
.aa-crumb{display:flex;align-items:center;gap:8px;margin-bottom:18px}
.aa-crumb a{font-size:11px;font-weight:700;letter-spacing:.08em;color:#a08e7a!important;text-decoration:none!important;text-transform:uppercase}
.aa-crumb a:hover{color:var(--aa-goldbg)!important}
.aa-crumb .aa-ms{font-size:13px;color:#a08e7a}
.aa-crumb span.aa-cur{font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--aa-goldbg);text-transform:uppercase}
.aa-shead-grid{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:32px}
.aa-shead-pill{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.35);margin-bottom:16px}
.aa-shead-pill i{width:6px;height:6px;border-radius:50%;background:var(--aa-goldbg);animation:aa-pulse 1.8s ease-in-out infinite}
.aa-shead-pill span{font-size:10px;font-weight:800;letter-spacing:.1em;color:var(--aa-goldbg);text-transform:uppercase}
.aa-shead h1{font-size:clamp(36px,4.4vw,50px);font-weight:800;letter-spacing:-.02em;line-height:1.12;color:var(--aa-tx)}
.aa-shead h1 .aa-grad{background:linear-gradient(92deg,#ffc174,#f59e0b 55%,#ffdf9e);-webkit-background-clip:text;background-clip:text;color:transparent}
.aa-shead p.aa-shead-sub{font-size:16px;color:var(--aa-warm);line-height:1.7;margin-top:12px;max-width:520px}
.aa-ssearch{width:100%;max-width:384px}
.aa-ssearch label{display:block;font-size:11px;font-weight:700;letter-spacing:.08em;color:#a08e7a;text-transform:uppercase;margin-bottom:8px}
.aa-ssearch-row{display:flex;gap:8px}
.aa-ssearch-row .aa-field{position:relative;flex:1}
.aa-ssearch-row .aa-ms{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:18px;color:#8b8fa3}
.aa-ssearch-row input{width:100%;background:var(--aa-low);border:1px solid var(--aa-line2);border-radius:10px;padding:12px 14px 12px 38px;color:var(--aa-tx);font-size:14px;outline:none;font-family:inherit}
.aa-ssearch-row input:focus{border-color:var(--aa-goldbg)}
.aa-ssearch-row input::placeholder{color:#7a7f8f}
.aa-ssearch-row button{background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-tx);font-size:12px;font-weight:800;border-radius:10px;padding:0 16px;cursor:pointer;letter-spacing:.06em}
.aa-ssearch-row button:hover{border-color:var(--aa-gold);color:var(--aa-gold)}
</style>
<section class="aa-shead">
  <div class="aa-shead-glow"></div>
  <div class="aa-wrap aa-shead-in">
    <nav class="aa-crumb">
      <a href="/">Home</a>
      <span class="aa-ms">chevron_right</span>
      <span class="aa-cur">Shop</span>
    </nav>
    <div class="aa-shead-grid">
      <div>
        <span class="aa-shead-pill"><i></i><span>Curated Catalog</span></span>
        <h1 data-aa-bookshelf-title>The <span class="aa-grad">Bookshelf</span></h1>
        <p class="aa-shead-sub" data-aa-bookshelf-sub>Browse every ebook in the library &mdash; instant PDF download, optimized for iPad, Kindle, and mobile reading.</p>
      </div>
      <div class="aa-ssearch">
        <label>Direct Library Search</label>
        <form action="/shop/" method="get">
          <div class="aa-ssearch-row">
            <div class="aa-field">
              <span class="aa-ms">search</span>
              <input type="search" name="book" placeholder="Search by title, author, or keyword..." value="">
            </div>
            <button type="submit">OK</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
HTML;

$aa_shop_toolbar = <<<'HTML'
<style>
.aa-tool{padding:22px 0 8px;background:var(--aa-surface)}
.aa-tool-in{display:flex;flex-wrap:wrap;align-items:center;gap:16px}
.aa-tool-chips{display:flex;gap:10px;overflow-x:auto;padding-bottom:4px}
.aa-chipbtn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:10px;background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-tx)!important;font-size:13px;font-weight:700;text-decoration:none!important;white-space:nowrap;transition:border-color .15s,color .15s}
.aa-chipbtn:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
.aa-chipbtn.aa-on{background:var(--aa-goldbg);color:var(--aa-ongold)!important;border-color:transparent}
.aa-chipbtn i{font-style:normal;font-size:10px;font-weight:800;background:rgba(160,142,122,.25);color:#a08e7a;border-radius:5px;padding:2px 7px}
.aa-chipbtn.aa-on i{background:rgba(71,42,0,.2);color:var(--aa-ongold)}
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
</style>
<section class="aa-tool">
  <div class="aa-wrap">
    <div class="aa-tool-in">
      <div class="aa-tool-chips">
        <a class="aa-chipbtn aa-on" href="/shop/">All <i data-aa-catnum="_all">6</i></a>
        <a class="aa-chipbtn" href="/product-category/technology/">Technology <i data-aa-catnum="technology">2</i></a>
        <a class="aa-chipbtn" href="/product-category/business/">Business <i data-aa-catnum="business">2</i></a>
        <a class="aa-chipbtn" href="/product-category/self-help/">Self-Help <i data-aa-catnum="self-help">1</i></a>
        <a class="aa-chipbtn" href="/product-category/fiction/">Fiction <i data-aa-catnum="fiction">1</i></a>
      </div>
      <div class="aa-tool-r">
        <span class="aa-showing" data-aa-total data-aa-total-max="9">Showing <strong>1&ndash;6</strong> of <strong>6</strong> ebooks</span>
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
HTML;

$aa_shop_grid = <<<'HTML'
<style>
.aa-shopgrid{padding:26px 0 40px;background:var(--aa-surface)}
.aa-shopgrid .aa-media{aspect-ratio:1/1.38}
</style>
<section class="aa-shopgrid aa-shop">
  <div class="aa-wrap">
    [aa_books limit="9" paginate="yes"]
  </div>
</section>
HTML;

$aa_shop_perks = <<<'HTML'
<style>
.aa-sperks{background:var(--aa-bg);padding:26px 0;border-top:1px solid var(--aa-line)}
.aa-sperks-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;text-align:center}
@media(min-width:768px){.aa-sperks-grid{grid-template-columns:repeat(4,1fr)}}
.aa-sperks-grid>div{display:flex;align-items:center;justify-content:center;gap:9px;font-size:13px;font-weight:600;color:var(--aa-dim)}
.aa-sperks-grid .aa-ms{font-size:20px;color:var(--aa-goldbg)}
</style>
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

/* ---------------------------------------------------------------- build */

$home_on  = 'aa-on';
$shop_on  = '';
$nav_home = str_replace( array( '%%HOME_ON%%', '%%SHOP_ON%%' ), array( $home_on, $shop_on ), '' );

$home_sections = array(
        $aa_common_html . aa_nav_html( 'home' ),
        $aa_hero,
        $aa_featured,
        $aa_categories,
        $aa_perks,
        $aa_newsletter,
        aa_footer_html(),
);

$shop_sections = array(
        $aa_common_html . aa_nav_html( 'shop' ),
        $aa_shop_head,
        $aa_shop_toolbar,
        $aa_shop_grid,
        $aa_shop_perks,
        aa_footer_html(),
);

aa_build_page( 24, $home_sections );
aa_build_page( 5, $shop_sections );

echo 'STITCH-PAGES-OK home=' . count( $home_sections ) . ' sections, shop=' . count( $shop_sections ) . " sections\n";
