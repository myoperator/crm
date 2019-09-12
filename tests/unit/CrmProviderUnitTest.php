<?php

use PHPUnit\Framework\TestCase;

use \MyOperator\Crm\CrmProvider;
use \MyOperator\TransportMock;

class myclass extends MyOperator\Crm\CrmProvider {
    function refreshToken($client_id, $client_secret, $refresh_token) {
        return ['access_token' => 'xyz', 'timeout' => 1000];
    }
    function getOauthTokenKey() {
        return 'somekey';
    }

    protected function search_crm() {
        // $token = $this->getAccessToken();
        // if($token != 'xyz') {
        //     throw new \Exception('Token invalid', 401);
        // }
        return 'hello';
    }
}

final class CrmProviderUnitTest extends TestCase 
{
    private function getMockTokenProvider($methods = []) {
        $methods = array_merge([
            'getAccessToken' => 'abc', 
            'getRefreshToken' => 'def', 
            'getClientId' => 1, 
            'getClientSecret' => 2, 
            'setAccessToken' => 0, 
            'setRefreshToken' => 0], $methods);
        $mockTokenProvider = $this->getMockBuilder('MyOperator\Crm\TokenProvider')->setMethods(array_keys($methods))->getMock();
        foreach($methods as $name => $returnVal) {
            $mockTokenProvider->method($name)->will($this->returnValue($returnVal));
        }
        return $mockTokenProvider;
    }

    private function getMockTransport($response, $status_code=200) {
        $transport = new TransportMock();
        $mockResponse = $transport->createResponse($response, [], $status_code);
        $transport->queue($mockResponse);
        $transport->mock();
        return $transport;
    }

    protected function getMockedAbstractClass($class, $args = [], $mock_methods=[]) {
        return $this->getMockForAbstractClass($class, $args, '', true, true, true, $mock_methods);
    }

    public function test_settokenprovider() {
        $crmprovider = $this->getMockedAbstractClass('MyOperator\Crm\CrmProvider', [1]);
        $provider = $this->getMockTokenProvider();
        $crmprovider->setTokenProvider($provider);
        $this->assertInstanceOf('MyOperator\Crm\TokenProvider', $crmprovider->getTokenProvider());
    }

    public function test_set_transport_sets_transport() {
        $crmprovider = $this->getMockedAbstractClass('MyOperator\Crm\CrmProvider', [1]);
        $transport1 = $crmprovider->getTransport();
        $this->assertInstanceOf('\MyOperator\Transport', $transport1);
        $transport2 = new \MyOperator\Transport;
        $crmprovider->setTransport($transport2);
        $transport3 = $crmprovider->getTransport();
        $this->assertInstanceOf('\MyOperator\Transport', $transport3);
        $this->assertSame($transport2 , $transport3);
        $this->assertNotSame($transport3 , $transport1);
    }

    public function test_get_oauthtokenkey_gives_bearer() {
        $crmprovider = $this->getMockedAbstractClass('MyOperator\Crm\CrmProvider', [1]);
        $myclass = new myclass(1);
        $this->assertEquals('Bearer', $crmprovider->getOauthTokenKey());
        $this->assertEquals('somekey', $myclass->getOauthTokenKey());
    }

    public function test_exception_tokenprovider_is_required() {
        $crmprovider = $this->getMockedAbstractClass('myclass', [1]);
        //$this->setExpectedException('Exception');
        try{
            $crmprovider->search_crm();
        } catch(\Exception $e){
            $this->assertContains('Token Provider is not set', $e->getMessage());
            return;
        }
        $this->fail('Exception did not occured');
    }

    public function test_search_calls_refresh_token() {
        $crmprovider = $this->getMockBuilder('myclass')->setConstructorArgs([1])->setMethods(['refreshToken'])->getMock();
        //$crmprovider = new myclass(1);
        $crmprovider->expects($this->once())->method('refreshToken')->will(
            $this->returnValue(['access_token' => 'xyz', 'timeout' => 1000])
        );
        $provider = $this->getMockTokenProvider(['getAccessToken' => null]);
        $crmprovider->setTokenProvider($provider);
        $search = $crmprovider->search_crm();
        $this->assertEquals('hello', $search);
    }
}