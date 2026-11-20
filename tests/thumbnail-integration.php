<?php

// Standalone regression: php tests/thumbnail-integration.php native|legacy|img|linked
// Real public API and a cached thumbnail: php tests/thumbnail-integration.php real /path/to/wp-thumbnail-plugin
$mode = $argv[1] ?? 'native';
$options = ['no_photo_url' => 'https://example.test/stub.png'];

class TermThumbnailSettings
{
    public $settings = ['taxonomies' => ['category']];
}

function add_action(...$args) {}
function plugin_dir_path($file) { return ''; }
function plugin_dir_url($file) { return ''; }
function content_url() { return 'https://example.test/content'; }
function get_queried_object() { return null; }
function wp_parse_args($args, $defaults) { return array_replace($defaults, (array) $args); }
function get_option($key, $default) { return $key === 'kama_thumbnail' ? $GLOBALS['options'] : $default; }
function get_term_meta($id, $key, $single) { return $id === 1 ? ['term_image_id' => '12', 'default_cover' => '34'][$key] : ''; }
function wp_get_attachment_image($id, $size, $icon = false) { return "native-$id-" . (is_array($size) ? implode('x', $size) : $size); }
function wp_get_attachment_image_src($id, $size, $icon) { return [wp_get_attachment_url($id)]; }
function wp_get_attachment_url($id) { return in_array((int) $id, [12, 34, 56, 78], true) ? "https://example.test/$id.png" : false; }
function get_attached_file($id) { return false; }
function esc_url($value, $protocols = null) { return htmlspecialchars($value, ENT_QUOTES); }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES); }
function apply_filters($hook, $value) { return $value; }

if ($mode === 'legacy') {
    function kama_thumb_img(...$args) { throw new RuntimeException('Legacy image function called'); }
    function kama_thumb_a_img(...$args) { throw new RuntimeException('Legacy linked function called'); }
}
if ($mode === 'img') {
    function thumb_img(array $args, int $id): string { return wp_get_attachment_url($id) ? "generated-$id" : ''; }
}
if ($mode === 'linked') {
    function thumb_a_img(array $args, int $id): string { return '<a href="' . wp_get_attachment_url($id) . '"><img /></a>'; }
}
if ($mode === 'real') {
    $generator = $argv[2];
    spl_autoload_register(static function ($class) use ($generator) {
        $prefix = 'KamaThumb\\';
        if (str_starts_with($class, $prefix)) {
            require $generator . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        }
    });
    define('WP_CONTENT_DIR', sys_get_temp_dir() . '/wp-term-thumb-' . bin2hex(random_bytes(8)));
    mkdir(WP_CONTENT_DIR . '/cache/thumb', 0700, true);
    foreach ([[12, 100], [56, 100], [34, 100], [12, 90]] as [$id, $size]) {
        touch(WP_CONTENT_DIR . '/cache/thumb/' . md5(wp_get_attachment_url($id)) . "_{$size}_{$size}_1_90.png");
    }
    register_shutdown_function(static function () {
        foreach (glob(WP_CONTENT_DIR . '/cache/thumb/*.png') as $file) {
            unlink($file);
        }
        rmdir(WP_CONTENT_DIR . '/cache/thumb');
        rmdir(WP_CONTENT_DIR . '/cache');
        rmdir(WP_CONTENT_DIR);
    });
    KamaThumb\Infrastructure\WordPress\TemplateFunctions::register();
}

require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../classes/TermThumbnail.php';

function check($condition, string $label): void
{
    if (! $condition) {
        throw new RuntimeException($label);
    }
}

$hasImg = in_array($mode, ['img', 'real'], true);
$hasLink = in_array($mode, ['linked', 'real'], true);
foreach ([[[], 12], [['attach_id' => '56'], 56]] as [$args, $id]) {
    $html = get_term_thumbnail($args + ['term_id' => 1]);
    $expected = $mode === 'real' ? md5(wp_get_attachment_url($id)) : ($hasImg ? "generated-$id" : "native-$id-100x100");
    check(str_contains($html ?? '', $expected), "Term image $id: $html");
}
$cover = get_default_post_thumbnail(1, ['src' => wp_get_attachment_url(56), 'attach_id' => 56, 'width' => 100, 'height' => 100, 'crop' => true]);
$expected = $mode === 'real' ? md5(wp_get_attachment_url(34)) : ($hasImg ? 'generated-34' : 'native-34-full');
check(str_contains($cover, $expected), 'Stored cover must win over caller source');
check(get_default_post_thumbnail(2) === '', 'Missing cover must be empty');
$preview = (new classes\TermThumbnail())->fill_image_column('', 'thumbnail', 1);
check($hasLink ? str_starts_with($preview, '<a data-rel="lightcase" href="https://example.test/12.png">') : $preview === 'native-12-90x90', "Admin preview: $preview");
$placeholder = get_term_thumbnail(['term_id' => 2]);
check($hasImg ? str_contains($placeholder, 'https://example.test/stub.png') : $placeholder === null, 'Placeholder availability');
check(! get_term_thumbnail(['term_id' => 2, 'show_placeholder' => false]), 'Opt out of placeholder');
$options['no_photo_url'] = '78';
check($hasImg ? str_contains(get_term_thumbnail(['attach_id' => 99]), 'https://example.test/78.png') : true, 'Attachment-based stub on generation failure');
$options['no_stub'] = true;
check(! get_term_thumbnail(['term_id' => 2]), 'Generator disables stub');

printf("Passed thumbnail integration cases (%s).\n", $mode);
