# Taxonomy Thumbnail (wp-term-thumbnail-plugin)

WordPress-плагин для загрузки изображений терминов таксономий и обложек записей по умолчанию.

## Требования

- WordPress 6.3+
- PHP 8.0+

## Установка

### Через submodule (rwsite-ru)

Плагин подключается как git submodule в `wp-content/plugins/wp-term-thumbnail-plugin`.

### Вручную с GitHub

1. Скачайте [последний релиз](https://github.com/tikhomirov/wp-term-thumbnail-plugin/releases).
2. Распакуйте в `wp-content/plugins/wp-term-thumbnail-plugin` (именно так, **без** суффикса `-master`).
3. Активируйте в админке WordPress.

> Не держите две копии плагина (`wp-term-thumbnail-plugin` и `wp-term-thumbnail-plugin-master`) — это вызовет fatal error из‑за повторного объявления классов.

## Использование

```php
// ID миниатюры термина
$image_id = get_term_meta($term_id, 'term_image_id', true);

// URL миниатюры
$image_url = wp_get_attachment_image_url($image_id, 'thumbnail');

// Вывод миниатюры текущего термина
the_term_thumbnail(['width' => 100, 'height' => 100]);
```

Настройки поддерживаемых таксономий: **Настройки → Медиафайлы**.

## Changelog

См. [CHANGELOG.md](CHANGELOG.md).
