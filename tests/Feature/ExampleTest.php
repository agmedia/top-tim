<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_login_form_renders_with_the_current_jetstream_components(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }
}
