<?php

namespace AratKruglik\Monobank\Tests;

use AratKruglik\Monobank\MonobankServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            MonobankServiceProvider::class,
        ];
    }
}
