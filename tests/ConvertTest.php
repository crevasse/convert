<?php

use PHPUnit\Framework\TestCase;
use Crevasse\Convert;

class ConvertTest extends TestCase
{
    public function testConvertValid()
    {
        $_SERVER['argv'][] = '';
        $_SERVER['argv'][] = 'convert';
        $_SERVER['argv'][] = 'example.conf';
        $_SERVER['argv'][] = 'export';
        $_SERVER['argv'][] = 'convert.json';
        new Convert();
        $this->assertNotEmpty(file_get_contents('convert.json'));
    }
}
