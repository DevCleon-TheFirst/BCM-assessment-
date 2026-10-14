# Question 6: Dynamic DOM Cloning, Separator Logic & Image Toggle

## 1. Requirements Implementation Overview
* **Randomized Clones on Click**:
  - Deep-cloned templates of the original 3 paragraphs are shuffled using the Fisher-Yates algorithm and appended to the content container on each click of the original `#hit-me-btn` ("Hit me").
* **Separator Invariant**:
  - Inserts an `<hr class="pair-separator">` after every pair (2nd, 4th, 6th, etc.) **only when another paragraph follows** (`P1, P2, HR, P3, P4, HR, P5`).
  - Mathematically: for $N$ total paragraphs, exactly $\lfloor (N - 1) / 2 \rfloor$ separators are rendered.
  - Executed on initial DOM load and recalculated after every click.
* **Second Button via JavaScript ONLY**:
  - Dynamically creates `#toggle-img-btn` ("Hide Image Paragraphs" / "Show Image Paragraphs") and appends it to `#button-container`.
  - Toggles visibility of all paragraphs containing an `<img>` tag via `.hidden-image-p` (`display: none !important`).
* **State Persistence & Hidden Counting**:
  - If images are toggled hidden, any newly appended paragraphs containing images are immediately set to hidden.
  - As required: **All paragraphs are counted for separator placement, including hidden ones**.

---

## 2. Evidence of Checks & Test Results

Run with `node question-06/test-checks.js` using headless Chromium:

```text
========================================================
STARTING QUESTION 6 AUTOMATED VERIFICATION SUITE
========================================================

[Check 1: Initial Page State]
 - Paragraph count: 3 (Expected: 3)
 - HR count:        1 (Expected: 1, placed as: P1, P2, HR, P3)
 - JS Toggle button created: true (Expected: true)
 [PASS] Initial load verified.

[Check 2: Toggling Image Visibility BEFORE Adding Paragraphs]
 - Hidden image paragraphs: 1 (Expected: 1)
 - HR count after toggle:   1 (Expected: 1 - hidden paragraphs must still be counted)
 - After toggle back, hidden count: 0 (Expected: 0)
 [PASS] Toggle before adding paragraphs verified.

[Check 3: Adding Paragraphs (Click 1)]
 - Paragraph count: 6 (Expected: 6)
 - HR count:        2 (Expected: 2 -> after P2 and P4)
 [PASS] Adding paragraphs verified.

[Check 4: Toggling Image Visibility AFTER Adding Paragraphs]
 - Paragraphs containing images: 2
 - Hidden image paragraphs:       2 (Expected: 2)
 [PASS] Toggle after adding paragraphs verified.

[Check 5: Adding Paragraphs WHILE Images are Hidden]
 - Paragraph count:       9 (Expected: 9)
 - HR count:              4 (Expected: 4 -> floor((9-1)/2))
 - Total image paragraphs:3 (Expected: 3)
 - Hidden image paragraphs:3 (Expected: 3 - newly added must stay hidden!)
 [PASS] Preservation of hidden state on new paragraphs verified.

[Check 6: Restoring Visibility]
 - Hidden image count after restore: 0 (Expected: 0)
 [PASS] Visibility restore verified.

[Check 7: Repeated Clicks Stress Test]
 - Final Paragraphs after 6 clicks: 21 (Expected: 21)
 - Final HR count:                  10 (Expected: 10)
 [PASS] Repeated clicks stress test verified.

Saved visual evidence to evidence-question-06.png
ALL QUESTION 6 CHECKS PASSED.
```

Visual evidence file: `question-06/evidence-question-06.png`.

---

## 3. AI Assistance & Verification
* **Where AI helped**: Outlining the headless browser test suite structure in Puppeteer.
* **How verified**: Executed via real Chrome instance testing state transitions, repeated clicks, and DOM invariants.
