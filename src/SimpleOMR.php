<?php

namespace LBHurtado\SimpleOMR;

use SimpleOMRPHP;

class SimpleOMR
{
    protected $mapPath;

    protected $tolerance;

    protected $debugFilePath;

    protected $debugFileName;

    protected $omr;

    public function __construct($mapPath, $tolerance, $debugFilePath, $debugFileName)
    {
        $this->mapPath = $mapPath;
        $this->tolerance = $tolerance;
        $this->debugFilePath = $debugFilePath;
        $this->debugFileName = $debugFileName;
    }

    public function process($imagePath)
    {
        $this->omr = new SimpleOMRPHP(
            $this->mapPath,
            $imagePath,
            $this->tolerance,
            $this->debugFilePath,
            $this->debugFileName
        );

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMapPath()
    {
        return $this->mapPath;
    }

    /**
     * @param mixed $mapPath
     * @return SimpleOMR
     */
    public function setMapPath($mapPath): SimpleOMR
    {
        $this->mapPath = $mapPath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTolerance()
    {
        return $this->tolerance;
    }

    /**
     * @param mixed $tolerance
     * @return SimpleOMR
     */
    public function setTolerance($tolerance): SimpleOMR
    {
        $this->tolerance = $tolerance;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDebugFilePath()
    {
        return $this->debugFilePath;
    }

    /**
     * @param $debugFilePath
     * @return SimpleOMR
     */
    public function setDebugFilePath($debugFilePath): SimpleOMR
    {
        $this->debugFilePath = $debugFilePath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDebugFileName()
    {
        return $this->debugFileName;
    }

    /**
     * @param mixed $debugFileName
     * @return SimpleOMR
     */
    public function setDebugFileName($debugFileName): SimpleOMR
    {
        $this->debugFileName = $debugFileName;

        return $this;
    }

    static public function createFromConfig($config)
    {
        return new static($config['mapPath'], $config['tolerance'], $config['debugFilePath'], $config['debugFileName']);
    }
}
