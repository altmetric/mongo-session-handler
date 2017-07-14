# Mongo Session Handler [![Build Status](https://travis-ci.org/altmetric/mongo-session-handler.svg?branch=master)](https://travis-ci.org/altmetric/mongo-session-handler)

A PHP session handler backed by MongoDB.

**Current version:** 2.1.0  
**Supported PHP versions:** 5.4, 5.5, 5.6, 7

_**Note:** This package depends on the [MongoDB PHP driver](http://php.net/manual/en/set.mongodb.php) extension (`mongodb`) and its companion [PHP library](https://docs.mongodb.com/php-library/master/). If you need to use the older, [legacy driver](http://php.net/manual/en/book.mongo.php) (`mongo`), please see [version 1.0](https://github.com/altmetric/mongo-session-handler/tree/1.x)._

## Installation

```shell
$ composer require altmetric/mongo-session-handler:^2.1
```

## Usage

```php
<?php
use Altmetric\MongoSessionHandler;

$sessions = $mongoClient->someDB->sessions;
$handler = new MongoSessionHandler($sessions);

session_set_save_handler($handler);
session_set_cookie_params(0, '/', '.example.com', false, true);
session_name('my_session_name');
session_start();
```

## API Documentation

### `public MongoSessionHandler::__construct(MongoDB\Collection $collection[, Psr\Log\LoggerInterface $logger])`

```php
$handler = new \Altmetric\MongoSessionHandler($client->db->sessions);
session_set_save_handler($handler);
session_start();

$handler = new \Altmetric\MongoSessionHandler($client->db->sessions, $logger);
```

Instantiate a new MongoDB session handler with the following arguments:

* `$collection`: a [`MongoDB\Collection`](http://mongodb.github.io/mongo-php-library/classes/collection/) collection to use for session storage;
* `$logger`: an optional [PSR-3](http://www.php-fig.org/psr/psr-3/)-compliant logger.

The given `$collection` will be populated with documents using the following schema:

* `_id`: the `String` session ID;
* `data`: the session data stored as a [`MongoDB\BSON\Binary`](http://php.net/manual/en/class.mongodb-bson-binary.php) object using the [old generic binary format](http://php.net/manual/en/class.mongodb-bson-binary.php#mongodb-bson-binary.constants.type-old-binary) for compatibility with older versions of this library;
* `last_accessed`: a [`MongoDB\BSON\UTCDateTime`](http://php.net/manual/en/class.mongodb-bson-utcdatetime.php) representation of the time this session was last written to.

This handler implements the [`SessionHandlerInterface`](http://php.net/manual/en/class.sessionhandlerinterface.php) meaning that it can be registered as a session handler with [`session_set_save_handler`](http://php.net/manual/en/function.session-set-save-handler.php).

## Expiring sessions

If you wish to clean up expired sessions using [`SessionHandlerInterface::gc`](http://php.net/manual/en/sessionhandlerinterface.gc.php), ensure that your [`session.gc_divisor`](http://php.net/manual/en/session.configuration.php#ini.session.gc-divisor), [`session.gc_probability`](http://php.net/manual/en/session.configuration.php#ini.session.gc-probability) and [`session.gc_maxlifetime`](http://php.net/manual/en/session.configuration.php#ini.session.gc-maxlifetime) settings are populated accordingly, e.g. the following settings in your [`php.ini`](http://php.net/manual/en/configuration.file.php) mean that sessions that haven't been updated in over an hour have a 1% chance of being cleaned whenever someone starts a new session:

```ini
session.gc_probability = 1
session.gc_divisor = 100
session.gc_maxlifetime = 3600
```

In order to keep session cleaning fast, you should [add an index](https://docs.mongodb.com/manual/reference/method/db.collection.createIndex/) on the `last_accessed` field of your session `$collection`, e.g.

```javascript
db.sessions.createIndex({last_accessed: 1});
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
* Thanks to [Josh Ribakoff](https://github.com/joshribakoff) for suggesting
  that the logger should be optional.

## License

Copyright © 2015-2017 Altmetric LLP

Distributed under the MIT License.
