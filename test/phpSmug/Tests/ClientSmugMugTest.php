<?php

/**
 * This series of tests actually checks we're working correctly by interacting
 * with SmugMug's API.  All options are provided via environment variables to
 * prevent accidental committing to the repository.
 */
namespace phpSmug\Tests;

use phpSmug\Client;

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
     *
     * Test unauthenticated GET
     */
    public function shouldGetPublicUserInfo()
    {
        $this->checkEnvVars();
        $client = new Client(getenv('APIKEY'));
    }

    /**
     * @test
     *
     * Tests POST
     */
    public function shouldCreateNewAlbum()
    {
        $this->checkEnvVars();

        $options = [
            'AppName' => 'phpSmug Unit Testing',
            'OAuthSecret' => getenv('OAUTH_SECRET'),
            '_verbosity' => 1,
            '_shorturis' => true,
        ];
        $client = new Client(getenv('APIKEY'), $options);
        $client->setToken(getenv('OAUTH_TOKEN'), getenv('OAUTH_TOKEN_SECRET'));
    }

    /**
     * @test
     * @depends shouldCreateNewAlbum
     *
     * Tests PUT
     */
    public function shouldModifyNewlyCreatedAlbum()
    {
        $this->checkEnvVars();
    }

    /**
     * @test
     * @depends shouldModifyNewlyCreatedAlbum
     *
     * Tests UPLOAD
     */
    public function shouldUploadPictureToNewlyCreatedAlbum()
    {
        $this->checkEnvVars();
    }

    /**
     * @test
     * @depends shouldUploadPictureToNewlyCreatedAlbum
     *
     * Tests GET
     */
    public function shouldGetNewlyCreatedAlbumWithUploadedPicture()
    {
        $this->checkEnvVars();
    }

    /**
     * @test
     * @depends shouldGetNewlyCreatedAlbumWithUploadedPicture
     * Tests DELETE
     */
    public function shouldDeleteNewlyCreatedAlbumWithUploadedPicture()
    {
        $this->checkEnvVars();
    }

    /**
     * @test
     *
     * Tests OPTIONS
     */
    public function shouldGetInfoAboutMethod()
    {
        $this->checkEnvVars();
    }
}
