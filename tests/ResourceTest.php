<?php

namespace TS\Web\Resource;


use PHPUnit\Framework\TestCase;
use InvalidArgumentException;


class ResourceTest extends TestCase
{

	public function testEmptyOptions()
	{
		$this->expectException(InvalidArgumentException::class);
		$r = new Resource([]);
	}

	public function testMissingFilename()
	{
		$this->expectException(InvalidArgumentException::class);
		$r = new Resource([
			'mimetype' => 'text/plain'
		]);
	}

	public function testMissingMimetype()
	{
		$this->expectException(InvalidArgumentException::class);
		$r = new Resource([
			'filename' => 'plaintext.txt'
		]);
	}

	public function testContent()
	{
		$r = new Resource([
			'filename' => 'plaintext.txt',
			'mimetype' => 'text/plain',
			'content' => 'plain text'
		]);
		$this->assertSame(10, $r->getLength());
		$this->assertSame('9f9443b8f3d8361541f8792562b3050f721ee534', $r->getHash());
		$this->assertSame('plain text', stream_get_contents($r->getStream()));
		$this->assertSame('text/plain', $r->getMimetype());
		$this->assertSame('plaintext.txt', $r->getFilename());
	}

	public function testMissingLength()
	{
		$this->expectException(InvalidArgumentException::class);
		$r = new Resource([
			'filename' => 'plaintext.txt',
			'mimetype' => 'text/plain',
			'stream' => function () {
				return null;
			},
		]);
	}

	public function testStream()
	{
		$r = new Resource([
			'filename' => 'plaintext.txt',
			'mimetype' => 'text/plain',
			'stream' => function () {
				$stream = fopen('php://memory', 'r+');
				fwrite($stream, 'plain text');
				rewind($stream);
				return $stream;
			},
			'length' => 10
		]);
		$this->assertSame(10, $r->getLength());
		$this->assertSame('9f9443b8f3d8361541f8792562b3050f721ee534', $r->getHash());
		$this->assertSame('plain text', stream_get_contents($r->getStream()));
		$this->assertSame('text/plain', $r->getMimetype());
		$this->assertSame('plaintext.txt', $r->getFilename());
	}

}

