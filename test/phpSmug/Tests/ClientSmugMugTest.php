<?php

namespace phpSmug\Tests;

use phpSmug\Client;
use GuzzleHttp\Client as GuzzleClient;

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
     * Test authenticated GET
     */
    public function shouldGetFullUserInfo()
    {
        $this->checkEnvVars();
        $response = $this->client->get('!authuser');
        $this->assertTrue(is_object($response));
        $this->assertEquals('Full', $response->User->ResponseLevel);
        $this->assertEquals('colinseymour', $response->User->NickName);
    }

    /**
     * @test
     *
     * Tests POST by creating a new album
     */
    public function shouldCreateNewAlbum()
    {
        $this->checkEnvVars();

        $uniqid = uniqid('UnitTesting-');
        $options = [
            'NiceName' => $uniqid,
            'Title' => 'New Album from unit testing phpSmug',
            'Privacy' => 'Private',
        ];
        $response = $this->client->post('folder/user/colinseymour/Other!albums', $options);
        $this->assertTrue(is_object($response));
        $this->assertEquals($options['NiceName'], $response->Album->NiceName);
        $this->assertEquals($options['Title'], $response->Album->Title);
        $this->assertEquals($options['Privacy'], $response->Album->Privacy);

        return $response->Album->Uri;
    }

    /**
     * @test
     * @depends shouldCreateNewAlbum
     *
     * Tests PATCH by modifying the previously created album.
     */
    public function shouldModifyNewlyCreatedAlbum($album_uri)
    {
        $this->checkEnvVars();

        $options = [
            'Title' => 'New Album from unit testing phpSmug (UPDATED)',
            'Description' => 'This album has been updated.',
        ];
        $response = $this->client->patch($album_uri, $options);
        $this->assertTrue(is_object($response));
        $this->assertEquals($options['Title'], $response->Album->Title);
        $this->assertEquals($options['Description'], $response->Album->Description);

        return $response->Album->Uri;
    }

    /**
     * @test
     * @depends shouldModifyNewlyCreatedAlbum
     *
     * Tests upload()
     */
    public function shouldUploadPictureToNewlyCreatedAlbum($album_uri)
    {
        $this->checkEnvVars();

        $options = [
            'Caption' => 'This is the phpSmug logo.',
            'FileName' => 'phpSmug-logo.png',
            'Hidden' => false,
            'Keywords' => 'test; logo; phpSmug',
            'Title' => 'I am a test image',
        ];
        $response = $this->client->upload($album_uri, 'examples/phpSmug-logo.png', $options);
        $this->assertTrue(is_object($response));
        $this->assertObjectHasAttribute('Image', $response);

        return $album_uri;
    }

    /**
     * @test
     * @depends shouldUploadPictureToNewlyCreatedAlbum
     * @expectedException GuzzleHttp\Exception\ClientException
     * @expectedExceptionMessage 404 Not Found
     *
     * Tests that we really can't access the private image
     */
    public function shouldFailToGetPrivateImage($album_uri)
    {
        $this->checkEnvVars();
        $thumbnail_url = $this->client->get($album_uri.'!images')->AlbumImage[0]->ThumbnailUrl;
        $client = new GuzzleClient();
        $client->get($thumbnail_url);
    }

    /**
     * @test
     * @depends shouldUploadPictureToNewlyCreatedAlbum
     *
     * Tests signResource()
     */
    public function shouldGetPrivateImageWithSignedUrl($album_uri)
    {
        $this->checkEnvVars();
        $thumbnail_url = $this->client->get($album_uri.'!images')->AlbumImage[0]->ThumbnailUrl;
        $signed_thumbnail_url = $this->client->signResource($thumbnail_url);
        $client = new GuzzleClient();
        $client->get($signed_thumbnail_url);

        return $album_uri;
    }

    /**
     * @test
     * @depends shouldGetPrivateImageWithSignedUrl
     *
     * Tests DELETE
     */
    public function shouldDeleteNewlyCreatedAlbumWithUploadedPicture($album_uri)
    {
        $this->checkEnvVars();
        $response = $this->client->delete($album_uri);
        $this->assertTrue(is_object($response));
        $this->assertEquals('Album', $response->Locator);
        $this->assertEquals($album_uri.'?_shorturis=1&_verbosity=1', $response->Uri);
    }

    /**
     * @test
     *
     * Tests OPTIONS
     */
    public function shouldGetInfoAboutMethod()
    {
        $this->checkEnvVars();
        $options = $this->client->options('user/colinseymour');
        $this->assertObjectHasAttribute('Output', $options);
        $this->assertTrue(is_array($options->Output));
        $this->assertObjectNotHasAttribute('Response', $options);
    }
}
