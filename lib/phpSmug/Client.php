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
        $this->options = $options;
    }
}

?>
