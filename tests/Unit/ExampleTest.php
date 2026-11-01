<?php

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
<<<<<<< HEAD
    public function test_basic_example(): void
=======
    public function test_halaman_utama_mengalihkan_tamu_ke_login(): void
>>>>>>> 97273f945f0e085eb6ccceff1bb4058df17dfc78
    {
        $this->get('/')->assertRedirect('/login');
    }
}