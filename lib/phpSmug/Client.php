<?php
namespace phpSmug;

use phpSmug\HttpClient\HttpClient;
use phpSmug\HttpClient\HttpClientInterface;


class Client
{
    /**
     * Constant for the API Key. Get your API Key from https://api.smugmug.com/api/developer/apply
     */
    const API_KEY = 'api_key';

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


?>
