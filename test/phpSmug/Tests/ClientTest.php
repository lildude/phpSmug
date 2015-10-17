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
    public function shouldInstantiateClientWithDefaultOptionsWithoutCallingClientNew()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        $client = Client::getHttpClient(); # Still not sure how to do this.
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
        $client = new Client($this->APIKey, $options);

        $this->assertEquals($this->APIKey, $client->APIKey);
        $this->assertEquals($options['AppName'], $client->AppName);
        $this->assertEquals($options['_verbosity'], $client->_verbosity);
        $this->assertEquals($options['_shorturis'], $client->_shorturis);
        $this->assertEquals($options['OAuthSecret'], $client->OAuthSecret);
        $this->assertEquals(sprintf('Testing phpSmug using phpSmug/%s', $client::VERSION), $client->getDefaultOptions()['headers']['User-Agent']);
    }

    /**
     * @test
     */
    public function shouldSetAndGetOAuthTokens()
    {
        $client = new Client($this->APIKey);
        $client->setToken($this->oauth_token, $this->oauth_token_secret);

        list($oauth_token, $oauth_token_secret) = $client->getToken();

        $this->assertEquals($this->oauth_token, $oauth_token);
        $this->assertEquals($this->oauth_token_secret, $oauth_token_secret);
    }

    /**
     * @test
     */
    public function shouldHaveAPIKeyInQuery()
    {
        $client = new Client($this->APIKey);

        $options = $client->getDefaultOptions();

        $this->assertEquals($this->APIKey, $options['query']['APIKey']);
    }
    }
}
