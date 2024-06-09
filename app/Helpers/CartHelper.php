<?php

namespace App\Helpers;

use App\Models\Front\Catalog\ProductOption;

class CartHelper
{

    /**
     * @param ProductOption $option
     *
     * @return string
     */
    public static function resolveItemOptionName(ProductOption $option): string
    {
        $default = $option->option()->first();

        if ($default) {
            if ($option->parent == 'option') {
                $top = $option->top()->first();

                if ($top) {
                    return $top->group . ': ' . $top->name . ' / ' . $default->group . ': ' . $default->name;
                }
            }

            return $default->group . ': ' . $default->name;
        }

        return '';
    }
}
