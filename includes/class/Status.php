<?php



class Status{

    protected $id = '';
    protected $file = '';
    protected $status = 'failed';
    protected $data = [];
    protected $msg = 'none';

    public function __construct($id){
        if(!empty($id)){
            $this->id = $id;
            $this->file = ROOT . '/' . MEDIA_DIR . '/' . $id;
        }
    }

    public function getResponse(){

        $response =  [];
        $response['status'] = $this->status;


        if(!empty($this->data)){
            $response['data'] = $this->data;
        }

        if(!empty($this->msg)){
            $response['msg'] = $this->msg;
        }

        return json_encode($response);

    }

    public function check(){

        if(!empty($this->file)){

            if($this->isProcessRunning()){

                $this->status = 'processing';
                $data = $this->getProcessData();

                if(!empty($data) && is_array($data)){

                    $this->data = $data;

                }else{

                    $this->msg = 'Not found process data !';

                }

            }else{

                if($this->isFileExist()){

                    $this->status = 'exist';
                    $this->msg = 'This file is already exist !';
                    $this->data = ['path'=>MEDIA_DIR . '/' . $this->id];

                }else{

                    $this->status = 'not exist';
                    $this->msg = 'This file does not exist !';

                }

            }

        }

        return $this;

    }

    protected function isProcessRunning(){
        return file_exists(HLS::getUniqFile()) ? true : false;
    }

    protected function getProcessData(){
        $stContent  = @file_get_contents(HLS::getUniqFile());
        if(!empty($stContent) && Helper::isJson($stContent)){
            $stContent = json_decode($stContent, true);
            if(isset($stContent['source'])){
                if($stContent['source'] == 'ffmpeg' && file_exists($this->file)){
                    $stContent['progress'] = $this->getFFStatus();
                }
            }
        }

        return $stContent;
    }

    protected function getFFStatus(){
        
        $dirSize = Helper::GetDirectorySize($this->file);
        if (empty($dirSize) || $dirSize < 0) $dirSize = 1;
        if (!empty($dirSize)) {
            return $dirSize;
        }
        return 0;
    }

    protected function isFileExist(){
        return file_exists($this->file) ? true : false;
    }



    



}