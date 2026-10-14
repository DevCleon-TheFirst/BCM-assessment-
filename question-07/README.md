# Question 7: Troubleshooting and Delivery (Post-Release Discrepancy)

## 1. Stated Assumptions
1. **Architecture**: The platform uses a standard high-traffic publishing stack: CDN/Edge caching (e.g., Cloudflare/Fastly), origin server caching (Nginx microcache, Varnish, or Redis Object Cache), and multiple load-balanced web server nodes.
2. **Nature of Change**: An article was edited/corrected around the same time a new theme version was deployed to production.
3. **Reader Differences**: Different readers route to different CDN Points of Presence (POPs), different backend server nodes, or possess different session states (e.g., logged-in vs anonymous).

---

## 2. First Three Checks

### Check 1: Inspect HTTP Response Headers & Cache Status (Origin vs Edge)
* **How to run**:
  ```bash
  # Query via CDN
  curl -sI https://www.site.com/article-slug/
  # Query directly to origin server bypassing CDN
  curl -sI -H "Host: www.site.com" https://origin-ip/article-slug/
  ```
* **What to look for**:
  - Inspect `CF-Cache-Status` / `X-Cache` (`HIT` vs `MISS`), `Age`, and `Last-Modified` / `ETag`.
  - Check whether the origin server already outputs the corrected content while the CDN edge responds with a stale cached object (`HIT` with high `Age`).

### Check 2: Verify Multi-Server Deployment Consistency (Cluster Sync)
* **How to run**:
  - Query each individual web server node behind the load balancer directly (or run `wp core version` and `git rev-parse HEAD` across all nodes via Ansible/SSH).
* **What to look for**:
  - Ensure all server nodes are running the exact same commit hash.
  - If server Node A has the new release but Node B failed during deployment or has stalled PHP-FPM workers, traffic hitting Node B will serve older content or templates.

### Check 3: Check Reader State & Browser Caching Headers
* **How to run**:
  - Compare the headers received by anonymous readers versus logged-in editorial users.
* **What to look for**:
  - Check if `Cache-Control` specifies an excessively long client `max-age` that forced readers' local browsers to cache the document, or if WordPress bypass cookies (`wordpress_logged_in_*`) explain why editors see the update while readers see cached edge content.

---

## 3. Distinguishing Caching vs Code vs Content

| Root Cause | Diagnostic Signature | Action Required |
|---|---|---|
| **Content Problem** | Content is wrong in WordPress Admin database (`wp_posts`). Origin server serves outdated text even with all caches bypassed. | Edit and re-save the post in WP Admin. |
| **Caching Problem** | Origin server outputs the correct article text, but CDN/Edge or Varnish returns stale HTML with `X-Cache: HIT`. | Purge the specific URL cache on the CDN/reverse proxy; do **not** trigger a code deployment. |
| **Code / Deployment Problem** | Origin server throws PHP errors, assets 404, or git commits differ across server nodes. | Fix deployment sync across nodes, or roll back if code is broken. |

---

## 4. When to Roll Back vs Hold
* **When to Roll Back**:
  - **Only roll back if**: The new release introduced broken application logic, PHP fatal errors, database errors, or severe visual breakage across multiple pages that cannot be remedied within minutes.
* **When NOT to Roll Back**:
  - **Do NOT roll back** simply because edge caches have not refreshed. Deploying an emergency rollback when the code itself is healthy adds deployment risk, resets all server caches simultaneously (risking a cache stampede / server overload), and fails to solve stale edge caches.

---

## 5. How to Verify Recovery
1. **Targeted Purge**: Purge the specific article URL from Cloudflare/Fastly and any local Redis/Varnish object caches.
2. **Multi-Region Automated Probe**:
   ```bash
   # Verify edge returns fresh MISS followed by refreshed HIT with expected updated text
   curl -sL https://www.site.com/article-slug/ | grep "corrected phrase"
   ```
   Query from multiple regional proxies/VPNs or run a multi-location check via WebPageTest.
3. **Session Verification**: Test in clean Incognito windows across mobile and desktop viewports to ensure both anonymous and authenticated states render the corrected text.

---

## 6. Two-Sentence Update to the Editor
> "We confirmed the updated article is saved correctly on our servers; the discrepancy was due to temporary edge caching following the release rather than a code defect. We have purged the cached article across our network, verified the update is now displaying live for all readers, and no rollback is required."

---

## 7. AI Assistance & Verification
* **Where AI helped**: Structuring the diagnostic triage matrix and drafting the stakeholder communication.
* **How verified**: Grounded in standard CDN edge cache verification headers and multi-server deployment protocols.
