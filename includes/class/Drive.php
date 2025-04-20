<?php


class Drive{


    protected $accessToken = null;
    protected $error = '';
    protected $baseURI = 'https://www.googleapis.com/drive';
    protected $client, $service = null;

    
    
        public function __construct($accessToken){

            $this->accessToken = $accessToken;

try{
            $tokenData = [
                'access_token' => $this->accessToken,
                'token_type' => 'Bearer'
            ];
            //set client
            $this->client = new Google_Client();
            $this->client->setAccessToken($tokenData);

            //set client serivece
            $this->service = new Google_Service_Drive($this->client);
            
}catch(Google_Service_Exception $e){
    
    $this->error = $e->getErrors()[0]['message'];
}


            
        }
        
        
        
        
        
        
    public function getFile($fileId, $fields = "*"){
        try{
            $optParams = array(
                'fields' => $fields,
              );
            return $this->service->files->get($fileId, $optParams);
        }catch(Google_Service_Exception $e){
            $this->error = 'An error occurred: ' . $e->getErrors()[0]['message'];
        }

         return false;
    }
        
        
        
        
        
        
        
        
    public function download($fileId, $_key = '', $saveProgress = false){

        session_write_close();
        // ignore_user_abort(true);
        // set_time_limit(0);

        
        
        $file = $this->getFile($fileId, "id, size");
        
        if(!empty($file)){
            
            $tmpFile = Helper::generateRandomString(25) . '.mp4';
            $fileSize = $file->getSize();
            
            try{
                
                $http = $this->client->authorize();
                $fp = fopen(TMP_DIR.'/'.$tmpFile, 'w');
                
                $chunkSizeBytes = DOWNLOAD_CHUNK_SIZE;
                $chunkStart = 0;
                
                
                while ($chunkStart < $fileSize) {
                    $chunkEnd = $chunkStart + $chunkSizeBytes;
                    $response = $http->request(
                      'GET',
                      sprintf('/drive/v3/files/%s', $fileId),
                      [
                        'query' => ['alt' => 'media'],
                        'headers' => [
                          'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd)
                        ]
                      ]
                    );
            
            
                    $uploaded = $chunkStart;
                    $p = ($uploaded * 100) / $fileSize;
                   
                    $pd = [
                        'source' => 'gdrive',
                        'progress'=> round($p)
                    ];
                    
                    if($saveProgress){
                        if(file_exists(HLS::getUniqFile())){
                            @file_put_contents(HLS::getUniqFile(), json_encode($pd));
                        }else{
                            exit;
                        }
                    }
                    
                    
                    $chunkStart = $chunkEnd + 1;
                    fwrite($fp, $response->getBody()->getContents());
            
            
                  }
                  
                  
                
                fclose($fp);
                
                
                return TMP_DIR . '/' . $tmpFile;

                
                
            }catch(Exception $e){
                $this->error = 'An error occurred: ' .$e->getMessage();
            }
            
            
        }



return false;
          

         

    }
        
        
        
        
        
        
        
        
        
        
        
            public function hasError(){
        return !empty($this->getError()) ? true : false;
    }

    public function getError(){
        return $this->error;
    }
    
    
    public function isOk(){
        return !empty($this->accessToken) && !$this->hasError() ? true : false;
    }

    
    
    
    
    
    
    
}