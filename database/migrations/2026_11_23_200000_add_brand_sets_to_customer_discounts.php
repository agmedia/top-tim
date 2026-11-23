<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $needsIds = !Schema::hasColumn('customer_discounts', 'brand_ids');
        $needsKey = !Schema::hasColumn('customer_discounts', 'brand_key');
        if ($needsIds || $needsKey) {
            Schema::table('customer_discounts', function (Blueprint $table) use ($needsIds, $needsKey) {
                if ($needsIds) { $table->json('brand_ids')->nullable(); }
                if ($needsKey) { $table->string('brand_key', 64)->nullable(); }
            });
        }
        if (!Schema::hasIndex('customer_discounts', 'customer_discounts_brand_key_index')) {
            Schema::table('customer_discounts', fn (Blueprint $table) => $table->index('brand_key'));
        }
        // Resume a partial MySQL DDL/backfill without rewriting completed or new multi-brand sets.
        DB::table('customer_discounts')->where(fn ($query) => $query->whereNull('brand_ids')->orWhereNull('brand_key'))
            ->select('id', 'brand_id', 'brand_ids')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $values = $row->brand_ids === null ? [(int) $row->brand_id]
                        : json_decode($row->brand_ids, true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($values) || !$values) {
                        throw new RuntimeException('Postojeći odabir brendova nije valjan; dopuna metapodataka je zaustavljena.');
                    }
                    $values = array_values(array_unique(array_map('intval', $values)));
                    sort($values, SORT_NUMERIC);
                    $ids = json_encode($values);
                    DB::table('customer_discounts')->where('id', $row->id)->update([
                        'brand_ids' => $ids, 'brand_key' => hash('sha256', $ids),
                    ]);
                }
            });
    }

    public function down(): void
    {
        foreach (DB::table('customer_discounts')->whereNotNull('brand_ids')->orderBy('id')->cursor() as $row) {
            $ids = json_decode($row->brand_ids, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($ids) || count($ids) > 1) {
                throw new RuntimeException('Postoji popust s više brendova; uklanjanje odabranih brendova je zaustavljeno.');
            }
        }
        Schema::table('customer_discounts', function (Blueprint $table) {
            $table->dropIndex(['brand_key']);
            $table->dropColumn(['brand_ids', 'brand_key']);
        });
    }
};
