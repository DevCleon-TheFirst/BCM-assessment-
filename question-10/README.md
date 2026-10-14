# Question 10: Security & Code Review (WordPress Endpoint Protection)

## 1. Review of the Claim: "Safe because the button is visible only to editors"

The claim is fundamentally invalid and introduces a severe vulnerability (**CWE-602: Client-Side Enforcement of Server-Side Security** / Broken Access Control).

### Why UI Hiding Fails
1. **The Client is Untrusted**: Hiding a button in the DOM only affects visual presentation in the browser. Any user (unauthenticated visitor, reader, or malicious actor) can inspect network traffic, find the endpoint URL, and send HTTP `POST` requests directly via `curl`, Postman, or the browser console.
2. **Cross-Site Request Forgery (CSRF)**: Without server-side nonce verification, an attacker could trick an authenticated editor into visiting a malicious third-party site containing a hidden script that issues requests to this endpoint using the editor's active session cookies.
3. **Privilege Escalation**: Without object-level permission checks (`current_user_can('edit_post', $post_id)`), an authenticated low-privileged user (e.g. Contributor or Subscriber) could alter or unpublish articles authored by senior editors or administrators.

---

## 2. Server-Side Checks Required (Defense in Depth)

1. **Authentication Verification**: Verify that the client is logged in with a valid session (`is_user_logged_in()`).
2. **CSRF / Nonce Verification**: Validate an active cryptographic token (`wp_verify_nonce()` via `X-WP-Nonce` header or `check_ajax_referer()`).
3. **Input Sanitization & Existence**: Verify that `$post_id` is a positive integer (`absint()`) and corresponds to an existing post of type `'post'`.
4. **Object-Level Authorization**: Verify that the authenticated user possesses permission to edit *this specific post* (`current_user_can('edit_post', $post_id)`).
5. **State Transition Whitelisting**: Ensure the requested `$new_status` belongs to an allowed whitelist (`draft`, `pending`, `publish`) and that the user has `publish_posts` capability if promoting to published status.

---

## 3. Order of Checks in Pseudocode

```php
function handle_update_article_status(WP_REST_Request $request): WP_REST_Response {
    // 1. Authentication Check
    if (!is_user_logged_in()) {
        return new WP_REST_Response(['error' => 'Authentication required.'], 401);
    }

    // 2. CSRF / Nonce Verification
    $nonce = $request->get_header('X-WP-Nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_REST_Response(['error' => 'Invalid or expired security token.'], 403);
    }

    // 3. Input Validation: Post ID & Existence
    $post_id = absint($request->get_param('post_id'));
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'post') {
        return new WP_REST_Response(['error' => 'Article not found.'], 404);
    }

    // 4. Object-Level Capability Check
    if (!current_user_can('edit_post', $post_id)) {
        return new WP_REST_Response(['error' => 'Permission denied to edit this article.'], 403);
    }

    // 5. Allowed Status Whitelist
    $allowed_statuses = ['draft', 'pending', 'publish'];
    $new_status = sanitize_key($request->get_param('status'));
    if (!in_array($new_status, $allowed_statuses, true)) {
        return new WP_REST_Response(['error' => 'Invalid post status requested.'], 422);
    }

    // 6. Capability Check for Publishing
    if ($new_status === 'publish' && !current_user_can('publish_posts')) {
        return new WP_REST_Response(['error' => 'Permission denied to publish content.'], 403);
    }

    // 7. Execute State Transition
    wp_update_post([
        'ID'          => $post_id,
        'post_status' => $new_status,
    ]);

    return new WP_REST_Response(['success' => true, 'status' => $new_status], 200);
}
```

---

## 4. Two Specific Tests That Must Be Rejected

### Test 1: Unauthenticated Direct Request (Simulating External Attacker)
* **Payload**: `POST /wp-json/custom/v1/update-status` with body `{"post_id": 101, "status": "publish"}`, with no cookies or authorization headers.
* **Expected Outcome**: **HTTP 401 Unauthorized** (or HTTP 403). The post status in the database must remain unchanged.

### Test 2: Horizontal Privilege Escalation (Cross-User Tampering)
* **Payload**: Authenticated Contributor sends request targeting another editor's published post (`post_id: 102`) with valid nonce, requesting status `draft`.
* **Expected Outcome**: **HTTP 403 Forbidden**. The server evaluates `current_user_can('edit_post', 102)` to `false` and aborts.

*(Both tests verified locally in `question-10/test-checks.php`)*.

---

## 5. How to Raise the Issue with the Developer
* **Tone**: Collaborative, constructive, focused on shared security standards rather than criticism.
* **Code Review Comment Example**:
  > *"Hey [Name], nice work getting the frontend button and UI flow wired up so smoothly!*
  >
  > *Before we merge, we have a critical security requirement to address on the server side: hiding a button in the UI doesn't prevent direct HTTP calls. Any user—or an attacker using curl or dev tools—can discover the endpoint URL and fire requests without going through our UI.*
  >
  > *If we don't enforce server-side authentication, CSRF nonces, and capability checks (`current_user_can('edit_post', $post_id)`), an unauthenticated attacker could publish or unpublish articles across the site. I've left a draft snippet in the PR showing how to wire this into WordPress's `permission_callback`. Let's hop on a quick 10-minute huddle so we can get this locked down together!"*

---

## 6. What MUST Be Fixed Before Release (Blockers)
1. **Server-Side Authorization**: Implement a `permission_callback` checking `is_user_logged_in()` and `current_user_can('edit_post', $post_id)`.
2. **CSRF Nonce Validation**: Enforce verification of `X-WP-Nonce` on the endpoint.
3. **Status Whitelist**: Restrict input statuses strictly to permitted workflow states.

---

## 7. AI Assistance & Verification
* **Where AI helped**: Structuring the code review feedback comment and pseudocode validation ladder.
* **How verified**: Simulated attack vectors and verified 401/403/422 rejection responses in `php question-10/test-checks.php`.
