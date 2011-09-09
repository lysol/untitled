<?php
require 'Twig/Autoloader.php';
Twig_Autoloader::register();

function tmpl_init()
{
    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader);
    return $twig;
}
