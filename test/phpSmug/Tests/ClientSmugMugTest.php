<?php

/**
 * This series of tests actually checks we're working correctly by interacting
 * with SmugMug's API.  All options are provided via environment variables to
 * prevent accidental committing to the repository.
 */
namespace phpSmug\Tests;

use phpSmug\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

class ClientSmugMugTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A quick handy feature to ensure we don't attempt to run any tests if these
     * env vars aren't set.
     */
    public function checkEnvVars()
    {
        foreach (['APIKEY', 'OAUTH_SECRET', 'OAUTH_TOKEN', 'OAUTH_TOKEN_SECRET'] as $env_var) {
            if (empty(getenv($env_var))) {
                $this->markTestSkipped("Environment variable $env_var not set.");

                return;
            }
        }
    }

    /**
     * @test
     */
    public function shouldCreateNewAlbum()
    {
        $this->checkEnvVars();

    }
}
