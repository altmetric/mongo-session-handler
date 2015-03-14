<?php
namespace Altmetric;

use Altmetric\MongoSessionHandler;
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
        $collection = $this->mockMongoCollection();
        $collection->method('findOne')
                   ->willReturn(['_id' => '123',
                                 'data' => new \MongoBinData('foo', \MongoBinData::BYTE_ARRAY)]);
        $handler = $this->buildHandler($collection);

        $this->assertEquals('foo', $handler->read('123'));
    }

    public function testReadMissingSessionReturnsAnEmptyString()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('findOne')->willReturn(null);
        $handler = $this->buildHandler($collection);

        $this->assertEquals('', $handler->read('123'));
    }

    public function testWriteSuccessfullyReturnsTrue()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('save');
        $handler = $this->buildHandler($collection);

        $this->assertTrue($handler->write('123', serialize(['user_id' => 1])));
    }

    public function testWriteUnsuccessfullyReturnsFalse()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('save')->will($this->throwException(new \MongoException));
        $handler = $this->buildHandler($collection);

        $this->assertFalse($handler->write('123', serialize(['user_id' => 1])));
    }

    public function testDestroySuccessfullyReturnsTrue()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('remove');
        $handler = $this->buildHandler($collection);

        $this->assertTrue($handler->destroy('123'));
    }

    public function testDestroyUnsuccessfullyReturnsFalse()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('remove')->will($this->throwException(new \MongoException));
        $handler = $this->buildHandler($collection);

        $this->assertFalse($handler->destroy('123'));
    }

    public function testGcSuccessfullyReturnsTrue()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('remove');
        $handler = $this->buildHandler($collection);

        $this->assertTrue($handler->gc(14400));
    }

    public function testGcUnsuccessfullyReturnsFalse()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('remove')->will($this->throwException(new \MongoException));
        $handler = $this->buildHandler($collection);

        $this->assertFalse($handler->gc(14400));
    }

    private function buildHandler($collection = null)
    {
        if ($collection === null) {
            $collection = $this->mockMongoCollection();
        }
        $logger = new NullLogger;

        return new MongoSessionHandler($collection, $logger);
    }

    private function mockMongoCollection()
    {
        return $this->getMockBuilder('MongoCollection')
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
