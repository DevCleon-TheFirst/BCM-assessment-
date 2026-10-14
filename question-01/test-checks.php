<?php
declare(strict_types=1);

if (!function_exists('absint')) {
    function absint($maybeint): int {
        return abs((int) $maybeint);
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return is_string($value) ? stripslashes($value) : $value;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        return trim(strip_tags($str));
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text): string {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wpautop')) {
    function wpautop(string $pee): string {
        return '<p>' . str_replace("\n", '</p><p>', trim($pee)) . '</p>';
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $content): string {
        return $content;
    }
}

class MockPost {
    public int $ID;
    public string $post_title;
    public string $post_content;

    public function __construct(int $id, string $title, string $content) {
        $this->ID = $id;
        $this->post_title = $title;
        $this->post_content = $content;
    }
}

$GLOBALS['mock_global_post'] = null;

if (!function_exists('get_the_title')) {
    function get_the_title(): string {
        global $mock_global_post;
        return $mock_global_post ? $mock_global_post->post_title : '';
    }
}

if (!function_exists('get_the_content')) {
    function get_the_content(): string {
        global $mock_global_post;
        return $mock_global_post ? $mock_global_post->post_content : '';
    }
}

if (!function_exists('the_ID')) {
    function the_ID(): void {
        global $mock_global_post;
        echo $mock_global_post ? $mock_global_post->ID : 0;
    }
}

if (!function_exists('post_class')) {
    function post_class(): void {
        echo 'class="post type-post status-publish"';
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void {
        global $mock_global_post;
        $mock_global_post = null;
    }
}

class WP_Query {
    public array $args;
    public array $posts = [];
    public int $post_count = 0;
    public int $current_post = -1;
    public int $max_num_pages = 1;

    public function __construct(array $args = []) {
        $this->args = $args;
        $this->init_mock_posts();
    }

    private function init_mock_posts(): void {
        if (($this->args['s'] ?? '') === '__NO_RESULTS__') {
            $this->posts = [];
            $this->post_count = 0;
            $this->max_num_pages = 0;
            return;
        }

        $this->posts = [
            new MockPost(101, 'First Post with Ads', "Paragraph 1 [ad]\nParagraph 2 [ad]\nParagraph 3 [ad]"),
            new MockPost(102, 'Second Post', "Here is an ad [ad] inside the text."),
            new MockPost(103, 'Regex Trap Post', "A bad ad was added to the standard post [ad]."),
        ];
        $this->post_count = count($this->posts);
        $this->max_num_pages = 4;
    }

    public function have_posts(): bool {
        return ($this->current_post + 1) < $this->post_count;
    }

    public function the_post(): void {
        global $mock_global_post;
        $this->current_post++;
        $mock_global_post = $this->posts[$this->current_post];
    }
}

function run_test(string $name, callable $testFn): void {
    echo "TEST: {$name}... ";
    try {
        $testFn();
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }
}

// 1. Missing parameters
run_test('Missing parameters (?page, ?s)', function () {
    $_GET = [];

    ob_start();
    include __DIR__ . '/search-template.php';
    $output = ob_get_clean();

    assert(str_contains($output, 'Page 1 of 4'));
    assert(!str_contains($output, 'Search for'));
    assert(!str_contains($output, 'Warning: Undefined array key'));
});

// 2. Page 2 pagination
run_test('Page 2 pagination', function () {
    $_GET = ['page' => '2', 's' => 'tech'];

    ob_start();
    include __DIR__ . '/search-template.php';
    $output = ob_get_clean();

    assert(str_contains($output, 'Page 2 of 4 : Search for tech'));
});

// 3. Zero search results
run_test('Zero search results', function () {
    $_GET = ['s' => '__NO_RESULTS__', 'page' => '1'];

    ob_start();
    include __DIR__ . '/search-template.php';
    $output = ob_get_clean();

    assert(str_contains($output, 'No posts found matching your criteria.'));
    assert(str_contains($output, 'Page 1 of 1'));
});

// 4. Multiple ads & regex safety
run_test('Multiple ad placeholders & word boundary safety', function () {
    $_GET = ['page' => '1'];

    ob_start();
    include __DIR__ . '/search-template.php';
    $output = ob_get_clean();

    assert(str_contains($output, 'class="ad ad-1"'));
    assert(str_contains($output, 'class="ad ad-2"'));
    assert(str_contains($output, 'class="ad ad-3"'));

    // Check words like "bad", "added" weren't corrupted
    assert(str_contains($output, 'A bad ad was added to the standard post'));
});

echo "All Question 1 checks passed.\n";
