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

    /**
     * @test
     */
    public function shouldThrowExceptionIfNoApikey()
    {
        $client = new Client();
    }

    /**
     * @test
     */
    public function shouldHaveOptionsSetInConstructor()
    {
        $APIKey = "I-am-not-a-valid-APIKey-but-it-does-not-matter-for-this-test";
        $options = [
            "AppName"   => "Testing phpSmug",
            "verbosity" => 1,
            ];
        $client = new Client($APIKey, $options);

        $this->assertArraySubset($options, $client->getOptions());
        $this->assertEquals($client->getOptions()['APIKey'], $APIKey);
        $this->assertEquals($client->getOptions()['AppName'], $options['AppName']);
        $this->assertEquals($client->getOptions()['verbosity'], $options['verbosity']);
        $this->assertEquals($client->getOptions()['headers']['User-Agent'], sprintf("Testing phpSmug using phpSmug/%s", $client::VERSION));
    }

}

?>
