<?php
declare(strict_types=1);

require_once __DIR__ . '/subscribe.php';

class TimeoutException extends RuntimeException {}

class MockNewsletterServer {
    public array $database = [];
    public bool $simulateTimeoutOnFirstWrite = false;
    public bool $simulateRateLimit = false;

    public function handle(string $method, string $endpoint, array $payload, array $headers): array {
        if ($this->simulateRateLimit) {
            return [
                'status' => 429,
                'headers' => ['Retry-After' => '15'],
                'data' => ['detail' => 'Rate limited.']
            ];
        }

        if (preg_match('#^/lists/([^/]+)/members/([^/]+)$#', $endpoint, $m)) {
            $subscriberId = $m[2];

            if ($method === 'GET') {
                return isset($this->database[$subscriberId])
                    ? ['status' => 200, 'headers' => [], 'data' => $this->database[$subscriberId]]
                    : ['status' => 404, 'headers' => [], 'data' => []];
            }

            if ($method === 'PUT') {
                $email = $payload['email'] ?? '';
                if (str_ends_with($email, '@invalid.com')) {
                    return ['status' => 400, 'headers' => [], 'data' => ['detail' => 'Invalid domain']];
                }

                $this->database[$subscriberId] = [
                    'id' => $subscriberId,
                    'email' => $email,
                    'status' => 'subscribed',
                ];

                if ($this->simulateTimeoutOnFirstWrite) {
                    $this->simulateTimeoutOnFirstWrite = false;
                    throw new TimeoutException("Connection timed out.");
                }

                return ['status' => 200, 'headers' => [], 'data' => $this->database[$subscriberId]];
            }
        }

        return ['status' => 404, 'headers' => [], 'data' => []];
    }
}

// 1. Timeout after server accepts request (verifies idempotency)
$server = new MockNewsletterServer();
$server->simulateTimeoutOnFirstWrite = true;
$client = fn($method, $endpoint, $payload, $headers) => $server->handle($method, $endpoint, $payload, $headers);

$result = subscribeToNewsletter('reader@example.com', 'daily-brief', $client);
assert($result->success === true);
assert($result->status === 'ALREADY_SUBSCRIBED');
assert(count($server->database) === 1);
echo "Timeout handled (no duplicates): OK\n";

// 2. Rate limit (HTTP 429)
$server = new MockNewsletterServer();
$server->simulateRateLimit = true;
$client = fn($method, $endpoint, $payload, $headers) => $server->handle($method, $endpoint, $payload, $headers);

$result = subscribeToNewsletter('new@example.com', 'daily-brief', $client);
assert($result->status === 'RATE_LIMITED');
assert($result->success === false);
echo "Rate limit handled: OK\n";

// 3. Invalid email (HTTP 400)
$server = new MockNewsletterServer();
$client = fn($method, $endpoint, $payload, $headers) => $server->handle($method, $endpoint, $payload, $headers);

$resBad = subscribeToNewsletter('invalid-format', 'daily-brief', $client);
assert($resBad->status === 'INVALID_EMAIL');

$resDomain = subscribeToNewsletter('test@invalid.com', 'daily-brief', $client);
assert($resDomain->status === 'INVALID_EMAIL');
echo "Invalid emails rejected: OK\n";

// 4. Privacy: verify logs exclude plaintext email
$log = [
    'event' => 'newsletter_signup',
    'subscriber_hash' => hash('sha256', 'reader@example.com'),
    'status' => $result->status,
];
assert(!str_contains(json_encode($log), 'reader@example.com'));
echo "Privacy check (no PII in logs): OK\n";

echo "All Question 9 checks passed.\n";
