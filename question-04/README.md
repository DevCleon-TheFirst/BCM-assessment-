# Question 4: Responsive Editorial Grid Layout (Desktop & Mobile)

## 1. Specifications & Implementation
* **Single HTML & Single CSS File**: `layout.html` and `style.css`.
* **Zero JavaScript & Zero DOM Duplication**: Pure CSS Grid implementation with `grid-template-areas`.
* **Chosen Breakpoint**: `768px` (standard tablet/mobile breakpoint).
* **Desktop Layout (>= 768px)**:
  - 3-column CSS Grid.
  - Row 1: Section A spans 2 columns (`area-a area-a`), Section B in column 3 (`area-b`).
  - Row 2: Sections C, D, E (`area-c area-d area-e`).
  - Row 3: Sections F, G, H (`area-f area-g area-h`).
* **Mobile Layout (< 768px)**:
  - Single-column CSS Grid with custom vertical re-ordering:
    `A -> D -> B -> C -> G`
  - Sections **E, F, and H** are completely removed from the flow via `display: none;`.

---

## 2. Keyboard Navigation & Screen Reader Impact (Accessibility Analysis)

The brief asks: *"briefly explain how your HTML order affects keyboard navigation and screen readers."*

### Key Principle: DOM Order vs Visual Order Disconnect (WCAG 2.1 SC 1.3.2 & 2.4.3)
* **DOM Source Sequence**: The markup is authored in natural reading order: `A -> B -> C -> D -> E -> F -> G -> H`.
* **Desktop Navigation**:
  - Focus order (`Tab` key) and screen reader narration follow the visual grid row by row:
    `A (links) -> B (links) -> C -> D -> E -> F -> G -> H`.
  - Visual focus indicators match the physical reading flow on desktop.
* **Mobile Navigation Discrepancy**:
  - On mobile screens, CSS Grid rearranges the visual display to `A -> D -> B -> C -> G`.
  - However, assistive technologies (screen readers like NVDA/VoiceOver) and sequential keyboard navigation (`Tab` index) strictly follow the **DOM source order**, NOT the CSS `grid-template-areas` or `order` properties.
  - As a result, a keyboard user tabbing through links on mobile will experience a visual jump:
    Focus moves from **A** directly to **B** (skipping D visually), then to **C**, then jumps upwards back to **D**, before jumping down to **G**.
  - **Engineering Tradeoff & Mitigation**:
    In production editorial code, if Section D is primary editorial content that must be consumed before B and C on all devices, the DOM order should be authored as `A -> D -> B -> C...` with desktop CSS placing D into Row 2 Column 2. CSS should not be used to wildly reorder critical reading content because it creates disorientation for low-vision and switch-device users.

---

## 3. Long Heading Resilience
Grid items in CSS have a default `min-width: auto`. If an article title contains an unbroken string (e.g. `UnbrokenTechnicalInfrastructureStringVerification`), the grid column will blow out and force horizontal viewport scrolling.
* **Solution Applied**:
  ```css
  .section-box {
    min-width: 0; /* Allows grid tracks to compress below content width */
  }
  .section-box h2 {
    overflow-wrap: break-word;
    word-break: break-word;
    hyphens: auto;
  }
  ```
  Both desktop and mobile screenshots confirm the long heading wraps neatly inside card D without breaking neighboring columns.

---

## 4. Visual Evidence (Screenshots)
* Desktop layout: `screenshot-desktop.png` (Row 1: A spans 2 cols, B; Row 2: C, D, E; Row 3: F, G, H).
* Mobile layout: `screenshot-mobile.png` (Single column: A -> D -> B -> C -> G; E, F, H hidden).

---

## 5. AI Assistance & Verification
* **Where AI helped**: Suggested testing with headless Chrome screenshot commands.
* **How verified**: Generated headless Chrome screenshots at 1200px and 375px viewports, inspected visual layout, verified word wrap and hidden elements.
