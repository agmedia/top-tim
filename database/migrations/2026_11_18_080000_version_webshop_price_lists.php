<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('price_list_publications', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1);
            $table->char('content_hash', 64)->nullable();
            $table->timestamp('replaced_at')->nullable()->index();
            $table->unique(['publication_date', 'revision']);
        });
        Schema::table('price_list_publications', fn (Blueprint $table) => $table->dropUnique(['publication_date']));

        // Existing files and their public IDs stay intact. The next publication replaced the previous one.
        $previous = null;
        foreach (DB::table('price_list_publications')->orderBy('publication_date')->get(['id', 'published_at']) as $publication) {
            if ($previous !== null) {
                DB::table('price_list_publications')->where('id', $previous)->update(['replaced_at' => $publication->published_at]);
            }
            $previous = $publication->id;
        }

        Schema::table('anchor_prices', function (Blueprint $table) {
            $table->decimal('net_quantity', 14, 6)->nullable();
            $table->string('measure_unit', 8)->nullable();
            $table->string('goods_barcode', 100)->nullable();
            $table->json('variant_prices')->nullable();
            $table->date('first_offered_on')->nullable();
            $table->string('reference_group', 15)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('price_list_publications')->where('revision', '>', 1)->exists()
            || DB::table('anchor_prices')->whereNotNull('net_quantity')->orWhereNotNull('goods_barcode')->orWhereNotNull('variant_prices')->orWhereNotNull('reference_group')->exists()) {
            throw new RuntimeException('Povrat bi uklonio evidentirane verzije cjenika ili podatke o pakiranju i barkodu.');
        }
        Schema::table('price_list_publications', function (Blueprint $table) {
            $table->unique('publication_date');
            $table->dropUnique(['publication_date', 'revision']);
            $table->dropIndex(['replaced_at']);
        });
        Schema::table('price_list_publications', fn (Blueprint $table) => $table->dropColumn(['revision', 'content_hash', 'replaced_at']));
        Schema::table('anchor_prices', fn (Blueprint $table) => $table->dropColumn(['net_quantity', 'measure_unit', 'goods_barcode', 'variant_prices', 'first_offered_on', 'reference_group']));
    }
};
