<?php
date_default_timezone_set('Asia/Bangkok');
// ini_set('max_execution_time',0);
// ini_set("memory_limit",-1);
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
	protected  $methodget = [];
	protected  $methodput = [];
	protected  $methodpost = [];
	protected  $methoddelete = [];
	protected  $reservemethod =[
	'getIndex',
	'getcreate',
	'getShow',
	'getEdit',
	'putUpdate',
	'postStore',
	'deleteDestroy',
	'getRoutes',
	];


	public function __construct() {
		( $this->debug ? $this->starttime() : null );
		(session_status() == PHP_SESSION_NONE ? session_start() : nulll );
		if(filter_input(INPUT_SERVER,'HTTP_HOST')){
			$this->host = 'http://'.filter_input(INPUT_SERVER,'HTTP_HOST');
		} 
		$this->server = $_SERVER;
		$this->method = filter_input(INPUT_SERVER,'REQUEST_METHOD');
		$this->qrypath = filter_input(INPUT_SERVER, 'PATH_INFO');
		$uri = filter_input(INPUT_SERVER,'REQUEST_URI');
		$this->request = filter_input(INPUT_SERVER, 'PATH_INFO');
		$this->request =  rtrim($this->request,"\/");
		$this->request = explode("/", substr(@$this->request, 1));
		($uri ? $this->uri = explode("/", substr(@$uri, 1)) : $this->uri = [] );
		$this->sessiones = $_SESSION;
		$this->posts = $_POST;
		$qrystr = filter_input(INPUT_SERVER, 'QUERY_STRING');
		parse_str($qrystr, $this->qrystr);
		$this->input = (object)   json_decode(file_get_contents("php://input"));
		$this->posts = $_POST;
		$this->reqs = $_REQUEST;
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

	public function  getGet(){
		foreach ($this->methodget as $get) {
			$get =(object) $get;
			if(strtolower($get->path) == strtolower($this->request[0])){
				array_shift($this->request);
				call_user_func_array([$this,$get->method], $this->request );
				return;
			}
		}
		$this->defaultlast();
	}

	public function run(){
		$this->preser_function();
		$func  = filter_input(INPUT_GET, 'method', FILTER_SANITIZE_SPECIAL_CHARS);
		if( $this->request ) {
			$this->request[] = '';
		}
		switch ($this->method) {
			case 'GET':
			$this->getGet();
			break;
			case 'PUT':
			foreach ($this->methodput as $put) {
				$put =(object) $put;
				if(strtolower($put->path) == strtolower($this->request[0])){
					array_shift($this->request);
					call_user_func_array([$this,$put->method], $this->request );
					return;
				}
			}
			if($this->request[0]){
				$this->update($this->request[0]);
			} else {
				$this->rest_error(-1,'Error: '.$this->request[0].' method not found.','');
			}
			break;
			case 'POST':
				foreach ($this->methodpost as $post) {
					$post =(object) $post;
					if(strtolower($post->path) == strtolower($this->request[0])){
						array_shift($this->request);
						call_user_func_array([$this,$post->method], $this->request );
						return;
					}
				}
				$this->rest_error(-1,'Error: '.$this->request[0].' method not found.','');
			break;
			case 'DELETE':
			foreach ($this->methoddelete as $delete) {
				$delete =(object) $delete;
				if(strtolower($delete->path) == strtolower($this->request[0])){
					array_shift($this->request);
					call_user_func_array([$this,$delete->method], $this->request );
					return;
				}
			}
			if($this->request[0]){
				$this->destroy($this->request[0]);
			} else {
				$this->rest_error(-1,'Error: '.$this->request[0].' method not found.','');
			}
			break;
			case 'HEAD':
			$this->rest_head();
			break;
			case 'OPTIONS':
			$this->rest_options();
			break;
			default:
			$err = 'error';
			$this->rest_error(-1,$err);
			break;
		}
	}

	private  function  preser_function() {
		$class_methods = get_class_methods(get_class($this));
		foreach ($class_methods as $method) {
			if ( in_array($method,$this->reservemethod) ){
				$this->rest_error(-9,$method . ' is reserve function.');
				exit();
			} else {
				if( preg_split('@(?=[A-Z])@', $method)[0] == 'get' ){
			                                            // echo 'get---->',strtolower($method),"\n";
					$this->methodget[] = [ 'method'=>$method,  'path' => strtolower(explode('get',$method,2)[1])  ];
				}elseif( preg_split('@(?=[A-Z])@', $method)[0] == 'put' ){
			                                            // echo 'put---->',$method,"\n";
					$this->methodput[] = ['method'=>$method , 'path'=> strtolower(explode('put',$method,2)[1])  ];
				}elseif( preg_split('@(?=[A-Z])@', $method)[0] == 'post' ){
			                                            // echo 'post--->',$method,"\n";
					$this->methodpost[] = ['method'=>$method,'path'=>strtolower(explode('post',$method,2)[1])  ];
				}elseif( preg_split('@(?=[A-Z])@', $method)[0] == 'delete' ){
			                                            // echo 'delete---->',$method,"\n";
					if($method != 'delete'){
						$this->methoddelete[] = ['method'=>$method,'path'=>strtolower(explode('delete',$method,2)[1])  ];
					}
				}
			}
		}

                    $this->methodget[] = ['method'=>'index','path'=>''];    //index()                /get---- getIndex
                    $this->methodget[] = ['method'=>'create','path'=>'create']; //   create()      /create/get----   getcreate
                    $this->methodget[] = ['method'=>'show','path'=>'show'];   // show($id)     /show/get----  getShow
                    $this->methodget[] = ['method'=>'edit','path'=>'edit'];    //edit($id)           /edit/get---- getEdit
                    $this->methodget[] = ['method'=>'routes','path'=>'routes'];    //edit($id)           /edit/get---- getEdit
                    $this->methodput[] = ['method'=>'update','path'=>'']; //    update($id)        /put--  putUpdate
                    $this->methodpost[] = ['method'=>'store','path'=>'']; //    store()                 /post--  postStore
                    $this->methoddelete[]=  ['method'=>'destroy','path'=>'']; //    destroy($id)      /delete--  deleteDestroy
                }


                protected function  defaultlast(){
                	$this->ERR404();
                	exit();
                	$this->rest_error(-1,'Error: '.$this->request[0].' method not found.','');
                }

                public function getVer(){
                	if($this->debug) {
                		echo  'Restful Server v.0.0.1',"\n<br>";
                	}
                }

                public function index()  {  
                	if($this->model){
                		(!$this->format ? $this->format = 'json' : null);
                		$this->response($this->all());
                	}
                }

                public function store(){ }
                public function show($id){
                	if($this->model) {
                		(!$this->format ? $this->format = 'json' : null);
                		$this->response($this->find($id));
                	}
                }

                public function update($id){ }
                public function destroy($id){ echo $id;}
                public function searchs(){}
                public function rest_options() {}
                public function rest_head() {}
                public function getServerinfo(){
                	if($this->debug){
                		dump($this);
                	}
                }


                public function rest_error($errno,$msg,$format='json'){ 
                	$this->response['code'] = 0;
                	$this->response['errno'] = $errno;
                	$this->response['status'] = $this->api_response_code[ $this->response['code'] ]['HTTP Response'];
                	$this->response['data'] = $msg;
                	$this->deliver_response($format, $this->response);
                	return;
                }
                protected  function deliver_response($format=null, $api_response){
	                // Set HTTP Response
                	header('HTTP/1.1 '.$api_response['status'].' '.$this->http_response_code[ $api_response['status'] ]);
	                // Process different content types
                	if( strcasecmp($format,'json') == 0 ){
	                // Set HTTP Response Content Type
                		header('Content-Type: application/json; charset=utf-8');
	                // Format data into a JSON response
                		$json_response = json_encode($api_response);
	                // Deliver formatted data
                		echo $json_response;
                	}elseif( strcasecmp($format,'xml') == 0 ){
	                // Set HTTP Response Content Type
                		header('Content-Type: application/xml; charset=utf-8');
	                // Format data into an XML response (This is only good at handling string data, not arrays)
                		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
                		'<response>'."\n".
                		"\t".'<code>'.$api_response['code'].'</code>'."\n".
                		"\t".'<data>'.$api_response['data'].'</data>'."\n".
                		'</response>';
	                // Deliver formatted data
                		echo $xml_response;
                	}else{
	                // Set HTTP Response Content Type (This is only good at handling string data, not arrays)
                		header('Content-Type: text/html; charset=utf-8');
	                // Deliver formatted data
                		echo $api_response['data'];
                	}
	                // End script process
                	exit;
                }

                protected function  response($data,$format=null) {
                	$this->response['code'] = 1;
                	$this->response['status'] = $this->api_response_code[ $this->response['code'] ]['HTTP Response'];
                	$this->response['data'] = $data;
                	if($format) {
                		$this->deliver_response($format, $this->response);
                	} else {
                		$this->deliver_response($this->format, $this->response);
                	}
                }
                protected function ERR404(){
                	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>
                	<title>404 &mdash; Not Found</title>
                	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
                	<meta name="description" content="Sorry, page not found"/>
                	<style type="text/css">
                		body {font-size:14px; color:#777777; font-family:arial; text-align:center;}
                		h1 {font-size:180px; color:#99A7AF; margin: 70px 0 0 0;}
                		h2 {color: #DE6C5D; font-family: arial; font-size: 20px; font-weight: bold; letter-spacing: -1px; margin: -3px 0 39px;}
                		p {width:320px; text-align:center; margin-left:auto;margin-right:auto; margin-top: 30px }
                		div {width:320px; text-align:center; margin-left:auto;margin-right:auto;}
                		a:link {color: #34536A;}
                		a:visited {color: #34536A;}
                		a:active {color: #34536A;}
                		a:hover {color: #34536A;}
                	</style>
                </head>
                <body>
                	<p><a href="'.$this->host.'">'.$this->host.'</a></p>
                	<h1>404</h1>
                	<h2>Page Not Found</h2>
                	<div>
                		It seems that the page you were trying to reach does not exist anymore, or maybe it has just moved.
                		You can start again from the <a href="'.$this->host.'">home</a> or go back to <a href="javascript:%20history.go(-1)">previous page</a>.
                	</div>
                </body>
                </html>';
                exit(0);
                return;
            }	

        }
