<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_discounts', function (Blueprint $table) {
            $table->boolean('applies_to_everyone')->default(false);
            $table->index(['applies_to_everyone', 'active', 'brand_id'], 'customer_discounts_everyone_active_brand_index');
        });
    }

    public function down(): void
    {
        if (DB::table('customer_discounts')->where('applies_to_everyone', true)->exists()) {
            throw new RuntimeException('Postoje popusti za sve kupce; uklanjanje oznake Svima je zaustavljeno.');
        }
        Schema::table('customer_discounts', function (Blueprint $table) {
            $table->dropIndex('customer_discounts_everyone_active_brand_index');
            $table->dropColumn('applies_to_everyone');
        });
    }
};
