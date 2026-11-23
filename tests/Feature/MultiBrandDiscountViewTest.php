<?php

namespace Tests\Feature;

use App\Models\CustomerDiscount;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class MultiBrandDiscountViewTest extends TestCase
{
    private string $views;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'hr', 'session.driver' => 'array']);
        app()->setLocale('hr');
        request()->setLaravelSession(app('session.store'));
        $this->views = '/tmp/top-tim-multi-brand-views-'.bin2hex(random_bytes(6));
        mkdir($this->views.'/back/layouts', 0700, true);
        file_put_contents($this->views.'/back/layouts/backend.blade.php', '<!doctype html><html><body>@yield("content")@stack("js_after")</body></html>');
        View::getFinder()->prependLocation($this->views);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->views);
        parent::tearDown();
    }

    public function test_edit_renders_one_required_multiple_brand_selector_with_existing_selections(): void
    {
        $xpath = $this->xpath($this->edit($this->discount(['brand_ids' => [7, 9]]), ['selectedBrandIds' => [7, 9]]));
        $select = $xpath->query('//select[@id="discount-brand"]')->item(0);
        $this->assertSame('brand_ids[]', $select->getAttribute('name'));
        $this->assertTrue($select->hasAttribute('multiple'));
        $this->assertTrue($select->hasAttribute('required'));
        $this->assertSame('Odaberite brendove', $select->getAttribute('data-placeholder'));
        $this->assertSame(['7', '9'], $this->selected($xpath));
        $this->assertSame(0, $xpath->query('//select[@name="brand_id"]')->length);
        $this->assertSame('Brendovi', $xpath->query('//label[@for="discount-brand"]')->item(0)->textContent);
        $this->assertStringContainsString('Količine se zbrajaju zasebno za svaki brend.', $xpath->query('//*[@id="discount-brands-help"]')->item(0)->textContent);
    }

    public function test_validation_array_selection_takes_priority_over_saved_and_legacy_ids(): void
    {
        session()->flashInput(['brand_ids' => ['13', '7'], 'brand_id' => '9']);
        $xpath = $this->xpath($this->edit($this->discount(['brand_ids' => [7, 9]]), ['selectedBrandIds' => [7, 9]]));
        $this->assertSame(['7', '13'], $this->selected($xpath));
        session()->flashInput(['brand_ids' => []]);
        $this->assertSame([], $this->selected($this->xpath($this->edit($this->discount(['brand_ids' => [7, 9]])))));
    }

    public function test_legacy_saved_brand_and_legacy_validation_input_are_preserved(): void
    {
        $legacy = $this->discount(['brand_ids' => null, 'brand_id' => 7]);
        $this->assertSame(['7'], $this->selected($this->xpath($this->edit($legacy))));
        session()->flashInput(['brand_id' => '9']);
        $this->assertSame(['9'], $this->selected($this->xpath($this->edit($legacy))));
    }

    public function test_new_form_starts_without_a_brand_and_preserves_everyone_target_and_tiers(): void
    {
        $discount = new CustomerDiscount(['applies_to_everyone' => true]);
        $xpath = $this->xpath($this->edit($discount, ['selectedBrandIds' => []]));
        $this->assertSame([], $this->selected($xpath));
        $this->assertSame('everyone', $xpath->query('//select[@name="target"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertTrue($xpath->query('//select[@name="user_id"]')->item(0)->hasAttribute('disabled'));
        $this->assertTrue($xpath->query('//select[@name="user_group_id"]')->item(0)->hasAttribute('disabled'));
        $this->assertSame('10', $xpath->query('//input[@name="tiers[0][min_quantity]"]')->item(0)->getAttribute('value'));
        $this->assertSame('8', $xpath->query('//input[@name="tiers[0][percentage]"]')->item(0)->getAttribute('value'));
    }

    public function test_index_renders_all_preloaded_brand_names_and_safe_legacy_fallback_without_queries(): void
    {
        $discounts = new LengthAwarePaginator([
            $this->discount(['id' => 11, 'brand_ids' => [7, 9, 13]]),
            $this->discount(['id' => 12, 'brand_ids' => null, 'brand_id' => 7]),
        ], 2, 30, 1);
        DB::connection()->enableQueryLog();
        DB::flushQueryLog();
        $html = view('back.discount.index', ['discounts' => $discounts,
            'brandLabels' => [7 => 'Zeus', 9 => '<b>Macron</b>'], 'errors' => new ViewErrorBag()])->render();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $xpath = $this->xpath($html);
        $brands = $xpath->query('//tbody/tr/td[2]');
        $this->assertStringContainsString('Zeus', $brands->item(0)->textContent);
        $this->assertStringContainsString('<b>Macron</b>', $brands->item(0)->textContent);
        $this->assertStringContainsString('Obrisani brend #13', $brands->item(0)->textContent);
        $this->assertStringContainsString('Zeus', $brands->item(1)->textContent);
        $this->assertSame(0, $xpath->query('//tbody/tr/td[2]/b')->length);
        $this->assertSame([], $queries);
    }

    private function edit(CustomerDiscount $discount, array $extra = []): string
    {
        return view('back.discount.edit', array_replace([
            'discount' => $discount, 'selectedUser' => null, 'groups' => collect(),
            'brands' => collect(array_map(fn ($id, $title) => (object) ['id' => $id, 'translation' => (object) ['title' => $title]], [7, 9, 13], ['Zeus', 'Macron', 'Joma'])),
            'levels' => [['id' => 11, 'min_quantity' => 10, 'percentage' => 8, 'active' => true]],
            'errors' => new ViewErrorBag(),
        ], $extra))->render();
    }

    private function discount(array $attributes = []): CustomerDiscount
    {
        $discount = new CustomerDiscount(array_replace(['applies_to_everyone' => true, 'brand_id' => 7], $attributes));
        $discount->id = $attributes['id'] ?? 11;
        $discount->exists = true;
        $discount->setRelation('levels', collect([(object) ['min_quantity' => 10, 'percentage' => 8, 'active' => true]]));
        return $discount;
    }

    private function selected(\DOMXPath $xpath): array
    {
        return array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($xpath->query('//select[@id="discount-brand"]/option[@selected]')));
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $before = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        return new \DOMXPath($document);
    }
}
