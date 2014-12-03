<?php
namespace phpSmug;

use phpSmug\Api\ApiInterface;
use phpSmug\Exception\InvalidArgumentException;
use phpSmug\Exception\BadMethodCallException;
use phpSmug\HttpClient\HttpClient;
use phpSmug\HttpClient\HttpClientInterface;

/**
 * Simple yet very cool PHP SmugMug client
 *
 * @method Api\User user()
 *
 */

class Client
{
    /**
     * @var array
     */
    private $options = array(
        'endpoint_url'  => 'https://api.smugmug.com/api/v2/',
        'user_agent'    => 'phpSmug (http://phpsmug.com)',
        'timeout'       => 10,
        'cache_dir'     => null,
        'api_key'       => null,
    );

    /**
     * The instance used to communicate with SmugMug
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Instantiate a new SmugMug client
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
     * @return ApiInterface
     *
     * @throws InvalidArgumentException
     */
    public function api($name)
    {
        switch ($name) {
            case 'user':
                $api = new Api\User($this);
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
     * Clears used headers
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
     * @return mixed
     *
     * @throws InvalidArgumentException
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
        $supportedApiVersions = $this->getSupportedApiVersions();
        if ('api_version' == $name && !in_array($value, $supportedApiVersions)) {
            throw new InvalidArgumentException(sprintf('Invalid API version ("%s"), valid are: %s', $name, implode(', ', $supportedApiVersions)));
        }

        $this->options[$name] = $value;
    }

        /**
     * @param string $name
     *
     * @return ApiInterface
     *
     * @throws InvalidArgumentException
     */
    public function __call($name, $args) {
        try {
            return $this->api($name);
        } catch (InvalidArgumentException $e) {
            throw new BadMethodCallException(sprintf('Undefined method called: "%s"', $name));
        }
    }
}

?>
