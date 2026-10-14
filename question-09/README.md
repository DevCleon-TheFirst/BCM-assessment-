# Question 9: API Reliability & Resilient Newsletter Subscriptions

## 1. Handling Failures Without Losing or Duplicating Signups

The core challenge is distributed network uncertainty: a request times out, but the newsletter service may have already processed and stored the record. A blind `POST` retry would create duplicate subscriber records or trigger "already exists" errors.

### Solution Pattern: Idempotent PUT / Upsert + Deterministic Key
1. **Timeout Handling**:
   - Instead of a non-idempotent `POST /subscribers`, use an idempotent `PUT /lists/{list_id}/members/{md5(email)}` with an `Idempotency-Key` header (`sha256(list_id + email)`).
   - If the request times out, the handler queries `GET /lists/{list_id}/members/{md5(email)}` to check if the subscriber was already registered during the timeout.
   - If network connectivity is completely severed, the payload is enqueued into a durable retry queue (e.g. database table or RabbitMQ/SQS) with exponential backoff. The subscriber is never lost.
2. **Rate Limit Handling (HTTP 429)**:
   - Inspect the `Retry-After` header (or default to exponential backoff with jitter).
   - Flag the subscription attempt as `RATE_LIMITED` and reschedule execution in the background queue rather than failing the user.
3. **Invalid Email Handling (HTTP 400 / 422)**:
   - Perform pre-flight email syntax validation (`filter_var($email, FILTER_VALIDATE_EMAIL)`).
   - If the remote provider returns a 400/422 (e.g. invalid mailbox or bounced domain), mark the transaction permanently as `INVALID_EMAIL`. Do **not** retry non-transient 4xx errors.

---

## 2. What to Store or Log vs What to AVOID Logging

### What to Store / Log (Audit & Operational Health)
* **Transaction UUID**: Unique local identifier for request correlation.
* **Hashed Subscriber Identifier**: `sha256(lowercase_email)` — allows tracing a user's subscription attempts across logs without exposing PII.
* **Newsletter List ID & Source**: Identifies campaign attribution.
* **HTTP Status Code & Duration (ms)**: Diagnostic telemetry for alerting on latency spikes or provider degradation.
* **Sanitized Error Code**: Provider error codes (e.g. `ERR_RATE_LIMIT`, `ERR_SYNTAX`).

### What to AVOID Logging (Data Privacy & Compliance)
* **Plaintext Email Address**: Storing raw emails in application logs violates GDPR/NDPR data minimization principles, as log streams are often aggregated in third-party tooling (Datadog, CloudWatch, Papertrail).
* **API Secret Keys / Bearer Tokens**: Never output Authorization headers.
* **Subscriber IP Addresses & User Agent**: Sensitive identifying metadata.

---

## 3. How to Test a Retry After Service Already Accepted the Request

1. **Mock Implementation**:
   - The test mock (`MockNewsletterServer` in `test-checks.php`) accepts the subscriber write on Request 1, writes the record to its database, and artificially throws a `TimeoutException` without sending an HTTP response to the client.
2. **Verification Logic**:
   - The client catches the timeout and triggers its idempotent reconciliation query.
   - The mock server receives the subsequent query/upsert and returns `200 OK` (status: `subscribed`).
   - The test asserts:
     - `result->success === true` (status: `ALREADY_SUBSCRIBED`).
     - `count(server->database) === 1` (zero duplicate records).

---

## 4. Evidence of Checks
Run locally with `php question-09/test-checks.php`:

```text
========================================================
QUESTION 9 API RELIABILITY & RETRY VERIFICATION
========================================================

[Check 1: Timeout After Server Accepted Request]
 - Result Status:  ALREADY_SUBSCRIBED
 - Result Message: Verified subscription after timeout.
 - Total DB Records in Mock Server: 1
 [PASS] Timeout handled without losing signup or creating duplicate.

[Check 2: Rate Limit Handling (HTTP 429)]
 - Result Status:  RATE_LIMITED
 - Result Message: Rate limited. Retry after 15s.
 [PASS] Rate limit detected and delay communicated.

[Check 3: Invalid Email Handling]
 - Bad Format Result: INVALID_EMAIL - Invalid email format.
 - Bad Domain Result: INVALID_EMAIL - Invalid email domain
 [PASS] Invalid emails rejected immediately without wasteful retries.

[Check 4: Data Privacy Logging Audit]
 - Safe Log Entry: {"event":"newsletter_subscription_attempt","timestamp":"2026-10-12T16:56:58+00:00","list_id":"daily-brief","subscriber_hash":"d108b279434fe1d54ac0f1da633564604b26c2e0e221d108b0fbadb87aba02c0","status":"RATE_LIMITED","duration_ms":45}
 [PASS] Privacy audit verified: Zero PII or API tokens in log entry.

ALL QUESTION 9 CHECKS PASSED SUCCESSFULLY.
```

---

## 5. AI Assistance & Verification
* **Where AI helped**: Outlining the error taxonomy (transient vs permanent).
* **How verified**: Tested with a runnable PHP mock server validating state transitions, deduplication, and privacy assertions.
