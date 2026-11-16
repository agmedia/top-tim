<?php

namespace App\Helpers;

class SizeOrder
{
    public static function key(string $title): array
    {
        $size = strtoupper(preg_replace('/\s+/u', '', trim($title)));
        if (is_numeric(str_replace(',', '.', $size))) {
            return [0, (float) str_replace(',', '.', $size), $size];
        }
        $letters = ['6XS', '5XS', '4XS', '3XS', '2XS', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL'];
        if (preg_match('/^(X{2,6})(S|L)$/', $size, $matches)) {
            $size = strlen($matches[1]) . 'X' . $matches[2];
        }
        $position = array_search($size, $letters, true);
        return $position !== false ? [1, $position, $size] : [2, 0, $size];
    }
}
