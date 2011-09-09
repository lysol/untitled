<?php

require 'view.php';
require 'model.php';
require 'app.php';

$app = new App();
$app->basePath('/~darnold/untitled/');
$app->get('/\//', function($args) {
    print 'Hello world!';
});
$app->serve();