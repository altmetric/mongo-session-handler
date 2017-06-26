# Mongo Session Handler [![Build Status](https://travis-ci.org/altmetric/mongo-session-handler.svg?branch=master)](https://travis-ci.org/altmetric/mongo-session-handler)

A PHP session handler backed by MongoDB.

_**Note:** This package depends on the [legacy MongoDB driver](http://php.net/manual/en/book.mongo.php) (`mongo`). If you need to use the [newer MongoDB driver](http://php.net/manual/en/set.mongodb.php) (`mongodb`) and its companion [PHP library](https://docs.mongodb.com/php-library/master/), please see [version 2.0](https://github.com/altmetric/mongo-session-handler/tree/2.x)._

## Installation

```shell
$ composer require altmetric/mongo-session-handler:^1.0
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

## Concurrency

As MongoDB prior to 3.0 does not support [document level
locking](http://docs.mongodb.org/manual/core/storage/#document-level-locking),
this session handler operates on a principle of Last Write Wins.

If a user of a session causes two simultaneous writes then you may end up with
the following situation:

1. Window A reads session value of `['foo' => 'bar']`;
2. Window B reads session value of `['foo' => 'bar']`;
3. Window B writes session value of `['foo' => 'baz']`;
4. Window A writes session value of `['foo' => 'quux']`.

The session will now contain `['foo' => 'quux']` as it was the last successful
write. This may be surprising if you're trying to increment some value in a
session as it is not locked during reads and writes:

1. Window A reads session value of `['count' => 0]`;
2. Window B reads session value of `['count' => 0]`;
3. Window B writes session value of `['count' => 1]`;
4. Window A writes session value of `['count' => 1]`.

## Acknowledgements

* [Nick Ilyin's
  `php-mongo-session`](https://github.com/nicktacular/php-mongo-session)
  served as a valuable existing implementation of MongoDB-backed sessions in
  PHP.

## License

Copyright © 2015-2017 Altmetric LLP

Distributed under the MIT License.
