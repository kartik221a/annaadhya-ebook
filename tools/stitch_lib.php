<?php
/**
 * Shared building blocks for the Stitch "Obsidian & Gold Lit" page builders.
 * Extracted from set_stitch_pages.php so every builder (home/shop, category
 * pages, future iterations) reuses the exact same nav, footer, tokens and
 * Elementor section machinery.
 *
 * Required via wp eval: impersonates an administrator first (Elementor strips
 * <style>/<script> from users without unfiltered_html).
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
