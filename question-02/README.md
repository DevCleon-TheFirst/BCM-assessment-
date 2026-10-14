# Question 2: WordPress Upload Failure Diagnosis

## Scenario
An editor cannot upload images, while a colleague can. Because one user can upload successfully, global server-down issues are ruled out. The issue stems from user-specific, file-specific, or client-environment discrepancies.

---

## Likely Causes, Diagnostic Order, Evidence & Resolution

### Cause 1: User Role & Capability Mismatch (User-Level)
* **Why it happens**: The user's account does not have the `upload_files` capability. They may have been mistakenly assigned the **Contributor** role instead of **Editor**, or a role-management plugin stripped media capabilities.
* **Order of Check**: **1st** (fastest to verify without disturbing the user or checking server logs).
* **Evidence to Look For**:
  - In WordPress Admin under **Users > All Users**, inspect the user's role.
  - Or via WP-CLI: `wp user get <username> --field=roles` and `wp user has-cap <username> upload_files`.
  - In the UI, the user will see *"Sorry, you are not allowed to attach files to this post"* or the upload dropzone will be absent.
* **Action Once Confirmed**:
  - Update the user's role back to **Editor** via WP Admin or WP-CLI (`wp user set-role <username> editor`).

---

### Cause 2: File-Specific Constraints (Size, Dimensions, or MIME Type)
* **Why it happens**: The colleague uploaded a standard 200KB JPEG, whereas this editor is attempting to upload an unsupported format (e.g., raw `.heic`, un-sanitized `.svg`) or a 25MB camera file that exceeds `upload_max_filesize`, `post_max_size`, or image processing memory limits during thumbnail generation.
* **Order of Check**: **2nd** (check before client-side machine debugging).
* **Evidence to Look For**:
  - Check the file extension, size, and MIME type.
  - Inspect the browser Network tab for `async-upload.php` or `/wp-json/wp/v2/media`:
    - `HTTP 413 Payload Too Large` -> Exceeds PHP or Nginx/Cloudflare upload limit.
    - `HTTP 500 Internal Server Error` -> PHP fatal error: memory exhausted while generating thumbnails.
    - UI message: *"Sorry, this file type is not permitted for security reasons"* or *"File exceeds the maximum upload size for this site."*
* **Action Once Confirmed**:
  - If file size is excessive: Compress image or adjust `upload_max_filesize` / `post_max_size` / `memory_limit` in `php.ini`.
  - If unsupported MIME type: Enable secure support via a vetted plugin (e.g., Safe SVG) or convert the file to WebP/JPEG.

---

### Cause 3: Client-Side Environment / Browser Interruption (Adblocker, Stale Nonce, VPN)
* **Why it happens**: Browser extensions can block REST API requests (`/wp-json/wp/v2/media`) or `admin-ajax.php`. Alternatively, an expired REST nonce / auth cookie or an aggressive proxy/VPN is stripping multipart headers or cookies.
* **Order of Check**: **3rd** (involves inspecting the user's browser environment).
* **Evidence to Look For**:
  - Have the editor test the upload in an **Incognito / Private Window** with extensions disabled.
  - Inspect Developer Tools:
    - Console shows `net::ERR_BLOCKED_BY_CLIENT`.
    - Network tab shows `HTTP 401 Unauthorized` or `HTTP 403 Forbidden` with `{ "code": "rest_cookie_invalid_nonce" }`.
* **Action Once Confirmed**:
  - If extension issue: Add the WordPress admin domain to the adblocker whitelist.
  - If nonce/session issue: Have the user clear cookies and log in again to receive a fresh authentication cookie and REST nonce.

---

## Evidence of Checks
Simulation script executed locally with `php question-02/diagnose-upload.php`:

```text
=== Media Upload Diagnostics ===

[Check 1: Capabilities]
 - editor_colleague (editor): OK
 - editor_failing (contributor): FAIL (missing upload_files)

[Check 2: PHP Limits]
 - upload_max_filesize: 15M
 - post_max_size:       20M
 - memory_limit:        -1
 - Candidate 15MB file: OK

[Check 3: MIME Types]
 - hero-image.jpg: ALLOWED
 - graphic.svg: REJECTED (disallowed MIME)
 - photo.heic: REJECTED (disallowed MIME)

[Check 4: Directory Permissions]
 - /tmp/wp-content-uploads: Writable (OK)

Done.
```

---

## AI Assistance & Verification
* **Where AI helped**: Used to structure the comparison list and verify WP-CLI command arguments.
* **How verified**: Simulated capability checks, ini parsing, and MIME validation in a standalone PHP script (`php question-02/diagnose-upload.php`).
