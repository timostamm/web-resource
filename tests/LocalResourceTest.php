<?php

namespace TS\Web\Resource;


use PHPUnit\Framework\TestCase;
use DateTime;
use InvalidArgumentException;


class LocalResourceTest extends TestCase
{

	public function testFromResource()
	{
		$this->markTestIncomplete("fromResource test not implemented");
	}

	public function testConstructor()
	{
		$r = new LocalResource(__DIR__ . '/Data/plaintext.txt');
		$this->assertSame('plaintext.txt', $r->getFilename());
		$this->assertSame(__DIR__ . '/Data/plaintext.txt', $r->getPath());
		$this->assertSame('text/plain', $r->getMimetype());
		$this->assertSame(10, $r->getLength());
		$this->assertInstanceOf(DateTime::class, $r->getLastModified());
		$this->assertSame('9f9443b8f3d8361541f8792562b3050f721ee534', $r->getHash());
	}

	public function testFileNotFound()
	{
		$this->expectException(InvalidArgumentException::class);
		new LocalResource(__DIR__ . '/Data/does-not-exist');
	}

	public function testOverrideFilename()
	{
		$r = new LocalResource(__DIR__ . '/Data/plaintext.txt', [
			'filename' => 'dummy.foo'
		]);
		$this->assertSame('dummy.foo', $r->getFilename());
		$this->assertSame(__DIR__ . '/Data/plaintext.txt', $r->getPath());
		$this->assertSame('text/plain', $r->getMimetype());
	}

	public function testOverrideMimetype()
	{
		$r = new LocalResource(__DIR__ . '/Data/plaintext.txt', [
			'mimetype' => 'image/jpeg'
		]);
		$this->assertSame('image/jpeg', $r->getMimetype());
	}

	public function testOverrideHash()
	{
		$r = new LocalResource(__DIR__ . '/Data/plaintext.txt', [
			'hash' => '31bc5c2b8fd4f20cd747347b7504a385'
		]);
		$this->assertSame('31bc5c2b8fd4f20cd747347b7504a385', $r->getHash());
	}

	public function testOverrideLastModified()
	{
		$now = new DateTime();
		$r = new LocalResource(__DIR__ . '/Data/plaintext.txt', [
			'lastmodified' => $now
		]);
		$this->assertSame($now, $r->getLastModified());
	}

	public function testOpen()
	{
		$r = new LocalResource(__DIR__ . '/Data/plaintext.txt');
		$h = $r->open('a');
		$this->assertTrue(is_resource($h));
		fclose($h);
	}

	public function testGetStream()
	{
		$r = new LocalResource(__DIR__ . '/Data/plaintext.txt');
		$h = $r->getStream();
		$this->assertTrue(is_resource($h));
		fclose($h);
	}

	/**
	 * Tests LocalResource->__toString()
	 */
	public function test__toString()
	{
		$file = __DIR__ . '/Data/plaintext.txt';
		$r = new LocalResource($file);
		$this->assertSame(sprintf('[LocalResource %s text/plain 10B]', $file), (string) $r);
	
	}

}

