<?php
namespace phpSmug;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Description;


class Client extends GuzzleClient
{
    public function __construct(array $config = [])
    {
        $client = new Client([
        //$client = new Client(["base_url" => "https://api.smugmug.com/api/v2/"]);
            "defaults" => [
                "headers" => [
                    "User-Agent" => sprintf("%s (%s)", "MyTestApp", "phpSmug/4.0"),
                    "Content-type" => "application/json; charset=utf-8"
                ],
                "base_url" => "https://api.smugmug.com/api/v2/"
            ]
        ]);

        $description = new Description(include __DIR__ . '/smugmug-api.php');
        //$description = new Description();


        parent::__construct($client, $description, $config);
    }
}

?>
