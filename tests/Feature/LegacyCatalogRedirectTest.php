<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class LegacyCatalogRedirectTest extends TestCase
{
    /** @dataProvider legacyRequests */
    public function test_verified_legacy_urls_redirect_permanently(string $method, string $source, string $destination): void
    {
        $this->call($method, '/' . $source)
            ->assertStatus(301)
            ->assertRedirect(url($destination));
    }

    public static function legacyRequests(): array
    {
        $requests = [];

        foreach (require __DIR__ . '/../../config/seo_redirects.php' as $source => $destination) {
            $requests[] = ['GET', $source, $destination];
            $requests[] = ['HEAD', $source, $destination];
        }

        return $requests;
    }

    public function test_query_parameters_do_not_change_the_redirect_destination(): void
    {
        $this->get('/macron?cat=old-category&utm_source=newsletter')
            ->assertStatus(301)
            ->assertRedirect(url('/brand/macron') . '?cat=old-category&utm_source=newsletter');
    }

    public function test_legacy_urls_with_a_trailing_slash_redirect_directly(): void
    {
        $this->get('/zeus/')
            ->assertStatus(301)
            ->assertRedirect(url('/brand/zeus'));
    }

    public function test_unverified_urls_are_not_redirected(): void
    {
        foreach (['test', 'unknown-brand', 'macron/missing-category', 'zeus/missing-category'] as $path) {
            $route = Route::getRoutes()->match(Request::create('/' . $path));

            $this->assertSame('catalog.route', $route->getName());
        }
    }

    public function test_redirects_stay_on_site_and_do_not_form_chains_or_loops(): void
    {
        $redirects = config('seo_redirects');

        foreach ($redirects as $source => $destination) {
            $this->assertNotSame('', $source);
            $this->assertSame(trim($source, '/'), $source);
            $this->assertStringNotContainsString('{', $source);
            $this->assertStringStartsWith('/', $destination);
            $this->assertStringStartsNotWith('//', $destination);
            $this->assertNull(parse_url($destination, PHP_URL_HOST));
            $this->assertNull(parse_url($destination, PHP_URL_SCHEME));
            $this->assertNull(parse_url($destination, PHP_URL_QUERY));
            $this->assertArrayNotHasKey(trim($destination, '/'), $redirects);
        }
    }

    public function test_unknown_paths_return_a_real_404_response(): void
    {
        View::shouldReceive('share')->andReturnNull();
        View::shouldReceive('addLocation')->andReturnNull();
        View::shouldReceive('make')->once()->with('front.404', [])->andReturn('<h1>404</h1>');

        $this->get('/missing/path/with/more/than/four/segments')
            ->assertNotFound()
            ->assertSee('404');
    }
}
