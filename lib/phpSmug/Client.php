<?php
namespace phpSmug;

use GuzzleHttp\Client as GuzzleClient;


class Client
{
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
        'query'       => [],
        'headers'     => [
                          'User-Agent' => 'phpSmug/4.0',
                          'Accept'     => 'application/json',
                      ],
        'APIKey'      => null,
        'OAuthSecret' => null,
        'AppName'     => 'Unknown Application',
        'timeout'     => 30,
        'verbosity'   => 2,
    );

    /**
     * Instantiate a new SmugMug client.
     */
    public function __construct($APIKey, array $options = array())
    {
        $this->options['APIKey'] = $APIKey;

        $options['query'] = [
          "APIKey" => $APIKey,
          "_verbosity" => (array_key_exists('verbosity', $options)) ? $options['verbosity'] : $this->options['verbosity'],
        ];

        $this->options = array_merge($this->options, $options);

        $this->options['headers']['User-Agent'] = $this->options['AppName'] . " using " . $this->options['headers']['User-Agent'];

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

    public function get($url)
    {
        # Strip off the /api/v2/ part if it's in the URL
        $url = strtr( $url, '/api/v2/', '' );
        $client = $this->httpClient->get($url);
        $code = $client->getStatusCode();
        $body = (string)$client->getBody();
        return json_decode($body);
    }

}
?>
