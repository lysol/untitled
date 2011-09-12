<?php

require 'view.php';
require 'model.php';
require 'app.php';

$app = new App();
$twig = tmpl_init();
$dbconn = mysql_connect('localhost', 'demo', 'demo', 'demo');
$res = mysql_query('SELECT 1', $dbconn);
if (!$res)
    mdie("Test");
$articleModel = new DBModel('articles', $dbconn, 'demo');

$app->basePath = '/~darnold/untitled/';

$app->post('/wiki/:article/edit', function($args)
    use ($app, $twig, $articleModel) {
    $articles = $articleModel->find(array(
        'name' => $args['article']
    ));
    $article = (count($articles) > 0) ? $articles[0] : null;
    if (!$article)
    {
        $article = $articleModel->insert(array(
            'name' => $args['article'],
            'body' => $_POST['article_body']
        ));
    } else {
        $article->body = $_POST['article_body'];
        $article->save();
    }
    $app->seeother('/wiki/' . $args['article']);
    return;
});

$app->get('/wiki/:article/edit', function($args)
    use ($app, $twig, $articleModel) {
    $articles = $articleModel->find(array(
        'name' => $args['article']
    ));
    $article = (count($articles) > 0) ? $articles[0] : null;
    $template = $twig->loadTemplate('article_edit.html');
    return $template->render(array(
        'article' => $article,
        'basePath' => $app->basePath,
        'article_body' => $article->body,
        'article_name' => $args['article']
    ));
});

$app->get('/wiki/:article', function($args)
    use ($app, $twig, $articleModel) {
    $articles = $articleModel->find(array(
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
        'article_body' => $article->body,
        'basePath' => $app->basePath,
        'article_name' => $args['article']
    ));
});

$app->get('/', function($args)
    use ($twig) {
    $template = $twig->loadTemplate('home.html');
    return $template->render(array());
});


$app->serve();