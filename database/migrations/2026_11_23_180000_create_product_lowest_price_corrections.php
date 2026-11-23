<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_lowest_price_corrections', function (Blueprint $table) {
            $table->id();
            // Audit survives removal of a catalogue product or staff account.
            $table->unsignedBigInteger('product_id');
            $table->string('mode', 10);
            $table->decimal('price', 13, 2)->nullable();
            $table->text('reason');
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at');
            $table->timestamp('expires_at')->nullable();
            $table->index(['product_id', 'id']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('product_lowest_price_corrections')
            && DB::table('product_lowest_price_corrections')->exists()) {
            throw new RuntimeException('Audit ispravaka najniže cijene nije prazan; povrat migracije je zaustavljen.');
        }
        Schema::dropIfExists('product_lowest_price_corrections');
    }
};
