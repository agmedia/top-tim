<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireAdminAccessTest extends TestCase
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

        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        (new \CreateUsersTable())->up();
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
        (new \CreateBouncerTables())->up();
        Schema::create('security_probe_writes', function (Blueprint $table) {
            $table->id();
        });

        Livewire::component('security-admin-probe', AdminSecurityProbe::class);

        // Mount through an actual protected route so its signed snapshot carries
        // the original admin URL used by Livewire's persistent middleware.
        $route = new Route(['GET', 'HEAD'], '/admin/livewire-security-test', function () {
            return response(Livewire::mount('security-admin-probe'));
        });
        $route->middleware(['web', 'auth:sanctum', 'no.customers']);

        $routes = new RouteCollection();
        $routes->add($route);
        foreach (app('router')->getRoutes() as $existingRoute) {
            $routes->add($existingRoute);
        }
        app('router')->setRoutes($routes);
    }

    /** @dataProvider requestFormats */
    public function test_admin_component_cannot_write_after_its_user_loses_admin_access(string $accept): void
    {
        $user = User::factory()->create();
        $user->assign('admin');
        $this->actingAs($user);

        $payload = $this->snapshot($this->get('/admin/livewire-security-test')->assertOk()->getContent());

        $user->retract('admin');
        $user->assign('customer');

        $this->updateProbe($payload, $accept)->assertForbidden();
        $this->assertDatabaseCount('security_probe_writes', 0);
    }

    public static function requestFormats(): array
    {
        return [
            'JSON request' => ['application/json'],
            'HTML request cannot turn denial into an ignored redirect' => ['text/html'],
        ];
    }

    public function test_user_with_admin_access_can_still_use_the_component(): void
    {
        $user = User::factory()->create();
        $user->assign('admin');
        $this->actingAs($user);

        $payload = $this->snapshot($this->get('/admin/livewire-security-test')->assertOk()->getContent());

        $this->updateProbe($payload, 'application/json')->assertOk();
        $this->assertDatabaseCount('security_probe_writes', 1);
    }

    private function snapshot(string $html): string
    {
        preg_match('/wire:snapshot="([^"]+)"/', $html, $matches);

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    private function updateProbe(string $payload, string $accept)
    {
        return $this->post('/livewire/update', [
            'components' => [[
                'snapshot' => $payload,
                'updates' => [],
                'calls' => [['path' => '', 'method' => 'write', 'params' => []]],
            ]],
        ], ['X-Livewire' => 'true', 'Accept' => $accept]);
    }
}

class AdminSecurityProbe extends Component
{
    public function write(): void
    {
        DB::table('security_probe_writes')->insert(['id' => 1]);
    }

    public function render(): string
    {
        return '<div>Admin security probe</div>';
    }
}
