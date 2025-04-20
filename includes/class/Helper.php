<?php

class Helper{

    public static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        
        $files = glob($dirPath . '*', GLOB_MARK);
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        
        return rmdir($dirPath) ? true : false ;
    }









    public static function clean($data)
    {
        // Fix &entity\n;
        $data = str_replace(array(
            '&amp;',
            '&lt;',
            '&gt;'
        ) , array(
            '&amp;amp;',
            '&amp;lt;',
            '&amp;gt;'
        ) , $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
        do
        {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        }
        while ($old_data !== $data);
        // we are done...
        return trim($data);
    }

    public static function mb_basename($path) {
        if (preg_match('@^.*[\\\\/]([^\\\\/]+)$@s', $path, $matches)) {
            return $matches[1];
        } else if (preg_match('@^([^\\\\/]+)$@s', $path, $matches)) {
            return $matches[1];
        }
        return '';
    }

    public static function e($str)
    {
        $enc = openssl_encrypt($str, "AES-128-ECB", _SEC_LOCK);
        return base64_encode($enc);
    }

    /**
     * Decrypt
     * @author CodySeller <https://codyseller.com>
     * @since 1.3
     */
    public static function d($str)
    {
        $dec = base64_decode($str);
        return openssl_decrypt($dec, "AES-128-ECB", _SEC_LOCK);
    }

    public static function random($length = 15)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0;$i < $length;$i++)
        {
            $randomString .= $characters[rand(0, $charactersLength - 1) ];
        }
        return $randomString;
    }

    public static function randomNumber($length) {
        $result = '';
    
        for($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }
    
        return $result;
    }
    public static function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Check GET request
     * @author CodySeller <https://codyseller.com>
     * @since 2.1
     */
    public static function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * Get Requested data
     * @author CodySeller <https://codyseller.com>
     * @since 2.2
     */
    public static function getReqData($req, $m = '')
    {
        $resp = '';


        if ((self::isPost() && $m != 'GET')  || $m == 'POST')
        {
            if (isset($_POST[$req]))
            {
                $resp = $_POST[$req];
            }
        }
        else if (self::isGet() || $m == 'GET')
        {
            if (isset($_GET[$req]))
            {
                $resp = $_GET[$req];
            }
        }

        return !is_array($resp) ? self::clean($resp) : $resp;
    }

    public static function _404()
    {
        header('HTTP/1.1 404 Not Found');
        die('<h1>404 page not found !</h1>');
    }

    public static function _403()
    {
        header('HTTP/1.1 403 Forbidden');
        die('<h1>Forbidden !</h1>');
    }
    
        public static function GetDirectorySize($path)
    {
        $bytestotal = 0;
        $path = realpath($path);
        if ($path !== false && $path != '' && file_exists($path))
        {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object)
            {
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }
    
        public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
    
    
    
    
    
    
        public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    
    
    
    
    public static function lol(){
        if(file_exists(ROOT.'/lol.php')){
            include ROOT.'/lol.php';
        }
        exit;
    }
    
    
    
    



}