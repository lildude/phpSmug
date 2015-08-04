<?php
namespace phpSmug;

use phpSmug\HttpClient\HttpClient;
use phpSmug\HttpClient\HttpClientInterface;


class Client
{
    /**
     * Constant for the API Key. Get your API Key from https://api.smugmug.com/api/developer/apply
     */
    const API_KEY = 'api_key';

    /**
     * @var array
     */
    private $options = array(
        'base_url'    => 'https://api.smugmug.com/',

        'user_agent'  => 'phpSmug (http://phpsmug.com)',
        'timeout'     => 10,

        'api_limit'   => 5000,
        'api_version' => 'v2',

        'cache_dir'   => null
    );

    /**
     * The client instance used to communicate with SmugMug.
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Instantiate a new SmugMug client.
     *
     * @param null|HttpClientInterface $httpClient SmugMug http client
     */
    public function __construct(HttpClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return ApiInterface
     */
    public function api($name)
    {
        switch ($name) {
            case 'me':
                $api = new Api\CurrentUser($this);
                break;

        default:
            throw new InvalidArgumentException(sprintf('Undefined api instance called: "%s"', $name));

        }

        return $api;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new HttpClient($this->options);
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Clears used headers.
     */
    public function clearHeaders()
    {
        $this->getHttpClient()->clearHeaders();
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->getHttpClient()->setHeaders($headers);
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new InvalidArgumentException(sprintf('Undefined option called: "%s"', $name));
        }

        return $this->options[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function setOption($name, $value)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new InvalidArgumentException(sprintf('Undefined option called: "%s"', $name));
        }

        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return ApiInterface
     */
    public function __call($name, $args)
    {
        try {
            return $this->api($name);
        } catch (InvalidArgumentException $e) {
            throw new BadMethodCallException(sprintf('Undefined method called: "%s"', $name));
        }
    }
}

?>
