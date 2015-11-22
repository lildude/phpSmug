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
    public $_verbosity;
    public $_shorturis = false;
    public $oauth_token;
    public $oauth_token_secret;

    /**
     * The Guzzle instance used to communicate with SmugMug.
     */
    private $httpClient;

    /**
     * The response object for each request.
     */
    private $response;

    /**
     * The per-request options that are merged in with the default options.
     */
    private $request_options;

    /**
     * @var array
     */
    private $default_options = array(
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
        if (is_null($APIKey)) {
            throw new \InvalidArgumentException('An API key is required for all SmugMug interactions.');
        }
        $option_keys = ['_verbosity', '_shorturis', 'AppName', 'OAuthSecret'];
        foreach ($option_keys as $option) {
            if (isset($options[$option])) {
                $this->{$option} = $options[$option];
                unset($options[$option]);
            }
        }

        $this->default_options = array_merge($this->default_options, $options);

        if ($this->_shorturis) {
            $this->default_options['query']['_shorturis'] = $this->_shorturis;
        }

        # SmugMug defaults to a verbosity of 2 so no point adding if it equals 2.
        if ($this->_verbosity != 2) {
            $this->default_options['query']['_verbosity'] = $this->_verbosity;
        }

        $this->default_options['headers']['User-Agent'] = sprintf('%s using %s/%s', $this->AppName, $this->default_options['headers']['User-Agent'], self::VERSION);

        if ($this->OAuthSecret) {
            # Setup the handler stack - we'll need this later.
            $this->stack = (isset($options['handler'])) ? $options['handler'] : HandlerStack::create();
            $this->default_options['handler'] = $this->stack;
        } else {
            # We only need the APIKey query parameter if we're not authenticating
            $this->default_options['query']['APIKey'] = $APIKey;
        }

        $this->httpClient = new GuzzleClient($this->default_options);
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @return statusCode Returns the HTTP status code for the last request
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return headers Returns the HTTP headers as an array for the last request
     */
    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    /**
     * @return ReasonPhrase Returns the HTTP status message for the last request
     */
    public function getReasonPhrase()
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * @return options
     */
    public function getDefaultOptions()
    {
        return $this->default_options;
    }

    /**
     * @return $response Returns the full response without any phpSmug touches.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return $request_options Returns the request options. These are set just before the request is made and cleared before every request.
     */
    public function getRequestOptions()
    {
        return $this->request_options;
    }

    public function __call($method, $args)
    {
        # Ensure the per-request options are empty
        $this->request_options = [];
        $client = self::getHttpClient();
        if (!empty($args)) {
            # Strip off /api/v2/ from any methods as we add this automatically
            if (is_string($args[0])) {
                $url = strtr($args[0], '/api/v2/', '');
                # Cater for any args passed in via `?whatever=foo`
                if (strpos($url, '?') !== false) {
                    $pairs = explode('&', explode('?', $url)[1]);
                    foreach ($pairs as $pair) {
                        list($key, $value) = explode('=', $pair);
                        $this->request_options['query'][$key] = $value;
                    }
                }
            }
        }
        $options = (count($args) == 2) ? $args[1] : array();
        switch ($method) {
            case 'get':
                if ($options) {
                    foreach (self::flattenOptimizers($options) as $key => $value) {
                        $this->request_options['query'][$key] = $value;
                    }
                }
            break;
            case 'getRequestToken':
                $http_method = 'POST';
                $url = 'https://secure.smugmug.com/services/oauth/1.0a/getRequestToken';

                # Unset all default query params
                unset($this->default_options['query']['_verbosity'], $this->default_options['query']['_shorturis'], $this->default_options['query']['APIKey']);

                $callback = $args[0];
                $this->request_options['form_params'] = [
                    'oauth_callback' => $callback,
                ];
            break;
            case 'getAccessToken':
                $http_method = 'GET';
                $url = 'https://secure.smugmug.com/services/oauth/1.0a/getAccessToken';

                # Unset all default query params
                unset($this->default_options['query']['_verbosity'], $this->default_options['query']['_shorturis'], $this->default_options['query']['APIKey']);

                $oauth_verifier = $args[0];
                $this->request_options['query'] = [
                    'oauth_verifier' => $oauth_verifier,
                ];

            break;
            case 'signRequest':
              # TODO Take query and append OAuth stuffs
            break;
            case 'upload':
                $http_method = 'POST';

                # Unset all default query params
                unset($this->default_options['query']['_verbosity'], $this->default_options['query']['_shorturis'], $this->default_options['query']['APIKey']);

                $file = $args[1];
                $options = (count($args) == 3) ? $args[2] : null;

                # Required headers
                $this->request_options['headers']['X-Smug-ResponseType'] = 'JSON';
                $this->request_options['headers']['X-Smug-Version'] = 'v2';
                $this->request_options['headers']['X-Smug-AlbumUri'] = (strpos($args[0], '/api/v2/') === false) ? "/api/v2/{$args[0]}" : $args[0];

                # Optional headers:
                $optional_headers = ['X-Smug-Altitude', 'X-Smug-Caption', 'X-Smug-FileName', 'X-Smug-Hidden', 'X-Smug-ImageUri', 'X-Smug-Keywords', 'X-Smug-Latitude', 'X-Smug-Longitude', 'X-Smug-Pretty', 'X-Smug-Title'];
                if ($options && is_array($options)) {
                    foreach ($options as $key => $value) {
                        $newkey = (strpos($key, 'X-Smug-') === false) ? 'X-Smug-'.$key : $key;
                        if (in_array($newkey, $optional_headers)) {
                            $this->request_options['headers'][$newkey] = $value;
                        }
                    }
                }

                $filename = (isset($this->request_options['X-Smug-FileName'])) ? $this->request_options['X-Smug-FileName'] : basename($file);

                $url = 'https://upload.smugmug.com/'.$filename;

                if (is_file($file)) {
                    $fp = fopen($file, 'r');
                    $data = fread($fp, filesize($file));
                    fclose($fp);
                } else {
                    throw new \InvalidArgumentException('File not found: '.$file);
                }
                $this->request_options['body'] = $data;
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
            ];

            $oauth_middleware = new Oauth1($oauth_middleware_config);

            $this->stack->unshift($oauth_middleware); # Bump OAuth to the top of the stack
        }

        # Merge the default and request options

        # Merge query params first - we do this manually as array_merge_recursive doesn't play nicely.
        $this->request_options['query'] = (isset($this->request_options['query'])) ? array_merge($this->default_options['query'], $this->request_options['query']) : $this->default_options['query'];
        # Merge the rest of the options.
        $this->request_options = array_merge($this->default_options, $this->request_options);

        # Perform the API request
        $this->response = $client->request((isset($http_method)) ? strtoupper($http_method) : strtoupper($method), $url, $this->request_options);

        switch ($method) {
          case 'getRequestToken':
          case 'getAccessToken':
              parse_str($this->response->getBody(), $token);
              $this->setToken($token['oauth_token'], $token['oauth_token_secret']);

              return $token;
          break;
          default:
              $body = json_decode((string) $this->response->getBody());
              if ($method == 'options') {
                  return $body->Options;
              } elseif (isset($body->Response)) {
                  return $body->Response;
              } else {
                  return $body;
              }
          break;
        }
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

    /**
     * Get the OAuth tokens that may have been set using setToken().
     */
    public function getToken()
    {
        return array($this->oauth_token, $this->oauth_token_secret);
    }
}
