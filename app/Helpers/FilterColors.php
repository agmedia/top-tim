<?php

namespace App\Helpers;

class FilterColors
{
    private const PALETTE = [
        'white' => ['Bijela', 'White', '#ffffff'],
        'black' => ['Crna', 'Black', '#000000'],
        'gray' => ['Siva', 'Gray', '#808080'],
        'blue' => ['Plava', 'Blue', '#1959d1'],
        'red' => ['Crvena', 'Red', '#e52329'],
        'green' => ['Zelena', 'Green', '#168039'],
        'yellow' => ['Žuta', 'Yellow', '#ffdf00'],
        'orange' => ['Narančasta', 'Orange', '#ff8a00'],
        'purple' => ['Ljubičasta', 'Purple', '#8726ad'],
        'pink' => ['Ružičasta', 'Pink', '#ef64ad'],
        'brown' => ['Smeđa', 'Brown', '#88552e'],
        'beige' => ['Bež', 'Beige', '#dac49a'],
        'turquoise' => ['Tirkizna', 'Turquoise', '#30c5bf'],
        'other' => ['Ostale boje', 'Other colors', '#b8b8b8'],
    ];

    public static function group(iterable $options): array
    {
        $ids = [];
        foreach ($options as $option) {
            foreach (array_filter([$option->value, $option->value_opt]) as $value) {
                $color = self::color($value);
                $ids[$color][(int) $option->id] = (int) $option->id;
            }
        }

        $groups = [];
        foreach (self::PALETTE as $key => [$hr, $en, $hex]) {
            if (empty($ids[$key])) {
                continue;
            }
            $groups[] = [
                'id' => 'color-' . $key,
                'title' => current_locale() === 'hr' ? $hr : $en,
                'type' => 'color',
                'style' => 'background-color: ' . $hex,
                'option_ids' => array_values($ids[$key]),
            ];
        }

        return $groups;
    }

    private static function color(string $value): string
    {
        $value = strtolower(trim($value));
        $names = [
            'white' => '#ffffff', 'black' => '#000000', 'gray' => '#808080', 'grey' => '#808080',
            'silver' => '#c0c0c0', 'red' => '#ff0000', 'maroon' => '#800000', 'blue' => '#0000ff',
            'navy' => '#000080', 'green' => '#008000', 'lime' => '#00ff00', 'olive' => '#808000',
            'yellow' => '#ffff00', 'gold' => '#ffd700', 'orange' => '#ffa500', 'purple' => '#800080',
            'violet' => '#ee82ee', 'pink' => '#ffc0cb', 'fuchsia' => '#ff00ff', 'brown' => '#a52a2a',
            'beige' => '#f5f5dc', 'ivory' => '#fffff0', 'cyan' => '#00ffff', 'aqua' => '#00ffff',
            'teal' => '#008080', 'turquoise' => '#40e0d0',
        ];
        $value = $names[$value] ?? $value;
        if (preg_match('/^#([a-f0-9])([a-f0-9])([a-f0-9])$/', $value, $matches)) {
            $value = '#' . $matches[1] . $matches[1] . $matches[2] . $matches[2] . $matches[3] . $matches[3];
        }
        if (!preg_match('/^#[a-f0-9]{6}$/', $value)) {
            return 'other';
        }

        [$r, $g, $b] = array_map(fn ($hex) => hexdec($hex) / 255, str_split(substr($value, 1), 2));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        $lightness = ($max + $min) / 2;
        if ($max < 0.18) {
            return 'black';
        }
        if ($min > 0.92) {
            return 'white';
        }
        if ($delta < 0.06) {
            return 'gray';
        }

        if ($max === $r) {
            $hue = 60 * fmod(($g - $b) / $delta + 6, 6);
        } elseif ($max === $g) {
            $hue = 60 * (($b - $r) / $delta + 2);
        } else {
            $hue = 60 * (($r - $g) / $delta + 4);
        }

        if ($hue >= 20 && $hue < 65 && $lightness > 0.65 && $delta < 0.45) {
            return 'beige';
        }
        if ($hue >= 10 && $hue < 50 && $lightness < 0.45) {
            return 'brown';
        }
        if (($hue >= 320 || $hue < 15) && $lightness > 0.65) {
            return 'pink';
        }
        if ($hue < 15 || $hue >= 345) {
            return 'red';
        }
        if ($hue < 45) {
            return 'orange';
        }
        if ($hue < 70) {
            return 'yellow';
        }
        if ($hue < 165) {
            return 'green';
        }
        if ($hue < 195) {
            return 'turquoise';
        }
        if ($hue < 260) {
            return 'blue';
        }
        if ($hue < 295 || ($hue < 345 && $lightness < 0.45)) {
            return 'purple';
        }

        return 'pink';
    }
}
