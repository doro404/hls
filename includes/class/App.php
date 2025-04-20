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



class App {


    protected $action;
    protected $actions = ['hls', 'convert', 'check', 'delete','status'];


    public function __construct() {
    }


    public function run() {
        if (isset($_GET['a']) && !empty($_GET['a'])) {
            $var = explode('/', $_GET['a']);
            $var[0] = str_replace('.', '', $var[0]);
            $this->action = Helper::clean($var[0]);
            unset($var[0]);
            if (in_array($this->action, $this->actions)) {
                if (method_exists($this, $this->action)) {
                    return call_user_func_array([$this, $this->action], $var);
                } else {
                    die('This method is does not exists in app !');
                }
            } else {
                $this->_404();
            }
        }
        // return $this->home();
        die('404 !');
    }

    
    public function hls($id = '', $file = '') {
        $hls = new HLS;
        $hls->set([$id, $file])->play();
    }


    public function convert() {

        set_time_limit(0);
        ignore_user_abort(true);
        session_write_close();

        $hlsFile = $e = '';
        $isOk = false;
        
        $id = Helper::getReqData('id');
        $file = Helper::getReqData('file');
        $token = Helper::getReqData('token');
        $secretKey = Helper::getReqData('secret_key');

        if(!empty($secretKey)  && $secretKey == HLS_API_SECRET_KEY){
            if(!file_exists(HLS::getUniqFile())){
                $hls = new HLS;
                if (!empty($id) && !empty($file) && !empty($token)) {
                    if(!file_exists(ROOT.'/'.MEDIA_DIR.'/'.$id)){
                         //deocoded google drive access token
                        $token = @Helper::d(base64_decode($token));
                        if (!empty($token)) {
                            //init drive
                            $drive = new Drive($token);
                            //check drive connection status
                            if ($drive->isOk()) {
                                //start process status
                                @file_put_contents(HLS::getUniqFile(), '');
                                //attempt to download file from google
                                if(file_exists(HLS::getUniqFile())){
                                    $tmpFile = $drive->download($file, $id, true);
                                    if ($tmpFile !== false) {
                                        //file downloaded to server successfully
                                        if (file_exists($tmpFile)) {
                                            $data = ['fileId' => $id, 'file' => $tmpFile];
                                            //now attempt to convert it
                                            if ($f = $hls->convert($data)) {
                                                $hlsFile = $f;
                                                $isOk = true;
                                            } else {
                                                $e = 'File conversation failed !';
                                            }
                                        } else {
                                            $e = 'Tempory downloaded file does not exist !';
                                        }
                                    } else {
                                        $e = 'File download failed from google drive !';
                                    }
                                }else{
                                    $e = 'Process status saving failed !';
                                }
        
                            } else {
                                $e = 'Google drive authuntication failed !';
                            }
                        } else {
                            $e = 'Invalid drive token !';
                        }
                    }else{
                        $e = 'File is already exist !';
                    }
    
                } else {
                    $e = 'Required paramerters are missing';
                }
            }else{
                $e = 'Some converting process running..please wait';
            }
        }else{
            $e = 'Invalid API screct key !';
        }



        if ($isOk) {
            $data = ['status' => 'success', 'path' => $hlsFile];
        } else {
            $data = ['status' => 'failed', 'error' => $e];
        }

        echo json_encode($data);
        
    }


    public function check($t = '') {



        if (empty($t)) {
            $errors = [];
            if (phpversion() < 7) $errors[] = 'Required PHP Version is PHP VERSION >= 7. !';
            if (!function_exists('curl_version')) $errors[] = 'Required cUrl Extension. !';
            if (!ini_get('allow_url_fopen')) $errors[] = 'Enable URL fopen. !';
            if (function_exists('exec')) {
                $ffmpeg = trim(exec('which ffmpeg'));
                if (empty($ffmpeg)) {
                    $errors[] = 'ffmpeg is not available.';
                }
            } else {
                $errors[] = 'PHP exec function not enbaled.';
            }
            if (!is_writable(ROOT . '/' . MEDIA_DIR)) {
                $errors[] = 'Folder "MEDIA" is not writable.';
            }
            if (!is_writable(TMP_DIR)) {
                $errors[] = 'Folder "TEMP" is not writable.';
            }
            if (empty($errors)) {
                echo '<p style="color:green">Looks Good :) <p>';
            } else {
               echo json_encode($errors);
            }
        } else {
            $secretKey = Helper::getReqData('secret_key');
            if(!(!empty($secretKey)  && $secretKey == HLS_API_SECRET_KEY)){
                exit;
            }   

            if ($t = 'space') {
                $totalDiskSpace = disk_total_space(ROOT . '/' . MEDIA_DIR);
                $freeDiskSpace = disk_free_space(ROOT . '/' . MEDIA_DIR);
                $usedDiskSpace = $totalDiskSpace - $freeDiskSpace;
                $resp = ['dir' => ROOT . '/' . MEDIA_DIR, 'total' => $totalDiskSpace, 'used' => $usedDiskSpace, 'free' => $freeDiskSpace];
                echo json_encode($resp);
            }
        }
    }


    public function status($id = '') {
        if (!empty($id)) {
            $status = new Status(Helper::d($id));
            echo $status->check()->getResponse();
        } else {
            echo 'Hello';
        }
    }

    public function delete($id = '') {
        $resp = [
            'status' => 'failed'
        ];


        $secretKey = Helper::getReqData('secret_key');

        if(!empty($secretKey) && $secretKey == HLS_API_SECRET_KEY){

            $id = str_replace('/','',Helper::clean($id));
            if (!empty($id) && is_string($id) && strlen($id) > 5) {
                $file = ROOT.'/'.MEDIA_DIR.'/'.Helper::d($id);
                if(file_exists($file) && is_dir($file)){
                    $rep = Helper::mb_basename($file);
                    if(!empty($rep) && $rep != MEDIA_DIR){
                        if(Helper::deleteDir($file)){
                            $resp = [
                                'status' => 'success'
                            ];
                        }
                    }
                }
                
            } 



        }





        echo json_encode($resp);


    }




    
}
