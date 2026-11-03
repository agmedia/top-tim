<?php

namespace Tests\Feature;

use App\Helpers\Chart;
use App\Http\Controllers\Back\DashboardController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_status_id');
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->unsignedInteger('quantity');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
        });
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->string('role');
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->boolean('status');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @dataProvider dashboardDates */
    public function test_monthly_cards_use_the_current_year_and_match_chart_revenue(string $date): void
    {
        Carbon::setTestNow(Carbon::parse($date, 'Europe/Zagreb'));
        $monthStart = now()->startOfMonth();

        foreach (range(0, 8) as $status) {
            $this->order($monthStart->copy()->addSeconds($status), $status, $status * 10.11);
        }

        $this->order($monthStart->copy()->subYear(), 4, 9000.99);
        $this->order($monthStart->copy()->subYears(2), 4, 8000.88);
        $this->order($monthStart->copy()->subSecond(), 4, 501.55);
        $this->order($monthStart->copy()->addMonth(), 4, 601.66);

        $viewData = app(DashboardController::class)->index()->getData();
        $currentYear = json_decode($viewData['this_year'], true);
        $previousYear = json_decode($viewData['last_year'], true);
        $monthIndex = now()->month - 1;

        // Every status counts as an order; only statuses 1–4 contribute to revenue.
        $this->assertSame(9, $viewData['data']['this_month']);
        $this->assertSame('101.10', $viewData['data']['this_month_total']);
        $this->assertEqualsWithDelta(101.10, $currentYear[$monthIndex]['value'], 0.00001);
        $this->assertEqualsWithDelta(9000.99, $previousYear[$monthIndex]['value'], 0.00001);
        $this->assertCount(12, $currentYear);
        $this->assertCount(12, $previousYear);
    }

    public static function dashboardDates(): array
    {
        return [
            'reported November discrepancy' => ['2026-11-03 12:36:00'],
            'first day of January' => ['2027-01-01 12:00:00'],
            'last day of December' => ['2026-12-31 23:59:59'],
        ];
    }

    public function test_sales_chart_preserves_cents_when_summing_orders(): void
    {
        $orders = collect([
            (object) ['total' => '40.37'],
            (object) ['total' => '59.68'],
        ]);

        $chart = new Chart();
        $data = $chart->setDataByYear(collect(['11' => $orders]));

        $this->assertEqualsWithDelta(100.05, $chart->sumOvjere($orders), 0.00001);
        $this->assertEqualsWithDelta(100.05, $data[10]['value'], 0.00001);
        $this->assertSame(0, $data[0]['value']);
    }

    private function order(Carbon $createdAt, int $status, float $total): void
    {
        DB::table('orders')->insert([
            'order_status_id' => $status,
            'total' => $total,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
