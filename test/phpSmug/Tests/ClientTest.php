<?php
namespace phpSmug\Tests;

use phpSmug\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldNotHaveToPassHttpClientToConstructor()
    {
      $client = new Client();

      $this->assertInstanceOf('phpSmug\HttpClient\HttpClient', $client->getHttpClient());
    }

    
}

?>
