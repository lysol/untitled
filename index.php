<?php

require 'view.php';
require 'model.php';
require 'app.php';
require 'markdown.php';

$app = new App();
$twig = tmpl_init();
$config = parse_ini_file("untitled.ini");

$dbconn = mysql_connect($config['db_server'], $config['db_username'],
    $config['db_password']);
$res = mysql_query('SELECT 1', $dbconn);
if (!$res)
    mdie("Could not connect to database");
$articleModel = new DBModel('articles', $dbconn, $config['db_name']);



function renderBody ($text, $basePath)
{
    $text = preg_replace('/\[\[([^ ]+)\]\]/', '[\1](' . $basePath
        . 'wiki/\1/)', $text);
    $text = strip_tags(Markdown($text),
        '<ul><ol><li><a><b><p><div><table><tr><td><h1><h2><h3><h4><h5><h6>'
        . '<i><u><span>');
    return $text;
}

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
        'article_body' => renderBody($article->body, $app->basePath),
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