# Blog / Artykuły

## Źródło danych

Publiczny blog `pnedu` korzysta z tabeli `articles` w bazie `pneadm`.

Model `App\Models\Article` ma ustawione:

```php
protected $connection = 'pneadm';
```

## Trasy publiczne

- `/blog` - lista opublikowanych artykułów.
- `/blog/{slug}` - pojedynczy artykuł.

Widoczne są tylko rekordy:

- ze statusem `published`,
- z ustawionym `published_at`,
- z datą publikacji nie późniejszą niż aktualny czas,
- bez `deleted_at`.

## SEO

Lista bloga ma własne `title`, `meta_description` i canonical.

Pojedynczy artykuł używa:

- `meta_title`, a gdy go brakuje: `title`,
- `meta_description`, a gdy go brakuje: `excerpt` lub fragment treści,
- canonical `blog.show`.

`SeoController` dodaje opublikowane artykuły do dynamicznej sitemapy.

## Komentarze

Etap 1 nie uruchamia publicznych komentarzy. Pole `comments_enabled` jest tylko przygotowaniem pod przyszły etap moderacji i ochrony antyspamowej.
