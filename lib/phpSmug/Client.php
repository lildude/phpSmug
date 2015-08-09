<?php
namespace phpSmug;

use GuzzleHttp\Client as GuzzleClient;


class Client
{
    /**
     * Constant for the API Key. Get your API Key from https://api.smugmug.com/api/developer/apply
     */
    const API_KEY = 'api_key';

    /**
     * The Guzzle instance used to communicate with SmugMug.
     *
     * @var $httpClient
     */
    private $httpClient;

    /**
     * @var array
     */
    private $options = array(
        'base_uri'    => 'https://api.smugmug.com/api/v2/',
        'query'       => ['APIKey' => self::API_KEY],
        'headers' => [
                          'User-Agent' => 'phpSmug Dev/4.0 (http://phpsmug.com)',
                          'Accept'     => 'application/json',
                      ],

        'user_agent'  => 'phpSmug (http://phpsmug.com)',
        'timeout'     => 10,
        'debug'       => true
    );

    /**
     * Instantiate a new SmugMug client.
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge($this->options, $options);
        $this->httpClient = new GuzzleClient($this->options);
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new GuzzleClient($this->options);
        }

        return $this->httpClient;
    }
}
?>
