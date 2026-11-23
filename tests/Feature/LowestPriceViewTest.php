<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LowestPriceViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'hr', 'session.driver' => 'array']);
        app()->setLocale('hr');
        request()->setLaravelSession(app('session.store'));
    }

    public function test_admin_gets_separate_patch_form_with_manual_and_automatic_actions_and_required_reason(): void
    {
        $html = $this->renderBlock(true);
        $xpath = $this->xpath($html);
        $forms = $xpath->query('//form');
        $this->assertSame(1, $forms->length);
        $form = $forms->item(0);
        $this->assertSame('lowest-price-form', $form->getAttribute('id'));
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame(route('products.lowest-price.update', ['product' => 42]), $form->getAttribute('action'));
        $this->assertSame('PATCH', $xpath->query('//input[@name="_method"]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//input[@name="_token"]')->length);
        $this->assertSame(1, $xpath->query('//button[@name="mode" and @value="manual"]')->length);
        $this->assertSame(1, $xpath->query('//button[@name="mode" and @value="automatic"]')->length);
        $reason = $xpath->query('//textarea[@name="reason"]')->item(0);
        $this->assertTrue($reason->hasAttribute('required'));
        $this->assertSame('3', $reason->getAttribute('minlength'));
        $this->assertSame('1000', $reason->getAttribute('maxlength'));
        $price = $xpath->query('//input[@name="price"]')->item(0);
        $this->assertSame('text', $price->getAttribute('type'));
        $this->assertSame('decimal', $price->getAttribute('inputmode'));
        $this->assertSame('45,80', $price->getAttribute('value'));
        $this->assertFalse($price->hasAttribute('required'), 'Automatic reset must not require a manual amount.');
        $this->assertStringContainsString('30 dana', $html);
        $this->assertStringContainsString('45,80 €', $html);
        $this->assertStringContainsString('39,00 €', $html);
        $this->assertStringContainsString('23.12.2026.', $html);
    }

    public function test_read_only_editor_sees_prices_and_history_without_write_form_or_action(): void
    {
        $html = $this->renderBlock(false);
        $xpath = $this->xpath($html);
        $this->assertSame(0, $xpath->query('//form|//input|//textarea|//button')->length);
        $this->assertStringNotContainsString('/lowest-price', $html);
        $this->assertStringContainsString('45,80 €', $html);
        $this->assertStringContainsString('39,00 €', $html);
        $this->assertStringContainsString('administratori', $html);
        $this->assertStringContainsString('Povijesni iznos', $html);
    }

    public function test_named_validation_errors_and_submitted_comma_amount_are_kept_without_rendering_html(): void
    {
        session()->flashInput(['mode' => 'manual', 'price' => '46,20', 'reason' => '<script>alert(1)</script>']);
        $errors = (new ViewErrorBag())
            ->put('lowestPrice', new MessageBag(['price' => 'ISPRAVAK_ERROR']))
            ->put('default', new MessageBag(['price' => 'PRODAJNA_ERROR']));
        $html = $this->renderBlock(true, [], $errors);
        $xpath = $this->xpath($html);
        $this->assertStringContainsString('ISPRAVAK_ERROR', $html);
        $this->assertStringNotContainsString('PRODAJNA_ERROR', $html);
        $this->assertSame('46,20', $xpath->query('//input[@name="price"]')->item(0)->getAttribute('value'));
        $this->assertSame('<script>alert(1)</script>', $xpath->query('//textarea[@name="reason"]')->item(0)->textContent);
        $this->assertSame(0, $xpath->query('//script')->length);
    }

    public function test_normal_product_validation_does_not_prefill_the_correction_from_the_selling_price(): void
    {
        session()->flashInput(['price' => '999,00', 'reason' => 'Not a correction']);
        $xpath = $this->xpath($this->renderBlock(true));
        $this->assertSame('45,80', $xpath->query('//input[@name="price"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//textarea[@name="reason"]')->item(0)->textContent);
    }

    public function test_no_recorded_price_renders_a_blank_amount_and_automatic_state(): void
    {
        $html = $this->renderBlock(true, ['automatic' => null, 'effective' => null,
            'correction' => null, 'history' => collect(), 'manual_active' => false]);
        $xpath = $this->xpath($html);
        $this->assertSame('', $xpath->query('//input[@name="price"]')->item(0)->getAttribute('value'));
        $this->assertStringContainsString('Primjenjuje se automatski izračun', $html);
        $this->assertSame(0, $xpath->query('//details')->length);
    }

    public function test_correction_block_is_included_after_the_main_product_form(): void
    {
        $template = file_get_contents(resource_path('views/back/catalog/product/edit.blade.php'));
        $start = strpos($template, '<form id="product-form"');
        $end = strpos($template, '</form>', $start);
        $include = strpos($template, "@include('back.catalog.product.lowest-price')");
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $this->assertNotFalse($include);
        $this->assertGreaterThan($end, $include, 'The correction form must never be nested in product-form.');
    }

    public function test_english_labels_and_audit_reasons_are_escaped(): void
    {
        app()->setLocale('en');
        $entry = $this->data()['correction'];
        $entry->reason = '<img src=x onerror=alert(1)>';
        $html = $this->renderBlock(true, ['correction' => $entry, 'history' => collect([$entry])]);
        $xpath = $this->xpath($html);
        $this->assertStringContainsString('Save lowest price', $html);
        $this->assertStringContainsString('Resume automatic calculation', $html);
        $this->assertSame(0, $xpath->query('//img')->length);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    private function renderBlock(bool $canCorrect, array $changes = [], ?ViewErrorBag $errors = null): string
    {
        return view('back.catalog.product.lowest-price', ['product' => (object) ['id' => 42],
            'lowestPriceData' => array_replace($this->data(), $changes),
            'canCorrectLowestPrice' => $canCorrect, 'errors' => $errors ?? new ViewErrorBag()])->render();
    }

    private function data(): array
    {
        $entry = (object) ['mode' => 'manual', 'price' => 45.8, 'reason' => 'Povijesni iznos',
            'actor_id' => 17, 'created_at' => '2026-11-23 16:00:00', 'expires_at' => '2026-12-23 16:00:00'];
        return ['automatic' => ['price' => 39, 'formatted' => '39,00 €'],
            'effective' => ['price' => 45.8, 'formatted' => '45,80 €'], 'correction' => $entry,
            'history' => collect([$entry]), 'manual_active' => true];
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
