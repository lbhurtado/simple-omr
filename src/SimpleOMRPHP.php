<?php

namespace LBHurtado\SimpleOMR;

use LBHurtado\SimpleOMR\Exceptions\JsonDecodeException;
use LBHurtado\SimpleOMR\Exceptions\FileNotFoundException;
use LBHurtado\SimpleOMR\Exceptions\InvalidToleranceException;
use LBHurtado\SimpleOMR\Exceptions\InvalidTargetTypeException;
use LBHurtado\SimpleOMR\Exceptions\UnexpectedResolutionException;

/**
 *
 * @author Romário Rodrigues Ramos <romariox@gmail.com>
 * @version 1.0.0
 * @license http://escolhaumalicenca.com.br/licencas/mit/ MIT
 *
 * SimpleOMRPHP verifica as marcações de um formulário atraves de um mapa
 * É necessário a instalação do Imagick, ImageMagick e GhostScript
 *
 * Antes de passar a imagem, verifique se ela já está na resolução(px) certa e, se possível, preto e branco
 *
 * É recomendado primeiro fazer um teste, com o debug ligado e usar a imagem do debug para fazer a anotação da posição das marcações em arquivos PDF
 */
class SimpleOMRPHP
{

    private $map;
    private $imagepath;
    private $mappath;
    private $tolerance;
    private $result = [];
    private $imagick;
    private $draw;
    private $debugmode = false;


    /**
     * Construtor, ira receber tudo que for necessário e irá jogar o resultado em $result, use getResult().
     * Se o caminho do $debugimagepath não existir, ele irá tentar criar um diretório 0777, é melhor você se certificar que o caminho existe.
     *
     * @param string $mappath
     * @param string $imagepath
     * @param int $tolerance
     * @param bool $debugimagepath
     * @param bool $debugfilename
     *
     * @uses Imagick
     * @uses ImagickDraw
     *
     *
     * @throws InvalidToleranceException
     * @throws JsonDecodeException
     * @throws FileNotFoundException
     *
     */
    public function __construct($mappath, $imagepath, $tolerance = 35, $debugimagepath = false, $debugfilename = false){
        try{

            if(!is_int($tolerance)){
                throw new InvalidToleranceException('Tolerância passada não é um número inteiro válido');
            }
            if(!@$file = file_get_contents($mappath)){
                throw new FileNotFoundException('Não foi possivel encontrar o arquivo de mapa');
            }
            if(!$json = json_decode($file,true)){
                throw new JsonDecodeException('Erro ao decodificar o arquivo json passado');
            }
            if($debugimagepath != false && $debugfilename != false){
                $this->createFolder($debugimagepath);
                $this->setDebugMode(true);
                $this->prepareDraw();
            }
            $this->setTolerance($tolerance);
            $this->setMap($json);
            $this->prepareImage($imagepath);
            $this->setResult($this->generateResult());

            if($this->isDebugMode()){
                $imagick = $this->getImagick();
                $draw = $this->getDraw();
                $imagick->drawImage($draw);
                $imagick->writeImage ($debugimagepath.DIRECTORY_SEPARATOR.$debugfilename);
            }

        }catch (Exception $e){
            echo $e->getMessage();
        }

    }

    /**
     * @return array
     */
    private function generateResult(){
        $map = $this->getMap();
        $result = [];
        foreach ($map['groups'] as $groupvalue){
            $result[]= $this->groupAnalytics($groupvalue);
        }
        return $result;
    }

    /**
     * @param $group
     * @return array
     * @throws InvalidTargetTypeException
     */
    private function groupAnalytics($group){
        $analyticsresults = ['groupname' => $group['groupname']];
        $markedtargets = '';
        $targetresult = [];

        foreach ($group['grouptargets'] as $target){
            if($target['type'] == 'rectangle'){
                $targetresult[] = array_merge(['id' => $target['id']],$this->rectangleMark($target['x'],$target['y'],$target['width'],$target['height']));
            }else{
                throw new InvalidTargetTypeException('Tipo não suportado: ',$target['type']);
            }
        }
        foreach ($targetresult as $row){
            if($row['ismarked'] == "true"){
                $markedtargets .= ($markedtargets == '')? $row['id'] : ','.$row['id'];
            }
        }

        $analyticsresults['markedtargets'] = $markedtargets;
        $analyticsresults['targets'] = $targetresult;
        return $analyticsresults;
    }

    /**
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @return array
     */
    private function rectangleMark($x, $y, $width, $height){
        $blackpixels = 0;
        $whidepixels = 0;
        $ismarked    = false;

        $imagick = $this->getImagick();
        $pixels = $imagick->exportImagePixels($x,$y,$width,$height,"I", \Imagick::PIXEL_CHAR);
        $counts = array_count_values($pixels);
        foreach($counts as $color => $qtd){
            if($color == 255)
                $whidepixels += $qtd;
            else
                $blackpixels += $qtd;
        }


        $percentblack = ((100 * $blackpixels) / count($pixels));
        $ismarked = ($percentblack >= $this->getTolerance()) ? true : false;

        if($this->isDebugMode()){
            $color = ($ismarked == 'true')? '#00ff00' : '#0000CC';
            $this->drawRectangle($x,$y,$width,$height,$color);
        }
        return ['ismarked' => $ismarked,'percentblack' => $percentblack];

    }


    /**
     * @param $imagepath
     *
     * @throws UnexpectedResolutionException
     */
    private function prepareImage($imagepath){
        $imagick = new \Imagick();
        $map = $this->getMap();
        $imagick->readImage($imagepath.'[0]');
        $imagick->modulateImage(100, 0, 100);
        $imagick->posterizeimage(2, false);
        $imagick->thresholdImage(0.5);
        // Depreciado, mas não encontrei outra solução
//        @$imagick->medianFilterImage(5);
        $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality(100);
        if($imagick->getImageWidth() != $map['expectedwidth'] || $imagick->getImageHeight() != $map['expectedheight']){
            throw new UnexpectedResolutionException('A resolução esperada não é igual a resolução final. expectedwidth = '.$map['expectedwidth'].' finalwidth = '.$imagick->getImageWidth().' expectedheight = '. $map['expectedheight'].' finalheight = '.$imagick->getImageHeight());
        }
        $this->setImagick($imagick);
    }

    /**
     *
     */
    private function prepareDraw(){
        $draw = new \ImagickDraw();

        $draw->setStrokeOpacity(1);
        $draw->setFillOpacity(0);
        $draw->setStrokeWidth(1);
        $this->setDraw($draw);
    }

    /**
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @param $color
     */
    private function drawRectangle($x, $y, $width, $height, $color = '#0000CC'){
        $this->draw->setStrokeColor($color);
        $this->draw->rectangle($x, $y, ($x+$width), ($y+$height));
    }

    /**
     * @param $path
     * @return bool
     * @throws Exception
     */
    private function createFolder($path){

        $path = utf8_decode($path);

        if (!file_exists($path)) {
            if (mkdir($path, 0777, true) == false) {
                throw new Exception('Não foi possivel criar o diretório: ' . utf8_encode($path));
            }
        }
        if (!file_exists($path)) {
            throw new Exception('Verificação falou, não foi possivel criar o diretório: ' . utf8_encode($path));
        }
        return true;
    }

    /**
     * @return mixed
     */
    private function getImagick()
    {
        return $this->imagick;
    }

    /**
     * @param mixed $imagick
     */
    private function setImagick($imagick)
    {
        $this->imagick = $imagick;
    }

    /**
     * @return int
     */
    private function getTolerance()
    {
        return $this->tolerance;
    }

    /**
     * @param int $tolerance
     */
    private function setTolerance($tolerance)
    {
        $this->tolerance = $tolerance;
    }

    /**
     * @return boolean
     */
    private function isDebugMode()
    {
        return $this->debugmode;
    }

    /**
     * @param boolean $debugenable
     */
    private function setDebugMode($debugenable)
    {
        $this->debugmode = $debugenable;
    }


    /**
     * @return mixed
     */
    private function getMap()
    {
        return $this->map;
    }

    /**
     * @param mixed $map
     */
    private function setMap($map)
    {
        $this->map = $map;
    }

    /**
     * @return mixed
     */
    private function getImagepath()
    {
        return $this->imagepath;
    }

    /**
     * @param mixed $imagepath
     */
    private function setImagepath($imagepath)
    {
        $this->imagepath = $imagepath;
    }

    /**
     * @return mixed
     */
    private function getMappath()
    {
        return $this->mappath;
    }

    /**
     * @param mixed $mappath
     */
    private function setMappath($mappath)
    {
        $this->mappath = $mappath;
    }
    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    private function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    private function getDraw()
    {
        return $this->draw;
    }

    /**
     * @param mixed $draw
     */
    private function setDraw($draw)
    {
        $this->draw = $draw;
    }

}
//class FileNotFoundException extends Exception{}
//class JsonDecodeException extends Exception{}
//class InvalidToleranceException extends Exception{}
//class InvalidTargetTypeException extends Exception{}
//class UnexpectedResolutionException extends Exception{}
