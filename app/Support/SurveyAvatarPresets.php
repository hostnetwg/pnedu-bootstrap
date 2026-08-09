<?php

namespace App\Support;

/**
 * Przykładowe awatary rekomendacji (DiceBear Avataaars, self-hosted SVG).
 *
 * Źródło stylu: Avataaars (Pablo Stanley) przez DiceBear — darmowe do użytku komercyjnego.
 * @see https://www.dicebear.com/styles/avataaars/
 * @see https://avataaars.com/
 */
final class SurveyAvatarPresets
{
    public const NONE = 'none';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::definitions(), 'key');
    }

    public static function isValid(?string $key): bool
    {
        return $key !== null && $key !== '' && in_array($key, self::keys(), true);
    }

    public static function isNone(?string $key): bool
    {
        return $key === self::NONE || $key === '';
    }

    public static function publicPath(string $key): string
    {
        return 'images/avatars/'.$key.'.svg';
    }

    public static function url(string $key): string
    {
        return asset(self::publicPath($key));
    }

    public static function defaultKey(): string
    {
        return 'woman-straight-brown';
    }

    /**
     * Mapowanie starych kluczy (proste silhouetty) → nowe DiceBear.
     */
    public static function migrateLegacyKey(?string $key): ?string
    {
        return match ($key) {
            'teacher-f-1' => 'woman-straight-brown',
            'teacher-f-2' => 'woman-bob-black',
            'teacher-m-1' => 'man-short-flat',
            'teacher-m-2' => 'man-beard',
            'director-f-1' => 'woman-long-glasses',
            'director-m-1' => 'man-beard-glasses',
            'neutral-1' => 'man-bald',
            'neutral-2' => 'woman-short-waved',
            default => self::isValid($key) ? $key : null,
        };
    }

    /**
     * @return list<array{key: string, url: string, label: string, group: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (array $def) => [
                'key' => $def['key'],
                'url' => self::url($def['key']),
                'label' => $def['label'],
                'group' => $def['group'],
            ],
            self::definitions()
        );
    }

    /**
     * @return array<string, list<array{key: string, url: string, label: string, group: string}>>
     */
    public static function optionsByGroup(): array
    {
        $grouped = [];
        foreach (self::options() as $option) {
            $grouped[$option['group']][] = $option;
        }

        return $grouped;
    }

    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    private static function definitions(): array
    {
        return [
            // Kobiety
            ['key' => 'woman-straight-brown', 'label' => 'Długie, brązowe', 'group' => 'Kobiety'],
            ['key' => 'woman-straight-blonde', 'label' => 'Długie, blond', 'group' => 'Kobiety'],
            ['key' => 'woman-bob-black', 'label' => 'Bob, czarne', 'group' => 'Kobiety'],
            ['key' => 'woman-curly-auburn', 'label' => 'Kręcone', 'group' => 'Kobiety'],
            ['key' => 'woman-bun-red', 'label' => 'Kok, rude', 'group' => 'Kobiety'],
            ['key' => 'woman-big-hair', 'label' => 'Obfite włosy', 'group' => 'Kobiety'],
            ['key' => 'woman-short-waved', 'label' => 'Krótkie, falowane', 'group' => 'Kobiety'],
            ['key' => 'woman-long-glasses', 'label' => 'Długie + okulary', 'group' => 'Kobiety'],
            ['key' => 'woman-bob-glasses', 'label' => 'Bob + okulary', 'group' => 'Kobiety'],
            ['key' => 'woman-mia', 'label' => 'Ciemne + okulary', 'group' => 'Kobiety'],
            ['key' => 'woman-fro', 'label' => 'Afro', 'group' => 'Kobiety'],
            ['key' => 'woman-hijab', 'label' => 'Hijab', 'group' => 'Kobiety'],
            // Mężczyźni
            ['key' => 'man-short-flat', 'label' => 'Krótkie, gładkie', 'group' => 'Mężczyźni'],
            ['key' => 'man-short-round', 'label' => 'Krótkie, czarne', 'group' => 'Mężczyźni'],
            ['key' => 'man-blonde-sides', 'label' => 'Boki, blond', 'group' => 'Mężczyźni'],
            ['key' => 'man-shaggy', 'label' => 'Dłuższe + okulary', 'group' => 'Mężczyźni'],
            ['key' => 'man-glasses', 'label' => 'Kręcone + okulary', 'group' => 'Mężczyźni'],
            ['key' => 'man-beard', 'label' => 'Broda', 'group' => 'Mężczyźni'],
            ['key' => 'man-beard-glasses', 'label' => 'Broda + okulary', 'group' => 'Mężczyźni'],
            ['key' => 'man-gray-beard', 'label' => 'Siwa broda', 'group' => 'Mężczyźni'],
            ['key' => 'man-moustache', 'label' => 'Wąsy', 'group' => 'Mężczyźni'],
            ['key' => 'man-dreads', 'label' => 'Dready + wąsy', 'group' => 'Mężczyźni'],
            ['key' => 'man-bald', 'label' => 'Łysy', 'group' => 'Mężczyźni'],
            ['key' => 'man-bald-beard', 'label' => 'Łysy + broda', 'group' => 'Mężczyźni'],
        ];
    }
}
