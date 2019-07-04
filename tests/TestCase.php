<?php

namespace LBHurtado\SimpleOMR\Tests;

use LBHurtado\SimpleOMR\SimpleOMRFacade;
use LBHurtado\SimpleOMR\SimpleOMRServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            SimpleOMRServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'SimpleOMR' => SimpleOMRFacade::class
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
//        $app['config']->set('engagespark.api_key', 'e333ee0937f093dbacc77db00dd5b48a199c4cc8');
//        $app['config']->set('engagespark.org_id', '7858');
    }
}