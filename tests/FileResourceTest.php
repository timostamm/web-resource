<?php

namespace TS\Web\Resource;


use PHPUnit\Framework\TestCase;
use DateTime;
use InvalidArgumentException;


class FileResourceTest extends TestCase
{

	public function testFromResource()
	{
		$r = new Resource([
			'content' => 'abc', 
			'mimetype' => 'text/plain', 
			'filename' => 'text.txt'
		]);
		
		$path = ResourceUtil::createTempFile('resource');
		
		$f = FileResource::fromResource($r, $path);
		
		$this->assertEquals($path, $f->getPath());
		
		$this->assertEquals('text.txt', $f->getFilename());
	}

	public function testConstructor()
	{
		$r = new FileResource(__DIR__ . '/Data/plaintext.txt');
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
		new FileResource(__DIR__ . '/Data/does-not-exist');
	}

	public function testOverrideFilename()
	{
		$r = new FileResource(__DIR__ . '/Data/plaintext.txt', [
			'filename' => 'dummy.foo'
		]);
		$this->assertSame('dummy.foo', $r->getFilename());
		$this->assertSame(__DIR__ . '/Data/plaintext.txt', $r->getPath());
		$this->assertSame('text/plain', $r->getMimetype());
	}

	public function testOverrideMimetype()
	{
		$r = new FileResource(__DIR__ . '/Data/plaintext.txt', [
			'mimetype' => 'image/jpeg'
		]);
		$this->assertSame('image/jpeg', $r->getMimetype());
	}

	public function testOverrideHash()
	{
		$r = new FileResource(__DIR__ . '/Data/plaintext.txt', [
			'hash' => '31bc5c2b8fd4f20cd747347b7504a385'
		]);
		$this->assertSame('31bc5c2b8fd4f20cd747347b7504a385', $r->getHash());
	}

	public function testOverrideLastModified()
	{
		$now = new DateTime();
		$r = new FileResource(__DIR__ . '/Data/plaintext.txt', [
			'lastmodified' => $now
		]);
		$this->assertSame($now, $r->getLastModified());
	}

	public function testOpen()
	{
		$r = new FileResource(__DIR__ . '/Data/plaintext.txt');
		$h = $r->open('a');
		$this->assertTrue(is_resource($h));
		fclose($h);
	}

	public function testGetStream()
	{
		$r = new FileResource(__DIR__ . '/Data/plaintext.txt');
		$h = $r->getStream();
		$this->assertTrue(is_resource($h));
		fclose($h);
	}

	/**
	 * Tests FileResource->__toString()
	 */
	public function test__toString()
	{
		$file = __DIR__ . '/Data/plaintext.txt';
		$r = new FileResource($file);
		$this->assertSame(sprintf('[FileResource %s text/plain 10B]', $file), (string) $r);
	
	}

}

