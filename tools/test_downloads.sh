#!/bin/bash
# E2E: auth card UI -> AJAX login -> library meter -> 5 downloads -> 6th blocked -> re-buy state
set -u
cd "$(dirname "$0")/.."
PHP=/home/z/my-project/tools/php
BASE=http://127.0.0.1:8080
JAR=/tmp/aa_jar.txt; rm -f $JAR
FAIL=0

PHP_CLI_SERVER_WORKERS=4 setsid $PHP -d memory_limit=512M -S 127.0.0.1:8080 -t site router.php > /tmp/php-server.log 2>&1 &
SRV=$!
sleep 2

echo "== 1. /my-account/ logged out: auth card markers"
ACC=$(curl -s $BASE/my-account/ --max-time 40)
for marker in "Continue with Google" "or continue with email" "data-aa-authcard" "data-aa-login" "data-aa-register" "5 full downloads"; do
  echo "$ACC" | grep -q "$marker" && echo "  ok: $marker" || { echo "  MISSING: $marker"; FAIL=1; }
done
NONCE=$(echo "$ACC" | grep -o 'data-nonce="[a-f0-9]*"' | head -1 | sed 's/data-nonce="//;s/"//')
echo "  nonce: ${NONCE:0:8}..."
[ -z "$NONCE" ] && { echo "  FATAL: no nonce"; kill $SRV 2>/dev/null; exit 1; }

echo "== 1b. auth inline JS syntax"
echo "$ACC" | awk '/data-aa-authcard<\/?/{f=1} f&&/<script>/{s=1} s{print} s&&/<\/script>/{exit}' | sed '1s/^<script>//' > /tmp/authjs.js
node --check /tmp/authjs.js && echo "  ok: auth JS parses" || { echo "  AUTH JS SYNTAX ERROR"; FAIL=1; }

echo "== 2. AJAX register (fresh random user)"
UE="t$RANDOM$RANDOM@annaadhya.test"
R=$(curl -s -c $JAR -b $JAR -X POST $BASE/wp-admin/admin-ajax.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "action=aa_register_submit" --data-urlencode "nonce=$NONCE" \
  --data-urlencode "aa_reg_name=Test Two" --data-urlencode "aa_reg_email=$UE" \
  --data-urlencode "aa_reg_pass=secondpass9" --max-time 30)
echo "  register $UE: $R"
echo "$R" | grep -q '"status":"ok"' || FAIL=1
# clear cookies -> login as the seeded reader
rm -f $JAR

echo "== 3. AJAX login (wrong pass)"
W=$(curl -s -X POST $BASE/wp-admin/admin-ajax.php \
  --data-urlencode "action=aa_login_submit" --data-urlencode "nonce=$NONCE" \
  --data-urlencode "aa_login_id=reader@annaadhya.test" --data-urlencode "aa_login_pass=WRONG" --max-time 30)
echo "  wrong-pass: $W"
echo "$W" | grep -q '"status":"error"' || FAIL=1

echo "== 4. AJAX login (correct)"
L=$(curl -s -c $JAR -b $JAR -X POST $BASE/wp-admin/admin-ajax.php \
  --data-urlencode "action=aa_login_submit" --data-urlencode "nonce=$NONCE" \
  --data-urlencode "aa_login_id=reader@annaadhya.test" --data-urlencode "aa_login_pass=readmore2026" \
  --data-urlencode "aa_remember=1" --max-time 30)
echo "  login: $L"
echo "$L" | grep -q '"status":"ok"' || FAIL=1

echo "== 5. /library/ meter + download URL"
LIB=$(curl -s -b $JAR $BASE/library/ --max-time 40)
echo "$LIB" | grep -q "0 of 5 downloads used" && echo "  ok: '0 of 5 downloads used' meter" || { echo "  MISSING meter"; FAIL=1; }
DLURL=$(echo "$LIB" | grep -o 'href="[^"]*download_file[^"]*"' | head -1 | sed 's/href="//;s/"$//;s/&#038;/\&/g;s/&amp;/\&/g')
echo "  dlurl: ${DLURL:0:90}..."
[ -z "$DLURL" ] && { echo "  FATAL: no download url"; kill $SRV 2>/dev/null; exit 1; }

echo "== 6. five downloads"
for i in 1 2 3 4 5; do
  RES=$(curl -s -b $JAR -o /tmp/dl_$i.pdf -w "%{http_code} %{size_download} %{content_type}" "$DLURL" --max-time 40)
  echo "  dl#$i -> $RES"
  echo "$RES" | grep -q "^200 .*pdf" || FAIL=1
done
file /tmp/dl_5.pdf | grep -qi "PDF document" && echo "  ok: 5th download is a real PDF" || { echo "  NOT A PDF"; FAIL=1; }

echo "== 7. sixth download blocked"
SIX=$(curl -s -b $JAR "$DLURL" --max-time 40)
echo "$SIX" | grep -qi "used all 5 downloads" && echo "  ok: limit message shown" || { echo "  6TH NOT BLOCKED:"; echo "$SIX" | head -c 300; FAIL=1; }
echo "$SIX" | grep -qi "Open My Library" && echo "  ok: message links to library" || { echo "  no library link in block message"; FAIL=1; }

echo "== 8. library now shows exhausted + re-buy"
LIB2=$(curl -s -b $JAR $BASE/library/ --max-time 40)
for marker in "5 of 5 downloads used" "Downloads used up" "Buy again" "0 left"; do
  echo "$LIB2" | grep -q "$marker" && echo "  ok: $marker" || { echo "  MISSING: $marker"; FAIL=1; }
done

echo "== 9. checkout fields are address-free (fresh guest)"
rm -f /tmp/aa_jar2.txt
ADD=$(curl -s -c /tmp/aa_jar2.txt -b /tmp/aa_jar2.txt $BASE/checkout/ --max-time 40)
# empty cart redirects; add item then load checkout
curl -s -c /tmp/aa_jar2.txt -b /tmp/aa_jar2.txt "$BASE/shop/?add-to-cart=19" -o /dev/null --max-time 40
ADD=$(curl -s -c /tmp/aa_jar2.txt -b /tmp/aa_jar2.txt $BASE/checkout/ --max-time 40)
for gone in 'id="billing_address_1"' 'id="billing_city"' 'id="billing_country"' 'id="billing_postcode"' 'id="order_comments"' 'ship-to-different-address'; do
  echo "$ADD" | grep -q "$gone" && { echo "  STILL PRESENT: $gone"; FAIL=1; } || echo "  ok: no $gone"
done
for keep in 'id="billing_first_name"' 'id="billing_email"'; do
  echo "$ADD" | grep -q "$keep" && echo "  ok: has $keep" || { echo "  MISSING: $keep"; FAIL=1; }
done

kill $SRV 2>/dev/null
echo "===== RESULT: $([ $FAIL -eq 0 ] && echo ALL-PASS || echo HAS-FAILURES) ====="
