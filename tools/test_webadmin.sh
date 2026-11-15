#!/bin/bash
# End-to-end test for the Annaadhya Web Admin panel (/web-admin).
# Boots a temporary PHP server and runs login + full CRUD checks.
#
# Needs `php` and `wp` (wp-cli) on your PATH; skips gracefully if missing.
# Inside Docker you can run the checks with:
#   docker compose exec -T web php tools/test_webadmin.sh   (php + wp needed in the image)
set -u
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE="${BASE:-http://localhost:8080}"
JAR="$(mktemp)"
PASS=0; FAIL=0

say()  { echo "== $*"; }
ok()   { PASS=$((PASS+1)); echo "PASS: $*"; }
bad()  { FAIL=$((FAIL+1)); echo "FAIL: $*"; }
nonce_from() { grep -oE 'name="_wpnonce" value="[a-f0-9]+' "$1" | head -1 | sed 's/.*value="//;s/"$//'; }

if ! command -v php >/dev/null 2>&1 || ! command -v wp >/dev/null 2>&1; then
	echo "tools/test_webadmin.sh needs 'php' and 'wp' (wp-cli) on PATH — skipping."
	exit 0
fi
WP="wp --path=$REPO/site --allow-root --user=1"

pkill -f "php -S .*8080" 2>/dev/null; sleep 1
(php -S 0.0.0.0:8080 -t "$REPO/site" "$REPO/router.php" >/tmp/aa-webadmin-test.log 2>&1 &)
sleep 2
rm -f "$JAR"

say "0. /admin redirects to /web-admin (never wp-admin)"
hdr=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "$BASE/admin")
echo "$hdr" | grep -q "302 .*web-admin" && ok "redirect: $hdr" || bad "redirect: $hdr"

say "1. anonymous GET /web-admin shows login page"
code=$(curl -s -o /tmp/aa1.html -w "%{http_code}" "$BASE/web-admin")
nonce=$(nonce_from /tmp/aa1.html)
grep -q "Sign in" /tmp/aa1.html && [ -n "$nonce" ] && ok "login page (HTTP $code)" || bad "login page"

say "2. wrong password rejected"
curl -s -c "$JAR" -o /tmp/aa2.html -L "$BASE/web-admin" \
	--data-urlencode "aa_login=1" --data-urlencode "aa_user=kartik" \
	--data-urlencode "aa_pass=WRONG" --data-urlencode "_wpnonce=$nonce"
grep -q "Wrong username or password" /tmp/aa2.html && ok "wrong password rejected" || bad "wrong password rejected"

say "3. correct password logs in"
curl -s -c "$JAR" -b "$JAR" -o /dev/null "$BASE/web-admin" \
	--data-urlencode "aa_login=1" --data-urlencode "aa_user=${WP_USER:-kartik}" \
	--data-urlencode "aa_pass=${WP_PASS:-Annaadhya!2026}" --data-urlencode "_wpnonce=$nonce"
code=$(curl -s -b "$JAR" -o /tmp/aa3.html -w "%{http_code}" "$BASE/web-admin")
grep -q "Books in store" /tmp/aa3.html && ok "dashboard (HTTP $code)" || bad "dashboard"

say "4. books list shows seed books"
curl -s -b "$JAR" -o /tmp/aa4.html "$BASE/web-admin/products"
grep -q "WordPress Mastery" /tmp/aa4.html && ok "list contains seed books" || bad "list contains seed books"

say "5. create book with cover + pdf upload"
curl -s -b "$JAR" -o /tmp/aa5.html "$BASE/web-admin/products/new"
nonce=$(nonce_from /tmp/aa5.html)
[ -n "$nonce" ] && ok "new-book form has nonce" || bad "new-book form has nonce"
catid=$($WP wc product_cat list --fields=id,slug 2>/dev/null | awk -F'\t' '$2=="technology"{print $1}' | head -1)
cover="$REPO/site/wp-content/uploads/2026/11/wordpress-mastery.png"
[ -f "$cover" ] || cover=$(ls "$REPO"/site/wp-content/uploads/20*/*/[a-z]*.png 2>/dev/null | head -1)
pdf="$REPO/site/wp-content/uploads/woocommerce_uploads/wordpress-mastery.pdf"
[ -f "$pdf" ] || pdf=$(ls "$REPO"/site/wp-content/uploads/woocommerce_uploads/*.pdf 2>/dev/null | head -1)
loc=$(curl -s -b "$JAR" -o /dev/null -w "%{redirect_url}" "$BASE/web-admin/products" \
	-F "aa_action=save" -F "_wpnonce=$nonce" \
	-F "aa_title=Admin Test Book" -F "aa_price=99" -F "aa_sale=79" \
	-F "aa_status=publish" -F "aa_short=Short line" -F "aa_desc=Full description here." \
	-F "aa_cats[]=$catid" \
	-F "aa_cover=@$cover;type=image/png" \
	-F "aa_pdf=@$pdf;type=application/pdf")
case "$loc" in *web-admin/products/*/edit?aa_msg=product_created*) ok "create redirects with flash";; *) bad "create redirect ($loc)";; esac
pid=$(echo "$loc" | sed 's|.*/products/||;s|/edit.*||')

say "6. verify created product in WooCommerce"
prod=$($WP wc product get "$pid" --fields=name,regular_price 2>/dev/null)
echo "$prod" | grep -q "Admin Test Book" && ok "product exists in WC" || bad "product exists ($prod)"
dlfile=$($WP eval-file "$REPO/tools/get_dlfile.php" "$pid" 2>/dev/null | tail -1)
[[ "$dlfile" == *.pdf ]] && ok "PDF download attached ($dlfile)" || bad "PDF download attached ($dlfile)"

say "7. edit route /web-admin/products/<id>/edit"
curl -s -b "$JAR" -o /tmp/aa7.html "$BASE/web-admin/products/$pid/edit"
grep -q "Save changes" /tmp/aa7.html && ok "edit form renders prefilled" || bad "edit form renders"
nonce=$(nonce_from /tmp/aa7.html)
curl -s -b "$JAR" -o /dev/null "$BASE/web-admin/products" \
	-F "aa_action=save" -F "_wpnonce=$nonce" -F "aa_id=$pid" \
	-F "aa_title=Admin Test Book v2" -F "aa_price=149" -F "aa_sale=" \
	-F "aa_status=publish" -F "aa_desc=Updated." -F "aa_cats[]=$catid"
p2=$($WP wc product get "$pid" --fields=name,regular_price 2>/dev/null)
echo "$p2" | grep -q "149.00" && ok "edit applied" || bad "edit applied ($p2)"

say "8. delete book"
curl -s -b "$JAR" -o /dev/null "$BASE/web-admin/products" \
	--data-urlencode "aa_action=delete" --data-urlencode "_wpnonce=$nonce" \
	--data-urlencode "aa_id=$pid"
gone=$($WP post list --post_type=product --post__in=$pid --field=ID 2>/dev/null)
[ -z "$gone" ] && ok "book deleted" || bad "book still exists"

say "9. category CRUD"
curl -s -b "$JAR" -o /tmp/aa9.html "$BASE/web-admin/categories/new"
nonce=$(nonce_from /tmp/aa9.html)
curl -s -b "$JAR" -o /dev/null "$BASE/web-admin/categories" \
	--data-urlencode "aa_action=save" --data-urlencode "_wpnonce=$nonce" \
	--data-urlencode "aa_name=Admin Test Cat" --data-urlencode "aa_desc=Temp" \
	--data-urlencode "aa_slug=admin-test-cat"
tid=$($WP wc product_cat list --fields=id,slug 2>/dev/null | awk -F'\t' '$2=="admin-test-cat"{print $1}' | head -1)
[ -n "$tid" ] && ok "category created" || bad "category created"
curl -s -b "$JAR" -o /dev/null "$BASE/web-admin/categories" \
	--data-urlencode "aa_action=save" --data-urlencode "_wpnonce=$nonce" \
	--data-urlencode "aa_id=$tid" --data-urlencode "aa_name=Admin Test Cat v2" \
	--data-urlencode "aa_desc=Temp" --data-urlencode "aa_slug=admin-test-cat"
renamed=$($WP wc product_cat get "$tid" --field=name 2>/dev/null)
[ "$renamed" = "Admin Test Cat v2" ] && ok "category renamed" || bad "category renamed ($renamed)"
curl -s -b "$JAR" -o /dev/null "$BASE/web-admin/categories" \
	--data-urlencode "aa_action=delete" --data-urlencode "_wpnonce=$nonce" --data-urlencode "aa_id=$tid"
left=$($WP wc product_cat list --fields=slug 2>/dev/null | grep -c "admin-test-cat" || true)
[ "$left" = "0" ] && ok "category deleted" || bad "category deleted"

say "10. orders page renders"
curl -s -b "$JAR" -o /tmp/aa10.html "$BASE/web-admin/orders"
grep -q "Orders" /tmp/aa10.html && ok "orders page" || bad "orders page"

say "11. storefront unaffected"
curl -s -o /tmp/aa11.html -w "%{http_code}" "$BASE/" | grep -q 200 && grep -q "Browse by collection" /tmp/aa11.html && ok "homepage renders" || bad "homepage"

pkill -f "php -S .*8080" 2>/dev/null
rm -f "$JAR"
echo
echo "RESULT: $PASS passed, $FAIL failed"
exit $FAIL
