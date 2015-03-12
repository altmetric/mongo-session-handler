<?php
namespace Altmetric;

use Altmetric\MongoSessionHandler;
use Psr\Log\NullLogger;

class MongoSessionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testOpen()
    {
        $handler = $this->buildHandler();

        $this->assertTrue($handler->open('/some/path', 'foo'));
    }

    public function testClose()
    {
        $handler = $this->buildHandler();

        $this->assertTrue($handler->close());
    }

    public function testReadExistingSession()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('findOne')
                   ->willReturn(['_id' => '123', 'data' => new \MongoBinData('foo', \MongoBinData::BYTE_ARRAY)]);
        $handler = $this->buildHandler($collection);

        $this->assertEquals('foo', $handler->read('123'));
    }

    public function testReadMissingSession()
    {
        $collection = $this->mockMongoCollection();
        $collection->method('findOne')->willReturn(null);
        $handler = $this->buildHandler($collection);

        $this->assertEquals('', $handler->read('123'));
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
