<?php
declare(strict_types=1);

class NewsletterResult {
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $message = null
    ) {}
}

function subscribeToNewsletter(string $email, string $listId, callable $apiClient): NewsletterResult {
    $cleanEmail = strtolower(trim($email));

    if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
        return new NewsletterResult(false, 'INVALID_EMAIL', 'Invalid email format.');
    }

    $subscriberId = md5($cleanEmail);
    $payload = ['email' => $cleanEmail, 'status' => 'subscribed'];
    $headers = ['Idempotency-Key' => hash('sha256', "{$listId}:{$cleanEmail}")];

    try {
        $response = $apiClient('PUT', "/lists/{$listId}/members/{$subscriberId}", $payload, $headers);

        if ($response['status'] === 200 || $response['status'] === 201) {
            return new NewsletterResult(true, 'SUBSCRIBED');
        }

        if ($response['status'] === 400 || $response['status'] === 422) {
            return new NewsletterResult(false, 'INVALID_EMAIL', $response['data']['detail'] ?? 'Invalid email.');
        }

        if ($response['status'] === 429) {
            $retryAfter = (int) ($response['headers']['Retry-After'] ?? 60);
            return new NewsletterResult(false, 'RATE_LIMITED', "Rate limited. Retry after {$retryAfter}s.");
        }

        return new NewsletterResult(false, 'QUEUED_FOR_RETRY', "HTTP {$response['status']}");
    } catch (TimeoutException $e) {
        // Query service to verify if subscription was accepted before the timeout
        try {
            $check = $apiClient('GET', "/lists/{$listId}/members/{$subscriberId}", [], []);
            if ($check['status'] === 200 && ($check['data']['status'] ?? '') === 'subscribed') {
                return new NewsletterResult(true, 'ALREADY_SUBSCRIBED', 'Verified after timeout.');
            }
        } catch (Exception) {}

        return new NewsletterResult(false, 'QUEUED_FOR_RETRY', 'Timed out; queued for retry.');
    }
}
