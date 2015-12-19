<?php

namespace phpSmug;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;

interface Exception
{
}

class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}

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
    private $stack;

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
        'base_uri' => 'https://api.smugmug.com/',
        'api_version' => 'v2',
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
            throw new InvalidArgumentException('An API key is required for all SmugMug interactions.');
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
            if (is_string($args[0])) {
                # Add '/api/v#' to the method if it doesn't exist
                if (strpos($args[0], '/api/'.$this->default_options['api_version']) === false) {
                    $url = '/api/'.$this->default_options['api_version'];
                    # Cater for ! queries like !authuser - these don't need a trailing / after the API version.
                    if (strpos($args[0], '!') !== 0) {
                        $url .= '/';
                    }
                    $url .= $args[0];
                } else {
                    $url = $args[0];
                }

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
                $http_method = 'GET';
                $url = 'https://secure.smugmug.com/services/oauth/1.0a/getRequestToken';
                $callback = $args[0];
                $this->request_options['query'] = [
                    'oauth_callback' => $callback,
                ];
            break;
            case 'getAccessToken':
                $http_method = 'GET';
                $url = 'https://secure.smugmug.com/services/oauth/1.0a/getAccessToken';
                $oauth_verifier = $args[0];
                $this->request_options['query'] = [
                    'oauth_verifier' => $oauth_verifier,
                ];
            break;
            case 'upload':
                $http_method = 'POST';

                # Unset all default query params
                unset($this->default_options['query']['_verbosity'], $this->default_options['query']['_shorturis'], $this->default_options['query']['APIKey']);

                $file = $args[1];
                $options = (count($args) == 3) ? $args[2] : null;

                # Required headers
                $this->request_options['headers']['X-Smug-ResponseType'] = 'JSON';
                $this->request_options['headers']['X-Smug-Version'] = $this->default_options['api_version'];
                $this->request_options['headers']['X-Smug-AlbumUri'] = (strpos($args[0], '/api/'.$this->default_options['api_version'].'/') === false) ? '/api/'.$this->default_options['api_version'].'/'.$args[0] : $args[0];

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
            case 'put':
            case 'post':
            case 'patch':
            case 'options':
                if ($options) {
                    $this->request_options['json'] = $options;
                }
            break;
            default:
                throw new \BadMethodCallException('Invalid method: '.$method);
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

            $oauth_middleware = new \GuzzleHttp\Subscriber\Oauth\Oauth1($oauth_middleware_config);

            $this->stack->unshift($oauth_middleware, 'oauth_middleware'); # Bump OAuth to the bottom of the stack
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
              # Remove the middleware so it is re-added with the updated credentials on subsequent requests.
              $this->stack->remove('oauth_middleware');

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
        if (is_null($this->OAuthSecret)) {
            throw new InvalidArgumentException('An OAuthSecret is required for all SmugMug OAuth interactions.');
        }
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

    /**
     * Helper function that generates and returns the authorization URL.
     *
     * If no arguments are passed, we use the default settings and set no
     * callback URL.  This means SmugMug won't redirect your user back to your
     * site.
     *
     * If the first argument is a string, we take this to be the callback URL.
     *
     * If this first argument is an array, we take this to be the options, which
     * can include the callback URL.  If one of those options happens to be
     * oauth_callback, you're in luck and you don't need to pass it as a
     * separate argument.
     */
    public function getAuthorizeURL()
    {
        $num_args = func_num_args();
        if ($num_args == 2) {
            list($callback, $auth_params) = func_get_args();
        } else {
            $arg = func_get_args();
            if (count($arg) > 0) {
                if (is_string($arg[0])) {
                    $callback = $arg[0];
                }
                if (is_array($arg[0])) {
                    $auth_params = $arg[0];
                }
            }
        }

        $oauth_callback = (isset($callback)) ? '&oauth_callback='.urlencode($callback) : '';
        $auth_params = (isset($auth_params)) ? '&'.\http_build_query($auth_params) : '';
        $url = 'https://secure.smugmug.com/services/oauth/1.0a/authorize';

        return "{$url}?oauth_token={$this->oauth_token}{$oauth_callback}{$auth_params}";
    }

    /**
     * Sign the passed resource with the OAuth params.
     *
     * This essentially generates a signature for the passed URL and returns a
     * string with the OAuth parameters and signature appended.
     *
     * This is very useful for allowing people to display images that are not set
     * to allow external view within the gallery's settings on SmugMug.
     *
     * This is a bit of a hack at the moment as I use the Mock handler to fake
     * making the requests so I can grab the final URL.
     *
     * I may need to find a better way of doing this, possibly by creating my own middleware.
     */
    public function signResource($url)
    {
        $oauth = new \GuzzleHttp\Subscriber\Oauth\Oauth1([
            'request_method' => 'query',
            'consumer_key' => $this->APIKey,
            'consumer_secret' => $this->OAuthSecret,
            'token' => $this->oauth_token,
            'token_secret' => $this->oauth_token_secret,
        ]);

        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200),
        ]);
        $container = [];
        $handler = HandlerStack::create($mock);

        # Add OAuth to the stack
        $handler->push($oauth);
        # Add history to the stack
        $history = \GuzzleHttp\Middleware::history($container);
        $handler->push($history);

        $client = new GuzzleClient(['handler' => $handler, 'auth' => 'oauth']);
        $client->get($url);
        foreach ($container as $transaction) {
            $url = $transaction['request']->getUri();
        }

        return (string) $url;
    }
}
