<?php

namespace LBHurtado\SimpleOMR\Tests;

use LBHurtado\SimpleOMR\SimpleOMR;

class SimpleOMRDriverTest extends TestCase
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
    public function SimpleOMR_works()
    {
        /*** arrange ***/
        $filename = $this->debugFilePath.$this->debugFileName;
        $mappath = "tests/ballot-omr.json";
        $imagepath = "tests/ballot.jpg";

        /*** assert ***/
        $this->assertFalse(file_exists($filename));

        /*** act */
        app(SimpleOMR::class)
            ->setMapPath($mappath)
            ->setDebugFilePath($this->debugFilePath)
            ->setDebugFileName($this->debugFileName)
            ->process($imagepath);

        /*** assert ***/
        $this->assertTrue(file_exists($filename));
    }
}