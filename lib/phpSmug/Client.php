<?php

namespace phpSmug;

use GuzzleHttp\Client as GuzzleClient;


class Client
{
    /**
     * A few default variables.
     */
    const VERSION = '4.0.0';
    public $AppName = 'Unknown Application';
    public $APIKey;
    public $verbosity = 2;
    public $shorturis = false;

    /**
     * The Guzzle instance used to communicate with SmugMug.
     */
    private $httpClient;

    /**
     * @var array
     */
    private $request_options = array(
        'base_uri' => 'https://api.smugmug.com/api/v2/',
        'query' => [],
        'headers' => [
                        'User-Agent' => 'phpSmug',
                        'Accept' => 'application/json',
                      ],
        'timeout' => 30,
        'auth' => null,
    );

    /**
     * Instantiate a new SmugMug client.
     */
    public function __construct($APIKey = null, array $options = array())
    {
        $this->APIKey = $APIKey;
        if (isset($options['verbosity'])) {
            $this->verbosity = $options['verbosity'];
        }
        if (isset($options['shorturis'])) {
            $this->shorturis = $options['shorturis'];
        }
        if (isset($options['AppName'])) {
            $this->AppName = $options['AppName'];
        }
        unset($options['verbosity'], $options['shorturis'], $options['AppName']);

        $this->request_options = array_merge($this->request_options, $options);


        if ($this->shorturis) {
            $this->request_options['query']['_shorturis'] = $this->shorturis;
        }
        $this->request_options['query']['_verbosity'] = $this->verbosity;
        $this->request_options['query']['APIKey'] = $APIKey;

        $this->request_options['headers']['User-Agent'] = sprintf('%s using %s/%s', $this->AppName, $this->request_options['headers']['User-Agent'], self::VERSION);

        $this->httpClient = new GuzzleClient($this->request_options);
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new GuzzleClient($this->request_options);
        }

        return $this->httpClient;
    }

    /**
     * @return options
     */
    public function getRequestOptions()
    {
        return $this->request_options;
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
        return json_decode((string) $request->getBody());
    }
}
