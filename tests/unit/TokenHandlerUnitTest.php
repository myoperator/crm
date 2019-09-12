<?php

use PHPUnit\Framework\TestCase;

use \MyOperator\Crm\TokenHandler;
use \MyOperator\Crm\TokenProvider;

final class TokenHandlerTest extends TestCase {

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

    public function test_can_get_access_token_if_set() {
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);
        $tokenProvider->expects($this->never())->method('getRefreshToken');

        $tokenCtrl = new TokenHandler(1);
        $tokenCtrl->setProvider($tokenProvider);
        $this->assertEquals('abc', $tokenCtrl->getAccessToken());
    }

    public function test_access_token_if_unset_calls_refreshtoken() {
        $tokenProvider = $this->getMockTokenProvider([
            'getAccessToken' => null,
            'getRefreshToken' => 'a',
            'getClientId' => 'b',
            'getClientSecret' => 'c'
        ]);
        $tokenProvider->expects($this->once())->method('getRefreshToken');
        $tokenProvider->expects($this->once())->method('getClientId');
        $tokenProvider->expects($this->once())->method('getClientSecret');
        $tokenProvider->expects($this->once())->method('setAccessToken');

        $tokenCtrl = new TokenHandler(1);
        $tokenCtrl->setProvider($tokenProvider);

        $tokenCtrl->setRefreshMethod(function($client_id, $client_secret, $refresh_token) {
            $this->assertEquals('a', $refresh_token);
            $this->assertEquals('b', $client_id);
            $this->assertEquals('c', $client_secret);
            return ['access_token' => 'xyz', 'timeout' => 1000];
        });

        $this->assertEquals('xyz', $tokenCtrl->getAccessToken());
    }

    public function test_can_get_refresh_token() {
        $tokenProvider = $this->getMockTokenProvider(['getRefreshToken' => 'a']);
        $tokenCtrl = new TokenHandler(1);
        $tokenCtrl->setProvider($tokenProvider);
        $this->assertEquals('a', $tokenCtrl->getRefreshToken());
    }

    public function test_null_refresh_token_returns_null_accesstoken() {
        $tokenProvider = $this->getMockTokenProvider([
            'getAccessToken' => null,
            'getRefreshToken' => null,
            'getClientId' => null,
            'getClientSecret' => null
        ]);
        $tokenProvider->expects($this->once())->method('getRefreshToken');
        $tokenProvider->expects($this->once())->method('getClientId');
        $tokenProvider->expects($this->once())->method('getClientSecret');
        $tokenProvider->expects($this->never())->method('setAccessToken');

        $tokenCtrl = new TokenHandler(1);
        $tokenCtrl->setProvider($tokenProvider);

        $tokenCtrl->setRefreshMethod(function($client_id, $client_secret, $refresh_token) {
            $this->assertNull($refresh_token);
            $this->assertNull($client_id);
            $this->assertNull($client_secret);
            return ['access_token' => null, 'timeout' => 0];
        });

        $this->assertEquals(null, $tokenCtrl->getAccessToken());
    }

    public function test_expired_token_refresh_automatically() {
        $tokenProvider = $this->getMockTokenProvider([
            'getClientId' => '1', 
            'getClientSecret' => '2', 
            'getAccessToken' => 'abc', 
            'getRefreshToken' => 'token',
            'setAccessToken' => true,
        ]);

        $tokenCtrl = new TokenHandler(1);
        $tokenCtrl->setProvider($tokenProvider);

        $this->assertEquals('abc', $tokenCtrl->getAccessToken());

        $tokenProvider = $this->getMockTokenProvider([
            'getClientId' => '1', 
            'getClientSecret' => '2', 
            'getAccessToken' => false, 
            'getRefreshToken' => 'token',
            'setAccessToken' => true,
        ]);
        $tokenCtrl->setProvider($tokenProvider);

        $tokenCtrl->setRefreshMethod(function ($client_id, $client_secret, $refresh_token) {
            $this->assertEquals('1', $client_id);
            $this->assertEquals('2', $client_secret);
            $this->assertEquals('token', $refresh_token);
            return 'mycooltoken';
        });

        $this->assertEquals('mycooltoken', $tokenCtrl->getAccessToken());
    }
}

