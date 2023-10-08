<?php

namespace TS\Web\Resource;

use PHPUnit\Framework\TestCase;
use TS\Web\Resource\Exception\InvalidArgumentException;

class OptionsTraitTest extends TestCase
{
    use OptionsTrait;

    public function testRequireOptions()
    {
        $options = ['foo' => 'bar', 'baz' => 'qux'];
        $keys = ['foo', 'baz'];
        $this->expectNotToPerformAssertions();
        $this->requireOptions($options, $keys);
    }

    public function testRequireOptionsInvalid()
    {
        $options = ['foo' => 'bar'];
        $keys = ['foo', 'baz'];
        $this->expectException(InvalidArgumentException::class);
        $this->requireOptions($options, $keys);
    }

    public function testValidateOptionFilename()
    {
        $val = $this->validateOption('filename', 'filename.txt');
        $this->assertSame('filename.txt', $val);

        $val = $this->validateOption('filename', 'filenameöäü.txt');
        $this->assertSame('filename.txt', $val);
    }


    public function testValidInputWithoutSpecialChars()
    {
        $input = "HelloWorld";
        $expectedOutput = "HelloWorld";
        $result = $this->validateOption('filename', $input);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testValidInputWithSpecialCharsAndSpaces()
    {
        $input = "Héllo\nWörld!";
        $expectedOutput = "HlloWrld!";
        $result = $this->validateOption('filename', $input);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testEmptyInput()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "filename" is empty.');
        $this->validateOption('filename', '');
    }

    public function testInputNotString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected option "filename" to be of type string but got integer');
        $this->validateOption('filename', 123);
    }

    public function testInputWithDoubleDots()
    {
        $input = "Hello..World";
        $expectedOutput = "HelloWorld";
        $result = $this->validateOption('filename', $input);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testInputWithColon()
    {
        $input = "Hello:World";
        $expectedOutput = "HelloWorld";
        $result = $this->validateOption('filename', $input);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testInputWithSlash()
    {
        $input = "Hello/World";
        $expectedOutput = "HelloWorld";
        $result = $this->validateOption('filename', $input);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testInputWithBackslash()
    {
        $input = "Hello\World";
        $expectedOutput = "HelloWorld";
        $result = $this->validateOption('filename', $input);
        $this->assertEquals($expectedOutput, $result);
    }
}
