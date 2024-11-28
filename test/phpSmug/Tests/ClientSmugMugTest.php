<?php

namespace phpSmug\Tests;

use phpSmug\Client;
use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;

class ClientSmugMugTest extends TestCase
{
    protected const DEFAULT_NICKNAME = 'colinseymour';
    protected const DEFAULT_FOLDER = 'Other';
    protected const APP_NAME = 'phpSmug Unit Testing';

    protected string $nickname;
    protected string $folder;

    public function setUp(): void
    {
        $this->nickname = getenv('NICKNAME') ?? self::DEFAULT_NICKNAME;
        $this->folder = getenv('FOLDER') ?? self::DEFAULT_FOLDER;

        $options = [
            'AppName' => self::APP_NAME,
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
     * Test unauthenticated GET
     */
    #[Test]
    public function shouldGetPublicUserInfo()
    {
        $this->checkEnvVars();
        $client = new \phpSmug\Client(getenv('APIKEY'), ['AppName' => self::APP_NAME]);
        $response = $client->get('user/' . $this->nickname);
        $this->assertTrue(is_object($response));
        $this->assertEquals('Public', $response->User->ResponseLevel);
        $this->assertEquals($this->nickname, $response->User->NickName);
    }

    /**
     * Test authenticated GET
     */
    #[Test]
    public function shouldGetFullUserInfo()
    {
        $this->checkEnvVars();
        $response = $this->client->get('!authuser');
        $this->assertTrue(is_object($response));
        $this->assertEquals('Full', $response->User->ResponseLevel);
        $this->assertEquals($this->nickname, $response->User->NickName);
    }

    /**
     * Tests POST by creating a new album
     */
    #[Test]
    public function shouldCreateNewAlbum()
    {
        $this->checkEnvVars();

        $uniqid = uniqid('UnitTesting-');
        $options = [
            'NiceName' => $uniqid,
            'Title' => 'New Album from unit testing phpSmug',
            'Privacy' => 'Private',
        ];
        $response = $this->client->post('folder/user/' . $this->nickname . '/' . $this->folder . '/!albums', $options);
        $this->assertTrue(is_object($response));
        $this->assertEquals($options['NiceName'], $response->Album->NiceName);
        $this->assertEquals($options['Title'], $response->Album->Title);
        $this->assertEquals($options['Privacy'], $response->Album->Privacy);

        return $response->Album->Uri;
    }

    /**
     * Tests PATCH by modifying the previously created album.
     */
    #[Test]
    #[Depends('shouldCreateNewAlbum')]
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
     * Tests upload()
     */
    #[Test]
    #[Depends('shouldModifyNewlyCreatedAlbum')]
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
        $this->assertObjectHasProperty('Image', $response);

        return $album_uri;
    }

    /**
     * Tests that we really can't access the private image
     */
    #[Test]
    #[Depends('shouldUploadPictureToNewlyCreatedAlbum')]
    public function shouldFailToGetPrivateImage($album_uri)
    {
        $this->checkEnvVars();
        $thumbnail_url = $this->client->get($album_uri . '!images')->AlbumImage[0]->ThumbnailUrl;

        $client = new GuzzleClient();

        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $this->expectExceptionMessage('404 Not Found');
        $client->get($thumbnail_url);
    }

    /**
     * Tests signResource()
     */
    #[Test]
    #[Depends('shouldUploadPictureToNewlyCreatedAlbum')]
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
     * Tests DELETE
     */
    #[Test]
    #[Depends('shouldGetPrivateImageWithSignedUrl')]
    public function shouldDeleteNewlyCreatedAlbumWithUploadedPicture($album_uri)
    {
        $this->checkEnvVars();
        $response = $this->client->delete($album_uri);
        $this->assertTrue(is_object($response));
        $this->assertEquals('Album', $response->Locator);
        $this->assertEquals($album_uri.'?_shorturis=1&_verbosity=1', $response->Uri);
    }

    /**
     * Tests OPTIONS
     */
    #[Test]
    public function shouldGetInfoAboutMethod()
    {
        $this->checkEnvVars();
        $options = $this->client->options('user/' . $this->nickname);
        $this->assertObjectHasProperty('Output', $options);
        $this->assertTrue(is_array($options->Output));
        $this->assertObjectNotHasProperty('Response', $options);
    }
}
