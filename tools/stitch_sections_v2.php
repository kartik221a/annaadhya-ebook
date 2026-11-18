<?php
/**
 * Stitch "Obsidian & Gold Lit" section library — the 10-page storefront (v2).
 * Self-contained <style> + HTML blocks consumed by tools/build_pages_v2.php.
 * Design system tokens live in tools/set_stitch_pages.php ($aa_common_html).
 */

/* ---------------------------------------------------------------- Woo pages */

$aa_woo_css = <<<'HTML'
<style>
.aa-woopage{background:var(--aa-surface);min-height:60vh;padding:8px 0 80px}
.aa-woopage .woocommerce{max-width:1280px;margin:0 auto;padding:0 24px;color:var(--aa-tx)}
.aa-woopage .woocommerce:after{content:"";display:table;clear:both}
/* notices */
.woocommerce-message,.woocommerce-info,.woocommerce-error{background:var(--aa-cont)!important;color:var(--aa-tx)!important;border:1px solid var(--aa-line2)!important;border-left:3px solid var(--aa-goldbg)!important;border-radius:10px;padding:14px 18px!important;list-style:none;margin:0 0 22px!important;font-size:14px}
.woocommerce-error{border-left-color:#e05252}
.woocommerce-message::before,.woocommerce-info::before,.woocommerce-error::before{color:var(--aa-goldbg)!important;font-family:'Material Symbols Outlined'}
/* tables */
.woocommerce table.shop_table{background:var(--aa-low);border:1px solid var(--aa-line)!important;border-radius:12px;border-collapse:separate;border-spacing:0;color:var(--aa-tx);font-size:14px}
.woocommerce table.shop_table th{background:var(--aa-high);color:var(--aa-warm);font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:14px 16px!important;border-bottom:1px solid var(--aa-line)!important;text-align:left}
.woocommerce table.shop_table td{padding:16px!important;border-bottom:1px solid var(--aa-line)!important;border-top:0!important;vertical-align:middle}
.woocommerce table.shop_table img{border-radius:8px;width:56px!important}
.woocommerce table.shop_table a{color:var(--aa-tx)!important;font-weight:600}
.woocommerce table.shop_table a:hover{color:var(--aa-gold)!important}
.woocommerce td.product-name dl.variation dt,.woocommerce td.product-name dl.variation dd{color:var(--aa-dim);font-size:12px}
.woocommerce .cart-subtotal th,.woocommerce .shipping td,.woocommerce .order-total th{color:var(--aa-warm)}
.woocommerce .cart-subtotal .amount,.woocommerce .order-total .amount{color:var(--aa-gold);font-weight:800}
.woocommerce a.remove{color:#e05252!important;background:rgba(224,82,82,.08);border-radius:8px;width:26px;height:26px;line-height:26px;font-weight:700}
/* buttons */
.woocommerce .button,.woocommerce button.button,.woocommerce a.button,.woocommerce input.button,#place_order,.woocommerce .checkout-button,.woocommerce .wc-forward,.woocommerce a.woocommerce-Button{background:var(--aa-goldbg)!important;color:var(--aa-ongold)!important;font-weight:700!important;border-radius:10px!important;border:0!important;padding:13px 24px!important;font-size:14px!important;cursor:pointer;font-family:inherit!important}
.woocommerce .button:hover,.woocommerce button.button:hover{filter:brightness(1.07);color:var(--aa-ongold)!important}
.woocommerce .coupon .button,.woocommerce .woocommerce-form-login__submit{padding:12px 18px!important}
.woocommerce .button.alt:disabled,.woocommerce .button:disabled{opacity:.55;cursor:not-allowed}
/* fields */
.woocommerce input[type=text],.woocommerce input[type=email],.woocommerce input[type=tel],.woocommerce input[type=password],.woocommerce input[type=number],.woocommerce input[type=search],.woocommerce textarea,.woocommerce select,.select2-container--default .select2-selection--single{background:var(--aa-low)!important;border:1px solid var(--aa-line2)!important;border-radius:10px;color:var(--aa-tx)!important;padding:12px 14px!important;font-size:14px;outline:none;font-family:inherit!important;max-width:100%}
.woocommerce input:focus,.woocommerce textarea:focus,.woocommerce select:focus,.select2-container--default.select2-container--open .select2-selection--single{border-color:var(--aa-goldbg)!important}
.woocommerce ::placeholder{color:#7a7f8f}
.woocommerce label,.woocommerce .woocommerce-form__label-for-checkbox span{color:var(--aa-warm);font-size:13px}
.woocommerce .select2-dropdown{background:var(--aa-high)!important;border:1px solid var(--aa-line2)!important;border-radius:10px}
.woocommerce .select2-container--default .select2-selection--single .select2-selection__rendered{color:var(--aa-tx)!important;padding:12px 14px!important;line-height:1.2}
.woocommerce .select2-container--default .select2-results__option--highlighted[aria-selected]{background:var(--aa-goldbg)!important;color:var(--aa-ongold)!important}
.woocommerce .select2-container--default .select2-results__option{color:var(--aa-tx);background:var(--aa-high)}
.woocommerce span.select2-selection__arrow b{border-color:var(--aa-dim) transparent transparent transparent!important}
/* qty + coupon row */
.woocommerce .quantity .qty{background:var(--aa-low);border:1px solid var(--aa-line2);border-radius:8px;color:var(--aa-tx);padding:9px;width:64px}
.woocommerce .coupon{display:flex;gap:8px;align-items:center}
.woocommerce .coupon label{display:none}
.woocommerce .coupon .input-text{width:180px!important}
/* cart layout */
.woocommerce-cart-form{float:left;width:62%}
.woocommerce .cart-collaterals{float:right;width:34%!important}
@media(max-width:900px){.woocommerce-cart-form,.woocommerce .cart-collaterals{width:100%!important;float:none}}
.woocommerce .cart-collaterals .cart_totals{width:100%!important}
/* checkout layout */
.woocommerce-checkout #payment{background:var(--aa-low)!important;border-radius:12px;border:1px solid var(--aa-line)!important;color:var(--aa-tx)!important}
.woocommerce-checkout #payment ul.payment_methods{border-bottom:1px solid var(--aa-line)!important;padding:20px!important}
.woocommerce-checkout #payment ul.payment_methods li{color:var(--aa-tx)}
.woocommerce-checkout #payment div.payment_box{background:var(--aa-high)!important;color:var(--aa-warm)!important;border-radius:8px;font-size:13px}
.woocommerce-checkout #payment div.payment_box::before{border-bottom-color:var(--aa-high)!important}
.woocommerce-checkout #payment .place-order{padding:20px!important}
.woocommerce-checkout-review-order-table tfoot th{background:var(--aa-cont)}
#order_review_heading,.woocommerce-billing-fields h3,.woocommerce-additional-fields h3{color:var(--aa-gold);font-size:18px;font-weight:700;margin:0 0 14px}
.woocommerce form .form-row label{color:var(--aa-warm);font-size:13px;font-weight:600}
.woocommerce form .form-row input.input-text,.woocommerce form .form-row textarea{background:var(--aa-low)!important;border:1px solid var(--aa-line2)!important;border-radius:10px;color:var(--aa-tx)!important;padding:12px 14px!important}
/* my account */
.aa-woopage .woocommerce h2,.aa-woopage .woocommerce h3{color:var(--aa-tx)}
.aa-woopage .woocommerce a:not(.button):not(.aa-btn-gold):not(.aa-btn-ghost){color:var(--aa-tert)}
.aa-woopage .cart_totals{background:var(--aa-low)!important;border:1px solid var(--aa-line)!important;border-radius:14px;padding:22px}
.aa-woopage .cart_totals h2{color:var(--aa-tx)!important;background:transparent!important;font-size:20px;margin:0 0 16px!important}
.aa-woopage .woocommerce h2,.aa-woopage .woocommerce h3,.aa-woopage .woocommerce h1{background:transparent!important}
.aa-woopage .woocommerce h2,.aa-woopage .woocommerce h3{color:var(--aa-tx)}
.woocommerce-MyAccount-content h2,.woocommerce-MyAccount-content h3{color:var(--aa-gold)}
.aa-woopage .woocommerce a:not(.button):not(.aa-btn-gold):not(.aa-btn-ghost){color:var(--aa-tert)}
.aa-woopage .cart_totals{background:var(--aa-low)!important;border:1px solid var(--aa-line)!important;border-radius:14px;padding:22px}
.aa-woopage .cart_totals h2{color:var(--aa-tx)!important;background:transparent!important;font-size:20px;margin:0 0 16px!important}
.aa-woopage .woocommerce h2,.aa-woopage .woocommerce h3,.aa-woopage .woocommerce h1{background:transparent!important}
.woocommerce .woocommerce-form-login,.woocommerce .woocommerce-form-register{background:var(--aa-low);border:1px solid var(--aa-line)!important;border-radius:12px;padding:24px!important;max-width:560px}
.woocommerce-MyAccount-navigation{float:none;width:100%;margin-bottom:26px}
.woocommerce-MyAccount-navigation ul{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:10px}
.woocommerce-MyAccount-navigation li{margin:0}
.woocommerce-MyAccount-navigation a{display:inline-flex;align-items:center;gap:8px;background:var(--aa-cont);border:1px solid var(--aa-line2);color:var(--aa-tx)!important;border-radius:10px;padding:10px 16px;font-size:13px;font-weight:700;text-decoration:none!important;transition:border-color .15s,color .15s}
.woocommerce-MyAccount-navigation a:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
.woocommerce-MyAccount-navigation .is-active a{background:var(--aa-goldbg);color:var(--aa-ongold)!important;border-color:transparent}
.woocommerce-MyAccount-content{float:none;width:100%!important}
.woocommerce-MyAccount-content h2,.woocommerce-MyAccount-content h3{color:var(--aa-gold)}
.woocommerce-MyAccount-content p{color:#b9bcc8;font-size:14px;line-height:1.75}
.woocommerce-MyAccount-content mark{background:var(--aa-goldbg);color:var(--aa-ongold);border-radius:6px;padding:1px 8px}
.woocommerce-account-downloads-table .button{white-space:nowrap}
.woocommerce table.my_account_orders .button{padding:8px 14px!important;font-size:12px!important}
.woocommerce-address-fields__field-wrapper,.woocommerce-EditAccountForm{max-width:640px}
.woocommerce-EditAccountForm fieldset{border:1px solid var(--aa-line)!important;border-radius:12px;padding:20px!important;margin:22px 0!important}
.woocommerce-EditAccountForm legend{color:var(--aa-gold);font-weight:700;padding:0 8px}
/* empty account blocks */
.aa-account-empty{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:48px 24px;text-align:center;color:var(--aa-dim)}
.aa-account-empty .aa-ms{font-size:40px;color:var(--aa-goldbg);display:block;margin-bottom:12px}
.aa-account-empty h3{color:var(--aa-tx);font-weight:700;font-size:20px;margin:0 0 10px}
.aa-account-empty p{font-size:14px;margin:0 auto 20px;max-width:420px}
</style>
HTML;

$aa_cart_head = <<<'HTML'
<style>
.aa-chead{position:relative;padding:46px 0 40px;border-bottom:1px solid var(--aa-line);background:var(--aa-bg);overflow:hidden}
.aa-chead-glow{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:480px;height:220px;background:radial-gradient(closest-side,rgba(245,158,11,.1),transparent 70%);filter:blur(46px);pointer-events:none}
.aa-chead .aa-crumb{display:flex;align-items:center;gap:8px;margin-bottom:18px}
.aa-chead .aa-crumb a{font-size:11px;font-weight:700;letter-spacing:.08em;color:#a08e7a!important;text-decoration:none!important;text-transform:uppercase}
.aa-chead .aa-crumb span.aa-cur{font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--aa-goldbg);text-transform:uppercase}
.aa-chead .aa-crumb .aa-ms{font-size:13px;color:#a08e7a}
.aa-chead h1{font-size:clamp(30px,4vw,44px);font-weight:600;letter-spacing:-.02em;color:var(--aa-tx)}
.aa-chead .aa-chead-sub{font-size:15px;color:var(--aa-warm);margin-top:10px}
</style>
<section class="aa-chead">
  <div class="aa-chead-glow"></div>
  <div class="aa-wrap">
    <nav class="aa-crumb"><a href="/">Home</a><span class="aa-ms">chevron_right</span><span class="aa-cur">Cart</span></nav>
    <h1>Your <span class="aa-grad" style="color:var(--aa-gold)">Cart</span></h1>
    <p class="aa-chead-sub">Review your picks &mdash; instant PDF download the moment you check out.</p>
  </div>
</section>
HTML;

$aa_checkout_head = <<<'HTML'
<section class="aa-chead">
  <div class="aa-chead-glow"></div>
  <div class="aa-wrap">
    <nav class="aa-crumb"><a href="/">Home</a><span class="aa-ms">chevron_right</span><span class="aa-cur">Checkout</span></nav>
    <h1>Secure <span class="aa-grad" style="color:var(--aa-gold)">Checkout</span></h1>
    <p class="aa-chead-sub"><span class="aa-ms" style="font-size:16px;color:var(--aa-goldbg);vertical-align:-3px">lock</span> 256-bit encrypted &middot; UPI &amp; RuPay ready &middot; download link delivered instantly</p>
  </div>
</section>
HTML;

$aa_account_head = <<<'HTML'
<section class="aa-chead">
  <div class="aa-chead-glow"></div>
  <div class="aa-wrap">
    <nav class="aa-crumb"><a href="/">Home</a><span class="aa-ms">chevron_right</span><span class="aa-cur">My Account</span></nav>
    <h1>My <span class="aa-grad" style="color:var(--aa-gold)">Account</span></h1>
    <p class="aa-chead-sub">Sign in, track orders, manage downloads and keep your wishlist handy.</p>
  </div>
</section>
HTML;

/* ---------------------------------------------------------------- library */

$aa_lib_css = <<<'HTML'
<style>
.aa-lib{background:var(--aa-surface);padding:16px 0 80px}
.aa-lib-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:860px){.aa-lib-grid{grid-template-columns:1fr}}
.aa-lib-item{display:flex;align-items:center;gap:16px;background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:20px}
.aa-lib-item>.aa-ms{font-size:30px;color:var(--aa-goldbg);flex:none}
.aa-lib-item-info{flex:1;min-width:0}
.aa-lib-item-info strong{display:block;color:var(--aa-tx);font-size:15px;font-weight:700}
.aa-lib-item-info span{color:var(--aa-dim);font-size:12.5px}
.aa-lib-item .aa-btn-gold{flex:none;padding:11px 18px;font-size:13px}
.aa-lib-h2{color:var(--aa-tx);font-size:22px;font-weight:700;margin:44px 0 18px}
.aa-lib-tablewrap{overflow-x:auto;border:1px solid var(--aa-line);border-radius:14px}
.aa-lib-table{width:100%;border-collapse:collapse;background:var(--aa-low);font-size:14px;min-width:640px}
.aa-lib-table th{background:var(--aa-high);color:var(--aa-warm);font-size:11px;letter-spacing:.07em;text-transform:uppercase;text-align:left;padding:13px 16px}
.aa-lib-table td{padding:14px 16px;border-top:1px solid var(--aa-line);color:var(--aa-tx)}
.aa-lib-table a{color:var(--aa-gold)!important;font-weight:700;text-decoration:none!important}
.aa-status{display:inline-flex;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.04em}
.aa-status-completed,.aa-status-processing{background:rgba(52,199,123,.14);color:#5fd79a}
.aa-status-on-hold,.aa-status-pending{background:rgba(245,158,11,.14);color:var(--aa-gold)}
.aa-status-cancelled,.aa-status-failed,.aa-status-refunded{background:rgba(224,82,82,.14);color:#ff9d8a}
.aa-lib-none{color:var(--aa-dim);font-size:14px}
.aa-lib-gate{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:16px;padding:64px 24px;text-align:center}
.aa-lib-gate .aa-ms{font-size:44px;color:var(--aa-goldbg);display:block;margin-bottom:14px}
.aa-lib-gate h2{color:var(--aa-tx);font-size:26px;font-weight:700;margin:0 0 12px}
.aa-lib-gate p{color:var(--aa-warm);font-size:15px;line-height:1.75;max-width:520px;margin:0 auto 26px}
.aa-lib-gate-lite{padding:48px 24px}
.aa-lib-gate-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
</style>
HTML;

$aa_lib_perks = <<<'HTML'
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
      <div><span class="aa-ms">offline_pin</span><span>Read offline, anytime</span></div>
      <div><span class="aa-ms">cloud_download</span><span>Re-download on any device</span></div>
      <div><span class="aa-ms">update</span><span>Lifetime access &amp; updates</span></div>
      <div><span class="aa-ms">support_agent</span><span>Download help in 24h</span></div>
    </div>
  </div>
</section>
HTML;

/* ---------------------------------------------------------------- about */

$aa_about_hero = <<<'HTML'
<style>
.aa-ab-hero{position:relative;padding:72px 0 64px;background:var(--aa-bg);border-bottom:1px solid var(--aa-line);overflow:hidden}
.aa-ab-glow{position:absolute;top:-140px;right:-120px;width:480px;height:480px;background:radial-gradient(closest-side,rgba(245,158,11,.14),transparent 70%);filter:blur(60px);pointer-events:none}
.aa-ab-hero-in{position:relative;max-width:860px}
.aa-ab-pill{display:inline-flex;align-items:center;gap:8px;padding:6px 13px;border-radius:999px;background:var(--aa-cont);border:1px solid var(--aa-line2);margin-bottom:20px}
.aa-ab-pill .aa-ms{font-size:15px;color:var(--aa-goldbg)}
.aa-ab-pill span{font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--aa-goldbg);text-transform:uppercase}
.aa-ab-hero h1{font-size:clamp(34px,5vw,54px);font-weight:600;letter-spacing:-.02em;color:var(--aa-tx);line-height:1.14}
.aa-ab-hero .aa-grad{background:linear-gradient(90deg,var(--aa-gold),var(--aa-tert));-webkit-background-clip:text;background-clip:text;color:transparent}
.aa-ab-hero p.aa-ab-lede{font-size:17px;color:var(--aa-warm);line-height:1.85;margin:22px 0 0;max-width:760px}
.aa-ab-chips{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}
.aa-ab-chips span{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--aa-tx);background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:10px;padding:9px 14px}
.aa-ab-chips .aa-ms{font-size:17px;color:var(--aa-goldbg)}
</style>
<section class="aa-ab-hero">
  <div class="aa-ab-glow"></div>
  <div class="aa-wrap">
    <div class="aa-ab-hero-in">
      <span class="aa-ab-pill"><span class="aa-ms">auto_stories</span><span>Our Story</span></span>
      <h1>Books that <span class="aa-grad">light the way</span>, priced for every Indian reader.</h1>
      <p class="aa-ab-lede">Annaadhya began with a simple frustration: great knowledge kept hiding behind heavy prices, slow shipping and formats that never fit the phone in your pocket. So in 2026 we set out to build a different kind of bookstore &mdash; one where every title is a beautifully edited ebook, delivered to your device in seconds, at a price that feels fair from Bengaluru to Bhubaneswar.</p>
      <div class="aa-ab-chips">
        <span><span class="aa-ms">rocket_launch</span>Founded 2026, Bengaluru</span>
        <span><span class="aa-ms">handshake</span>Author-first royalties</span>
        <span><span class="aa-ms">currency_rupee</span>Honest INR pricing</span>
        <span><span class="aa-ms">translate</span>Regional languages on the roadmap</span>
      </div>
    </div>
  </div>
</section>
HTML;

$aa_about_mission = <<<'HTML'
<style>
.aa-ab-mv{background:var(--aa-surface);padding:64px 0}
.aa-ab-mv-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:860px){.aa-ab-mv-grid{grid-template-columns:1fr}}
.aa-ab-mv-card{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:16px;padding:36px;position:relative;overflow:hidden}
.aa-ab-mv-card .aa-ms{font-size:34px;color:var(--aa-goldbg);margin-bottom:16px;display:block}
.aa-ab-mv-card h2{color:var(--aa-gold);font-size:22px;font-weight:700;margin:0 0 14px}
.aa-ab-mv-card p{color:#b9bcc8;font-size:15px;line-height:1.85;margin:0}
.aa-ab-mv-card:after{content:"";position:absolute;right:-60px;bottom:-60px;width:180px;height:180px;border-radius:50%;background:rgba(245,158,11,.07);filter:blur(30px);pointer-events:none}
</style>
<section class="aa-ab-mv">
  <div class="aa-wrap">
    <div class="aa-ab-mv-grid">
      <div class="aa-ab-mv-card">
        <span class="aa-ms">flag</span>
        <h2>Our Mission</h2>
        <p>Make high-quality, practical and joyful books instantly accessible to every Indian reader &mdash; at honest prices, in formats that respect the device they read on, with zero friction between the decision to read and the first page.</p>
      </div>
      <div class="aa-ab-mv-card">
        <span class="aa-ms">visibility</span>
        <h2>Our Vision</h2>
        <p>A country where a student in a tier-3 town and a founder in a metro read the same great book on the same evening. By 2030 we want Annaadhya to be the most-loved ebook home for Indian readers across ten languages.</p>
      </div>
    </div>
  </div>
</section>
HTML;

$aa_about_values = <<<'HTML'
<style>
.aa-ab-val{background:var(--aa-bg);padding:64px 0;border-top:1px solid var(--aa-line)}
.aa-ab-eyebrow{display:flex;align-items:center;gap:10px;font-size:11px;font-weight:800;letter-spacing:.12em;color:var(--aa-goldbg);text-transform:uppercase;margin-bottom:14px}
.aa-ab-eyebrow .aa-ms{font-size:18px}
.aa-ab-val h2{font-size:clamp(26px,3.4vw,38px);font-weight:600;letter-spacing:-.015em;color:var(--aa-tx);margin-bottom:14px}
.aa-ab-val p.aa-ab-sub{font-size:16px;color:var(--aa-warm);line-height:1.8;max-width:680px;margin:0 0 36px}
.aa-ab-val-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
@media(max-width:960px){.aa-ab-val-grid{grid-template-columns:1fr}}
.aa-ab-val-card{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:30px}
.aa-ab-val-card .aa-ms{font-size:28px;color:var(--aa-goldbg);display:block;margin-bottom:14px}
.aa-ab-val-card h3{color:var(--aa-tx);font-size:18px;font-weight:700;margin:0 0 10px}
.aa-ab-val-card p{color:#b9bcc8;font-size:14px;line-height:1.8;margin:0}
</style>
<section class="aa-ab-val">
  <div class="aa-wrap">
    <div class="aa-ab-eyebrow"><span class="aa-ms">psychology</span>How we publish</div>
    <h2>A publishing philosophy with three honest rules</h2>
    <p class="aa-ab-sub">Every title that carries the Annaadhya name is edited, formatted and priced under the same three commitments.</p>
    <div class="aa-ab-val-grid">
      <div class="aa-ab-val-card">
        <span class="aa-ms">currency_rupee</span>
        <h3>Price like a reader</h3>
        <p>Digital books cost less to make and deliver, so they should cost less to buy. We price every ebook between &#8377;49 and &#8377;399 &mdash; and we say so on the label.</p>
      </div>
      <div class="aa-ab-val-card">
        <span class="aa-ms">devices</span>
        <h3>Respect the device</h3>
        <p>Crisp typography, tested reflows and generous sample chapters on every title. If it does not read beautifully on a &#8377;10,000 Android phone, it does not ship.</p>
      </div>
      <div class="aa-ab-val-card">
        <span class="aa-ms">handshake</span>
        <h3>Partner like a co-author</h3>
        <p>Authors keep their voice, get transparent sales reports and industry-leading royalty shares. A rising tide of honest publishing lifts every pen it touches.</p>
      </div>
    </div>
  </div>
</section>
HTML;

$aa_about_authors = <<<'HTML'
<style>
.aa-ab-auth{background:var(--aa-surface);padding:64px 0;border-top:1px solid var(--aa-line)}
.aa-ab-auth h2{font-size:clamp(26px,3.4vw,38px);font-weight:600;letter-spacing:-.015em;color:var(--aa-tx);margin-bottom:14px}
.aa-ab-auth p.aa-ab-sub{font-size:16px;color:var(--aa-warm);line-height:1.8;max-width:680px;margin:0 0 36px}
.aa-ab-auth-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
@media(max-width:960px){.aa-ab-auth-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:640px){.aa-ab-auth-grid{grid-template-columns:1fr}}
.aa-ab-auth-card{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:26px;text-align:center}
.aa-ab-av{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--aa-goldbg),#b45309);color:var(--aa-ongold);font-weight:800;font-size:22px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
.aa-ab-auth-card h3{color:var(--aa-tx);font-size:17px;font-weight:700;margin:0 0 6px}
.aa-ab-auth-card span.aa-ab-role{display:block;color:var(--aa-goldbg);font-size:12px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;margin-bottom:10px}
.aa-ab-auth-card p{color:#b9bcc8;font-size:13.5px;line-height:1.7;margin:0}
</style>
<section class="aa-ab-auth">
  <div class="aa-wrap">
    <h2>The authors &amp; creators behind the shelf</h2>
    <p class="aa-ab-sub">Builders, teachers and storytellers who write the way India actually reads &mdash; practical, warm and straight to the point.</p>
    <div class="aa-ab-auth-grid">
      <div class="aa-ab-auth-card"><span class="aa-ab-av">AD</span><h3>Arjun Desai</h3><span class="aa-ab-role">Business</span><p>Day-job product manager turned launch coach. Author of <em>From Idea to Launch</em>.</p></div>
      <div class="aa-ab-auth-card"><span class="aa-ab-av">TB</span><h3>Tanmay Bhatia</h3><span class="aa-ab-role">Technology</span><p>Makes AI feel less like magic and more like a tool. Author of <em>AI for Everyone</em>.</p></div>
      <div class="aa-ab-auth-card"><span class="aa-ab-av">RS</span><h3>R. Senthil</h3><span class="aa-ab-role">Technology</span><p>Ten years of building WordPress stores that actually sell. Author of <em>WordPress Mastery</em>.</p></div>
      <div class="aa-ab-auth-card"><span class="aa-ab-av">AI</span><h3>Dr. Ananya Iyer</h3><span class="aa-ab-role">Self-Help</span><p>Cognitive-science researcher writing about focus for the notification age. Author of <em>Digital Discipline</em>.</p></div>
      <div class="aa-ab-auth-card"><span class="aa-ab-av">IR</span><h3>Ishita Rao</h3><span class="aa-ab-role">Business</span><p>Bootstrapper of two profitable side hustles. Author of <em>The Lean Side Hustle</em>.</p></div>
      <div class="aa-ab-auth-card"><span class="aa-ab-av">AS</span><h3>Arya Shakara</h3><span class="aa-ab-role">Fiction</span><p>Writes mountain-hearted stories rooted in the Himalayas. Author of <em>Echoes of the Himalayas</em>.</p></div>
    </div>
  </div>
</section>
HTML;

$aa_about_stats = <<<'HTML'
<style>
.aa-ab-stats{background:var(--aa-bg);border-top:1px solid var(--aa-line);padding:52px 0}
.aa-ab-stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;text-align:center}
@media(max-width:760px){.aa-ab-stats-grid{grid-template-columns:repeat(2,1fr)}}
.aa-ab-stats-grid strong{display:block;color:var(--aa-gold);font-size:30px;font-weight:800;letter-spacing:-.02em}
.aa-ab-stats-grid span{color:var(--aa-dim);font-size:13px;font-weight:600}
</style>
<section class="aa-ab-stats">
  <div class="aa-wrap">
    <div class="aa-ab-stats-grid">
      <div><strong>6</strong><span>Titles on the shelf</span></div>
      <div><strong>4,800+</strong><span>Readers served</span></div>
      <div><strong>4.9/5</strong><span>Average reader rating</span></div>
      <div><strong>&lt;10s</strong><span>Delivery to your device</span></div>
    </div>
  </div>
</section>
HTML;

/* ---------------------------------------------------------------- contact */

$aa_contact_css = <<<'HTML'
<style>
.aa-ct{background:var(--aa-surface);padding:20px 0 72px}
.aa-ct-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:22px;align-items:start}
@media(max-width:900px){.aa-ct-grid{grid-template-columns:1fr}}
.aa-ct-card{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:16px;padding:32px}
.aa-ct-card h2{color:var(--aa-tx);font-size:22px;font-weight:700;margin:0 0 6px}
.aa-ct-card>p.aa-ct-sub{color:var(--aa-dim);font-size:14px;margin:0 0 26px}
.aa-field{display:block;margin-bottom:16px}
.aa-field>span{display:block;color:var(--aa-warm);font-size:12.5px;font-weight:700;letter-spacing:.03em;margin-bottom:7px}
.aa-field input,.aa-field select,.aa-field textarea{width:100%;background:var(--aa-low);border:1px solid var(--aa-line2);border-radius:10px;padding:12px 14px;color:var(--aa-tx);font-size:14px;outline:none;font-family:inherit;box-sizing:border-box}
.aa-field select{appearance:none;-webkit-appearance:none;padding-right:36px;background-image:url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%23ffc174' stroke-width='2' stroke-linecap='round'%3E%3Cpath d='m4 6 4 4 4-4'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;cursor:pointer}
.aa-field select:focus,.aa-field input:focus,.aa-field textarea:focus{border-color:var(--aa-goldbg)}
.aa-field textarea{resize:vertical;min-height:120px}
.aa-cform-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
@media(max-width:640px){.aa-cform-grid{grid-template-columns:1fr}}
.aa-cform-done,.aa-cform-err{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;border-radius:10px;padding:12px 14px;margin:0 0 16px}
.aa-cform-done{color:#5fd79a;background:rgba(52,199,123,.1);border:1px solid rgba(52,199,123,.35)}
.aa-cform-done[hidden],.aa-cform-err[hidden]{display:none}
.aa-cform-err{color:#ff9d8a;background:rgba(224,82,82,.1);border:1px solid rgba(224,82,82,.35)}
.aa-ct-side{display:grid;gap:16px}
.aa-ct-side-card{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:24px}
.aa-ct-side-card .aa-ms{font-size:26px;color:var(--aa-goldbg);display:block;margin-bottom:10px}
.aa-ct-side-card h3{color:var(--aa-tx);font-size:16px;font-weight:700;margin:0 0 8px}
.aa-ct-side-card p{color:#b9bcc8;font-size:13.5px;line-height:1.75;margin:0 0 10px}
.aa-ct-side-card a{color:var(--aa-gold)!important;font-weight:700;text-decoration:none!important;font-size:13.5px}
.aa-faq{padding:56px 0 8px}
.aa-faq h2{font-size:clamp(24px,3vw,32px);font-weight:600;color:var(--aa-tx);margin:0 0 26px}
.aa-faq details{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:12px;margin-bottom:12px;overflow:hidden}
.aa-faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;color:var(--aa-tx);font-weight:700;font-size:15px}
.aa-faq summary::-webkit-details-marker{display:none}
.aa-faq summary .aa-ms{color:var(--aa-goldbg);font-size:20px;flex:none;transition:transform .2s}
.aa-faq details[open] summary .aa-ms{transform:rotate(45deg)}
.aa-faq details p{color:#b9bcc8;font-size:14px;line-height:1.8;margin:0;padding:0 20px 20px}
</style>
HTML;

$aa_contact_main = <<<'HTML'
<section class="aa-ct">
  <div class="aa-wrap">
    <div class="aa-ct-grid">
      <div class="aa-ct-card">
        <h2>Send us a message</h2>
        <p class="aa-ct-sub">Order issues, download trouble, refund requests or author submissions &mdash; one form, one team, one working day.</p>
        [aa_contact_form]
      </div>
      <div class="aa-ct-side">
        <div class="aa-ct-side-card">
          <span class="aa-ms">mail</span>
          <h3>Support desk</h3>
          <p>Email us any time &mdash; we reply within one working day, usually much faster.</p>
          <a href="mailto:support@annaadhya.com">support@annaadhya.com</a>
        </div>
        <div class="aa-ct-side-card">
          <span class="aa-ms">schedule</span>
          <h3>Support hours</h3>
          <p>Mon&ndash;Sat, 10:00&ndash;19:00 IST. Download links work around the clock even when we sleep.</p>
        </div>
        <div class="aa-ct-side-card">
          <span class="aa-ms">download</span>
          <h3>Download &amp; refund help</h3>
          <p>Link expired or payment twice? The fastest fixes live in our policies.</p>
          <a href="/digital-delivery-policy/">Digital delivery guide &rarr;</a><br>
          <a href="/refund-policy/">Refund policy &rarr;</a>
        </div>
      </div>
    </div>
  </div>
</section>
HTML;

$aa_contact_faq = <<<'HTML'
<section class="aa-faq">
  <div class="aa-wrap">
    <h2>Frequently asked questions</h2>
    <details open>
      <summary>I paid but did not get my download. What now?<span class="aa-ms">add</span></summary>
      <p>Seconds after payment the PDF is attached to a delivery email sent to the address you used at checkout &mdash; check spam first, then send us a message with your order number and we will re-send it the same day.</p>
    </details>
    <details>
      <summary>Can I read my ebook on Kindle or an iPad?<span class="aa-ms">add</span></summary>
      <p>Yes. Every Annaadhya title is a standard PDF tested on phones, tablets, Kindle Scribe and desktops. Transfer it like any personal file &mdash; no special app needed.</p>
    </details>
    <details>
      <summary>My download link stopped working. Can I get another one?<span class="aa-ms">add</span></summary>
      <p>Of course. The links in your delivery email never expire and there is no download limit &mdash; open the order page from your confirmation email and download again any time. See our <a href="/digital-delivery-policy/" style="color:var(--aa-gold)">Digital Delivery Policy</a> for the details.</p>
    </details>
    <details>
      <summary>What is your refund policy?<span class="aa-ms">add</span></summary>
      <p>If a download fails or a file is corrupted and we cannot fix it, you get a full refund. Tell us within 7 days of purchase with your order number. Full rules live in the <a href="/refund-policy/" style="color:var(--aa-gold)">Refund Policy</a>.</p>
    </details>
    <details>
      <summary>Do you accept UPI, GPay and PhonePe?<span class="aa-ms">add</span></summary>
      <p>Yes &mdash; UPI, RuPay, cards and netbanking all work at checkout through our payment gateway. Prices are in INR and include GST where applicable.</p>
    </details>
    <details>
      <summary>I am an author. How do I submit my manuscript?<span class="aa-ms">add</span></summary>
      <p>We would love to read it. Use the form on this page with the topic &ldquo;Author submission&rdquo;, paste a 200-word synopsis and tell us where the full manuscript lives. Our editorial team replies to every serious submission.</p>
    </details>
  </div>
</section>
HTML;

/* ---------------------------------------------------------------- legal */

$aa_legal_css = <<<'HTML'
<style>
.aa-legal{background:var(--aa-surface);padding:8px 0 72px}
.aa-legal-in{max-width:880px;margin:0 auto;padding:0 24px}
.aa-legal-meta{display:flex;align-items:center;gap:10px;margin-bottom:26px}
.aa-legal-meta span{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:700;color:var(--aa-dim);background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:8px;padding:6px 11px}
.aa-legal-meta .aa-ms{font-size:15px;color:var(--aa-goldbg)}
.aa-tldr{background:var(--aa-low);border:1px solid rgba(245,158,11,.45);border-radius:14px;padding:24px 26px;margin-bottom:34px}
.aa-tldr h3{display:flex;align-items:center;gap:9px;color:var(--aa-gold);font-size:14px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;margin:0 0 14px}
.aa-tldr h3 .aa-ms{font-size:18px}
.aa-tldr ul{list-style:none;margin:0;padding:0;display:grid;gap:9px}
.aa-tldr li{display:flex;gap:10px;color:var(--aa-tx);font-size:14.5px;line-height:1.7}
.aa-tldr li .aa-ms{font-size:17px;color:var(--aa-goldbg);flex:none;margin-top:2px}
.aa-prose h2{color:var(--aa-gold);font-size:21px;font-weight:700;margin:40px 0 14px;padding-top:20px;border-top:1px solid var(--aa-line)}
.aa-prose h2:first-child{border-top:0;padding-top:0;margin-top:0}
.aa-prose p{color:#b9bcc8;font-size:15px;line-height:1.85;margin:0 0 14px}
.aa-prose ul{list-style:none;margin:0 0 16px;padding:0;display:grid;gap:9px}
.aa-prose ul li{display:flex;gap:10px;color:#b9bcc8;font-size:14.5px;line-height:1.75}
.aa-prose ul li .aa-ms{font-size:16px;color:var(--aa-goldbg);flex:none;margin-top:3px}
.aa-prose strong{color:var(--aa-tx)}
.aa-prose a{color:var(--aa-gold)!important;font-weight:600}
.aa-legal-related{max-width:880px;margin:44px auto 0;padding:26px 24px;border-top:1px solid var(--aa-line);display:flex;flex-wrap:wrap;gap:10px}
.aa-legal-related a{display:inline-flex;align-items:center;gap:8px;background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:10px;padding:9px 15px;color:var(--aa-tx)!important;font-size:13px;font-weight:700;text-decoration:none!important}
.aa-legal-related a:hover{border-color:var(--aa-gold);color:var(--aa-gold)!important}
.aa-legal-related .aa-ms{font-size:16px;color:var(--aa-goldbg)}
</style>
HTML;

function aa_legal_related() {
        return '<div class="aa-legal-related">'
                . '<a href="/privacy-policy/"><span class="aa-ms">shield</span>Privacy Policy</a>'
                . '<a href="/terms/"><span class="aa-ms">description</span>Terms &amp; Conditions</a>'
                . '<a href="/refund-policy/"><span class="aa-ms">currency_rupee</span>Refund Policy</a>'
                . '<a href="/digital-delivery-policy/"><span class="aa-ms">cloud_download</span>Digital Delivery</a>'
                . '<a href="/copyright-policy/"><span class="aa-ms">copyright</span>Copyright Policy</a>'
                . '</div>';
}

/* ---------------------------------------------------------------- product page */

$aa_pdp_css = <<<'HTML'
<style>
.aa-pdp{background:var(--aa-surface);padding:0 0 72px}
.aa-pdp-grid{display:grid;grid-template-columns:420px 1fr;gap:48px;padding-top:36px}
@media(max-width:980px){.aa-pdp-grid{grid-template-columns:1fr;gap:28px}}
@media(max-width:980px){.aa-pdp-grid{grid-template-columns:1fr}}
.aa-pdp-media{display:flex;justify-content:center}
.aa-pdp-coverwrap{position:relative;width:100%;max-width:420px;aspect-ratio:3/4;border-radius:18px;overflow:hidden;border:1px solid var(--aa-line2);background:var(--aa-low);box-shadow:0 30px 80px rgba(0,0,0,.5)}
.aa-pdp-coverwrap:after{content:"";position:absolute;inset:0;background:radial-gradient(120% 90% at 80% 0%,rgba(245,158,11,.16),transparent 55%);pointer-events:none}
.aa-pdp-cover{width:100%;height:100%;object-fit:cover;display:block}
.aa-pdp-title{font-size:clamp(28px,3.6vw,40px);font-weight:600;letter-spacing:-.02em;color:var(--aa-tx);line-height:1.2;margin:12px 0 10px}
.aa-pdp-author{color:var(--aa-warm);font-size:15px;margin:0 0 14px}
.aa-pdp-author strong{color:var(--aa-gold)}
.aa-pdp-rate{display:flex;align-items:center;gap:10px;margin-bottom:22px}
.aa-fmtchip{font-size:11px;font-weight:800;letter-spacing:.07em;color:var(--aa-warm);background:var(--aa-cont);border:1px solid var(--aa-line2);border-radius:6px;padding:4px 9px}
.aa-dot{color:var(--aa-dim)}
.aa-pdp-pricebox{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:16px;padding:26px}
.aa-pdp-price{display:flex;align-items:center;gap:14px;margin-bottom:20px;font-size:26px;font-weight:800;color:var(--aa-gold)}
.aa-pdp-price del{color:var(--aa-dim);font-size:17px;font-weight:600}
.aa-pdp-price .aa-now{color:var(--aa-gold)}
.aa-savepill{display:inline-flex;align-items:center;background:var(--aa-goldbg);color:var(--aa-ongold);font-size:12px;font-weight:800;border-radius:999px;padding:5px 12px}
.aa-pdp-cta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.aa-wishbtn-lg{position:static;width:48px;height:48px;border-radius:12px}
.aa-wishbtn-lg .aa-ms{font-size:22px}
.aa-pdp-trust{list-style:none;margin:0;padding:20px 0 0;border-top:1px solid var(--aa-line);display:grid;gap:10px}
.aa-pdp-trust li{display:flex;align-items:center;gap:10px;color:var(--aa-dim);font-size:13.5px;font-weight:600}
.aa-pdp-trust .aa-ms{font-size:18px;color:var(--aa-goldbg)}
.aa-pdp-body{display:grid;grid-template-columns:1.7fr 1fr;gap:34px;padding-top:52px;border-top:1px solid var(--aa-line);margin-top:52px}
@media(max-width:900px){.aa-pdp-body{grid-template-columns:1fr}}
.aa-pdp-body h2{color:var(--aa-tx);font-size:24px;font-weight:700;margin:0 0 16px}
.aa-pdp-side{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:26px;height:fit-content}
.aa-pdp-side h3{color:var(--aa-gold);font-size:16px;font-weight:800;margin:0 0 16px}
.aa-pdp-side ul{list-style:none;margin:0;padding:0;display:grid;gap:0}
.aa-pdp-side li{display:flex;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid var(--aa-line);font-size:13.5px}
.aa-pdp-side li:last-child{border-bottom:0}
.aa-pdp-side li span{color:var(--aa-dim)}
.aa-pdp-side li strong{color:var(--aa-tx);font-weight:700}
.aa-pdp-reviews{padding-top:52px;border-top:1px solid var(--aa-line);margin-top:52px}
.aa-pdp-reviews h2{color:var(--aa-tx);font-size:24px;font-weight:700;display:flex;align-items:center;gap:14px;margin:0 0 22px}
.aa-ratepill{display:inline-flex;align-items:center;gap:5px;background:var(--aa-goldbg);color:var(--aa-ongold);font-size:13px;font-weight:800;border-radius:999px;padding:5px 12px}
.aa-ratepill .aa-ms{font-size:15px}
.aa-review-list{display:grid;gap:14px;max-width:760px}
.aa-review{background:var(--aa-low);border:1px solid var(--aa-line);border-radius:14px;padding:22px}
.aa-review-top{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.aa-av{width:38px;height:38px;border-radius:50%;background:var(--aa-high2);color:var(--aa-gold);font-weight:800;font-size:15px;display:flex;align-items:center;justify-content:center;flex:none}
.aa-review-top strong{display:block;color:var(--aa-tx);font-size:14px}
.aa-review-top .aa-when{margin-left:auto;color:var(--aa-dim);font-size:12px}
.aa-review p{color:#b9bcc8;font-size:14px;line-height:1.75;margin:0}
.aa-review-none{display:flex;align-items:center;gap:12px;color:var(--aa-dim);font-size:14px}
.aa-review-none .aa-ms{font-size:24px;color:var(--aa-goldbg)}
.aa-review-none p{margin:0}
.aa-pdp-related{background:var(--aa-bg);border-top:1px solid var(--aa-line);padding:52px 0 64px;margin-top:0}
.aa-pdp-related h2{color:var(--aa-tx);font-size:24px;font-weight:700;margin:0 0 26px}
.aa-pdp .aa-prose p{color:#b9bcc8;font-size:15px;line-height:1.85;margin:0 0 14px}
.aa-pdp .aa-stars{margin-bottom:0}
</style>
HTML;

/* section wrappers for dynamic shortcodes */

$aa_cart_body = $aa_woo_css . '<section class="aa-woopage">[woocommerce_cart]</section>';
$aa_checkout_body = $aa_woo_css . '<section class="aa-woopage">[woocommerce_checkout]</section>';
$aa_account_body = $aa_woo_css . '<section class="aa-woopage">[woocommerce_my_account]</section>';
$aa_library_body = $aa_lib_css . '<section class="aa-lib-wrap">[aa_library]</section>';
$aa_pdp_body = $aa_pdp_css . '<section class="aa-pdp-shell">[aa_product_single]</section>';
