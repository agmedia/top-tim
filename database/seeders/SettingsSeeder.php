<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $group_list = [
            0 => [
                'id' => 'product',
                'title' => 'Artikl'
            ],
            1 => [
                'id' => 'category',
                'title' => 'Kategorija'
            ],
            2 => [
                'id' => 'publisher',
                'title' => 'Nakladnik'
            ],
            3 => [
                'id' => 'author',
                'title' => 'Autor'
            ]
        ];

        $type_list = [
            0 => [
                'id' => 'P',
                'title' => 'Postotak'
            ],
            1 => [
                'id' => 'F',
                'title' => 'Fiksni'
            ]
        ];

        //
        DB::insert(
            "INSERT INTO `settings` (`user_id`, `code`, `key`, `value`, `json`, `created_at`, `updated_at`) VALUES
              (null, 'action', 'group_list', '" . collect($group_list)->toJson() . "', 1, NOW(), NOW()),
              (null, 'action', 'type_list', '" . collect($type_list)->toJson() . "', 1, NOW(), NOW()),
              (null, 'payment', 'list.cod', '[{\"title\":\"Gotovinom prilikom pouze\\u0107a\",\"code\":\"cod\",\"min\":\"10\",\"data\":{\"price\":\"5\",\"short_description\":\"Pla\\u0107anje gotovinom prilikom preuzimanja.\",\"description\":null},\"geo_zone\":\"1\",\"status\":true,\"sort_order\":\"2\"}]', 1, NOW(), NOW()),
              (null, 'payment', 'list.bank', '[{\"title\":\"Op\\u0107om uplatnicom \\/ Virmanom \\/ Internet bankarstvom\",\"code\":\"bank\",\"min\":null,\"data\":{\"price\":\"0\",\"short_description\":\"Uplatite direktno na na\\u0161 bankovni ra\\u010dun. Uputstva i uplatnice vam sti\\u017ee putem maila.\",\"description\":null},\"geo_zone\":null,\"status\":true,\"sort_order\":\"1\"}]', 1, NOW(), NOW()),
              (null, 'payment', 'list.pickup', '[{\"title\":\"Platite prilikom preuzimanja\",\"code\":\"pickup\",\"min\":null,\"data\":{\"price\":\"0\",\"short_description\":\"Platiti mo\\u017eete gotovinom ili karticama na POS ure\\u0111ajima\",\"description\":null},\"geo_zone\":\"1\",\"status\":true,\"sort_order\":\"0\"}]', 1, NOW(), NOW()),
              (null, 'currency', 'list', '[{\"id\":1,\"title\":\"Euro\",\"code\":\"EUR\",\"status\":false,\"main\":false,\"symbol_left\":\"\\u20ac\",\"symbol_right\":null,\"value\":\"0.13272280\",\"decimal_places\":\"2\"}]', 1, NOW(), NOW()),
              (null, 'language', 'list', '[{\"id\":1,\"title\":{\"hr\":\"Hrvatski\",\"en\":\"Croatian\"},\"code\":\"hr\",\"status\":true,\"main\":false},{\"id\":2,\"title\":{\"hr\":\"Engleski\",\"en\":\"English\"},\"code\":\"en\",\"status\":true,\"main\":false}]', 1, NOW(), NOW()),
              (null, 'order', 'statuses', '[{\"id\":1,\"title\":\"Novo\",\"sort_order\":\"0\",\"color\":\"info\"},{\"id\":2,\"title\":\"\\u010ceka uplatu\",\"sort_order\":\"1\",\"color\":\"warning\"},{\"id\":3,\"title\":\"Pla\\u0107eno\",\"sort_order\":\"3\",\"color\":\"success\"},{\"id\":4,\"title\":\"Poslano\",\"sort_order\":\"4\",\"color\":\"secondary\"},{\"id\":5,\"title\":\"Otkazano\",\"sort_order\":\"5\",\"color\":\"danger\"},{\"id\":6,\"title\":\"Vra\\u0107eno\",\"sort_order\":\"6\",\"color\":\"dark\"},{\"id\":7,\"title\":\"Odbijeno\",\"sort_order\":\"2\",\"color\":\"danger\"},{\"id\":8,\"title\":\"Nedovr\\u0161ena\",\"sort_order\":\"7\",\"color\":\"light\"}]', 1, NOW(), NOW()),
              (null, 'tax', 'list', '{\"1\":{\"id\":1,\"geo_zone\":\"1\",\"title\":\"PDV 25%\",\"rate\":\"25\",\"sort_order\":\"0\",\"status\":true}}', 1, NOW(), NOW()),
              (null, 'geo_zone', 'list', '{\"0\":{\"status\":true,\"title\":\"Hrvatska\",\"description\":null,\"state\":{\"2\":\"Croatia\"},\"id\":1},\"2\":{\"title\":\"World\",\"description\":null,\"id\":3,\"status\":true,\"state\":[]}}', 1, NOW(), NOW())"
        );
    }
}
