<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_are_sent_from_the_home_page_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
