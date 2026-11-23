<?php

namespace Tests\Feature;

use App\Models\CustomerDiscount;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class EveryoneDiscountViewTest extends TestCase
{
    private string $views;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'hr', 'session.driver' => 'array']);
        app()->setLocale('hr');
        request()->setLaravelSession(app('session.store'));
        // Render the actual page without unrelated navigation queries or user data.
        $this->views = '/tmp/top-tim-everyone-views-'.bin2hex(random_bytes(6));
        mkdir($this->views.'/back/layouts', 0700, true);
        file_put_contents($this->views.'/back/layouts/backend.blade.php', '<!doctype html><html><body>@yield("content")@stack("js_after")</body></html>');
        View::getFinder()->prependLocation($this->views);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->views);
        parent::tearDown();
    }

    public function test_explicit_everyone_target_is_selected_with_both_private_selectors_disabled(): void
    {
        $html = $this->edit($this->discount(['applies_to_everyone' => true]));
        $xpath = $this->xpath($html);
        $this->assertSame('Svima', trim($xpath->query('//select[@name="target"]/option[@selected]')->item(0)->textContent));
        $this->assertSame('everyone', $xpath->query('//select[@name="target"]/option[@selected]')->item(0)->getAttribute('value'));
        foreach (['user', 'group'] as $target) {
            $select = $xpath->query('//select[@id="discount-'.$target.'"]')->item(0);
            $this->assertTrue($select->hasAttribute('disabled'));
            $this->assertFalse($select->hasAttribute('required'));
            $this->assertTrue($xpath->query('//*[@id="discount-'.$target.'-field"]')->item(0)->hasAttribute('hidden'));
        }
        $help = $xpath->query('//*[@id="discount-everyone-help"]')->item(0);
        $this->assertFalse($help->hasAttribute('hidden'));
        $this->assertSame('Vrijedi za goste i sve prijavljene kupce.', trim($help->textContent));
        $this->assertTrue($xpath->query('//select[@name="brand_ids[]"]')->item(0)->hasAttribute('required'));
        $this->assertSame('10', $xpath->query('//input[@name="tiers[0][min_quantity]"]')->item(0)->getAttribute('value'));
        $this->assertSame('8', $xpath->query('//input[@name="tiers[0][percentage]"]')->item(0)->getAttribute('value'));
    }

    public function test_new_and_legacy_unassigned_discounts_never_default_to_everyone(): void
    {
        foreach ([new CustomerDiscount(), $this->discount()] as $discount) {
            $xpath = $this->xpath($this->edit($discount));
            $this->assertSame('user', $xpath->query('//select[@name="target"]/option[@selected]')->item(0)->getAttribute('value'));
            $this->assertTrue($xpath->query('//select[@id="discount-user"]')->item(0)->hasAttribute('required'));
            $this->assertFalse($xpath->query('//select[@id="discount-user"]')->item(0)->hasAttribute('disabled'));
            $this->assertTrue($xpath->query('//*[@id="discount-everyone-help"]')->item(0)->hasAttribute('hidden'));
        }
    }

    public function test_existing_private_targets_and_validation_selection_are_preserved(): void
    {
        foreach (['user' => ['user_id' => 21], 'group' => ['user_group_id' => 3]] as $target => $attributes) {
            $xpath = $this->xpath($this->edit($this->discount($attributes)));
            $this->assertSame($target, $xpath->query('//select[@name="target"]/option[@selected]')->item(0)->getAttribute('value'));
            $this->assertTrue($xpath->query('//select[@id="discount-'.$target.'"]')->item(0)->hasAttribute('required'));
        }
        session()->flashInput(['target' => 'everyone']);
        $xpath = $this->xpath($this->edit($this->discount(['user_id' => 21])));
        $this->assertSame('everyone', $xpath->query('//select[@name="target"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertTrue($xpath->query('//select[@id="discount-user"]')->item(0)->hasAttribute('disabled'));
    }

    public function test_index_identifies_public_and_legacy_unassigned_sets_separately(): void
    {
        $public = $this->discount(['id' => 11, 'applies_to_everyone' => true]);
        $legacy = $this->discount(['id' => 12]);
        $discounts = new LengthAwarePaginator([$public, $legacy], 2, 30, 1);
        $html = view('back.discount.index', ['discounts' => $discounts, 'errors' => new ViewErrorBag()])->render();
        $xpath = $this->xpath($html);
        $targets = $xpath->query('//tbody/tr/td[1]');
        $this->assertStringContainsString('Svima (gosti i svi prijavljeni kupci)', $targets->item(0)->textContent);
        $this->assertStringContainsString('Bez korisnika ili grupe (nije javno)', $targets->item(1)->textContent);
        $this->assertStringNotContainsString('Svima', $targets->item(1)->textContent);
    }

    private function edit(CustomerDiscount $discount): string
    {
        return view('back.discount.edit', [
            'discount' => $discount,
            'selectedUser' => $discount->user_id ? (object) ['id' => 21, 'name' => 'Test kupac', 'email' => 'kupac@example.test'] : null,
            'groups' => collect([(object) ['id' => 3, 'status' => true, 'translation' => (object) ['title' => 'Test grupa']]]),
            'brands' => collect([(object) ['id' => 7, 'translation' => (object) ['title' => 'Test brend']]]),
            'levels' => [['id' => 11, 'min_quantity' => 10, 'percentage' => 8, 'active' => true]],
            'errors' => new ViewErrorBag(),
        ])->render();
    }

    private function discount(array $attributes = []): CustomerDiscount
    {
        $discount = new CustomerDiscount(array_replace(['id' => 11, 'user_id' => null, 'user_group_id' => null,
            'applies_to_everyone' => false, 'brand_id' => 7], $attributes));
        $discount->id = $attributes['id'] ?? 11;
        $discount->exists = true;
        $discount->setRelation('brand', (object) ['translation' => (object) ['title' => 'Test brend']]);
        $discount->setRelation('levels', collect([(object) ['min_quantity' => 10, 'percentage' => 8, 'active' => true]]));
        return $discount;
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
