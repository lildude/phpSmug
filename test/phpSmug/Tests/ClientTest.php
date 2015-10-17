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

    /**
     * @test
     */
    public function shouldGetSmugMugMethodOptions()
    {
        $mock = new MockHandler([
            # We don't care about headers for this test so we don't set them.
            new Response(200, [], $this->fauxSmugMugResponse), # TODO: Populate with JSON that resembles the response we get.
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);

        $options = $client->options('user/'.$this->user);

        $this->assertEquals('boo', $options->foo);
        //$this->assertDoesNotHaveAttribute('Response', $options); # TODO: Need to negate the test too.
    }

    /**
     * @test
     */
    public function shouldExtractQueryFromURLAndSetQueryInRequest()
    {
        $mock = new MockHandler([
            new Response(200), # We don't care about headers or body for this test so we don't set them.
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);
        $response = $client->get('user/'.$this->user.'?_expand=UserProfile&_verbosity=2');

        $request_options = $client->getRequestOptions();

        $this->assertArrayHasKey('_expand', $request_options['query']);
        $this->assertEquals('UserProfile', $request_options['query']['_expand']);
        $this->assertArrayHasKey('_verbosity', $request_options['query']);
        $this->assertEquals(2, $request_options['query']['_verbosity']);
    }

    /**
     * @test
     */
    public function shouldSetQueryFromOptionsPassedOnRequest()
    {
        $mock = new MockHandler([
            new Response(200), # We don't care about headers or body for this test so we don't set them.
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);
        $options = [
            '_filter' => ['BioText', 'CoverImage'],  # TODO: Prevent encoding of commas.
            '_filteruri' => ['User'],
            '_shorturis' => true,
        ];
        $response = $client->get('user/'.$this->user, $options);

        $request_options = $client->getRequestOptions(); # TODO: If possible, need to get the actual URL as this is blindly trusting Guzzle to use these params.

        $this->assertArrayHasKey('_filter', $request_options['query']);
        $this->assertEquals('BioText,CoverImage', $request_options['query']['_filter']);
        $this->assertArrayHasKey('_filteruri', $request_options['query']);
        $this->assertEquals('User', $request_options['query']['_filteruri']);
        $this->assertArrayHasKey('_shorturis', $request_options['query']);
        $this->assertEquals(true, $request_options['query']['_shorturis']);
    }

    /**
     * @test
     */
    public function shouldReturnReponseObject()
    {
        $mock = new MockHandler([
            new Response(200, [], $this->fauxSmugMugResponse), # We don't care about headers for this test so we don't set them.
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);

        $response = $client->get('user/'.$this->user);

        $this->assertEquals('bar', $response->ano);
    }

    /**
     * @test
     */
    public function shouldSetOAuthParamsInQuery()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        $mock = new MockHandler([
            new Response(200), # TODO: Do we care about headers or body for this test so we don't set them?
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler, 'OAuthSecret' => $this->OAuthSecret]); # TODO: This currently barfs because when we use the OAuth library, it creates its own handler that tramples all over the Mock handler.

        $client->setToken($this->oauth_token, $this->oauth_token_secret);

        $client->get('album/rAnD0m');
        $request_options = $client->getRequestOptions();

        //$this->assertEquals
    }

    /**
     * @test
     */
    public function shouldSetAndUnSetHeadersEtcForUploadAndAssumeUploadWorkedWithOptionsThatMatchHeaders()
    {
        $mock = new MockHandler([
            new Response(200), # TODO: Do we care about headers or body for this test so we don't set them?
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler, '_verbosity' => 1]);

        $options = [
            'X-Smug-Altitude' => 1085,
            'X-Smug-Caption' => 'This is a test image.',
            'X-Smug-FileName' => 'test-file.jpg',
            'X-Smug-Hidden' => false,
            'X-Smug-ImageUri' => '/api/v2/image/nSCcZwm-0',
            'X-Smug-Keywords' => 'test; table',
            'X-Smug-Latitude' => -34.045034,
            'X-Smug-Longitude' => 18.386065,
            'X-Smug-Pretty' => true,
            'X-Smug-Title' => 'I am a test image',
        ];

        $client->upload('album/rAnD0m', './examples/phpSmug-logo.png', $options); # TODO: Make this a bit more resiliant and make the pic nice ;-)
        $request_options = $client->getRequestOptions();  # TODO: This assumes Guzzle sets the headers correctly.  We can sort of test this from the response we get from SmugMug, but maybe not in testing.
        foreach ($options as $header => $value) {
            $this->assertArrayHasKey($header, $request_options['headers']);
            $this->assertEquals($value, $request_options['headers'][$header]);
        }
        # TODO: These query params should _not_ be set
        //$this->assertArrayHasKey('_verbosity', $request_options['query']['_verbosity']);
    }

    /**
     * @test
     */
    public function shouldSetAndUnSetHeadersEtcForUploadAndAssumeUploadWorkedWithOptionsThatDontHaveXSmugInTheirName()
    {
        $mock = new MockHandler([
            new Response(200), # TODO: Do we care about headers or body for this test so we don't set them?
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler, '_verbosity' => 1]);

        $options = [
            'Altitude' => 1085,
            'Caption' => 'This is a test image on Table Mountain',
            'FileName' => 'test-file.jpg',
            'Hidden' => false,
            'ImageUri' => '/api/v2/image/XGZmXXh-0',
            'Keywords' => 'test; table',
            'Latitude' => -34.045034,
            'Longitude' => 18.386065,
            'Pretty' => true,
            'Title' => 'I am a test image',
        ];

        $client->upload('album/rAnD0m', './examples/phpSmug-logo.png', $options); # TODO: Make this a bit more resiliant by getting the full path.
        $request_options = $client->getRequestOptions();  # TODO: This assumes Guzzle sets the headers correctly.  We can sort of test this from the response we get from SmugMug, but maybe not in testing.
        foreach ($options as $header => $value) {
            $this->assertArrayHasKey('X-Smug-'.$header, $request_options['headers']);
            $this->assertEquals($value, $request_options['headers']['X-Smug-'.$header]);
        }
        # TODO: These query params should _not_ be set
        //$this->assertArrayHasKey('_verbosity', $request_options['query']['_verbosity']);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function shouldThrowExceptionIfUploadFileNotFound()
    {
        $client = new Client($this->APIKey);
        $client->upload('album/rAnD0m', '/path/to/non/existant/file.jpg');
    }

    /**
     * @test
     */
    public function shouldSetJsonOptionOnPutAndPatchRequests()
    {
        $mock = new MockHandler([
            new Response(200), # TODO: Do we care about headers or body for this test so we don't set them?
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler, '_verbosity' => 1]);
        $options = [
            'NiceName' => 'New-Album-from-API',
            'Title' => 'Ano Different Album Name from API',
            'Privacy' => 'Private',
        ];
        $client->put('album/rAnD0m', $options);
        $request_options = $client->getRequestOptions();

        $this->assertArrayHasKey('json', $request_options);
        $this->assertEquals($options, $request_options['json']);
    }

    /**
     * @test
     */
    public function shouldSetOAuthParamsInAuthorizationHeader()
    {
    }
}
