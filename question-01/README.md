# Question 1: PHP WordPress Search & Ad Replacer

## 1. Errors in Original Code & Effects in PHP 8 / WordPress

| # | Bug / Anti-Pattern | Exact Cause | Runtime Effect in PHP 8 / WordPress |
|---|---|---|---|
| 1 | `WP_Query([...])` missing `new` | Calling class as function | **Fatal Error**: `Call to undefined function WP_Query()`. Script crashes immediately. |
| 2 | `'page' => $page` query argument | `WP_Query` expects `'paged'`, not `'page'` | Pagination is ignored. Page 2 returns Page 1 posts repeatedly. |
| 3 | Direct `$_GET['s']` & `$_GET['page']` access | No existence or type check | **PHP 8 Warning**: `Undefined array key "s"`. Also security risk (unsanitized input). |
| 4 | Undefined `$pages` variable | Variable never calculated or defined | **PHP 8 Warning**: `Undefined variable $pages`. Blank string outputted in `<h1>`. |
| 5 | Regex `#[ad]#` character class | Unescaped brackets `[...]` form a regex character set | Matches any literal character `'a'` or `'d'`. Corrupts legitimate words (e.g., replaces `'a'` in `"bad"`, `"added"`). |
| 6 | Broken `preg_replace_callback` signature | Callback defined as `function()` with no args, but uses `$m[1]` | **PHP 8 Warning**: `Undefined variable $m`. Replacement returns empty. |
| 7 | Direct `echo` inside callback | `echo '<div...'` prints output straight to output buffer | Prints ad tags out of document flow before the post loop; `$content` becomes empty string. |
| 8 | Double-echo in `<h2><?= esc_html(the_title()) ?></h2>` | `the_title()` echoes and returns `void` | Post title is printed immediately outside `<h2>`, leaving `<h2></h2>` empty. |
| 9 | Missing `wp_reset_postdata()` | Global `$post` mutated by `$q->the_post()` | Leaves global WordPress post context polluted, breaking sidebars, footers, and subsequent queries. |
| 10 | Missing zero-results handling | No `else` condition on `have_posts()` | Page renders blank content when no posts match. |

---

## 2. Assumptions About the `[ad]` Placeholder
* **Literal Marker**: The content contains the exact literal marker `[ad]` as placed by editors or raw content feeds.
* **Sequential Slot Numbering**: The markup requires unique identifiers (e.g. `ad-1`, `ad-2`) so client-side ad scripts can bind unique slot configurations to each rendered placeholder.
* **Content Safety**: Standard WordPress paragraphs (`wpautop`) and KSES sanitization are respected so ads sit between properly formatted paragraphs.

---

## 3. Evidence of Checks

All checks were executed locally with `php question-01/test-checks.php` under PHP 8.4:

```text
TEST: Missing parameters (?page, ?s)... OK
TEST: Page 2 pagination... OK
TEST: Zero search results... OK
TEST: Multiple ad placeholders & word boundary safety... OK
All Question 1 checks passed.
```

---

## 4. AI Assistance & Verification
* **Where AI helped**: Used to outline potential PHP 8 warnings and draft test doubles for `WP_Query`.
* **How verified**: Tested locally using native `php` CLI with an automated test suite verifying edge cases, regex boundaries, and assertion checks.
