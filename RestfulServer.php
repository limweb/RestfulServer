<?php
require_once __DIR__.'/vendor/autoload.php';

class RestfulServer {


	    private   $debug = true;
        private   $start_time;
        protected $host = 'http://127.0.0.1:8000';
        protected $server = null;
        protected $sessiones = null;
        protected $method  = null;
        protected $request  = null;
        protected $qrystr     = null;
        protected $input   = null;
        protected $qrypath = null;
        protected $posts = [];
        protected $reqs = [];
        protected $format = null;
        protected $uri = [];
        protected $response = [
               'code' =>0,
               'status' => 404,
               'data' => null,
        ];
        protected $http_response_code = [
               200 => 'OK',
               400 => 'Bad Request',
               401 => 'Unauthorized',
               403 => 'Forbidden',
               404 => 'Not Found'
        ];
        protected $HTTPS_required = FALSE;
        protected $authentication_required = false;
        protected $api_response_code = array(
            0 => array('HTTP Response' => 400, 'Message' => 'Unknown Error'),
            1 => array('HTTP Response' => 200, 'Message' => 'Success'),
            2 => array('HTTP Response' => 403, 'Message' => 'HTTPS Required'),
            3 => array('HTTP Response' => 401, 'Message' => 'Authentication Required'),
            4 => array('HTTP Response' => 401, 'Message' => 'Authentication Failed'),
            5 => array('HTTP Response' => 404, 'Message' => 'Invalid Request'),
            6 => array('HTTP Response' => 400, 'Message' => 'Invalid Response Format')
        );

	 public function __construct() {
	 		( $this->debug ? $this->starttime() : null );
			(session_status() == PHP_SESSION_NONE ? session_start() : nulll );
            if(filter_input(INPUT_SERVER,'HTTP_HOST')){
            	$this->host = 'http://'.filter_input(INPUT_SERVER,'HTTP_HOST');
            } 
    	    $this->server = $_SERVER;
            $uri = filter_input(INPUT_SERVER,'REQUEST_URI');
        	($uri ? $this->uri = explode("/", substr(@$uri, 1)) : $this->uri = [] );
            $this->sessiones = $_SESSION;
            $this->posts = $_POST;
	 }

	 public function  __destruct() {
		  if($this->debug) {
		                     $mic_time = microtime();
		                     $mic_time = explode(" ",$mic_time);
		                     $mic_time = $mic_time[1] + $mic_time[0];
		                     $endtime = $mic_time;
		                     $total_execution_time = ($endtime - $this->start_time);
		                     echo "\n<br>Total Executaion Time ".$total_execution_time." seconds";
		                 }
	 }

 		private  function starttime() {
             $mic_time = microtime();
             $mic_time = explode(" ",$mic_time);
             $mic_time = $mic_time[1] + $mic_time[0];
             $this->start_time = $mic_time;
        }

	 public function run(){
	 	dump($this);
	 }
	
}
