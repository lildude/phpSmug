<?php

namespace phpSmug\Tests;

use phpSmug\Client;

class ClientSmugMugTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $options = [
            'AppName' => 'phpSmug Unit Testing',
            'OAuthSecret' => getenv('OAUTH_SECRET'),
            '_verbosity' => 1,
            '_shorturis' => true,
        ];

        $this->client = new \phpSmug\Client(getenv('APIKEY'), $options);
        $this->client->setToken(getenv('OAUTH_TOKEN'), getenv('OAUTH_TOKEN_SECRET'));
    }

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
        $client = new \phpSmug\Client(getenv('APIKEY'), ['AppName' => 'phpSmug Unit Testing']);
        $response = $client->get('user/colinseymour');
        $this->assertTrue(is_object($response));
        $this->assertEquals('Public', $response->User->ResponseLevel);
        $this->assertEquals('colinseymour', $response->User->NickName);
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
     */
    public function shouldFailToGetPrivateImage()
    {
        $this->checkEnvVars();
    }

    /**
     * @test
     * @depends shouldGetNewlyCreatedAlbumWithUploadedPicture
     *
     * Tests signResource()
     */
    public function shouldGetPrivateImageWithSignedUrl()
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
