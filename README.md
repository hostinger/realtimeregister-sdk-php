```
<?php
require 'vendor/autoload.php';
$config = array(
    'dealer' => 'name@mail.com',
    'password' => 'realtimedemo',
    'test_mode' => true,
);

$o = new RealtimeRegisterApi($config);
var_dump($o->isDomainAvailable('example.com'));
```