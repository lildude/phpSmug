<?php
namespace phpSmug;

//use phpSmug\Api\ApiInterface;
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
}

?>
