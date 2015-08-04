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

    /**
     * @test
     */
    public function shouldPassHttpClientInterfaceToConstructor()
    {
        $client = new Client($this->getHttpClientMock());

        $this->assertInstanceOf('phpSmug\HttpClient\HttpClientInterface', $client->getHttpClient());
    }






    public function getHttpClientMock(array $methods = array())
    {
        $methods = array_merge(
            array('get', 'post', 'patch', 'put', 'delete', 'request', 'setOption', 'setHeaders', 'authenticate'),
            $methods
        );

        return $this->getMock('phpSmug\HttpClient\HttpClientInterface', $methods);
    }
}

?>
