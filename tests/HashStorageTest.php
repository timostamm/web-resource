<?php

namespace TS\Web\Resource;


use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use TS\Web\Resource\Exception\IOException;


class HashStorageTest extends TestCase
{


    public function testEmpty()
    {
        $this->assertCount(0, $this->storage->listHashes());
    }

    public function testCleanupAfterWriteFailure()
    {
        $mock = $this->getMockBuilder(FileResource::class)
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setConstructorArgs([
                $this->a->getPath()
            ])
            ->setMethods([
                'getStream'
            ])
            ->getMock();

        $mock->method('getStream')->willThrowException(new \Exception('failure'));

        try {

            $this->storage->put($mock);
            $this->fail('Expected exception.');

        } catch (IOException $ex) {
            $this->assertEquals('Failed to store resource.', $ex->getMessage());
            $this->assertCount(0, $this->storage->listHashes());
        }
    }

    public function testHas()
    {
        $this->assertFalse($this->storage->has($this->a->getHash()));
        $this->storage->put($this->a);
        $this->assertTrue($this->storage->has($this->a->getHash()));
    }

    public function testPut()
    {
        $this->assertCount(0, $this->storage->listHashes());
        $this->storage->put($this->a);
        $this->assertCount(1, $this->storage->listHashes());
    }

    private $storageDir;

    private $storage;

    private $a;

    private $b;

    public function setUp(): void
    {

        $root = vfsStream::setup('root', null, [
            'a.txt' => 'a content',
            'b.txt' => 'b content'
        ]);

        $this->a = Resource::fromFile($root->url() . '/a.txt');
        $this->a = Resource::fromFile($root->url() . '/b.txt');
        $this->storageDir = $root->url() . '/store';
        $this->storage = new HashStorage($this->storageDir);
    }

}

