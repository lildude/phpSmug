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
        $client = new Client("I-am-not-a-valid-APIKey-but-it-does-not-matter-for-this-test");

        $this->assertInstanceOf('GuzzleHttp\Client', $client->getHttpClient());
    }

}

?>
