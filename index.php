<?php

require_once __DIR__.'/RestfulServer.php';

class Indexx extends  RestfulServer {
	public function index(){
		echo '<h1>Hello world</h1>';
	}
}

$app = new Indexx();
$app->run();