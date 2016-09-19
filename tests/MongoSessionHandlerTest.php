<?php
namespace Altmetric;

use Altmetric\MongoSessionHandler;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use Psr\Log\NullLogger;

class MongoSessionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testOpenReturnsTrue()
    {
        $handler = $this->buildHandler();

        $this->assertTrue($handler->open('/some/path', 'foo'));
    }

    public function testCloseReturnsTrue()
    {
        $handler = $this->buildHandler();

        $this->assertTrue($handler->close());
    }

    public function testReadExistingSessionReturnsTheData()
    {
        $this->collection->insertOne([
            '_id' => '123',
            'data' => new Binary('foo', Binary::TYPE_OLD_BINARY)
        ]);
        $handler = $this->buildHandler();

        $this->assertSame('foo', $handler->read('123'));
    }

    public function testReadMissingSessionReturnsAnEmptyString()
    {
        $handler = $this->buildHandler();

        $this->assertSame('', $handler->read('123'));
    }

    public function testWriteSuccessfullyReturnsTrue()
    {
        $handler = $this->buildHandler();

        $this->assertTrue($handler->write('123', serialize(['user_id' => 1])));
    }

    public function testWriteSuccessfullyPersistsTheSessionToMongoDB()
    {
        $handler = $this->buildHandler();
        $handler->write('123', serialize(['user_id' => 1]));

        $this->assertEquals(serialize(['user_id' => 1]), $handler->read('123'));
    }

    public function testWriteUnsuccessfullyReturnsFalse()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('replaceOne')->will($this->throwException(new \MongoDB\Driver\Exception\RuntimeException));
        $handler = $this->buildHandler($collection);

        $this->assertFalse($handler->write('123', serialize(['user_id' => 1])));
    }

    public function testDestroySuccessfullyReturnsTrue()
    {
        $handler = $this->buildHandler();
        $handler->write('123', serialize(['user_id' => 1]));

        $this->assertTrue($handler->destroy('123'));
    }

    public function testDestroyUnsuccessfullyReturnsFalse()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('deleteOne')->will($this->throwException(new \MongoDB\Driver\Exception\RuntimeException));
        $handler = $this->buildHandler($collection);

        $this->assertFalse($handler->destroy('123'));
    }

    public function testDestroyRemovesSessionFromCollection()
    {
        $handler = $this->buildHandler();
        $handler->write('123', serialize(['user_id' => 1]));
        $handler->write('345', serialize(['user_id' => 2]));
        $handler->destroy('123');

        $this->assertEquals(1, $this->collection->count());
    }

    public function testGcSuccessfullyReturnsTrue()
    {
        $handler = $this->buildHandler();

        $this->assertTrue($handler->gc(14400));
    }

    public function testGcRemovesAllSessionsOlderThanLifetime()
    {
        $now = floor(microtime(true) * 1000);
        $handler = $this->buildHandler();
        $this->collection->insertMany([
            ['_id' => '1', 'last_accessed' => new UTCDateTime($now - (60 * 60 * 3 * 1000))],
            ['_id' => '2', 'last_accessed' => new UTCDateTime($now - (60 * 60 * 2 * 1000))]
        ]);
        $handler->write('3', 'bar');

        $handler->gc(60 * 60);

        $this->assertEquals(1, $this->collection->count());
    }

    public function testGcUnsuccessfullyReturnsFalse()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('deleteMany')->will($this->throwException(new \MongoDB\Driver\Exception\RuntimeException));
        $handler = $this->buildHandler($collection);

        $this->assertFalse($handler->gc(14400));
    }

    public function setUp()
    {
        $client = new \MongoDB\Client();
        $this->collection = $client->selectCollection('sessionhandlertest', 'sessions');
    }

    public function tearDown()
    {
        $this->collection->drop();
    }

    private function buildHandler($collection = null)
    {
        if ($collection === null) {
            $collection = $this->collection;
        }
        $logger = new NullLogger();

        return new MongoSessionHandler($collection, $logger);
    }

    private function mockMongoCollection()
    {
        return $this->getMockBuilder('MongoDB\Collection')
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
