<?php

namespace LBHurtado\SimpleOMR\Tests;

use LBHurtado\SimpleOMR\SimpleOMRPHP;

class SimpleOMRPHPTest extends TestCase
{
    protected $debugFilePath = "tests/";

    protected $debugFileName = "debug.png";

    public function tearDown(): void
    {
        $filename = $this->debugFilePath.$this->debugFileName;

        if (file_exists($filename))
            unlink($filename);

        parent::tearDown();
    }

    /** @test */
    public function SimpleOMRPHP_works()
    {
        /*** arrange ***/
        $mappath = "tests/ballot-omr.json";
        $imagepath = "tests/ballot.jpg";
        $filename = $this->debugFilePath.$this->debugFileName;

        /*** assert ***/
        $this->assertFalse(file_exists($filename));

        /*** act */
        new SimpleOMRPHP(
            $mappath,
            $imagepath,
            28,
            $this->debugFilePath,
            $this->debugFileName
        );

        /*** assert ***/
        $this->assertTrue(file_exists($filename));
    }
}
