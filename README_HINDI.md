# Simple PHP on Vercel — no Redis

UPSTASH_REDIS_REST_URL aur UPSTASH_REDIS_REST_TOKEN ki zarurat nahi.
UI, Add to Cart, Buy Now, image slider aur existing payment flow preserved.

## Deploy
ZIP extract karke saari root files GitHub repository mein upload karein.
Vercel Framework: Other; Build command: npm run build; Output: public.
Root setting.php mein apna upi_id, merchant_name, pixel_id aur verification ke liye real paytm_mid set karein. Redeploy karein.
Admin login ke liye COMFYNEST_ADMIN_USERNAME aur COMFYNEST_ADMIN_PASSWORD env variables set karein. Inke bina admin login disabled hai; storefront ko inki zarurat nahi.

## Bahut zaroori limitation
Yeh simple PHP ka instance-local temporary file/session version hai. Vercel /tmp permanent ya shared nahi hai. Cold start, deployment, scaling ya doosre instance par request se cart, address, payment session, admin edits aur order history gayab ho sakti hai. Bank mein payment receive ho sakti hai lekin session missing hone se Thank You/receipt complete na ho. Repeat payment mat karein. Real customer payments ke liye persistent storage wala version ya GoDaddy/Hostinger PHP hosting use karein.
No Redis dependency does NOT mean durable storage. Yeh limitation code se permanently fix nahi hoti bina shared backend ke.

UPI/Pixel root setting.php se hi aate hain. Payment browser ke kehne par successful mark nahi hoti; existing server verification remains. Native PhonePe untracked flow automatically success confirm nahi karta.

## Meta Pixel events
Consent accept hone par ordinary storefront pages: PageView only. Verified thankyou/success route: Purchase only (no PageView), with actual order value, INR, transaction/event ID. Product view, add-to-cart aur checkout se Meta commerce events nahi bheje jaate. Refresh par browser localStorage duplicate Purchase ko rokta hai; browser storage clear/block hone par dedup guarantee nahi. Existing consent choice respected. Pixel setting.php se aata hai. Meta Events Manager ke auto-events ya external GTM tags alag se events bhej sakte hain; un settings ko bhi review karein.
