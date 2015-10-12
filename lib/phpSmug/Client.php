<?php

namespace phpSmug;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class Client
{
    /**
     * A few default variables.
     */
    const VERSION = '4.0.0';
    public $AppName = 'Unknown Application';
    public $APIKey;
    public $OAuthSecret;
    public $_verbosity = 2;
    public $_shorturis = false;
    public $oauth_token;
    public $oauth_token_secret;

    /**
     * The Guzzle instance used to communicate with SmugMug.
     */
    private $httpClient;

    /**
     * @var array
     */
    private $request_options = array(
        'base_uri' => 'https://api.smugmug.com/api/v2/',
        'query' => [],
        'headers' => [
                        'User-Agent' => 'phpSmug',
                        'Accept' => 'application/json',
                      ],
        'timeout' => 30,
        'auth' => null,
    );

    /**
     * Instantiate a new SmugMug client.
     */
    public function __construct($APIKey = null, array $options = array())
    {
        $this->APIKey = $APIKey;
        $option_keys = ['_verbosity', '_shorturis', 'AppName', 'OAuthSecret'];
        foreach ($option_keys as $option) {
            if (isset($options[$option])) {
                $this->{$option} = $options[$option];
                unset($options[$option]);
            }
        }

        $this->request_options = array_merge($this->request_options, $options);

        if ($this->_shorturis) {
            $this->request_options['query']['_shorturis'] = $this->_shorturis;
        }
        $this->request_options['query']['_verbosity'] = $this->_verbosity;
        $this->request_options['query']['APIKey'] = $APIKey;

        $this->request_options['headers']['User-Agent'] = sprintf('%s using %s/%s', $this->AppName, $this->request_options['headers']['User-Agent'], self::VERSION);

        if ($this->OAuthSecret) {
            # Setup the handler stack - we'll need this later.
            $this->stack = HandlerStack::create();
            $this->request_options['handler'] = $this->stack;
        }

        $this->httpClient = new GuzzleClient($this->request_options);
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new GuzzleClient($this->request_options);
        }

        return $this->httpClient;
    }

    /**
     * @return options
     */
    public function getRequestOptions()
    {
        return $this->request_options;
    }

    public function __call($method, $args)
    {
        $client = self::getHttpClient();
        $url = strtr($args[0], '/api/v2/', '');
        $options = (count($args) == 2) ? $args[1] : array();
        switch ($method) {
            case 'get':
                if ($options) {
                    foreach (self::flattenOptimizers($options) as $key => $value) {
                        $this->request_options['query'][$key] = $value;
                    }
                }
            break;
            default:
                if ($options) {
                    $this->request_options['json'] = $options;
                }
            break;
        }
        if ($this->OAuthSecret) {
            $this->request_options['auth'] = 'oauth';
            $oauth_middleware_config = [
                'consumer_key' => $this->APIKey,
                'consumer_secret' => $this->OAuthSecret,
                'token' => $this->oauth_token,
                'token_secret' => $this->oauth_token_secret,
                'version' => '1.0a',
            ];

            $oauth_middleware = new Oauth1($oauth_middleware_config);

            $this->stack->push($oauth_middleware);
        }

        $request = $client->request(strtoupper($method), $url, $this->request_options);
        //$code = $request->getStatusCode();
        //$body = (string)$request->getBody();
        //$headers = $request->getHeaders();
        return json_decode((string) $request->getBody());
    }

    /**
     * Convert the array of optimizers into a string to append to the end of the URL.
     *
     * @param array $optimizers An array of optimizations to apply to the SmugMug results
     *
     * @return string
     */
    private static function flattenOptimizers($optimizers)
    {
        $o = [];
        foreach ($optimizers as $key => $value) {
            $o[$key] = (is_array($value)) ? implode(',', $value) : $value;
        }

        return $o;
    }

    /**
     * Set Token and Token Secret for use by other methods in phpSmug.
     *
     * Use this method to pull in the token and token secret obtained during
     * the OAuth authorisation process.
     *
     * If OAuth is being used, this method MUST be called so phpSmug knows about
     * the token and token secret.
     *
     * NOTE: It's up to the application developer using phpSmug to store the Access
     * token and token secret in a location convenient for their application.
     * phpSmug can not do this as all storage and caching done by phpSmug is
     * of a temporary nature.
     *
     * @param string $id     Token ID returned by auth_getAccessToken()
     * @param string $Secret Token secret returned by auth_getAccessToken()
     **/
    public function setToken($oauth_token, $oauth_token_secret)
    {
        $this->oauth_token = $oauth_token;
        $this->oauth_token_secret = $oauth_token_secret;
    }

}
