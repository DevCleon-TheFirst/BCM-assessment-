/**
 * Automated Verification Suite for Question 6
 * Runs against real Chromium instance via puppeteer-core.
 */

const puppeteer = require('puppeteer-core');
const path = require('path');

const CHROME_PATH = '/usr/bin/google-chrome-stable';
const HTML_URL = `file://${path.resolve(__dirname, 'index.html')}`;

function assert(condition, message) {
  if (!condition) {
    throw new Error(`[ASSERTION FAILED] ${message}`);
  }
}

async function runChecks() {
  console.log('========================================================');
  console.log('STARTING QUESTION 6 AUTOMATED VERIFICATION SUITE');
  console.log('========================================================\n');

  const browser = await puppeteer.launch({
    executablePath: CHROME_PATH,
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const page = await browser.newPage();
  await page.goto(HTML_URL, { waitUntil: 'domcontentloaded' });

  // ------------------------------------------------------------------------
  // Step 1: Initial Load Verification
  // ------------------------------------------------------------------------
  console.log('[Check 1: Initial Page State]');
  const initialPCount = await page.$$eval('#article-content > p', (els) => els.length);
  const initialHRCount = await page.$$eval('#article-content > hr.pair-separator', (els) => els.length);
  const toggleBtnExists = await page.$('#toggle-img-btn') !== null;

  console.log(` - Paragraph count: ${initialPCount} (Expected: 3)`);
  console.log(` - HR count:        ${initialHRCount} (Expected: 1, placed as: P1, P2, HR, P3)`);
  console.log(` - JS Toggle button created: ${toggleBtnExists} (Expected: true)`);

  assert(initialPCount === 3, 'Initial paragraph count must be 3');
  assert(initialHRCount === 1, 'Initial HR count must be 1');
  assert(toggleBtnExists, 'Dynamic toggle button must exist in DOM');
  console.log(' [PASS] Initial load verified.\n');

  // ------------------------------------------------------------------------
  // Step 2: Toggle Images BEFORE adding paragraphs
  // ------------------------------------------------------------------------
  console.log('[Check 2: Toggling Image Visibility BEFORE Adding Paragraphs]');
  await page.click('#toggle-img-btn');

  let hiddenImageCount = await page.$$eval('p.hidden-image-p', (els) => els.length);
  let hrCountAfterToggle = await page.$$eval('#article-content > hr.pair-separator', (els) => els.length);

  console.log(` - Hidden image paragraphs: ${hiddenImageCount} (Expected: 1)`);
  console.log(` - HR count after toggle:   ${hrCountAfterToggle} (Expected: 1 - hidden paragraphs must still be counted)`);
  assert(hiddenImageCount === 1, 'Image paragraph must be hidden');
  assert(hrCountAfterToggle === 1, 'Separator count must not change when paragraphs are hidden');

  // Toggle back to visible
  await page.click('#toggle-img-btn');
  hiddenImageCount = await page.$$eval('p.hidden-image-p', (els) => els.length);
  console.log(` - After toggle back, hidden count: ${hiddenImageCount} (Expected: 0)`);
  assert(hiddenImageCount === 0, 'Image paragraph must become visible again');
  console.log(' [PASS] Toggle before adding paragraphs verified.\n');

  // ------------------------------------------------------------------------
  // Step 3: Click 'Hit me' to Add Paragraphs (Click 1)
  // ------------------------------------------------------------------------
  console.log('[Check 3: Adding Paragraphs (Click 1)]');
  await page.click('#hit-me-btn');

  const pCountClick1 = await page.$$eval('#article-content > p', (els) => els.length);
  const hrCountClick1 = await page.$$eval('#article-content > hr.pair-separator', (els) => els.length);

  console.log(` - Paragraph count: ${pCountClick1} (Expected: 6)`);
  console.log(` - HR count:        ${hrCountClick1} (Expected: 2 -> after P2 and P4)`);
  assert(pCountClick1 === 6, 'Paragraph count must be 6 after 1st click');
  assert(hrCountClick1 === 2, 'HR count must be 2 (P1, P2, HR, P3, P4, HR, P5, P6)');
  console.log(' [PASS] Adding paragraphs verified.\n');

  // ------------------------------------------------------------------------
  // Step 4: Toggle Images AFTER adding paragraphs
  // ------------------------------------------------------------------------
  console.log('[Check 4: Toggling Image Visibility AFTER Adding Paragraphs]');
  await page.click('#toggle-img-btn'); // Set to hidden

  hiddenImageCount = await page.$$eval('p.hidden-image-p', (els) => els.length);
  let totalWithImages = await page.$$eval('#article-content > p', (els) => els.filter(p => p.querySelector('img')).length);

  console.log(` - Paragraphs containing images: ${totalWithImages}`);
  console.log(` - Hidden image paragraphs:       ${hiddenImageCount} (Expected: ${totalWithImages})`);
  assert(hiddenImageCount === totalWithImages, 'All paragraphs with images must be hidden');
  console.log(' [PASS] Toggle after adding paragraphs verified.\n');

  // ------------------------------------------------------------------------
  // Step 5: Add paragraphs WHILE images are toggled hidden
  // "If they are hidden, newly added image paragraphs must stay hidden too."
  // ------------------------------------------------------------------------
  console.log('[Check 5: Adding Paragraphs WHILE Images are Hidden]');
  await page.click('#hit-me-btn'); // 2nd click: total 9 paragraphs

  const pCountClick2 = await page.$$eval('#article-content > p', (els) => els.length);
  const hrCountClick2 = await page.$$eval('#article-content > hr.pair-separator', (els) => els.length);
  totalWithImages = await page.$$eval('#article-content > p', (els) => els.filter(p => p.querySelector('img')).length);
  hiddenImageCount = await page.$$eval('p.hidden-image-p', (els) => els.length);

  console.log(` - Paragraph count:       ${pCountClick2} (Expected: 9)`);
  console.log(` - HR count:              ${hrCountClick2} (Expected: 4 -> floor((9-1)/2))`);
  console.log(` - Total image paragraphs:${totalWithImages} (Expected: 3)`);
  console.log(` - Hidden image paragraphs:${hiddenImageCount} (Expected: 3 - newly added must stay hidden!)`);

  assert(pCountClick2 === 9, 'Paragraph count must be 9');
  assert(hrCountClick2 === 4, 'HR count must be 4');
  assert(hiddenImageCount === totalWithImages, 'Newly added image paragraph must stay hidden');
  console.log(' [PASS] Preservation of hidden state on new paragraphs verified.\n');

  // ------------------------------------------------------------------------
  // Step 6: Unhide and verify all become visible
  // ------------------------------------------------------------------------
  console.log('[Check 6: Restoring Visibility]');
  await page.click('#toggle-img-btn');
  hiddenImageCount = await page.$$eval('p.hidden-image-p', (els) => els.length);
  console.log(` - Hidden image count after restore: ${hiddenImageCount} (Expected: 0)`);
  assert(hiddenImageCount === 0, 'All image paragraphs must be visible');
  console.log(' [PASS] Visibility restore verified.\n');

  // ------------------------------------------------------------------------
  // Step 7: Repeated Clicks Stress Test
  // ------------------------------------------------------------------------
  console.log('[Check 7: Repeated Clicks Stress Test]');
  for (let click = 3; click <= 6; click++) {
    await page.click('#hit-me-btn');
  }
  const finalPCount = await page.$$eval('#article-content > p', (els) => els.length);
  const finalHRCount = await page.$$eval('#article-content > hr.pair-separator', (els) => els.length);
  // Total paragraphs = 3 (initial) + 6 clicks * 3 = 21 paragraphs
  const expectedHRs = Math.floor((finalPCount - 1) / 2); // 10 HRs

  console.log(` - Final Paragraphs after 6 clicks: ${finalPCount} (Expected: 21)`);
  console.log(` - Final HR count:                  ${finalHRCount} (Expected: ${expectedHRs})`);
  assert(finalPCount === 21, 'Total paragraphs must be 21');
  assert(finalHRCount === expectedHRs, `Expected exactly ${expectedHRs} separators`);
  console.log(' [PASS] Repeated clicks stress test verified.\n');

  // Capture verification screenshot
  await page.screenshot({ path: path.resolve(__dirname, 'evidence-question-06.png'), fullPage: true });
  console.log('Saved visual evidence to evidence-question-06.png');

  await browser.close();
  console.log('\nALL QUESTION 6 CHECKS PASSED.');
}

runChecks().catch((err) => {
  console.error(err);
  process.exit(1);
});
