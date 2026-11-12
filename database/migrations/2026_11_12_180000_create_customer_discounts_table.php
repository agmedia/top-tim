<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('user_group_id')->nullable();
            $table->unsignedBigInteger('brand_id');
            $table->unsignedInteger('min_quantity');
            $table->decimal('percentage', 5, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'active', 'brand_id']);
            $table->index(['user_group_id', 'active', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_discounts');
    }
};
