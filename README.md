# Developer Test Submission

This repository contains the code solutions, documentation, and automated test checks for all ten questions.

All solutions were developed and tested locally under PHP 8.4 and Node.js 20.

---

## Setup & Running Checks

### Prerequisites
* PHP 8.1+
* Node.js 18+
* Google Chrome / Chromium (used for headless visual checks)

### Run All Checks
To run all verification suites across the questions at once:
```bash
./run-all-checks.sh
```

---

## Project Structure

```text
├── README.md                      # Primary documentation
├── run-all-checks.sh              # Test runner for all questions
│
├── question-01/                   # PHP WordPress Search & [ad] Replacer
│   ├── search-template.php        # Corrected template
│   ├── test-checks.php            # Standalone test suite
│   └── README.md                  # Error breakdown and notes
│
├── question-02/                   # Media Upload Failure Diagnosis
│   ├── diagnose-upload.php        # Diagnostic simulation script
│   └── README.md                  # Analysis of upload issues
│
├── question-03/                   # Mobile Performance Optimization
│   └── README.md                  # Improvements, metrics, and verification steps
│
├── question-04/                   # Responsive Layout (Desktop & Mobile)
│   ├── layout.html                # HTML markup
│   ├── style.css                  # CSS Grid stylesheet
│   ├── screenshot-desktop.png     # Desktop layout screenshot
│   ├── screenshot-mobile.png      # Mobile layout screenshot
│   └── README.md                  # Layout and accessibility notes
│
├── question-05/                   # HTML5 Conformance & Semantics
│   ├── index.html                 # Corrected HTML
│   ├── test.jpeg                  # Local test image
│   ├── validation-result.txt      # HTML validator output (0 errors)
│   └── README.md                  # Validity errors vs discoverability improvements
│
├── question-06/                   # Dynamic DOM Manipulation & Paragraph Toggling
│   ├── index.html                 # Base HTML
│   ├── app.js                     # Paragraph cloner, separator, and toggle logic
│   ├── test-checks.js             # Automated browser test suite
│   ├── evidence-question-06.png   # Verification screenshot
│   └── README.md                  # Verification notes
│
├── question-07/                   # Post-Release Discrepancy & Caching Triage
│   └── README.md                  # Triage checks, rollback criteria, editor update
│
├── question-08/                   # Editorial Workflow Efficiency
│   └── README.md                  # Proposed workflow improvements and short case study
│
├── question-09/                   # API Reliability & Newsletter Subscriptions
│   ├── subscribe.php              # Subscription handler function
│   ├── test-checks.php            # Local mock test suite
│   └── README.md                  # Timeout, rate limit, and logging notes
│
└── question-10/                   # Security & Endpoint Review
    ├── test-checks.php            # Negative test suite
    └── README.md                  # Code review and server-side checks
```

---

## Summary of Completed Work

### 1. PHP WordPress Search & Ad Placement
* **Errors identified in original**: Missing `new` keyword on `WP_Query` (fatal in PHP 8); `'page'` parameter instead of `'paged'`; unescaped regex `#[ad]#` acting as a character class matching any `a` or `d`; broken callback parameter and direct `echo` inside callback; double title output; undefined variables `$pages` and unverified `$_GET` access; missing `wp_reset_postdata()`.
* **Fixes applied**: Strictly sanitized input parameters, used `'paged'` for pagination, derived total pages from `$q->max_num_pages`, escaped the ad placeholder regex (`/\[ad\]/`), returned sequential ad divs, and restored the global post state.
* **Evidence of checks**: Tested locally with `php question-01/test-checks.php` covering missing parameters, page 2, zero search results, and multiple ad markers with word boundary safety.

### 2. WordPress Admin Media Upload Triage
* **Likely causes**:
  1. *User Capabilities*: Editor account missing `upload_files` capability or assigned an incorrect role. Checked first via WP Admin or WP-CLI (`wp user get`).
  2. *File Asset Constraints*: File exceeds PHP upload limits (`upload_max_filesize`, `memory_limit`) or has an unsupported format (e.g., SVG, HEIC). Checked second by testing the exact file asset.
  3. *Client Environment*: Browser adblocker blocking REST API endpoints or an expired session nonce. Checked third via an Incognito window and browser console.
* **Evidence of checks**: Simulated locally with `php question-02/diagnose-upload.php`.

### 3. Mobile Performance Optimization
* **Three improvements**:
  1. *Hero Image Optimization*: Responsive WebP/AVIF formats via `srcset`, adding `fetchpriority="high"`, and removing `loading="lazy"` from above-the-fold media. Measured via LCP and image transfer size.
  2. *Third-Party Script Orchestration*: Preconnecting to critical ad origins, deferring non-critical embeds via `IntersectionObserver`, and configuring lazy loading margins on ad slots. Measured via TBT and INP.
  3. *Full-Page Edge Caching*: CDN caching for anonymous readers with cookie bypass for logged-in users. Measured via TTFB.
* **Verification**: Documented verification protocols using Google Ad Manager console (`googletag.openConsole()`) for ad delivery, GA4 DebugView for event tracking, and authenticated editor sessions for signed-in behavior.

### 4. Responsive CSS Grid Layout
* **Implementation**: Built with a single HTML file and single CSS file without JavaScript or duplicated markup. Breakpoint set at `768px`.
  - *Desktop*: 3-column grid where Section A spans two columns, Section B occupies the third column, followed by rows C-D-E and F-G-H.
  - *Mobile*: Single column with order A, D, B, C, G. Sections E, F, and H are hidden with `display: none`.
* **Heading protection**: Applied `min-width: 0` and `overflow-wrap: break-word` to prevent unbroken strings from expanding the grid container.
* **Accessibility notes**: Documented the disconnect between visual order and DOM source order (WCAG 2.4.3), explaining that keyboard and screen reader focus follows the underlying HTML sequence.
* **Visual evidence**: Rendered screenshots saved in `screenshot-desktop.png` and `screenshot-mobile.png`.

### 5. HTML5 Validation & Semantics
* **Validity errors corrected**: Relocated `<body>` inside `<html>`, added `<!DOCTYPE html>`, replaced invalid `name` on `<abbr>` with `title`, corrected `with` to `width`, replaced `height=auto` with integer dimensions, added missing `alt` attribute, quoted attribute values, and corrected nested tag closure `<code><b>...</b></code>`.
* **Search discoverability**: Added semantic landmarks (`<main>`, `<article>`), meta description and canonical links, and `<time>` element with Open Graph tags.
* **Validation check**: Verified with `@html-validate/cli` (0 errors, 0 warnings saved in `validation-result.txt`). Local image provided in `test.jpeg`.

### 6. JavaScript DOM Manipulation & Toggling
* **Implementation**:
  - Clones the three original paragraphs in randomized order on each click of the original button.
  - Inserts `<hr>` separators after every pair only when another paragraph follows (`P1, P2, HR, P3, P4, HR, P5`), applied on load and on click.
  - Dynamically creates a second button via JavaScript only to toggle paragraphs containing images.
  - Persists the hidden state so newly added image paragraphs remain hidden if the toggle is active.
  - Counts all paragraphs (including hidden ones) when calculating separator placement.
* **Evidence of checks**: Verified through automated headless browser tests in `question-06/test-checks.js` and captured in `evidence-question-06.png`.

### 7. Post-Release Discrepancy & Caching Triage
* **Initial checks**:
  1. Compare origin response headers directly with CDN response headers (`CF-Cache-Status`, `Age`).
  2. Verify git commit consistency across all load-balanced server nodes.
  3. Inspect reader session state and browser caching directives.
* **Rollback criteria**: Roll back only if application code or database migrations are broken; avoid rolling back for edge cache propagation delays.
* **Editor communication**: Provided a concise two-sentence status update explaining the cache purge and live recovery.

### 8. Editorial Workflow Efficiency
* **Discovery**: Observe the editorial publishing process directly to note required fields, formats, and frequent error points.
* **1-Day MVP**: Post editor quick-copy helper to copy sanitized headlines and canonical permalinks with one click.
* **Simpler alternative**: Webhook / RSS feed integration to automatically populate draft cards in external publishing tools with zero custom code.
* **Case study**: 87-word description of a previous automated editorial sync implementation and key lessons learned.

### 9. API Reliability & Newsletter Subscriptions
* **Implementation**: Implemented an idempotent subscription handler using deterministic subscriber ID hashing (`PUT /lists/{id}/members/{md5(email)}`).
* **Error handling**: Recovers from timeouts by verifying status prior to retry; reads `Retry-After` on HTTP 429 rate limits; immediately rejects invalid emails without retrying.
* **Logging privacy**: Logs transaction IDs and hashed identifiers while strictly omitting plaintext emails and authorization tokens.
* **Evidence of checks**: Validated locally with mock server tests in `question-09/test-checks.php`.

### 10. Security & Endpoint Code Review
* **Review of claim**: Addressed the flaw in relying on UI button visibility for security (CWE-602), noting that client-side controls do not protect against direct HTTP calls or CSRF.
* **Server-side check order**: Authentication -> CSRF Nonce -> Input sanitization & post existence -> Object-level capability (`edit_post`, `$post_id`) -> Status whitelist -> Publishing capability.
* **Negative tests**: Verified rejections for unauthenticated direct requests (401) and unauthorized role editing (403).
* **Developer feedback**: Provided constructive code review comments and identified required release blockers. Verified in `question-10/test-checks.php`.

---

## AI Assistance & Verification Disclosure
* **Where AI helped**: Used for drafting boilerplate test doubles and formatting diagnostic check tables.
* **How verified**: All code was run and tested locally using PHP 8.4 and Node.js 20. HTML conformance was verified with `@html-validate/cli`, CSS layouts were checked via headless browser screenshots, and API/security functions were verified with automated assertion scripts.
