<?php

namespace Tests\Feature;

use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class PersistentRateLimitTest extends TestCase
{
    public function test_production_uses_persistent_limits_without_changing_the_legacy_default_cache(): void
    {
        foreach (['production', 'local', 'testing'] as $environment) {
            $cache = $this->cacheConfiguration($environment);
            $this->assertSame('array', $cache['default']);
            $this->assertSame($environment === 'production' ? 'file' : 'array', $cache['limiter']);
        }
    }

    public function test_separate_production_limiter_instances_share_the_request_budget(): void
    {
        $cache = $this->cacheConfiguration('production');
        $directory = sys_get_temp_dir().'/top-tim-rate-limit-'.bin2hex(random_bytes(10));
        $cache['stores']['file']['path'] = $directory;

        try {
            $first = $this->newRateLimiter($cache);
            $first->hit('password-reset:example-test', 60);

            // A new container represents the next request, without shared memory.
            $second = $this->newRateLimiter($cache);
            $this->assertNotSame($first, $second);
            $this->assertSame(1, $second->attempts('password-reset:example-test'));
            $this->assertTrue($second->tooManyAttempts('password-reset:example-test', 1));
            $second->clear('password-reset:example-test');
            $this->assertSame(0, $first->attempts('password-reset:example-test'));
        } finally {
            (new Filesystem)->deleteDirectory($directory);
        }
    }

    private function newRateLimiter(array $cache): RateLimiter
    {
        $container = new Container;
        $container->instance('config', new Repository(['cache' => $cache]));
        $container->instance('files', new Filesystem);
        (new CacheServiceProvider($container))->register();

        return $container->make(RateLimiter::class);
    }

    private function cacheConfiguration(string $environment): array
    {
        $values = ['APP_ENV' => $environment, 'CACHE_DRIVER' => 'array', 'CACHE_STORE' => null];
        $previous = [];
        foreach ($values as $key => $value) {
            $previous[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
            if ($value !== null) {
                $_ENV[$key] = $_SERVER[$key] = $value;
                putenv($key.'='.$value);
            }
        }

        try {
            return require config_path('cache.php');
        } finally {
            foreach ($previous as $key => [$env, $server, $process]) {
                unset($_ENV[$key], $_SERVER[$key]);
                if ($env !== null) {
                    $_ENV[$key] = $env;
                }
                if ($server !== null) {
                    $_SERVER[$key] = $server;
                }
                $process === false ? putenv($key) : putenv($key.'='.$process);
            }
        }
    }
}
