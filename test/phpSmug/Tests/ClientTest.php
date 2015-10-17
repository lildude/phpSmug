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

    /**
     * @test
     */
    public function shouldGetReasonPhrase()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar']),  # We don't need a body so we don't set one.
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);

        $r = $client->get('user/'.$this->user);

        $this->assertEquals('OK', $client->getReasonPhrase());
    }

    /**
     * @test
     */
    public function shouldGetHeaders()
    {
        $mock = new MockHandler([
            # We don't care about the body for this test, so we don't set it.
            new Response(200, ['X-Foo' => 'Bar']),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);

        $r = $client->get('user/'.$this->user);

        $this->assertArrayHasKey('X-Foo', $client->getHeaders());
    }

    /**
     * @test
     */
    public function shouldGetStatusCode()
    {
        $mock = new MockHandler([
            # We don't care about headers or body for this test so we don't set them.
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);

        $r = $client->get('user/'.$this->user);

        $this->assertEquals('200', $client->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldReturnUntouchedResponse()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        $mock = new MockHandler([
            # We don't care about headers for this test so we don't set them.
            new Response(200, [], $this->fauxSmugMugResponse), # TODO: Populate with JSON that resembles the full normal response we get.
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);
        $client->get('user/'.$this->user);
        $decoded_response = (json_decode((string) $client->getResponse()->getBody()));

        $this->assertArrayHasKey('ano', $decoded_response->Response);
        $this->assertEquals('bar', $decoded_response->Response->ano);
    }
    }
}
