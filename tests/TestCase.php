<?php

namespace Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Panel Filament harus aktif agar test resource tidak menemui panel null.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }
}
