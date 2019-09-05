<?php

use PHPUnit\Framework\TestCase;

use \MyOperator\Zoho\TokenController;
use \MyOperator\Zoho\Providers\APITokenProvider;

final class TokenControllerUnitTest extends TestCase {

    private function getMockTokenProvider($methods = []) {
        $methods = array_merge(['setClientid' => null, 'setClientSecret' => null, 'getAccessToken' => 'abc', 'getRefreshToken' => 'def', 'getClientId' => 1, 'getClientSecret' => 2, 'isApiV2' => true, 'setAccessToken' => 0, 'setRefreshToken' => 0], $methods);
        $mockTokenProvider = $this->getMockBuilder('MyOperator\Zoho\TokenProvider')->setMethods(array_keys($methods))->getMock();
        foreach($methods as $name => $returnVal) {
            $mockTokenProvider->method($name)->will($this->returnValue($returnVal));
        }
        return $mockTokenProvider;
    }

    public function test_can_get_access_token() {
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);
        $tokenCtrl = new TokenController(1);
        $tokenCtrl->setProvider($tokenProvider);
        $this->assertEquals('abc', $tokenCtrl->getAccessToken());
    }

    public function test_can_get_refresh_token() {
        $tokenProvider = $this->getMockTokenProvider(['getRefreshToken' => 'abc']);
        $tokenCtrl = new TokenController(1);
        $tokenCtrl->setProvider($tokenProvider);
        $this->assertEquals('abc', $tokenCtrl->getRefreshToken());
    }

    public function test_refresh_token_gets_access_token() {
        $tokenProvider = $this->getMockTokenProvider([
            'getClientId' => '1', 
            'getClientSecret' => '2', 
            'getAccessToken' => false, 
            'getRefreshToken' => 'token',
            'setAccessToken' => true,
        ]);

        $tokenCtrl = new TokenController(1);
        $tokenCtrl->setProvider($tokenProvider);

        $tokenCtrl->setRefreshMethod(function ($client_id, $client_secret, $refresh_token) {
            $this->assertEquals('1', $client_id);
            $this->assertEquals('2', $client_secret);
            $this->assertEquals('token', $refresh_token);
            return 'mytoken';
        });

        $this->assertEquals('mytoken', $tokenCtrl->getAccessToken());
    }

    public function test_expired_token_refresh_automatically() {
        $tokenProvider = $this->getMockTokenProvider([
            'getClientId' => '1', 
            'getClientSecret' => '2', 
            'getAccessToken' => 'abc', 
            'getRefreshToken' => 'token',
            'setAccessToken' => true,
        ]);

        $tokenCtrl = new TokenController(1);
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

