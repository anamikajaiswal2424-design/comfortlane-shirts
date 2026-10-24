<?php

function policyDocuments(array $business): array
{
    $brand = $business['brand_name'];
    $legalName = $business['legal_name'];
    $email = businessPublicValue($business, 'support_email');
    $phone = businessPublicValue($business, 'support_phone');
    $address = businessPublicValue($business, 'business_address');
    $returnDays = (int) $business['return_window_days'];
    $delivery = $business['delivery_timeline'];
    $refund = $business['refund_timeline'];

    return [
        'about' => [
            'title' => 'About Us',
            'description' => 'Learn who operates ComfyNest, what the store offers, and how to contact the business.',
            'intro' => $brand . ' is an India-focused online home-comfort store offering seating, storage, bedroom and everyday lifestyle products.',
            'sections' => [
                ['title' => 'Who we are', 'paragraphs' => [$brand . ' is operated by ' . $legalName . '. Our aim is to make practical home-comfort products easier to discover, compare and order online. We present product names, prices, available images, delivery information and purchase terms before checkout.']],
                ['title' => 'How we work', 'bullets' => ['Products are sourced from the catalogue supplied by the store operator.', 'Prices and availability may change, but the amount shown at checkout is the amount applicable to that order.', 'Orders are quality-checked and packed before dispatch where operationally possible.', 'Customer support is available during ' . $business['support_hours'] . '.']],
                ['title' => 'Business identity', 'bullets' => ['Legal name: ' . $legalName, 'Country of operation: ' . $business['country'], 'Support email: ' . $email, 'Support phone: ' . $phone, 'Business / return address: ' . $address, $business['gstin'] ? 'GSTIN: ' . $business['gstin'] : 'GSTIN: Not provided']],
                ['title' => 'No platform affiliation', 'paragraphs' => [$brand . ' is an independent store. Unless expressly stated, we are not affiliated with, endorsed by, or sponsored by Google or any marketplace whose product information may have been supplied to the catalogue.']],
            ],
        ],
        'contact' => [
            'title' => 'Contact Us',
            'description' => 'Contact ComfyNest for order help, returns, refunds, payments, product information, or privacy requests.',
            'intro' => 'We want it to be easy to reach a real support channel before and after a purchase.',
            'sections' => [
                ['title' => 'Customer support', 'bullets' => ['Email: ' . $email, 'Phone: ' . $phone, 'Support hours: ' . $business['support_hours'], 'Typical response time: within 1–2 working days']],
                ['title' => 'Business and return address', 'paragraphs' => [$address, 'Please contact support before sending a return. Unauthorised or cash-on-delivery return parcels may not be accepted.']],
                ['title' => 'What to include', 'bullets' => ['Your order ID', 'The mobile number used for the order', 'A short description of the issue', 'Clear photos or video if an item arrived damaged, defective or incorrect']],
                ['title' => 'Privacy requests', 'paragraphs' => ['For access, correction or deletion requests concerning your personal information, contact the support email above with “Privacy Request” in the subject line.']],
            ],
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'description' => 'How ComfyNest collects, uses, protects, retains, and shares customer information.',
            'intro' => 'This Privacy Policy explains how ' . $legalName . ' collects and uses information when you browse, place an order, contact support or interact with advertising and analytics tools.',
            'sections' => [
                ['title' => 'Information we collect', 'bullets' => ['Contact and delivery information such as name, mobile number, address, city, state and PIN code.', 'Order details such as products, quantities, prices, selected payment method, order ID, transaction reference and order status.', 'Support communications, return reasons and any images you send to resolve an issue.', 'Privacy-friendly site metrics such as page path, time, device category, referring domain and an anonymous session identifier. The built-in dashboard does not store raw visitor IP addresses.', 'After analytics consent, approximate city, state, country and rounded map coordinates may be stored for aggregate and live visitor reporting. These are city-level estimates and are not suitable for identifying an exact address.']],
                ['title' => 'How information is used', 'bullets' => ['To process, deliver and support orders.', 'To prevent fraud, protect the site and comply with legal obligations.', 'To improve products, navigation and customer service.', 'To measure advertising performance or personalise marketing only when the relevant tool is enabled and consent is obtained where required.', 'To send service messages about an order; promotional messages require an appropriate legal basis and opt-out method.']],
                ['title' => 'Cookies, location and advertising tools', 'paragraphs' => ['Essential session cookies keep cart, checkout security and administrator sessions working. The built-in visit counter uses an anonymous hash of the current PHP session rather than a raw IP address. Configured Google Tag, Google Ads and Meta Pixel scripts load only after the visitor selects “Allow analytics” in the privacy choices interface.', 'If location reporting is enabled, accepting analytics allows the visitor’s network address to be processed by ipwhois.io to estimate city, state, country and map coordinates. ComfyNest stores only the returned approximate location with an anonymous session reference, rounds coordinates before storage, caches the result for the browser session, and does not store the raw IP. If that free lookup is unavailable and Google Maps is configured, the browser may offer its separate location permission as an optional fallback.', 'IP geolocation provider privacy information: https://ipwhois.io/privacy. Google advertising technology information: https://policies.google.com/technologies/ads and ad controls: https://adssettings.google.com/. Meta privacy information: https://www.facebook.com/privacy/policy/.']],
                ['title' => 'Sharing and service providers', 'paragraphs' => ['Information may be shared only as needed with payment providers, delivery partners, hosting/security providers, customer-support providers, analytics/advertising providers, professional advisers, or authorities where required by law. We do not sell personal information as a standalone product.']],
                ['title' => 'Payments and security', 'paragraphs' => ['Payment credentials should be processed by the selected payment provider over HTTPS. ' . $brand . ' does not intentionally store complete card, bank-account or UPI PIN details. When the QR fallback is used, the QR-rendering service receives the payment URI containing the store UPI address, amount and order reference, but not the customer’s UPI PIN. No internet transmission is risk-free; reasonable administrative and technical safeguards should be maintained.']],
                ['title' => 'Retention and your choices', 'paragraphs' => ['Order and support records are retained only for operational, accounting, fraud-prevention and legal needs. You may request access, correction or deletion where applicable by contacting ' . $email . '. You can also control cookies in your browser and use the opt-out controls offered by advertising providers.']],
                ['title' => 'Children and updates', 'paragraphs' => ['The store is not directed to children under 13 and does not knowingly collect their personal information. We may update this policy when our practices or legal obligations change; the effective date appears on this page.']],
            ],
        ],
        'terms' => [
            'title' => 'Terms & Conditions',
            'description' => 'The terms governing use of ComfyNest, product orders, pricing, payments, delivery, cancellations, and liability.',
            'intro' => 'By using this website or placing an order, you agree to these terms together with the Privacy, Shipping and Returns policies.',
            'sections' => [
                ['title' => 'Store use and eligibility', 'paragraphs' => ['You must provide accurate information and be legally capable of entering a purchase contract. You may not misuse the website, interfere with security, submit unlawful content or attempt unauthorised access.']],
                ['title' => 'Product information and availability', 'paragraphs' => ['We aim to present accurate names, images, colours, dimensions, features and prices. Screen colours and packaging may vary. Orders are subject to availability, serviceability and verification. We may correct obvious errors or cancel unavailable items and issue any applicable refund.']],
                ['title' => 'Pricing and order acceptance', 'paragraphs' => ['Prices are shown in Indian Rupees and include taxes unless stated otherwise. Delivery or other authorised charges are shown before order placement. An order acknowledgement does not guarantee acceptance; acceptance occurs when the order is confirmed for fulfilment.']],
                ['title' => 'Payments', 'paragraphs' => ['Available methods are shown at checkout. Online payment may be handled by third-party processors. Cash on delivery may be unavailable for certain PIN codes, order values or products. Do not share OTPs, UPI PINs or card credentials with anyone claiming to represent ' . $brand . '.']],
                ['title' => 'Delivery, cancellation and returns', 'paragraphs' => ['Shipping estimates are not guarantees. Cancellation is normally available before dispatch. After dispatch, the Returns & Refunds Policy applies. Product eligibility, return shipping, inspection and refund timelines are described on that policy page.']],
                ['title' => 'Liability and governing law', 'paragraphs' => ['To the extent permitted by law, liability is limited to direct losses connected to the affected order and does not exclude rights that cannot legally be excluded. These terms are governed by applicable laws of India, and disputes are subject to the competent courts for the operator’s registered location.']],
                ['title' => 'Changes and contact', 'paragraphs' => ['Policies may be updated prospectively. Material changes will be reflected by a new effective date. Questions may be sent to ' . $email . '.']],
            ],
        ],
        'shipping' => [
            'title' => 'Shipping & Delivery Policy',
            'description' => 'ComfyNest delivery areas, processing, timelines, fees, tracking, delays, and address responsibilities.',
            'intro' => 'This policy explains how orders are prepared and delivered within serviceable locations in India.',
            'sections' => [
                ['title' => 'Processing and delivery estimate', 'bullets' => ['Normal order processing: 1–2 working days after confirmation.', 'Estimated delivery: ' . $delivery . '.', 'Working days exclude Sundays and public or local holidays.', 'Made-to-order, oversized or remote-location deliveries may take longer; any known difference should be shown on the product or checkout page.']],
                ['title' => 'Shipping charges', 'paragraphs' => ['The applicable delivery charge is displayed before the order is placed. If checkout shows “Free delivery”, no standard shipping fee is charged for that order. Extra charges must not be collected after checkout unless the customer has expressly agreed to a documented change.']],
                ['title' => 'Tracking and delivery attempts', 'paragraphs' => ['Tracking details are shared when available. The delivery partner may call the provided mobile number. Failed attempts caused by an incorrect address, unreachable recipient or refusal may lead to return-to-origin and eligible deductions.']],
                ['title' => 'Delays, damage and missing items', 'paragraphs' => ['Weather, transport disruption, high-volume periods, service restrictions or force-majeure events can delay delivery. If packaging is visibly damaged, record photos/video and contact support promptly. Report missing, incorrect or damaged items within 48 hours of delivery so the claim can be assessed.']],
                ['title' => 'Address changes', 'paragraphs' => ['Contact ' . $email . ' as soon as possible. An address cannot always be changed after dispatch. Never send an OTP or payment credential to a courier or support agent.']],
            ],
        ],
        'returns' => [
            'title' => 'Returns, Refunds & Cancellation Policy',
            'description' => 'Clear ComfyNest return eligibility, return window, method, fees, refund timeline, and cancellation rules.',
            'intro' => 'Eligible products may be requested for return within ' . $returnDays . ' days after recorded delivery, including eligible change-of-mind cases.',
            'sections' => [
                ['title' => 'Eligible returns', 'bullets' => ['Damaged, defective, incomplete or incorrect products.', 'Products materially different from the published description.', 'Change-of-mind returns when the product is unused, unwashed, unassembled where applicable, and returned with original packaging, accessories, manuals and tags.']],
                ['title' => 'Items that may not be returnable', 'bullets' => ['Used, altered, washed, stained or customer-damaged items.', 'Products missing original parts, packaging, serial labels or accessories.', 'Customised items and sealed hygiene-sensitive goods after opening, unless defective or required otherwise by law.', 'Items identified as final sale before purchase, except where defective, damaged, incorrect or where non-returnability is not permitted by law.']],
                ['title' => 'How to request a return', 'paragraphs' => ['Contact ' . $email . ' or ' . $phone . ' within the return window and provide the order ID, reason, and supporting images where relevant. Wait for return instructions before shipping the item. Approved pickup availability depends on the delivery location.']],
                ['title' => 'Return fees and inspection', 'paragraphs' => ['For confirmed damage, defect, incorrect item or fulfilment error, standard return shipping is borne by the store. For an eligible change-of-mind return, return transport charges may be deducted from the refund and will be disclosed before collection. Returned products are inspected before approval.']],
                ['title' => 'Refund timing and method', 'paragraphs' => ['Approved refunds are initiated to the original payment method, or another lawful method agreed with the customer, within ' . $refund . '. Banks and payment providers may need additional processing time. Cash-on-delivery refunds require verified bank or UPI details collected through a secure support process.']],
                ['title' => 'Cancellations and replacements', 'paragraphs' => ['An order may be cancelled before dispatch by contacting support. Once dispatched, use the return process. Replacement may be offered for damaged, defective or incorrect items when stock is available; otherwise an eligible refund is provided.']],
            ],
        ],
        'payments' => [
            'title' => 'Payment & Pricing Policy',
            'description' => 'ComfyNest accepted payment methods, pricing, taxes, payment security, COD, refunds, and failed transactions.',
            'intro' => 'All payable amounts and available payment methods are shown before you place an order.',
            'sections' => [
                ['title' => 'Prices and charges', 'paragraphs' => ['Prices are displayed in Indian Rupees. Taxes are included unless a product or invoice states otherwise. Product discounts and delivery charges shown at checkout apply only to that order and availability period. We do not add undisclosed mandatory charges after order placement.']],
                ['title' => 'Payment methods', 'bullets' => ['UPI app intents or QR payment when the store operator has configured a business UPI address. The selected app displays the payee, amount and reference for confirmation before the customer enters a UPI PIN.', 'Cash on delivery where serviceable and permitted for the order.', 'Any additional method displayed by the secure payment provider at checkout.']],
                ['title' => 'Security', 'paragraphs' => ['Never share card PINs, CVV, OTPs or UPI PINs with ' . $brand . ' staff, couriers or callers. Online credentials should be entered only inside the selected UPI/payment-provider app or its secure HTTPS interface. Opening an app or displaying a QR does not by itself verify settlement; final payment status should be confirmed through the authorised provider before fulfilment.']],
                ['title' => 'Failed or duplicate payments', 'paragraphs' => ['If money is debited but the order is not confirmed, contact support with the transaction reference. Banks commonly reverse failed payments automatically; timing depends on the provider. Verified duplicate collections are refunded after reconciliation.']],
                ['title' => 'Refunds and COD', 'paragraphs' => ['Refund eligibility follows the Returns & Refunds Policy. Cash-on-delivery availability can be restricted by location, order value, product type, fraud checks or prior delivery history.']],
            ],
        ],
        'cookies' => [
            'title' => 'Cookie Policy',
            'description' => 'How ComfyNest uses essential, analytics and advertising cookies and how visitors can control them.',
            'intro' => 'Cookies and similar storage can help the store remember sessions, measure performance and—when enabled with appropriate consent—support advertising.',
            'sections' => [
                ['title' => 'Essential storage', 'paragraphs' => ['Session cookies are used for cart, checkout security, CSRF protection and administrator login. Blocking these may prevent key features from working.']],
                ['title' => 'Analytics, advertising and location', 'paragraphs' => ['The built-in dashboard counts public page views and anonymous sessions without saving raw IP addresses. If the operator configures Google Tag, Google Ads or Meta Pixel, those third-party scripts load only after “Allow analytics” is selected. Standard ecommerce events may describe a viewed product, cart addition, checkout start or completed purchase using catalogue product IDs, quantities, currency and value. Completed-purchase events can also include an order ID and transaction reference for reporting and deduplication.', 'When location analytics is enabled, “Allow analytics” also permits an ipwhois.io lookup that returns an approximate city, state, country and map position. The result is reused for the browser session to reduce third-party calls. ComfyNest stores rounded coordinates and an anonymous session reference, not the raw IP. Google Maps/browser location may be used only as an optional fallback when configured. Shopping remains available if analytics is declined.']],
                ['title' => 'Your choices', 'paragraphs' => ['You can select “Only necessary” on the consent notice, reopen “Privacy choices” from the site footer, or clear cookies/local storage through browser settings. Location access can also be withdrawn in browser site permissions. Advertising preferences can be managed at https://adssettings.google.com/.']],
                ['title' => 'Policy updates', 'paragraphs' => ['This page will be updated when cookie categories, providers or purposes materially change. Questions may be sent to ' . $email . '.']],
            ],
        ],
        'disclaimer' => [
            'title' => 'Disclaimer',
            'description' => 'Important limitations concerning ComfyNest product images, reviews, availability, warranties, links and affiliations.',
            'intro' => 'This page clarifies how catalogue information and demonstration content should be interpreted.',
            'sections' => [
                ['title' => 'Product presentation', 'paragraphs' => ['Product images are supplied through the store catalogue and may include manufacturer or supplier imagery. Colours can vary by display. Dimensions, materials, compatibility and care instructions should be checked before purchase.']],
                ['title' => 'Demo ratings and reviews', 'paragraphs' => ['Ratings and review cards currently marked “Demo Feedback” are deterministic design previews, not verified customer endorsements. They must not be used in advertising as evidence of actual customer experience. Before commercial launch, replace demo feedback with genuine, moderated reviews or remove it.']],
                ['title' => 'Availability and results', 'paragraphs' => ['Offers, inventory and delivery estimates can change. We do not promise outcomes beyond the product description and applicable warranty or consumer rights.']],
                ['title' => 'External services and links', 'paragraphs' => ['Third-party payment, delivery, analytics, advertising or linked services operate under their own terms and privacy notices. A link or technical integration does not imply endorsement.']],
                ['title' => 'Google relationship', 'paragraphs' => [$brand . ' is not Google, does not claim endorsement by Google, and does not guarantee Google Ads or Merchant Center approval. Advertising approval is determined by Google under its current policies.']],
            ],
        ],
    ];
}
