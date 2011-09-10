<?php
require 'Twig/Autoloader.php';
Twig_Autoloader::register();

function tmpl_init($templatePath = 'templates')
{
    $loader = new Twig_Loader_Filesystem($templatePath);
    $twig = new Twig_Environment($loader);
    return $twig;
}
