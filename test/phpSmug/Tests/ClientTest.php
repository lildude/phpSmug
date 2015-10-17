<?php

namespace phpSmug\Tests;

use phpSmug\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup a few variables for use in later tests.
     */
    public function setup()
    {
        $this->APIKey = 'I-am-not-a-valid-APIKey-but-it-does-not-matter-for-this-test';
        $this->user = 'random-user';
        $this->OAuthSecret = 'I-am-not-a-valid-OAuthSecret-but-it-does-not-matter-for-this-test';
        $this->oauth_token = 'I-am-an-oauth-token';
        $this->oauth_token_secret = 'I-am-an-oauth-token-secret';
        $this->fauxSmugMugResponse = '{"Options": {"foo":"boo"}, "Response": {"ano":"bar"}}';
    }
    /**
     * @test
     */
    public function shouldNotHaveToPassHttpClientToConstructorWithDefaultOptionsSet()
    {
        $client = new Client($this->APIKey);

        $this->assertInstanceOf('GuzzleHttp\Client', $client->getHttpClient());
        $options = $client->getDefaultOptions();

        $this->assertInstanceOf('GuzzleHttp\Client', $client->getHttpClient());
        $this->assertEquals('https://api.smugmug.com/api/v2/', $options['base_uri']);
        $this->assertEquals($client->AppName.' using phpSmug/'.$client::VERSION, $options['headers']['User-Agent']);
        $this->assertEquals('application/json', $options['headers']['Accept']);
        $this->assertEquals(30, $options['timeout']);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function shouldThrowExceptionIfNoApikey()
    {
        $client = new Client();
    }

    /**
     * @test
     */
    public function shouldHaveOptionsSetInInstance()
    {
        $options = [
            'AppName' => 'Testing phpSmug',
            'OAuthSecret' => $this->OAuthSecret,
            '_verbosity' => 1,
            '_shorturis' => true,
            ];
        $client = new Client($APIKey, $options);

        $this->assertEquals($client->APIKey, $APIKey);
        $this->assertEquals($client->AppName, $options['AppName']);
        $this->assertEquals($client->_verbosity, $options['_verbosity']);
        $this->assertEquals($client->_shorturis, $options['_shorturis']);
        $this->assertEquals($client->OAuthSecret, $options['OAuthSecret']);
        $this->assertEquals($client->getRequestOptions()['headers']['User-Agent'], sprintf('Testing phpSmug using phpSmug/%s', $client::VERSION));
    }
}
