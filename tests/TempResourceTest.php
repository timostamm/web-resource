<?php

namespace TS\Web\Resource;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class TempResourceTest extends WebTestCase
{

	
	public function testFromResource()
	{
		$this->markTestIncomplete("fromResource test not implemented");
	}
	
	
	public function test()
	{
		$res = new TempResource('foo.txt', 'text/plain');
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
		$r = new TempResource('foo.txt', 'text/plain');
		$h = $r->open('a');
		$this->assertTrue(is_resource($h));
		fclose($h);
	}
	
	public function testGetStream()
	{
		$r = new TempResource('foo.txt', 'text/plain');
		$h = $r->getStream();
		$this->assertTrue(is_resource($h));
		fclose($h);
	}
	

	public function testFilenameSane()
	{
		$res = new TempResource("../bad\n\0.txt", 'text/plain');
		$this->assertSame('bad.txt', $res->getFilename());
	}
	
	
	/**
	 * Tests LocalResource->__toString()
	 */
	public function test__toString()
	{
		$r = new TempResource('test.txt', 'text/plain');
		$this->assertSame(sprintf('[TempResource %s text/plain 0B]', $r->getFilename()), (string) $r);
		
	}

}