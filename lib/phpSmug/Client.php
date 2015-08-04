<?php
namespace phpSmug;

use phpSmug\HttpClient\HttpClient;
use phpSmug\HttpClient\HttpClientInterface;


class Client
{
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
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new HttpClient($this->options);
        }

        return $this->httpClient;
    }
}

?>
