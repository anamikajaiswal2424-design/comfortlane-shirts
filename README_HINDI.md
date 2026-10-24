# Payment replacement from gunofknew-main

Pehli cart-action.zip ki website ka UI/catalogue use hua hai. Payment behavior doosri gunofknew-main ZIP ke reference par adapt hua hai. Yeh full Vercel-ready project hai; PHP source app/ mein aur editable central setting.php repository root mein hai.

## Aapko kya edit karna hai

Root setting.php mein upi_id, merchant_name, pixel_id, paytm_mid aur paytm_env bhariye. Har app/QR ka receiver isi file se banta hai; PAYTM_UPI_ID environment variable ya purane admin values ise override nahi karte. Existing Pixel code retained hai.

## Kya replace/remove hua

- Purane MongoDB payment-verification.php, mongo-settings.php, database receipt writes aur tracker dependencies remove hue.
- Extra processing.php server-refresh verification screen retire hui; payment page par hi polling/result handle hota hai.
- Reference initiation response fields: txnid, amount, upiUrl, qrCodeUrl, paytmIntent, phonepeIntent, merchantName, upiId. Existing PHP form fields compatible hain; JSON requests bhi supported hain.
- /api/initiate-payment aur /api/verify-payment aliases supplied API names ko support karte hain. Session/CSRF required hain; existing UI unhe send karti hai.
- Page open par transaction prepare hota hai. App select karne par wahi current checkout reference reuse hota hai.
- Paytm/QR status API ~9-second long-poll aur ~2.5-second provider interval use karti hai. PhonePe native COLLECT poll nahi karta.
- Reference ki tarah missing Paytm MID intent generation ko block nahi karta; verification pending rahegi, automatic success nahi.
- QR popup close karne se polling continue rehti hai. App modal mein explicit cancellation 60 seconds baad available hai; cancellation polling stop karti hai. Five-minute automatic polling stop removed hai; checkout session/attempt expiry still applies.
- Confirmed failure failure page kholti hai. Verified server response par existing order/thank-you UI use hota hai.
- Browser URL/status/button click ko bank success nahi maana jata. Cart amount/merchant/order checks aur CSRF retained hain. Payment status save hone ke liye local merchant pool/webhook/scraper ki zarurat nahi.
- Source ka hard-coded merchant signature/category reuse nahi hua; standard Paytm intent parameters use hue. Different merchant ke liye fixed signature valid configuration nahi hoti.

## Important distinction

Doosri ZIP mein bhi Paytm Order Status API verification hai. Isliye Paytm status lookup retained hai; MongoDB real-time tracker remove hua hai. PhonePe native flow reference ki tarah automatic thank-you nahi deta. Generic UPI link aur arbitrary receiver + MID se Paytm merchant order automatically create nahi hota. Real merchant-linked payment test required hai.

Vercel ka Redis connection ab bhi required hai, lekin **sirf website cart sessions, saved orders, catalogue/admin settings aur analytics persistence ke liye**. Redis bank/payment verifier nahi hai. Credentials Vercel environment mein configure karein; instructions neeche hain.

## Validation for this replacement

Local dual-instance fixture tests passed: central setting.php receiver, reference API response shape, Paytm verification/UTR/thank-you, missing-MID intent launch, failure route, retired processing screen, durable cart/admin/orders, protected config and unchanged buttons/slider. No live payment or remote deployment has been performed.

---

# Comfortlane — Vercel deployable PHP project

Yeh **full website** hai. Existing storefront UI, Add to Cart / Buy Now, five-visible-thumbnail slider, checkout, server verification aur thank-you layout preserve hain. PHP ko Next.js mein rewrite nahi kiya gaya: Vercel-compatible `vercel-php@0.9.0` community runtime (PHP 8.5 / Node.js 22) use hota hai.

## Project structure

- `vercel.json`: PHP function runtime aur safe routing.
- `api/index.php`: dynamic pages/API ka single serverless entry point.
- `api/php.ini`: PHP/session/upload configuration.
- `app/`: private PHP pages, admin, helpers aur seed product catalogue.
- `public/assets/`: browser ke CSS, JavaScript, images aur icons.
- `setting.php`: central UPI/merchant/Pixel/Paytm configuration.
- `scripts/build.mjs`: deployment structure/public-output check.
- `.env.example`: private environment variable names; actual secrets included nahi hain.

## Vercel mein deploy karne ke steps

1. ZIP extract karke **iske andar ke files** GitHub repository root mein upload karein. Root par vercel.json, package.json, api, app, public aur setting.php hone chahiye. Extra parent folder ko Root Directory banana padega agar aapne use bhi commit kiya ho.
2. Vercel > New Project > apna GitHub repository Import karein.
3. Framework Preset **Other**. Node.js **22.x**. Build Command `npm run build`; Output Directory `public`. vercel.json bhi ye values set karta hai. Next.js preset select na karein.
4. Vercel Storage/Marketplace se **Upstash Redis** project ke saath connect karein. Deployment ke liye persistent storage required hai; local PHP JSON/session writes Vercel par reliable nahi hote.
5. Project > Settings > Environment Variables mein:
   - `UPSTASH_REDIS_REST_URL`
   - `UPSTASH_REDIS_REST_TOKEN`
   - `COMFYNEST_ADMIN_USERNAME` (example admin)
   - `COMFYNEST_ADMIN_PASSWORD` (apna strong password; chat/GitHub mein paste na karein)
   - `STORE_REDIS_PREFIX` (example `comfortlane:production:`)
6. Integration agar `KV_REST_API_URL` aur `KV_REST_API_TOKEN` names set kare, code unhe bhi support karta hai. Read-only token use na karein.
7. Preview ke liye alag `STORE_REDIS_PREFIX` (example `comfortlane:preview:`) ya alag database set karein. Production aur preview ko accidental same state na dein.
8. Root `setting.php` mein apni UPI ID, merchant name, Pixel ID, Paytm MID aur production/test environment set karein.
9. Deploy karein. Environment changes ke baad Redeploy karein. setting.php/source changes GitHub mein commit karke redeploy hoti hain — Vercel par cPanel-style PHP file editing nahi hoti.

Is delivery mein connected Vercel project par deploy nahi kiya gaya aur koi Redis resource provision nahi hua. Aapko integration/environment configure karna hoga. Storage missing ho to dynamic site 503 setup failure deta hai; fake temporary order/session storage use nahi hoti.

## setting.php

Isi root file mein:

```php
'upi_id' => 'yourbusiness@bank',
'merchant_name' => 'Your Store',
'pixel_id' => '123456789012345',
'paytm_mid' => 'YOUR_REAL_PAYTM_MID',
'paytm_env' => 'production',
```

Multiple Pixel IDs comma se likh sakte hain. Defaults mein real UPI/Pixel/MID nahi bhara gaya hai. Blank UPI par payment initiation disabled hai. setting.php public asset nahi hai; browser request ko 404 milta hai. Redis tokens aur admin password sirf Vercel Environment Variables mein rakhein.

## Vercel ke liye actual changes

- PHP URLs (`product.php?id=...`, `cart.php`, `/admin/index.php`, etc.) allowlisted router se execute hote hain. PHP files download nahi hoti.
- PHP source, settings aur data paths public response se blocked hain. Public output checker bhi PHP/JSON/ZIP/log files ko reject karta hai.
- Cart/address/admin/payment session external Redis mein hai. Session keys 24-hour expiry aur per-session lock use karti hain; separate function instances ke beech continuity test hui hai.
- Orders Redis hash mein immutable order ID se atomic/idempotent write hote hain; latest 2500 receipts retain hote hain.
- Admin catalogue/settings updates Redis mein persist hote hain; readonly deployed seed file ko write nahi kiya jata.
- Analytics Redis mein bounded 20000-event list use karti hai. Optional analytics failure payment receipt ko false-success nahi banata.
- UPI, Paytm MID aur Meta Pixel root setting.php se hi aate hain. Purane admin values unhe override nahi karte.
- CSV import 3 MB tak limited hai, taaki serverless request size ke andar rahe.
- `.htaccess` ki zarurat nahi: routing/static headers vercel.json mein hain.
- Existing hosting ki JSON runtime history automatically migrate nahi hoti. Supplied 40-product seed catalogue included hai. Existing live orders/admin catalogue data ko migrate karna alag step hoga.

## Payment verification limitation

Kitchan reference ka Paytm status-check approach preserve hai: server par MID + ORDERID + exact amount + successful provider transaction match hone par receipt/thank-you aata hai. Generic UPI deep link arbitrary VPA par Paytm gateway order automatically create nahi karta. Sirf MID bhar dena sufficient nahi; aapke merchant-linked order ko Paytm API recognise karna zaroori hai.

PhonePe native COLLECT Kitchan ki tarah untracked hai. App khulta hai lekin automatic verified thank-you nahi aata. App-return, “I've paid” aur timer ko payment proof nahi maana jata.

Real merchant payment/settlement test aur Vercel remote deployment is delivery mein nahi hua. Production payments enable karne se pehle apne merchant account ka small-value end-to-end test karein.

## Validation

- 68 PHP files syntax/lint passed on local PHP 8.3.
- `npm run build` project/public structure check passed.
- Local tests used two independent PHP servers with shared Redis-compatible REST + Lua fixture storage: storefront routing, static assets, protected source/config, cart/address sessions, payment initiation/verification, receipt/UTR, duplicate finalise, admin authentication, catalogue writes aur analytics persistence passed.
- Paytm transport was mocked; tests did not charge anyone. PHP 8.5 community runtime documentation/package was inspected, but the actual Vercel PHP bundle was not deployed remotely.

## Local development

PHP 8.3+ aur cURL extension install karein. Redis REST URL/token environment mein set karein. Root se:

```bash
php -S localhost:8000 -t public api/index.php
```

`vercel dev` ko PHP community runtime support nahi karta. `npm run build` is project's structure check hai, full remote Vercel function packaging ka replacement nahi.

## Official references

- https://github.com/vercel-community/php
- https://vercel.com/docs/functions/runtimes
- https://upstash.com/docs/redis/features/restapi
