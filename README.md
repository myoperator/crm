## CRM Provider

This repository acts as a CRM provider base for CRMs using OAuth. You can easily use this library to extend your CRM provider implementations. For instance, zoho, pipedrive etc. can easily be implemented to use Oauth using this CRM provider

## Quick Start

Lets suppose you're going to implement Zoho CRM Provider. You can start by:

Extend your `ZohoCrmProvider` class from `\MyOperator\Crm\CrmProvider` class.

```php

use \MyOperator\Crm\CrmProvider;


// Your Zoho provider
class ZohoCrmProvider extends CrmProvider {
    // Your implementation here
}

// Your PipeDrive provider
class PipeDriveCrmProvider extends CrmProvider {
    // Your implementation here
}
```

Once you extend this class, you get access to several methods, which will help you get access
token and refresh token, so you can focus on your implementation for sending and getting data from CRM Provider of your choice.

Once you have extended, you need to implement following functions for your CRM controller to
work correctly:

- `getOauthTokenKey()`
- `getClientId()`
- `getClientSecret()`
- `refreshToken()`

Hence, the basic implementation looks like this:

```php
use \MyOperator\Crm\CrmProvider;

class ZohoCrmProvider extends CrmProvider {

    /* *
     * Use this function to return the value which will be set in OAuth Authorization
     * header. This will send the following header with each request
     * 
     * Ex- header('Authorization', 'Zoho-oauthtoken $token')
     * */
    function getOauthTokenKey() {
        return 'Zoho-oauthtoken';
    }

    /* *
     * Use this function to return the client id of your crm provider
     * */
    function getClientId() {
        return 'your-crm-client-id';
    }

    /* *
     * Use this function to return the client secret of your crm provider
     * */
    function getClientSecret() {
        return 'your-crm-client-secret';
    }

    /* *
     * Use this function to refresh the token as per your CRM implementation.
     * Return values should be either array ['access_token' => $token, 'timeout' => $timeout]
     * or simply a string of access token
     * 
     * @param $client_id Client id
     * @param $client_secret Client Secret
     * @param $refresh_token Refresh token
     * 
     * @return ['access_token' => $token, 'timeout' => $timeout] || $token
     * */
    function refreshToken($client_id, $client_secret, $refresh_token) {
        // This is only a sample implementation
        $response = $your_curl_lib->post("/token?refresh_token={$refresh_token}&client_id= {$client_id}&client_secret={$client_secret}&grant_type=refresh_token");
        return ['access_token' => $response['access_token'], 'timeout' => $response['expiry_in_secs']];
    }
}
```

## Available Methods

### getTransport()

This gives you a `MyOperator\Transport` instance. You can use this to make `get` or `post` calls to your API.

```php
class MyClass extends CrmProvider {

	function my_method() {
		$curlTransport = $this->getTransport();
		$response = $curlTransport->post(
            '/some-endpoint',
            ['data' => 'some-data']
		);
        var_dump($response->json());
        // {"some response": "in json"}
	}
}
```

### setHeader($key, $val)

You can also set custom headers to your crm endpoint, if you need so.

```php
class MyClass extends CrmProvider {

	function my_method() {
        $this->setHeader('a', 'b');
        // Or $this->getTransport()->setHeaders(['a' => 'b']);
        $response = $this->getTransport()->post('/some-endpoint',['data' => 'some-data']);
        var_dump($response->json());
        // {"some response": "in json"}
	}
}
```

## Sending/Receiving data from CRM Provider

Lets begin implementing our `ZohoCrmProvider` class. Lets say we wish to search records and create lead. We can easily implement this.

### Search records

```php
    // This searches Zoho CRM for phone records
    public function searchByPhone($phonenumber) {
        $criteria = "((Phone:equals:{$phonenumber})or(Mobile:equals:{$phonenumber}))";

        // You can use your own curl lib here as well
        $curl_lib = $this->getTransport();

        try { 
            $response = $curl_lib->get(
                self::CRM_BASE_URL . '/leads/search', 
                ['criteria' => $criteria]
            );
            if($response->getStatus() == 204) {
                return null;
            }
            return $response->json();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
```

## Handling expired token

The Major benefit of using `Crm\CrmProvider` library is to handle things such as 
exception handling, refreshing tokens automatically and providing a basic curl 
transport mechanism. 

Hence, any method you create on the class that extends `Crm\CrmProvider` class, it
automatically makes a call to refresh your token. Remember having the need to implement
`refreshToken` method in your class? This is where the magic is. 

`Crm\CrmProvider` interally searches for `refreshToken` method in your class to get the
refreshed `access_token` and `timeout`. 

Essentially, the method your wrote earlier `searchByPhone` can be thought as:

```php
try {
    $this->searchByPhone($phonenumber);
} catch(\Exception $e) {
    // 401 means your api is unauthorized because the token was invalid
    // hence CrmProvider will try to refresh the token and make call
    // once again
    if ($e->getCode() === 401) {
        $this->searchByPhone($phonenumber);
    } else {
        // throw all other exceptions as they are
        throw $e;
    }
}
```

## TODO
Implement following testcases:

- unit
- integration