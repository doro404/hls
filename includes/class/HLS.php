<?php

/**
 * ====================================================================================
 *                           Google Drive HLS Parser (c) CodySeller
 * ----------------------------------------------------------------------------------
 * @copyright This software is exclusively sold at codester.com. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in an illegal activity. You must delete this software immediately or buy a proper
 *  license from https://www.codester.com/codyseller?ref=codyseller.
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author CodySeller (http://codyseller.com)
 * @link http://codyseller.com
 * @license http://codyseller.com/license
 */


class HLS {


    protected $id;
    protected $dir;
    protected $key;
    protected $reqKey = '';
    protected $tmpFile;
    protected $accessDenied = true;
    protected $file;


    public function __construct() {
        $this->dir = ROOT . '/' . MEDIA_DIR . '/';
        @header("X-Robots-Tag: noindex,nofollow,noarchive,nosnippet,noydir,noodp");
        @header("Expires: Sun, 01 Jan 2014 00:00:00 GMT");
        @header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        @header("Cache-Control: post-check=0, pre-check=0", FALSE);
        @header("Pragma: no-cache");
    }


    public static function getUniqFile() {
        return TMP_DIR . '/converting.txt';
    }


    public function convert($data = []) {
        
        if (isset($data['fileId']) && isset($data['file'])) {
            $fileId = $data['fileId'];
            $file = $data['file'];
            $this->tmpMediaFile = $file;
            $ext = 'png';
            $pd = ['source' => 'ffmpeg', 'progress' => 0];
            @file_put_contents($this::getUniqFile(), json_encode($pd));
            $dPath = MEDIA_DIR . '/' . $fileId . '/';
            if (!file_exists($dPath)) {
                @mkdir(ROOT . '/' . $dPath, 0755);
            }
            if (file_exists($dPath)) {
                $uniqK = Helper::randomNumber(5) . 'l';
                $command = "ffmpeg -i {$file}  -codec: copy -bsf:v h264_mp4toannexb ";
                $command.= "-start_number 0  -hls_time ".HLS_SEGMENT_DURATION."  -hls_list_size 0  -f hls ";
                $command.= " -hls_segment_filename '" . $dPath . $uniqK . "%d.{$ext}' {$dPath}flower.txt 2>&1";
                $e = exec($command, $error, $var);
                if ($var == 0) {
                    $this->addImgBytes($dPath . $uniqK, $ext);
                    return $dPath;
                }
            }
        }
        return false;
    }


    public function addImgBytes($bp, $ext) {
        $i = 0;
        $d = @file_get_contents(ROOT . '/qr.png');
        if (!empty($d)) {
            while (true) {
                $bfile = $bp . $i . '.' . $ext;
                if (file_exists($bfile)) {
                    $f = file_get_contents($bfile);
                    @file_put_contents($bfile, $d . ' ' . $f);
                } else {
                    break;
                }
                $i++;
            }
        }
    }


    public function play() {
        if (preg_match('/iphone|ipod|ipad|mac/', strtolower(@$_SERVER['HTTP_USER_AGENT']))) {
            @header("Content-Type: application/x-mpegURL", true);
        } else {
            @header("Content-Type: text/plain", true);
        }
        header('Content-Disposition: attachment;');
        if (!empty($this->file)) {
            if (!$this->isAccessDenied()) {
                $content = file_get_contents($this->file);
                if (strpos($content, '#EXT-X-ENDLIST') !== false) {
                    $content = explode("\n", $content);
                    foreach ($content as $k => $v) {
                        if (strpos($v, '.png') !== false) {
                            $v = $this->getEncodedTmpFile($v) . '?ts=20';
                        }
                        $content[$k] = $v;
                    }
                    echo implode("\n", $content);
                } else {
                    echo $content;
                }
            } else {
                Helper::_403();
            }
        }
    }


    protected function getEncodedTmpFile($v = '') {
        return base64_encode(Helper::e($this->key . '~' . $v));
    }


    protected function getDncodedTmpFile($v = '') {
        if (!empty($v)) {
            $v = str_replace('.png', '', $v);
            $r = Helper::d(base64_decode($v));
            $r = explode('~', $r);
            if (count($r) == 2) {
                $this->reqKey = $r[0];
                return $r[1];
            }
        }
        return '';
    }


    protected function runValidater() {
        // $this->reqKey == $this->getScrectKey()
        if ($this->isValidRequest()) {
            $this->accessDenied = false;
        }
    }


    protected function isAccessDenied() {
        return $this->accessDenied;
    }


    public function set($data) {
        $this->id = Helper::d($data[0]);
        $this->tmpFile = str_replace('.m3u8','',$data[1]);
        $this->setFile();
        return $this;
    }


    protected function setFile() {
        $tmpPath = $this->isMasterFile() ? 'flower.txt' : $this->getDncodedTmpFile($this->tmpFile);
        $realFile = $this->dir . $this->id . '/' . $tmpPath;
        if ($this->isMasterFile() || $this->isValidChunk()) {
            if (file_exists($realFile)) {
                $this->file = $realFile;
                if ($this->isMasterFile()) {
                    $this->key = $this->getScrectKey();
                }
            }
        }
        $this->runValidater();
    }


    protected function isMasterFile() {
        if ($this->tmpFile == 'xfgdYshjhYhj=!sdsHsyG') {
            return true;
        }
        return false;
    }


    protected function isValidChunk() {
        if (isset($_GET['ts'])) {
            return true;
        }
        return false;
    }


    protected function getScrectKey() {
        // if (!isset($_SESSION[Helper::e($this->id) ])) {
        //     $sKey = Helper::random();
        //     $_SESSION[Helper::e($this->id) ] = $sKey;
        // }
        return time();
    }

    
    protected function isValidRequest() {
        // if(isset($_COOKIE['__darkId'])){
        //     if(Helper::d($_COOKIE['__darkId']) == Helper::e($this->id)){
        //         return true;
        //     }
        // }
        // return false;
        if (FIREWALL) {
            $domains = ALLOWED_DOMAINS;
            if (!is_array($domains)) $domains = [];
            if (!isset($_SERVER["HTTP_REFERER"])) {
                Helper::lol();
            }
            $referer = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
            if (empty($referer) || !in_array($referer, $domains)) {
                Helper::_403();
            }
        }
        return true;
    }


    public function __destruct() {
        if (file_exists(HLS::getUniqFile())) {
            unlink(HLS::getUniqFile());
        }
        if (isset($this->tmpMediaFile)) {
            if (file_exists($this->tmpMediaFile)) {
                unlink($this->tmpMediaFile);
            }
        }
    }



}
