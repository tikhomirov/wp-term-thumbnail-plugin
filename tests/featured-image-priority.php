<?php

// Standalone hook regression: php tests/featured-image-priority.php
require $argv[1] ?? __DIR__ . '/../classes/TermThumbnail.php';

class TermThumbnailSettings
{
    public $settings = ['taxonomies' => ['category']];

    public function add_actions() {}
}

function plugin_dir_path($file) { return ''; }
function plugin_dir_url($file) { return ''; }
function taxonomy_exists($name) { return true; }
function add_action(...$args) {}
function add_filter($hook, $callback, ...$args)
{
    if ($hook === 'get_post_thumbnail') {
        $GLOBALS['filter'] = $callback;
    }
}
function get_post_thumbnail_id($postId) { return [1 => 11, 2 => 12][$postId] ?? 0; }
function get_post($postId) { return $postId; }
function get_the_terms($post, $taxonomy)
{
    return $post === 4 ? false : [(object) ['taxonomy' => $taxonomy, 'term_id' => 42]];
}
function is_wp_error($value) { return false; }
function get_default_post_thumbnail($termId, $attr) { return 'term-cover'; }

(new classes\TermThumbnail())->add_actions();

$cases = [
    'existing HTML' => ['featured-html', 1, [], 'featured-html'],
    'draft preview with a featured attachment' => ['', 1, ['attach_id' => null], ''],
    'archive card uses its own post ID' => ['', 2, [], ''],
    'explicit attachment argument' => ['', 3, ['attach_id' => 13], ''],
    'no featured attachment' => ['', 3, ['attach_id' => null], 'term-cover'],
    'no term fallback available' => ['', 4, [], ''],
];

foreach ($cases as $label => [$html, $postId, $attr, $expected]) {
    $actual = $filter($html, $postId, $attr);
    if ($actual !== $expected) {
        fwrite(STDERR, "$label: expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

printf("Passed %d hook regression cases.\n", count($cases));
