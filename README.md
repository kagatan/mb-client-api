# mb-client-api
MikBiLL client API


## Documentation

https://wiki.mikbill.pro/billing/external/api_cabinet
https://documenter.getpostman.com/view/5969645/TVCfXTtK
 
## Installation

Install using composer:

```bash
composer require kagatan/mb-client-api
```

## Usage

```php

<?php 

use Kagatan\MikBillClientAPI\ClientAPI;

$host = 'http://api.mybilling.com.ua';
$userLogin = 'xxx';
$userPass = 'xxx';

$api = new ClientAPI($host);

$response = $api->auth($userLogin, $userPass);

if (isset($response['success']) and $response['success'] == true) {
    var_dump($api->getUser());
} else {
    var_dump($response);
}


```