<?php
namespace phpSmug;

class Client
{
    /**
     * @var array
     */
    private $options = array(
        'endpoint_url'  => 'https://api.smugmug.com/api/v2/',
        'user_agent'    => 'phpSmug (http://phpsmug.com)',
        'timeout'       => 10,
        'cache_dir'     => null
    );

    /**
     * The Buzz instance used to communicate with GitHub
     *
     * @var HttpClient
     */
    private $httpClient;
    /**
     * Instantiate a new GitHub client
     *
     * @param null|HttpClientInterface $httpClient Github http client
     */
    public function __construct(HttpClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient;
        $this->options = $options;
    }
}

?>
