<?php

namespace TS\Web\Resource;


use PHPUnit\Framework\TestCase;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class TemporaryFileResourceTest extends TestCase
{

	public function testFromResource()
	{
		$r = new Resource([
			'content' => 'abc',
			'mimetype' => 'text/plain',
			'filename' => 'text.txt'
		]);
		$t = TemporaryFileResource::fromResource($r);
		$this->assertEquals('text.txt', $t->getFilename());
	}

	public function test()
	{
		$res = new TemporaryFileResource('foo.txt', 'text/plain');
		$this->assertTrue(file_exists($res->getPath()));
		$this->assertSame('foo.txt', $res->getFilename());
		$this->assertSame(0, $res->getLength());
		$this->assertSame('text/plain', $res->getMimetype());
		$this->assertSame('da39a3ee5e6b4b0d3255bfef95601890afd80709', $res->getHash());
		
		$res->dispose();
		$this->assertFalse(file_exists($res->getPath()));
	}

	public function testOpen()
	{
		$r = new TemporaryFileResource('foo.txt', 'text/plain');
		$h = $r->open('a');
		$this->assertTrue(is_resource($h));
		fclose($h);
	}

	public function testGetStream()
	{
		$r = new TemporaryFileResource('foo.txt', 'text/plain');
		$h = $r->getStream();
		$this->assertTrue(is_resource($h));
		fclose($h);
	}

	public function testFilenameSane()
	{
		$res = new TemporaryFileResource("../bad\n\0.txt", 'text/plain');
		$this->assertSame('bad.txt', $res->getFilename());
	}

	public function testAutoExtension()
	{
		$res = new TemporaryFileResource(null, 'text/plain');
		$this->assertSame('temp.txt', $res->getFilename());
	}

	/**
	 * Tests FileResource->__toString()
	 */
	public function test__toString()
	{
		$r = new TemporaryFileResource('test.txt', 'text/plain');
		$this->assertSame(sprintf('[TemporaryFileResource %s text/plain 0B]', $r->getFilename()), (string) $r);
	
	}

}