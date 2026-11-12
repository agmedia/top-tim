<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Separate tables: existing catalog prices, orders and discounts are untouched.
        Schema::create('anchor_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->decimal('price', 15, 4);
            $table->date('reference_date');
            $table->string('status', 20)->default('pending')->index();
            $table->text('note');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('anchor_price_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->json('before')->nullable();
            $table->json('after');
            $table->text('reason');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('price_list_publications', function (Blueprint $table) {
            $table->id();
            $table->date('publication_date')->unique();
            $table->string('filename');
            $table->string('path');
            $table->string('sha256', 64);
            $table->unsignedInteger('product_count');
            $table->unsignedInteger('missing_barcodes')->default(0);
            $table->timestamp('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_publications');
        Schema::dropIfExists('anchor_price_revisions');
        Schema::dropIfExists('anchor_prices');
    }
};
