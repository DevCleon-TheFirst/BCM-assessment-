# Question 3: Mobile Performance Optimization for WordPress Publishing

## Context
High-traffic publishing sites suffer on mobile primarily due to unoptimized media payloads, heavy third-party advertising scripts, and un-cached database queries.

---

## 1. Three High-Impact Improvements & What to Measure First

### Improvement 1: Hero Image Optimization & Responsive Delivery
* **Description**:
  Ensure featured hero images on mobile use responsive `<picture>` / `srcset` targeting the mobile viewport (e.g. 400px wide WebP/AVIF instead of a 2400px desktop JPEG), apply `fetchpriority="high"` to the hero image, and remove `loading="lazy"` from above-the-fold media.
* **What to Measure First**:
  - **Largest Contentful Paint (LCP)**: Target < 2.5s on mobile via WebPageTest or Chrome DevTools.
  - **Transferred Asset Size**: Total image payload size in kilobytes on initial mobile load.

---

### Improvement 2: Third-Party Ad & Tracking Script Orchestration
* **Description**:
  Publishing sites frequently have dozens of ad bidders, analytics scripts, and social embed widgets executing synchronously, choking low-power mobile CPU cores. Optimizations include:
  1. Using `<link rel="preconnect">` for key ad/CDN origins (`securepubads.g.doubleclick.net`).
  2. Deferring non-critical social embeds and marketing pixels until user scroll or `requestIdleCallback`.
  3. Configuring lazy-loading for ad slots with a smart viewport margin (e.g., fetch ad creative 200px before the user scrolls to it).
* **What to Measure First**:
  - **Total Blocking Time (TBT)** (target < 200ms) and **Interaction to Next Paint (INP)** on simulated mobile CPU (4x slowdown).
  - **Main Thread Work & Long Tasks**: Count and duration of JS tasks exceeding 50ms in the DevTools Performance panel.

---

### Improvement 3: Full-Page Edge Caching with Cache-Bypass for Authenticated Sessions
* **Description**:
  Offload article delivery to edge CDN servers with `stale-while-revalidate`. Public article pages are served instantly from the nearest edge POP without hitting WordPress PHP or MySQL.
* **What to Measure First**:
  - **Time to First Byte (TTFB)** on real mobile networks (target < 150ms from edge cache).
  - **Origin Server Load**: PHP-FPM process count and database query load under peak traffic.

---

## 2. In-Depth Focus: Third-Party Ad & Script Orchestration

### How to Check that It Helped (Performance Verification)
1. **Lab Testing (WebPageTest / Lighthouse Mobile)**:
   - Check that **TBT** drops substantially (e.g., from 1,400ms down to <250ms).
   - Verify that **Speed Index** and **First Contentful Paint (FCP)** improve.
2. **Field Data (Real User Monitoring / CrUX)**:
   - Monitor real-user **INP** and **LCP** percentiles in Google Search Console over a 7-day rollout.

---

### How to Verify Without Breaking Critical Systems

#### A. Ensuring Ads Remain Intact (Fill Rate & Viewability)
* **Diagnostic Tool**: Open the Google Ad Manager Console in the browser (`googletag.openConsole()` or `?googletag=console`).
* **Checks**:
  - Confirm all slots register and fire ad bids with valid ad targeting keys.
  - Verify that lazy loading doesn't delay slot rendering until the reader has already passed it; set render margin to fetch ads before viewport entry.
  - Compare 24-hour ad impressions, click-through rates (CTR), and Viewability metrics against historical averages in Google Ad Manager reporting.

#### B. Ensuring Analytics Remain Intact (Event Accuracy)
* **Diagnostic Tool**: Google Tag Manager Preview Mode & GA4 Realtime DebugView.
* **Checks**:
  - Confirm the initial `page_view` event fires reliably on DOMContentLoaded without being dropped.
  - Verify that custom user engagement triggers (scroll depth at 25%, 50%, 75%, and outbound affiliate link clicks) continue firing in chronological order.
  - Run side-by-side automated test sessions to confirm that total event counts match between staging and production.

#### C. Ensuring Signed-In Behaviour Remains Intact
* **Checks**:
  - Log in with subscriber and editor accounts.
  - Confirm the **WordPress Admin Bar** renders correctly and is not stripped or served to logged-out users.
  - Verify that user-specific features (comment forms pre-filled with the user's name/email, member-only content/paywall checks) function properly.
  - Confirm that REST API / AJAX nonces (`wp_rest` nonce) remain fresh and valid upon interaction.

---

## 3. AI Assistance & Verification
* **Where AI helped**: Outlining the verification criteria for Google Ad Manager and GA4 debugging.
* **How verified**: Cross-referenced against modern Core Web Vitals (INP/TBT) standards and Google Publisher Tag (GPT) lifecycle documentation.
