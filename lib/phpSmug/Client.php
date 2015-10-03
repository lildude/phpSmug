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

    public function __call($method, $args)
    {
      $url = strtr($args[0], '/api/v2/', '');
      $optimizers = (count($args) == 2) ? $args[1] : '';

      $client = self::getHttpClient();
      $request = $client->request(strtoupper($method), $url);
      //$code = $request->getStatusCode();
      //$body = (string)$request->getBody();
      //$headers = $request->getHeaders();
      return json_decode((string)$request->getBody());
    }
}
?>
