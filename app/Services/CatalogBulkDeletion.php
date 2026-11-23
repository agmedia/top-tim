<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogBulkDeletion
{
    public function products(array $ids, User $actor): int
    {
        return DB::transaction(function () use ($ids, $actor) {
            $this->authorizeLockedActor($actor);
            $ids = $this->lockSelection('products', $ids);
            $categoryIds = DB::table('product_category')->whereIn('product_id', $ids)
                ->distinct()->pluck('category_id')->all();

            // Options can reference product images in the live schema.
            DB::table('product_option')->whereIn('product_id', $ids)->delete();
            DB::table('product_images_translations')->whereIn('product_image_id',
                DB::table('product_images')->select('id')->whereIn('product_id', $ids)
            )->delete();
            foreach (['product_images', 'product_category', 'product_attribute', 'product_translations'] as $table) {
                DB::table($table)->whereIn('product_id', $ids)->delete();
            }
            $deleted = DB::table('products')->whereIn('id', $ids)->delete();

            // Media files and order/price snapshots deliberately survive catalogue deletion.
            DB::afterCommit(function () use ($ids, $categoryIds) {
                foreach ($ids as $id) {
                    Helper::resolveCache('product')->forget((string) $id);
                }
                foreach ($categoryIds as $id) {
                    Helper::resolveCache('cat')->forget('cpc'.$id);
                    Helper::resolveCache('cat')->forget('scpc'.$id);
                }
                Helper::bumpActionCacheVersion();
            });

            return $deleted;
        });
    }

    public function actions(array $ids, User $actor): int
    {
        return DB::transaction(function () use ($ids, $actor) {
            $this->authorizeLockedActor($actor);
            $ids = $this->lockSelection('product_actions', $ids);
            $productIds = DB::table('products')->whereIn('action_id', $ids)
                ->orderBy('id')->lockForUpdate()->pluck('id')->all();
            DB::table('products')->whereIn('action_id', $ids)->update([
                'action_id' => 0, 'special' => null, 'special_from' => null, 'special_to' => null,
            ]);
            DB::table('product_actions_translations')->whereIn('product_action_id', $ids)->delete();
            $deleted = DB::table('product_actions')->whereIn('id', $ids)->delete();
            DB::afterCommit(function () use ($productIds) {
                foreach ($productIds as $id) {
                    Helper::resolveCache('product')->forget((string) $id);
                }
                Helper::bumpActionCacheVersion();
            });

            return $deleted;
        });
    }

    private function authorizeLockedActor(User $actor): void
    {
        $fresh = User::query()->whereKey($actor->id)->lockForUpdate()->first();
        $detail = $fresh?->details()->lockForUpdate()->first();
        abort_unless($fresh && $detail && $detail->status
            && $fresh->roles()->whereIn('name', ['superadmin', 'admin', 'editor'])->exists(), 403);
    }

    private function lockSelection(string $table, array $ids): array
    {
        $ids = array_map('intval', $ids);
        sort($ids, SORT_NUMERIC);
        $found = DB::table($table)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->pluck('id');
        if (!$ids || count($ids) > 50 || count(array_unique($ids)) !== count($ids)
            || min($ids) < 1 || $found->count() !== count($ids)) {
            throw ValidationException::withMessages(['ids' => 'Jedna ili više odabranih stavki više ne postoji. Osvježite popis i pokušajte ponovno.']);
        }

        return $ids;
    }
}
