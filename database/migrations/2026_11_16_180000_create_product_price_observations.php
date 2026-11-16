<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_observations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 14, 4);
            $table->timestamp('observed_at');
            $table->timestamp('last_seen_at');
            $table->string('source', 40)->default('public_price_snapshot');
            $table->index(['product_id', 'id']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_observations');
    }
};
