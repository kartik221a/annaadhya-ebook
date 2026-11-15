#!/bin/bash
# Build demo content for the Annaadhya eBooks local store (idempotent-ish; run once on fresh DB)
set -e
SITE=./site
WP="php wp --path=$SITE --allow-root --user=1"

echo "== product categories =="
cat_ids=()
for cname in Technology Business "Self-Help" Fiction; do
  id=$($WP wc product_cat create --name="$cname" --porcelain 2>/dev/null || $WP wc product_cat list --field=id --name="$cname" 2>/dev/null)
  cat_ids+=("$id")
  echo "category '$cname' -> $id"
done
TECH=${cat_ids[0]}; BUS=${cat_ids[1]}; SELF=${cat_ids[2]}; FIC=${cat_ids[3]}

echo "== copy protected download files =="
mkdir -p $SITE/wp-content/uploads/woocommerce_uploads
cp ./sample-pdfs/*.pdf $SITE/wp-content/uploads/woocommerce_uploads/

create_product () {
  local slug=$1 title=$2 price=$3 catid=$4 blurb=$5
  att=$($WP media import ./covers/$slug.png --title="$title cover" --porcelain --post_name="$slug-cover")
  id=$($WP wc product create \
    --name="$title" \
    --slug="$slug" \
    --type=simple \
    --virtual=true --downloadable=true --sold_individually=true \
    --regular_price="$price" \
    --short_description="$blurb" \
    --description="$blurb Instant PDF download after checkout. This is demo content for the Annaadhya eBooks store - replace with your real ebook." \
    --categories="[{\"id\":$catid}]" \
    --images="[{\"id\":$att}]" \
    --downloads='[{"name":"eBook PDF (sample)","file":"wp-content/uploads/woocommerce_uploads/'$slug'.pdf"}]' \
    --porcelain)
  echo "product '$title' -> $id (attachment $att)"
}

create_product wordpress-mastery "WordPress Mastery: Build Sites That Sell" 299 $TECH "A hands-on guide to building fast, professional WordPress sites that convert visitors into customers. Covers hosting, themes, plugins, and performance."
create_product lean-side-hustle "The Lean Side Hustle" 199 $BUS "A practical playbook for starting a low-cost online business on the side. Validate fast, spend little, and reach your first sale."
create_product digital-discipline "Digital Discipline: Focus in the Age of Noise" 249 $SELF "Rebuild your attention in a world engineered to steal it. Simple systems for deep work, calm mornings, and screen-life balance."
create_product echoes-himalayas "Echoes of the Himalayas" 149 $FIC "A sweeping novel of a young trekking guide whose quiet mountain life changes forever when a lost traveler arrives at his village."
create_product ai-for-everyone "AI for Everyone: A Practical Guide" 349 $TECH "Understand and actually use modern AI tools - no coding required. Workflows for writing, research, design, and everyday productivity."
create_product idea-to-launch "From Idea to Launch: 30 Days to Your First Product" 399 $BUS "A day-by-day program that takes you from raw idea to a launched digital product in one month, with checklists for every step."

echo "== content pages =="
HOMEPG=$(cat <<'EOF'
<!-- wp:cover {"overlayColor":"dark-blue","minHeight":55,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:55px"><span aria-hidden="true" class="wp-block-cover__background has-dark-blue-background-color"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":1} --><h1 class="wp-block-heading has-text-align-center">Annaadhya eBooks</h1><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} --><p class="has-text-align-center has-white-color">Curated ebooks. Instant downloads. Read on any device.</p><!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"vivid-cyan-blue"} --><div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background" href="/shop/">Browse the Shop</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div></div><!-- /wp:cover -->
<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Featured ebooks</h2><!-- /wp:heading -->
<!-- wp:shortcode -->[products limit="3" columns="3" orderby="date" order="DESC"]<!-- /wp:shortcode -->
<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><em>Demo content: these sample titles exist so you can test the complete store flow.</em></p><!-- /wp:paragraph -->
EOF
)
HOME_ID=$($WP post create --post_type=page --post_title="Home" --post_name="home" --post_status=publish --post_content="$HOMEPG" --porcelain)

ABOUT_ID=$($WP post create --post_type=page --post_title="About" --post_name="about" --post_status=publish --porcelain --post_content='<!-- wp:paragraph --><p>Welcome to Annaadhya eBooks - a digital bookstore demo built for the Annaadhya team. This page is placeholder copy: replace it with your real story, mission, and team details before launch.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Every ebook here is delivered instantly as a PDF download, with no shipping and no waiting. If you have questions about an order or want to publish with us, reach out through the Contact page.</p><!-- /wp:paragraph -->')

CONTACT_ID=$($WP post create --post_type=page --post_title="Contact" --post_name="contact" --post_status=publish --porcelain --post_content='<!-- wp:paragraph --><p>Questions about an ebook, an order, or publishing with us? Email <strong>hello@annaadhya.com</strong> (placeholder address - update before launch) and we will reply within two business days.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>For instant answers, check the Refund and Returns Policy and your My Account page for order history and re-downloads.</p><!-- /wp:paragraph -->')

echo "pages -> home:$HOME_ID about:$ABOUT_ID contact:$CONTACT_ID"

echo "== static front page =="
$WP option update show_on_front page
$WP option update page_on_front $HOME_ID

echo "== navigation menu =="
$WP menu create "Primary Menu" || true
$WP menu item add-post home --title="Home" || true
$WP menu item add-post 5 --title="Shop" || true
$WP menu item add-post $ABOUT_ID --title="About" || true
$WP menu item add-post $CONTACT_ID --title="Contact" || true
$WP menu location assign "Primary Menu" primary || echo "note: check astra menu location slug"
echo "== DONE =="
