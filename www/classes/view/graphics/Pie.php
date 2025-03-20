<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * needs some defaults
 */
require_once("system/SystemConfig.php");

class Pie
{

    /**
     * file name
     * @var string
     */
    private $filename_ = 'untitled.png';

    /**
     * chart width and height
     * @var  array
     */
    private $size_ = ["width" => 0, "height" => 0];

    /**
     * chart 3d effect width
     */
    private $width3d_ = 20;

    /**
     * datas
     * @var array
     */
    private $datas_ = [];

    /**
     * sum of all the values
     * @var numeric
     */
    private $sum_values_ = 0;
    /**
     * constructor
     */
    public function __construct()
    {
    }

    /**
     * set the filename
     * @param  $filename  string  the filename
     * @return            boolean true on success, false on failure
     */
    public function setFilename($filename)
    {
        if ($filename != "") {
            $this->filename_ = $filename;
            return true;
        }
        return false;
    }

    /**
     * set the Pie size
     * @param $width  integer   graphic width
     * @param $height integer   graphic height
     * @return        boolean   true on success, false on failure
     */
    public function setSize($width, $height)
    {
        if (is_int($width) && is_int($height)) {
            $this->size_['width'] = $width;
            $this->size_['height'] = $height;
            return true;
        }
        return false;
    }

    /**
     * add a data value
     * @param $value   numeric   numeric value
     * @param $name    string    name of the value
     * @param $color   array     color
     * @return         boolean    true on success, false on failure
     */
    public function addValue($value, $name, $color)
    {
        if (!is_numeric($value) || !is_array($color)) {
            return false;
        }
        if ($value == 0) {
            return true;
        }
        array_push($this->datas_, ['v' => $value, 'n' => $name, 'c' => $color]);
        $this->sum_values_ += $value;
        return true;
    }


    /**
     * generate the graphic file
     * @return      boolean  true on success, false on failure
     */
    public function generate()
    {
        $sysconf = SystemConfig::getInstance();

        $delta = 270;
        $width = $this->size_['width'] * 2;
        $height = ($this->size_['height'] + $this->width3d_) * 2;
        $ext_width = $width + 5;
        $ext_height = $height + $this->width3d_ + 5;
        $image = imagecreatetruecolor($ext_width, $ext_height);

        $white    = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
        imagefilledrectangle($image, 0, 0, $ext_width, $ext_height, $white);

        $xcenter = intval($ext_width / 2);
        $ycenter = intval($ext_height / 2) - ($this->width3d_ / 2);

        $gray    = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);
        $darkgray = imagecolorallocate($image, 0x90, 0x90, 0x90);

        $colors = [];
        ## create 3d effect
        for ($i = $ycenter + $this->width3d_; $i > $ycenter; $i--) {
            $last_angle = 0;
            imagefilledarc($image, $xcenter, $i, $width, $height, 0, 360, $darkgray, IMG_ARC_PIE);
            foreach ($this->datas_ as $data) {
                $name = $data['n'];
                $rcomp = intval($data['c'][0] - 0x40);
                if ($rcomp < 0) {
                    $rcomp = 0;
                }
                $gcomp = intval($data['c'][1] - 0x40);
                if ($gcomp < 0) {
                    $gcomp = 0;
                }
                $bcomp = intval($data['c'][2] - 0x40);
                if ($bcomp < 0) {
                    $bcomp = 0;
                }
                $colors[$name] = imagecolorallocate($image, $rcomp, $gcomp, $bcomp);
                $percent = (100 / $this->sum_values_) * $data['v'];
                $angle = $percent * 3.6;
                if ($angle < 1.1) {
                    $angle = 1.1;
                }
                imagefilledarc($image, $xcenter, $i, $width, $height, $last_angle + $delta, $last_angle + $angle + $delta, $colors[$name], IMG_ARC_PIE);
                $last_angle += $angle;
            }
        }

        ## create default
        imagefilledarc($image, $xcenter, $ycenter, $width, $height, 0, 360, $gray, IMG_ARC_PIE);

        ## creates real pies
        $last_angle = 0;
        foreach ($this->datas_ as $data) {
            $name = $data['n'];
            $colors[$name] = imagecolorallocate($image, $data['c'][0], $data['c'][1], $data['c'][2]);
            $percent = (100 / $this->sum_values_) * $data['v'];
            $angle = $percent * 3.6;
            if ($angle < 1.1) {
                $angle = 1.1;
            }
            imagefilledarc($image, $xcenter, $ycenter, $width, $height, $last_angle + $delta, $last_angle + $angle + $delta, $colors[$name], IMG_ARC_PIE);
            $last_angle += $angle;
        }

        // resample to get anti-alias effect
        $destImage = imagecreatetruecolor($this->size_['width'], $this->size_['height']);
        imagecopyresampled($destImage, $image, 0, 0, 0, 0, $this->size_['width'], $this->size_['height'], $ext_width, $ext_height);
        imagepng($destImage, $sysconf->VARDIR_ . "/www/" . $this->filename_);
        imagedestroy($image);
        imagedestroy($destImage);
        return true;
    }
}
