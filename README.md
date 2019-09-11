## CRM Provider

This repository acts as a CRM provider base for CRMs using OAuth. You can easily use this library to extend your CRM provider implementations. For instance, zoho, pipedrive etc. can easily be implemented to use Oauth using this CRM provider

## Install

Use composer to install this package. To install, add following in your compsoer.json

```json
{
   "require": {
        "myoperator/crm": "dev-master"
    },
    "repositories": [
        {
            "url": "http://github.com/myoperator/crm",
            "type": "vcs"
        }
    ]
}
```

and then `composer install` to install the package.

```sh

## Quick Start

### Creating your CRM Class

Extend your `MyCrm` class from `\MyOperator\Crm\CrmProvider` class.

```php

use \MyOperator\Crm\CrmProvider;


// Your Zoho provider
class MyCrm extends CrmProvider {
    // Your implementation here
}
```

### Implement Refresh token mechanism
Once you extend this class, you get access to several methods, which will help you get access
token and refresh token. You will need to implement `refreshToken()` method for your CRM controller to tell how to refresh token from your crm provider.

```php
use \MyOperator\Crm\CrmProvider;

class MyCrm extends CrmProvider {

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

    function search($records) {
        // Implement your crm search here
    }
}
```

### Set your token provider

`CrmProvider` uses any token provider which implements `MyOperator\Crm\TokenProvider` class. 
You can use `apitokenprovider.php` as an example (from examples folder). Basic implementation follows:

```php

use \MyOperator\Crm\TokenProvider;

class MyTokenProvider implements TokenProvider {

    public function getClientId() {
        // Implementation to get client id
    }
    
    // Implement rest of the methods from TokenProvider
}
```

### Implementation

Now comes the final part. At this point, you will have two files:

- `MyCrm.php`
- `MyTokenProvider.php`

You can implement this as:

```php
$company_id = 1;

$mycrm = new MyCrm($company_id);

$provider = new MyTokenProvider();
$mycrm->setTokenProvider($provider);

$results = $mycrm->search($record);
```


## Available Methods

### getOauthTokenKey()

You can get the [Bearer](https://tools.ietf.org/html/rfc6750) name by this method.

```php
class MyCrm extends CrmProvider {

    function search(){
        var_dump($this->getOauthTokenKey());
        // Bearer
    }
}
```

You can also override this method to set custom bearer on your authorization headers.
For example, zoho uses `Zoho-oauthtoken` as bearer. You can easily set this as:

```php
class MyCrm extends CrmProvider {

    // This means our authorization header will look like
    // `Authorization: my-bearer $token`
    function getOauthTokenKey() {
        return 'my-bearer';
    }
}
```


### getTransport()

This gives you a [`MyOperator\Transport`](http://github.com/myoperator/transport) instance. You can use this to make `get` or `post` calls to your API.

```php
class MyCrm extends CrmProvider {

	function search() {
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

	function search() {
        	$this->setHeader('a', 'b');
       		// Or $this->getTransport()->setHeaders(['a' => 'b']);
        	$response = $this->getTransport()->post('/some-endpoint',['data' => 'some-data']);
        	var_dump($response->json());
        	// {"some response": "in json"}
	}
}
```

## Sample Zoho CRM Provider

Lets implement `ZohoCrm` class to search leads. Lets say we wish to search records and create lead. We can easily implement this.

```php
use \MyOperator\Crm\CrmProvider;

class ZohoCrm extends CrmProvider{

    // Zoho uses Zoho-oauthtoken as bearer
    function getOauthTokenKey() {
        return 'Zoho-oauthtoken';
    }

    // Zoho implementation to refresh token
    function refreshToken($client_id, $client_secret, $refresh_token) {
        $transport = $this->getTransport();
        $response = $transport->post("https://accounts.zoho.in/oauth/v2/token?refresh_token={$refresh_token}&client_id={$client_id}&client_secret={$client_secret}&grant_type=refresh_token");
        $response =  $response->json();
        return ['access_token' => $response['access_token'], 'timeout' => $response['expiry_in_secs']];
    }

    // This searches Zoho CRM for phone records
    public function searchByPhone($phonenumber) {
        $criteria = "((Phone:equals:{$phonenumber})or(Mobile:equals:{$phonenumber}))";

        // You can use your own curl lib here as well
        $curl_lib = $this->getTransport();

        try { 
            $response = $curl_lib->get(
                'https://www.zohoapis.in/crm/v2/leads/search', 
                ['criteria' => $criteria]
            );
            if($response->getStatus() == 204) {
                return null;
            }
            return $response->json();
        } catch (\Exception $e) {
            // Some zoho exception occured. Handle it
            throw $e;
        }
    }
}
```

## Handling expired token

The Major benefit of using `\MyOperator\Crm\CrmProvider` library is to handle things such as 
exception handling, refreshing tokens automatically and providing a basic curl 
transport mechanism. 

Hence, any method you create on the class that extends `\MyOperator\Crm\CrmProvider` class, it
automatically makes a call to refresh your token. Remember having the need to implement
`refreshToken` method in your class? This is where the magic is. 

`\MyOperator\Crm\CrmProvider` interally searches for `refreshToken` method in your class to get the refreshed `access_token` and `timeout`. 

Essentially, the method your wrote earlier can be thought as:

```php
class MyCrm {

    function refreshToken($client_id, $client_secret, $refresh_token) {
        // This is only a sample implementation
        $response = $your_curl_lib->post("/token?refresh_token={$refresh_token}&client_id= {$client_id}&client_secret={$client_secret}&grant_type=refresh_token");
        return ['access_token' => $response['access_token'], 'timeout' => $response['expiry_in_secs']];
    }

    function search($record) {
        try {
            // Your crm search api
        } catch(\Exception $e) {
            if ($e->getCode() === 401) {
                list($accesstoken, $expiry) = $this->refreshToken($clientid, $clientsecret, $refreshtoken);
                // Set access token in headers using your curl library
                // Your crm search api (again)
            } else {
                // throw all other exceptions as they are
                throw $e;
            }
        }
    }
}
```

## TODO
Implement following testcases:

- unit
- integration
