<?php

use PHPUnit\Framework\TestCase;

use MyOperator\Zoho\Providers\APITokenProvider;
use MyOperator\Transport;
use MyOperator\TransportMock;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

final class APITokenProviderUnitTest extends TestCase {

    public function getMockTransport($response, $status_code=200) {
        // Inititalising a mocker
        $transport = new TransportMock();
        $mockResponse = $transport->createResponse($response, [], $status_code);
        $transport->queue($mockResponse);
        $transport->mock();
        return $transport;
    }

    public function test_uses_transport_instance() {
        $apiTokenProvider = new APITokenProvider();
        $this->assertInstanceOf(Transport::class, $apiTokenProvider->getHTTPClient());
    }

    public function test_can_get_access_token() {
        $apiTokenProvider = new APITokenProvider();
        $mockTransport = $this->getMockTransport(json_encode(['status'=> 'success', 'result' => ['1_zoho_access_token' => 'abc']]));
        $apiTokenProvider->setHTTPClient($mockTransport);
        $apiTokenProvider->setKey('zoho');
        $this->assertEquals('abc', $apiTokenProvider->getAccessToken(1));
    }

    public function test_can_get_refresh_token() {
        $apiTokenProvider = new APITokenProvider();
        $mockTransport = $this->getMockTransport(json_encode(['status'=> 'success', 'result' => ['1_zoho_refresh_token' => 'abc']]));
        $apiTokenProvider->setHTTPClient($mockTransport);
        $apiTokenProvider->setKey('zoho');
        $this->assertEquals('abc', $apiTokenProvider->getRefreshToken(1));
    }

    public function test_can_set_access_token() {
        $apiTokenProvider = new APITokenProvider();
        $mockTransport = $this->getMockTransport(json_encode(['status'=> 'success', 'code' => 200, 'data' => ['1_zoho_access_token' => 'def']]));
        $apiTokenProvider->setHTTPClient($mockTransport);
        $apiTokenProvider->setKey('zoho');
        $this->assertTrue($apiTokenProvider->setAccessToken(1, 'def'));
    }

    public function test_can_set_refresh_token() {
        $apiTokenProvider = new APITokenProvider();
        $mockTransport = $this->getMockTransport(json_encode(['status'=> 'success', 'code' => 200, 'data' => ['1_zoho_refresh_token' => 'def']]));
        $apiTokenProvider->setHTTPClient($mockTransport);
        $apiTokenProvider->setKey('zoho');
        $this->assertTrue($apiTokenProvider->setRefreshToken(1, 'def'));
    }

    public function test_client_id() {
        $apiTokenProvider = new APITokenProvider();
        $apiTokenProvider->setClientId('abc');
        $this->assertEquals('abc', $apiTokenProvider->getClientId());
    }

    public function test_client_secret() {
        $apiTokenProvider = new APITokenProvider();
        $apiTokenProvider->setClientSecret('xyz');
        
        $this->assertEquals('xyz', $apiTokenProvider->getClientSecret());
    }

    public function test_is_api_v2() {
        $apiTokenProvider = new APITokenProvider();
        $mockTransport = $this->getMockTransport(json_encode(['status'=> 'success', 'code' => 200, 'result' => ['1_zoho_api_version' => 2]]));
        $apiTokenProvider->setHTTPClient($mockTransport);
        $apiTokenProvider->setKey('zoho');
        $this->assertTrue($apiTokenProvider->isApiV2(1));
    }

    /**
     * @expectedException Exception
     **/
    public function test_invalid_response_throws_exception_access_token() {
        $apiTokenProvider = new APITokenProvider();

        $mockTransport = $this->getMockTransport(json_encode(['a' => 'b']), 500);
        $apiTokenProvider->setHTTPClient($mockTransport);
        $apiTokenProvider->setKey('zoho');
        $apiTokenProvider->getAccessToken(1);
    }

    public function test_is_crm_active() {
        $apiTokenProvider = new APITokenProvider();
        $mockTransport = $this->getMockTransport(json_encode(['status'=> 'success', 'code' => 200, 'result' => ['1_zoho_status' => '1']]));
        $apiTokenProvider->setHTTPClient($mockTransport);
        $apiTokenProvider->setKey('zoho');
        $this->assertEquals(true, $apiTokenProvider->isCrmActive(1));
    }
}


