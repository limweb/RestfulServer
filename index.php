<?php

require_once __DIR__.'/RestfulServer.php';

class Indexx extends  RestfulServer {
}

$app = new Indexx();
$app->run();