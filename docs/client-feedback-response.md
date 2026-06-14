# HtoEAU — Client feedback response

**Date:** June 2026  
**Theme version:** 1.8.6 (pending deploy)

---

## Message to client

Hi,

Thanks for the feedback — here’s how we’re handling each point.

### Customer account

**Downloads** — Agreed. You don’t sell digital products, so the Downloads tab has no purpose. We’re **removing it** from My Account.

**Delete account** — We’re adding a **“Delete my account”** section on **Account details**, with a confirmation step. This removes the login and personal details. **Order history is kept** for legal/accounting reasons (standard for e‑commerce).

### Promo codes

Promo codes weren’t applying because of a technical clash with our checkout layout (WooCommerce expected a specific field ID and was hiding the promo form). **This is fixed** — “Have a promo code?” → Apply should work again.

Please confirm:

- Which promo codes should be live (code name, % or fixed discount, expiry)?
- Any restrictions (first order only, minimum spend, one use per customer)?

We can create/test them in WooCommerce once you confirm.

### Social links

Updated as requested:

| Platform   | Link |
|------------|------|
| Instagram  | https://www.instagram.com/htoeau_official/ |
| LinkedIn   | https://www.linkedin.com/company/the-hydrogen-innovation-company |
| Facebook   | unchanged |

### Mollie — all payment options via API

The site **already uses the Mollie API** through the official **Mollie Payments for WooCommerce** plugin. The theme does **not** remove payment methods.

What often confuses people: **“Enabled in WooCommerce admin” ≠ “shown to every customer.”**

Mollie’s API only shows methods that are valid for that specific checkout:

- **Billing country** (e.g. UK vs Netherlands vs Belgium)
- **Currency** (GBP vs EUR)
- **Order amount**
- **What’s activated on your Mollie merchant account**

**Typical behaviour:**

| Customer      | Currency | What they usually see |
|---------------|----------|------------------------|
| UK            | GBP      | **Card** (+ **Apple Pay** in Safari if enabled) |
| Netherlands   | EUR      | **Card**, **iDEAL** |
| Belgium       | EUR      | **Card**, **Bancontact** |

**iDEAL and Bancontact are not meant for UK/GBP checkout** — they’re EU/EUR methods. Showing them to UK customers would cause payment failures (we saw this when we briefly forced all methods on).

**Apple Pay** is separate from the Card/iDEAL list — it appears as an express button when enabled in Mollie and the customer uses Safari.

**To get “all Mollie options” working as you expect, we need from you:**

1. **Mollie dashboard access** (or screenshots of **Settings → Payment methods**) — confirm iDEAL, Bancontact, bank transfer, and Apple Pay are **live**, not pending.
2. Confirmation the Mollie account supports **both GBP and EUR** (if you want UK and EU checkout in local currency).
3. A **test matrix** — who should see what?
   - UK + GBP: Card only, or also bank transfer?
   - NL + EUR: Card + iDEAL?
   - BE + EUR: Card + Bancontact?
4. Someone to test **Netherlands billing + EUR** and confirm iDEAL appears (if it doesn’t, it’s Mollie/account config, not the website theme).
5. **Apple Pay**: enable “Express on checkout” in Mollie; test on **iPhone/Mac Safari**.

We cannot safely show every method to every customer in every currency — Mollie will reject invalid combinations and customers get *“There was an error processing your order.”* The correct setup is **API-driven methods per country + currency**, which is what the plugin does when Mollie and Woo settings are aligned.

---

## Implementation summary (our side)

| Item | Status | Notes |
|------|--------|-------|
| Remove Downloads from My Account | Done | `inc/my-account.php` |
| Delete account on Account details | Done | Confirmation + nonce; orders retained |
| Fix promo code at checkout | Done | `#coupon_code` + show form after WC hide |
| Instagram `@htoeau_official` | Done | `inc/social-links.php` |
| LinkedIn company page | Done | Footer + Elementor newsletter widget |
| Mollie all methods | Config / account | Theme does not filter; Mollie API rules apply |

**Deploy:** not yet pushed — say **push** (staging) or **push prod** when ready.

---

## Mollie — technical note (internal)

- Theme only reorders **Card** first and provides an Apple Pay express slot (`inc/mollie-checkout-gateways.php`).
- Forcing all gateways visible (v1.8.2) caused order errors; reverted in v1.8.4+.
- Live test (htoeau.com): UK + GBP and NL + EUR both showed **Card only** — suggests Mollie account or per-gateway Woo settings, not theme.
- Multi-currency (v1.8.5): checkout currency follows display (geo/cookie) — EU EUR, UK GBP.

---

## Open questions for client

### Account

- [ ] OK to keep order history when account is deleted?

### Promo codes

- [ ] List of codes, discount type, expiry, restrictions

### Mollie

- [ ] Mollie dashboard access or payment-methods screenshots
- [ ] GBP + EUR both enabled on Mollie account?
- [ ] Desired payment matrix per country/currency
- [ ] Apple Pay express enabled in Mollie?
- [ ] Who will test NL/EUR (iDEAL) and BE/EUR (Bancontact)?
