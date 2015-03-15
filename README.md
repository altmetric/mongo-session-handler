# Mongo Session Handler [![Build Status](https://travis-ci.org/altmetric/mongo-session-handler.svg?branch=master)](https://travis-ci.org/altmetric/mongo-session-handler)

A **work-in-progress** PHP session handler backed by MongoDB.

## Installation

```shell
$ composer require altmetric/mongo-session-handler
```

## Usage

```php
<?php
use Monolog\Logger; // or any other PSR-3 compliant logger
use Altmetric\MongoSessionHandler;

$sessions = $mongoClient->someDB->sessions;
$logger = new Logger;
$handler = new MongoSessionHandler($sessions, $logger);

session_set_save_handler($handler);
session_set_cookie_params(0, '/', '.example.com', false, true);
session_name('my_session_name');
session_start();
```

## Acknowledgements

* [Nick Ilyin's
  `php-mongo-session`](https://github.com/nicktacular/php-mongo-session)
  served as a valuable existing implementation of MongoDB-backed sessions in
  PHP.

## License

Copyright © 2015 Altmetric LLP

Distributed under the MIT License.
