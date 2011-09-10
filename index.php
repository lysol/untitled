<?php

require 'view.php';
require 'model.php';
require 'app.php';

$app = new App();
$twig = tmpl_init();
$dbconn = mysql_connect('localhost', 'demo', 'demo', 'demo');
$articleModel = new DBModel('articles', $dbconn);

$app->basePath = '/~darnold/untitled/';

$app->get('/wiki/:article/edit', function($args)
    use ($app, $twig, $articleModel) {
    $articles = $articleModel.find(array(
        'name' => $args['article']
    ));
    $article = (count($articles) > 0) ? $articles[0] : null;
    $template = $twig->loadTemplte('article_edit.html');
    return $template->render(array(
        'article' => $article,
        'basePath' => $app->basePath,
        'article_name' => $args['article']
    ));
});

$app->get('/wiki/:article', function($args)
    use ($app, $twig, $articleModel) {
    print $args['article'];
    $articles = $articleModel.find(array(
        'name' => $args['article']
    ));
    $article = (count($articles) > 0) ? $articles[0] : null;
    if (!$article)
    {
        $app->seeother('/wiki/' . $args['article'] . '/edit');
        return;
    }
    $template = $twig->loadTemplate('article.html');
    return $template->render(array(
        'article' => $article,
        'basePath' => $app->basePath
    ));
});

$app->get('/', function($args)
    use ($twig) {
    $template = $twig->loadTemplate('home.html');
    return $template->render(array());
});


$app->serve();