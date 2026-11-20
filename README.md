![Taxonomy Thumbnail for WordPress: a term image and its default post cover](docs/cover.png)

# Taxonomy Thumbnail

[Русская версия](README.ru.md)

Assign an image to a WordPress taxonomy term and, separately, a default cover for posts assigned to that term.

## Requirements

- WordPress 6.3+
- PHP 8.0+

## Installation

1. Download the plugin from [GitHub Releases](https://github.com/tikhomirov/wp-term-thumbnail-plugin/releases), or download the `master` ZIP for the code documented here if the latest release is older.
2. Extract it to `wp-content/plugins/wp-term-thumbnail-plugin` (without a suffix such as `-master`). If you manage your WordPress site with Git, you can also add the repository as a submodule at that path. Keep only one copy of the plugin.
3. Activate **Taxonomy Thumbnail** under **Plugins**.

## Usage

1. Select the taxonomies under **Settings → Media → Term Thumbnail Settings**. On Multisite, use **Network Admin → Settings → Term Thumbnail**. The default selection includes publicly queryable taxonomies.
2. Open the term editor for a selected taxonomy. Set **Taxonomy thumbnail** for the term image and **Default Post Taxonomy Thumbnail** for a cover that its posts can inherit. The two fields are independent.

| Term meta key | Value |
| --- | --- |
| `term_image_id` | Attachment ID of the term's own thumbnail |
| `default_cover` | Attachment ID of the term's default post cover |

## PHP API

Call these helpers after the plugin has loaded:

```php
$image_id = get_term_thumbnail_id($term_id);
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : false;
$cover_id = get_post_default_cover_id($post_id, ['category', 'my_taxonomy']);

// On a term archive, display the queried term's thumbnail.
the_term_thumbnail(['width' => 100, 'height' => 100]);
```

- `get_term_thumbnail_id($term_id)` returns the stored ID or `false`. `has_term_thumbnail($term_id)` checks for a stored ID, not whether the attachment still exists.
- `get_term_thumbnail($args)` returns the term image markup (or no image); use `term_id` to select a specific term or omit it on a term archive. `the_term_thumbnail($args)` echoes the result and uses the queried term on taxonomy archives. The result passes through the `get_term_thumbnail` filter.
- `get_default_post_thumbnail_id($term_id)` returns the term's cover ID or `null`. `get_default_post_thumbnail($term_id, $args)` renders the cover or returns an empty string; without Kama Thumbnail, it uses the full-size WordPress attachment image.
- `get_post_default_cover_id($post, $taxonomies)` returns the first stored cover ID or `null`. It checks the supplied taxonomies in order (default: `['category']`) and reads terms in the order returned by `get_the_terms()`. This helper does not check for a featured image or read the plugin's taxonomy settings.

## Fallback behavior

- **Term images:** When Kama Thumbnail is available, the plugin delegates rendering to it and supplies a stub URL if `show_placeholder` is enabled (the default). Without Kama Thumbnail, it uses `wp_get_attachment_image()` when an attachment ID is available and does not render a placeholder otherwise.
- **Post covers:** The plugin hooks into the `get_post_thumbnail` filter for the enabled taxonomies. It leaves existing markup alone and does not substitute a term cover when the caller supplied `attach_id` or the post has a featured-image ID, even if rendering produced no markup. Otherwise, it uses the first term cover that renders, checking enabled taxonomies and then their assigned terms in order. It does not set the post's featured image or hook into WordPress core's `post_thumbnail_html` filter.
- **Rank Math:** On singular pages other than the front page, the plugin can fill an empty Facebook or Twitter image list for a post without a featured image. It tries the inherited cover from the configured taxonomies first, then Rank Math's configured default image. It does not replace images Rank Math has already selected.

See the [changelog](CHANGELOG.md).
