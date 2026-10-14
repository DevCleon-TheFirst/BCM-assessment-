<?php
declare(strict_types=1);

class MockWPUser {
    public function __construct(
        public int $id,
        public string $role,
        public array $caps = []
    ) {}
}

class SecurityEndpointHandler {
    private ?MockWPUser $currentUser = null;
    private array $posts = [
        101 => ['id' => 101, 'author_id' => 1, 'status' => 'draft', 'type' => 'post'],
        102 => ['id' => 102, 'author_id' => 2, 'status' => 'publish', 'type' => 'post'],
    ];

    public function setCurrentUser(?MockWPUser $user): void {
        $this->currentUser = $user;
    }

    public function handleRequest(array $params, array $headers): array {
        // 1. Authentication
        if ($this->currentUser === null) {
            return ['status' => 401, 'body' => ['error' => 'Authentication required']];
        }

        // 2. CSRF Nonce
        $nonce = $headers['X-WP-Nonce'] ?? null;
        if ($nonce !== 'valid_secure_nonce') {
            return ['status' => 403, 'body' => ['error' => 'Invalid nonce']];
        }

        // 3. Post existence
        $postId = (int) ($params['post_id'] ?? 0);
        if (!isset($this->posts[$postId])) {
            return ['status' => 404, 'body' => ['error' => 'Article not found']];
        }

        // 4. Object-level capability
        $post = $this->posts[$postId];
        $isAuthor = $post['author_id'] === $this->currentUser->id;
        $isEditorOrAdmin = in_array($this->currentUser->role, ['editor', 'administrator'], true);

        if (!$isAuthor && !$isEditorOrAdmin) {
            return ['status' => 403, 'body' => ['error' => 'Cannot edit posts owned by other users']];
        }

        // 5. Allowed statuses
        $newStatus = (string) ($params['status'] ?? '');
        $allowedStatuses = ['draft', 'pending', 'publish'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            return ['status' => 422, 'body' => ['error' => 'Invalid status']];
        }

        // 6. Publishing capability
        if ($newStatus === 'publish' && !in_array($this->currentUser->role, ['editor', 'administrator'], true)) {
            return ['status' => 403, 'body' => ['error' => 'User lacks publishing capabilities']];
        }

        $this->posts[$postId]['status'] = $newStatus;
        return ['status' => 200, 'body' => ['success' => true, 'new_status' => $newStatus]];
    }
}

$endpoint = new SecurityEndpointHandler();

// Test 1: Unauthenticated request
$endpoint->setCurrentUser(null);
$r1 = $endpoint->handleRequest(['post_id' => 101, 'status' => 'publish'], ['X-WP-Nonce' => 'valid_secure_nonce']);
assert($r1['status'] === 401);
echo "Unauthenticated request (401): OK\n";

// Test 2: Horizontal privilege escalation (contributor editing editor's post)
$contributor = new MockWPUser(3, 'contributor');
$endpoint->setCurrentUser($contributor);
$r2 = $endpoint->handleRequest(['post_id' => 102, 'status' => 'draft'], ['X-WP-Nonce' => 'valid_secure_nonce']);
assert($r2['status'] === 403);
echo "Horizontal privilege escalation (403): OK\n";

// Test 3: Missing CSRF nonce
$editor = new MockWPUser(2, 'editor');
$endpoint->setCurrentUser($editor);
$r3 = $endpoint->handleRequest(['post_id' => 102, 'status' => 'draft'], []);
assert($r3['status'] === 403);
echo "Missing CSRF nonce (403): OK\n";

// Test 4: Authorized editor request
$r4 = $endpoint->handleRequest(['post_id' => 102, 'status' => 'draft'], ['X-WP-Nonce' => 'valid_secure_nonce']);
assert($r4['status'] === 200);
echo "Authorized editor request (200): OK\n";

echo "All Question 10 checks passed.\n";
