<?php

use PHPUnit\Framework\TestCase;

use MyOperator\Zoho\ZohoController;
use MyOperator\Zoho\TokenController;
use MyOperator\TransportMock;

final class ZohoControllerUnitTest extends TestCase {

    private function getMockTokenProvider($methods = []) {
        $methods = array_merge(['setClientid' => null, 'setClientSecret' => null, 'getAccessToken' => 'abc', 'getRefreshToken' => 'def', 'getClientId' => 1, 'getClientSecret' => 2, 'isApiV2' => true, 'setAccessToken' => 0, 'setRefreshToken' => 0], $methods);
        $mockTokenProvider = $this->getMockBuilder('MyOperator\Zoho\TokenProvider')->setMethods(array_keys($methods))->getMock();
        foreach($methods as $name => $returnVal) {
            if($returnVal != null)
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

    /**
     * We aim this to test if search method actually tries to refresh the token
     * and get 200 on consecutive request, thus returning the data
     **/
    public function test_searchlead_returns_data_when_unauthorized_and_success() {
        $zohoController = new ZohoController(1);
        //Mocking token provider
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);

        $tokenHandler = new TokenController(1);
        $tokenHandler->setProvider($tokenProvider);

        $zohoController->setTokenHandler($tokenHandler);

        //Mocking API Request
        $transport = $this->getMockTransport(json_encode(['code' => 'INVALID_TOKEN']), 401);
        $mockResponse = $transport->createResponse(json_encode(['access_token' => '', 'expires_in_sec' => 3600, 'expires_in' => 3600000]), [], 200);
        $transport->queue($mockResponse);
        $transport->mock();
        $zohoController->setTransport($transport);

        //Since we have queued only single response, and since this function is making a retry,
        // this should fail by status 0
        try {
            $zohoController->searchByPhone('1234');
        } catch(\Exception $e) {
            $this->assertEquals(0, $e->getCode());
            $this->assertInstanceOf(\OutOfBoundsException::class, $e);
        }


        $zohoController = new ZohoController(1);
        $zohoController->setTokenHandler($tokenHandler);
        $transport = $this->getMockTransport(json_encode(['code' => 'INVALID_TOKEN']), 401);
        $mockResponse = $transport->createResponse(json_encode(['access_token' => '', 'expires_in_sec' => 3600, 'expires_in' => 3600000]), [], 200);
        $mockResponse2 = $transport->createResponse(json_encode(['data' => [['Company' => 'MyOperator']]]), [], 200);

        $transport->queue($mockResponse);
        $transport->queue($mockResponse2);
        $transport->mock();

        $zohoController->setTransport($transport);
        $response = $zohoController->searchByPhone('1234');
        $this->assertEquals(['data' => [['Company' => 'MyOperator']]], $response);
   }

    public function test_searchlead_throws_exception_when_unauthorized() {

        $zohoController = new ZohoController(1);
        //Mocking token provider
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);

        $tokenHandler = new TokenController(1);
        $tokenHandler->setProvider($tokenProvider);

        $zohoController->setTokenHandler($tokenHandler);

        //Mocking API Request
        $transport = $this->getMockTransport(json_encode(['code' => 'INVALID_TOKEN']), 401);
        $mockResponse = $transport->createResponse(json_encode(['access_token' => '', 'expires_in_sec' => 3600, 'expires_in' => 3600000]), [], 200);
        $mockResponse2 = $transport->createResponse(json_encode(['code' => 'INVALID_TOKEN']), [], 401);
        $transport->queue($mockResponse);
        $transport->queue($mockResponse2);
        $transport->mock();
        $zohoController->setTransport($transport);

        //Since we have queued only single response, and since this function is making a retry,
        // this should fail by status 0
        try {
            $zohoController->searchByPhone('1234');
        } catch(\Exception $e) {
            $this->assertEquals(401, $e->getCode());
        }
    }

    public function test_can_search_lead() {

        $zohoController = new ZohoController(1);

        //Mocking API Request
        $mockResponse = json_encode(['data' => [
            [
                'Company' => 'MyOperator',
                "Email" => "person@ivr.com"
            ]
        ]]);
        $transport = $this->getMockTransport($mockResponse, 200);

        //Mocking token provider
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);

        $tokenHandler = new TokenController(1);
        $tokenHandler->setProvider($tokenProvider);

        $zohoController->setTokenHandler($tokenHandler);
        $zohoController->setTransport($transport);

        $response = $zohoController->searchByPhone('1234');

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('Company', $response['data'][0]);
    }

    public function test_can_create_lead() {

        $zohoController = new ZohoController(1);

        //Mocking API Request
        $mockResponse = json_encode(['data' => [
            [
                'code' => 'SUCCESS',
                "status" => "success",
                'details' => ['id' => 'xyz']
            ]
        ]]);
        $transport = $this->getMockTransport($mockResponse, 200);

        //Mocking token provider
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);

        $tokenHandler = new TokenController(1);
        $tokenHandler->setProvider($tokenProvider);

        $zohoController->setTokenHandler($tokenHandler);
        $zohoController->setTransport($transport);

        $response = $zohoController->createLead(['Company'=>'1234']);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('success', strtolower($response['data'][0]['code']));
    }

    public function test_can_update_lead() {

        $zohoController = new ZohoController(1);

        //Mocking API Request
        $mockResponse = json_encode(['data' => [
            [
                'code' => 'SUCCESS',
                "status" => "success",
                'details' => ['id' => 'xyz']
            ]
        ]]);
        $transport = $this->getMockTransport($mockResponse, 200);

        //Mocking token provider
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);

        $tokenHandler = new TokenController(1);
        $tokenHandler->setProvider($tokenProvider);

        $zohoController->setTokenHandler($tokenHandler);
        $zohoController->setTransport($transport);

        $response = $zohoController->updateLead(['Company'=>'1235']);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('success', strtolower($response['data'][0]['code']));
    }

    public function test_can_add_activity() {

        $zohoController = new ZohoController(1);

        //Mocking API Request
        $mockResponse = json_encode(['data' => [
            [
                'code' => 'SUCCESS',
                "status" => "success",
                'details' => ['id' => 'xyz']
            ]
        ]]);
        $transport = $this->getMockTransport($mockResponse, 200);

        //Mocking token provider
        $tokenProvider = $this->getMockTokenProvider(['getAccessToken' => 'abc']);

        $tokenHandler = new TokenController(1);
        $tokenHandler->setProvider($tokenProvider);

        $zohoController->setTokenHandler($tokenHandler);
        $zohoController->setTransport($transport);

        $response = $zohoController->createActivity(['Company'=>'1235']);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('success', strtolower($response['data'][0]['code']));
    }
}


