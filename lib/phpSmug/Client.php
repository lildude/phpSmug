<?php
namespace phpSmug;

use GuzzleHttp\Client as GuzzleClient;


class Client
{
    const VERSION = "4.0.0";

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
                          'User-Agent' => 'phpSmug',
                          'Accept'     => 'application/json',
                      ],
        'APIKey'      => null,
        'OAuthSecret' => null,
        'AppName'     => 'Unknown Application',
        'timeout'     => 30,
        'verbosity'   => 2,
        'shorturis'   => false,
    );

    /**
     * Instantiate a new SmugMug client.
     */
    public function __construct($APIKey = null, array $options = array())
    {
        $this->options['APIKey'] = $APIKey;

        $options['query'] = [
          "APIKey" => $APIKey,
          "_verbosity" => (array_key_exists('verbosity', $options)) ? $options['verbosity'] : $this->options['verbosity'],
        ];

        $this->options = array_merge($this->options, $options);

        if ($this->options['shorturis']) {
            $this->options['query']['_shorturis'] = $this->options['shorturis'];
        }
        $this->options['query']['_verbosity'] = $this->options['verbosity'];
        $this->options['query']['APIKey'] = $APIKey;

        $this->options['headers']['User-Agent'] = sprintf("%s using %s/%s", $this->options['AppName'], $this->options['headers']['User-Agent'], Client::VERSION);

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

    /**
     * @return options
     */
    public function getOptions()
    {
        return $this->options;
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
