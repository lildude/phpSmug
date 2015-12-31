<?php

namespace phpSmug\Tests;

use phpSmug\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

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
        $this->fauxSmugMugResponse = '{"Options":{"foo":"bar"},"Response": {"ano":"bar"},"Code":200,"Message":"OK"}';
        $this->fauxRequestTokenResponse = "oauth_token={$this->oauth_token}&oauth_token_secret={$this->oauth_token_secret}&oauth_callback_confirmed=true";
        $this->fauxAccessTokenResponse = "oauth_token={$this->oauth_token}&oauth_token_secret={$this->oauth_token_secret}";
        $this->fauxDeleteResponse = '{"Response":{"Uri":"/api/v2/album/rAnD0m","Locator":"Album","LocatorType":"Object"},"Code":200,"Message":"Ok"}';
    }
    /**
     * @test
     */
    public function shouldNotHaveToPassHttpClientToConstructorWithDefaultOptionsSet()
    {
        $client = new Client($this->APIKey);
        $this->assertInstanceOf('GuzzleHttp\Client', $client->getHttpClient());
        $options = $client->getDefaultOptions();

        $this->assertEquals('https://api.smugmug.com/', $options['base_uri']);
        $this->assertEquals($client->AppName.' using phpSmug/'.$client::VERSION, $options['headers']['User-Agent']);
        $this->assertEquals('application/json', $options['headers']['Accept']);
        $this->assertEquals(30, $options['timeout']);
    }

    /**
     * @test
     * @expectedException phpSmug\Exception\InvalidArgumentException
     * @expectedExceptionMessage An API key is required for all SmugMug interactions.
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
        $options = ['OAuthSecret' => $this->OAuthSecret];
        $client = new Client($this->APIKey, $options);
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
        $mock = new MockHandler([
            # We don't care about headers for this test so we don't set them.
            new Response(200, [], $this->fauxSmugMugResponse),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);
        $client->get('user/'.$this->user);
        $decoded_response = (json_decode((string) $client->getResponse()->getBody()));
        $this->assertNotNull($decoded_response->Response);
        $this->assertNotNull($decoded_response->Options);
        $this->assertEquals('bar', $decoded_response->Response->ano);
        $this->assertEquals('bar', $decoded_response->Options->foo);
        $this->assertEquals(200, $decoded_response->Code);
        $this->assertEquals('OK', $decoded_response->Message);
    }

    /**
     * @test
     */
    public function shouldGetSmugMugMethodOptions()
    {
        $mock = new MockHandler([
            # We don't care about headers for this test so we don't set them.
            new Response(200, [], $this->fauxSmugMugResponse),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);

        $options = $client->options('user/'.$this->user);

        $this->assertObjectHasAttribute('foo', $options);
        $this->assertEquals('bar', $options->foo);
        $this->assertObjectNotHasAttribute('Response', $options);
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
    public function shouldSetQueryFromOptionsPassedOnRequestAndOverWriteDefaults()
    {
        $mock = new MockHandler([
            new Response(200), # We don't care about headers or body for this test so we don't set them.
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);
        $options = [
            '_filter' => ['BioText', 'CoverImage'],  # TODO: Ensure default options are over-written and none are lost.
            '_filteruri' => ['User'],
            '_shorturis' => true,
            '_verbosity' => 2,
        ];
        $response = $client->get('user/'.$this->user, $options);

        $request_options = $client->getRequestOptions();
        # We're not testing Guzzle, so we assume it does the right thing with these options.
        foreach ($options as $key => $value) {
            $this->assertArrayHasKey($key, $request_options['query']);
            $this->assertEquals((is_array($value)) ? implode(',', $value) : $value, $request_options['query'][$key]);
        }
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

        $this->assertObjectHasAttribute('ano', $response);
        $this->assertEquals('bar', $response->ano);
    }

    /**
     * @test
     */
    public function shouldSetOAuthParamsInQuery()
    {
        $mock = new MockHandler([
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $container = [];
        // Add the history middleware to the handler stack.
        $history = Middleware::history($container);
        $handler->push($history);

        $client = new Client($this->APIKey, ['handler' => $handler]);
        $client->get('album/rAnD0m');
        foreach ($container as $transaction) {
            $query = $transaction['request']->getUri()->getQuery();
            $this->assertEquals('APIKey='.$this->APIKey, $query);
        }
    }

    /**
     * @test
     */
    public function shouldSetCorrectQueryUrl()
    {
        $mock = new MockHandler([
            new Response(200),
            new Response(200),
            new Response(200),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $container = [];
        // Add the history middleware to the handler stack.
        $history = Middleware::history($container);
        $handler->push($history);

        $client = new Client($this->APIKey, ['handler' => $handler]);
        $queries = ['!album', 'album/rAnD0m', '/api/v2/album/rAnD0m', 'album/rAnD0m!images'];
        foreach ($queries as $q) {
            $client->get($q);
        }
        foreach ($container as $key => $transaction) {
            $query_path = $transaction['request']->getUri()->getPath();
            if (strpos($queries[$key], '!') === 0) {
                $expected = '/api/v2'.$queries[$key];
            } elseif (strpos($queries[$key], '/api/v2/') === 0) {
                $expected = $queries[$key];
            } else {
                $expected = '/api/v2/'.$queries[$key];
            }
            $this->assertEquals($expected, $query_path);
        }
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
        # There should be no query params.
        $this->assertEmpty($request_options['query']);
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
        # There should be no query params.
        $this->assertEmpty($request_options['query']);
    }

    /**
     * @test
     * @expectedException phpSmug\Exception\InvalidArgumentException
     * @expectedExceptionMessage File not found: /path/to/non/existant/file.jpg
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
        $mock = new MockHandler([
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $container = [];
        // Add the history middleware to the handler stack.
        $history = Middleware::history($container);
        $handler->push($history);

        $client = new Client($this->APIKey, ['handler' => $handler, 'OAuthSecret' => $this->OAuthSecret]);
        $client->setToken($this->oauth_token, $this->oauth_token_secret);
        $client->get('album/rAnD0m');
        foreach ($container as $transaction) {
            $auth_header = Psr7\parse_header($transaction['request']->getHeader('Authorization'));
            // Asserts the header is set and populated
            $this->assertNotEmpty($auth_header);

            // Now parse and flatten the header so we can check the values
            $parsed_auth_header = [];
            foreach ($auth_header as $h) {
                foreach ($h as $key => $value) {
                    $parsed_auth_header[$key] = $value;
                }
            }
            $this->assertEquals($this->APIKey, $parsed_auth_header['OAuth oauth_consumer_key']);
            $this->assertEquals($this->oauth_token, $parsed_auth_header['oauth_token']);
            $this->assertEquals('HMAC-SHA1', $parsed_auth_header['oauth_signature_method']);
            $this->assertEquals('1.0', $parsed_auth_header['oauth_version']);
            $this->assertArrayHasKey('oauth_nonce', $parsed_auth_header);
            $this->assertArrayHasKey('oauth_signature', $parsed_auth_header);
            $this->assertArrayHasKey('oauth_timestamp', $parsed_auth_header);
        }
    }

    /**
     * @test
     * @expectedException phpSmug\Exception\InvalidArgumentException
     * @expectedExceptionMessage An OAuthSecret is required for all SmugMug OAuth interactions.
     */
    public function shouldThrowExcetionIfNoOAuthSecret()
    {
        $client = new Client($this->APIKey);
        $client->setToken($this->oauth_token, $this->oauth_token_secret);
    }

    /**
     * @test
     */
    public function shouldGetRequestToken()
    {
        $mock = new MockHandler([
            # We don't care about headers for this test so we don't set them.
            new Response(200, [], $this->fauxRequestTokenResponse),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['OAuthSecret' => $this->OAuthSecret, 'handler' => $handler]);

        $callback = 'http://localhost/index.php';
        $request_token = $client->getRequestToken($callback);

        $this->assertArrayHasKey('oauth_token', $request_token);
        $this->assertEquals($this->oauth_token, $request_token['oauth_token']);
        $this->assertArrayHasKey('oauth_token_secret', $request_token);
        $this->assertEquals($this->oauth_token_secret, $request_token['oauth_token_secret']);
        $this->assertArrayHasKey('oauth_callback_confirmed', $request_token);
        $this->assertEquals('true', $request_token['oauth_callback_confirmed']);
    }

    /**
     * @test
     */
    public function shouldGetAccessToken()
    {
        $mock = new MockHandler([
            # We don't care about headers for this test so we don't set them.
            new Response(200, [], $this->fauxAccessTokenResponse),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['OAuthSecret' => $this->OAuthSecret, 'handler' => $handler]);

        $oauth_verifier = '12345678'; # This isn't important
        $request_token = $client->getAccessToken($oauth_verifier);

        $this->assertArrayHasKey('oauth_token', $request_token);
        $this->assertEquals($this->oauth_token, $request_token['oauth_token']);
        $this->assertArrayHasKey('oauth_token_secret', $request_token);
        $this->assertEquals($this->oauth_token_secret, $request_token['oauth_token_secret']);
    }

    /**
     * @test
     */
    public function shouldGenerateAuthorizationUrl()
    {
        $options = ['OAuthSecret' => $this->OAuthSecret];
        $client = new Client($this->APIKey, $options);
        $callback = 'http://localhost/index.php';
        $options = [
            'Access' => 'Public',
            'Permissions' => 'Add',
            'allowThirdPartyLogin' => false,
            'showSignUpButton' => false,
            'viewportScale' => 0.0,
        ];

        $client->setToken($this->oauth_token, $this->oauth_token_secret);

        $authorize_url = $client->getAuthorizeURL();
        $this->assertTrue(is_string($authorize_url));
        $this->assertEquals("https://secure.smugmug.com/services/oauth/1.0a/authorize?oauth_token={$this->oauth_token}", $authorize_url);

        $authorize_url = $client->getAuthorizeURL($callback);
        $this->assertTrue(is_string($authorize_url));
        $this->assertEquals("https://secure.smugmug.com/services/oauth/1.0a/authorize?oauth_token={$this->oauth_token}&oauth_callback=".urlencode($callback), $authorize_url);

        $authorize_url = $client->getAuthorizeURL($options);
        $this->assertTrue(is_string($authorize_url));
        $this->assertEquals("https://secure.smugmug.com/services/oauth/1.0a/authorize?oauth_token={$this->oauth_token}".'&'.\http_build_query($options), $authorize_url);

        $authorize_url = $client->getAuthorizeURL($callback, $options);
        $this->assertTrue(is_string($authorize_url));
        $this->assertEquals("https://secure.smugmug.com/services/oauth/1.0a/authorize?oauth_token={$this->oauth_token}&oauth_callback=".urlencode($callback).'&'.\http_build_query($options), $authorize_url);
    }

    /**
     * @test
     * @expectedException phpSmug\Exception\InvalidArgumentException
     * @expectedExceptionMessage All methods need an argument.
     */
    public function shouldThrowInvalidArgumentExceptionIfCallMethodWithoutDestination()
    {
        $client = new Client($this->APIKey);
        $client->get();
    }

    /**
     * @test
     * @expectedException phpSmug\Exception\BadMethodCallException
     * @expectedExceptionMessage Invalid method: badmethod
     */
    public function shouldThrowBadMethodCallException()
    {
        $client = new Client($this->APIKey);
        $client->badmethod('album/r4nD0m');
    }

    /**
     * @test
     */
    public function shouldSignRequestUrlWithOAuthParams()
    {
        $client = new Client($this->APIKey, ['OAuthSecret' => $this->OAuthSecret]);
        $client->setToken($this->oauth_token, $this->oauth_token_secret);
        $signed_url = $client->signResource('https://photos.smugmug.com/photos/i-am-not-a-valid-img/0/L/i-am-not-a-valid-img-L.jpg');
        parse_str(parse_url($signed_url)['query'], $query_parts);

        $this->assertArrayHasKey('oauth_consumer_key', $query_parts);
        $this->assertEquals($this->APIKey, $query_parts['oauth_consumer_key']);
        $this->assertArrayHasKey('oauth_nonce', $query_parts);
        $this->assertArrayHasKey('oauth_signature', $query_parts);
        $this->assertArrayHasKey('oauth_signature_method', $query_parts);
        $this->assertEquals('HMAC-SHA1', $query_parts['oauth_signature_method']);
        $this->assertArrayHasKey('oauth_timestamp', $query_parts);
        $this->assertArrayHasKey('oauth_token', $query_parts);
        $this->assertEquals($this->oauth_token, $query_parts['oauth_token']);
        $this->assertArrayHasKey('oauth_version', $query_parts);
        $this->assertEquals('1.0', $query_parts['oauth_version']);
    }

    /**
     * @test
     */
    public function shouldDeleteAlbum()
    {
        $mock = new MockHandler([
            # We don't care about headers for this test so we don't set them.
            new Response(200, [], $this->fauxDeleteResponse),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client($this->APIKey, ['handler' => $handler]);
        $response = $client->delete('album/rAnD0m');

        $this->assertTrue(is_object($response));
        $this->assertEquals('Album', $response->Locator);
        $this->assertEquals('/api/v2/album/rAnD0m', $response->Uri);
    }
}
