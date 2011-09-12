<?php

class App
{
    private $gets = array();
    private $posts = array();
    public $notFoundBody = '';
    public $errorBody = '';
    public $basePath = '';

    public function notFound()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        if ($this->notFoundBody == '')
        {
            print "404 Not Found\n";
        } else {
            print $this->notFoundBody;
        }
    }

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    public function returnError()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error',
            true, 500);
        if ($this->errorBody == '')
        {
            print "500 Internal Server Error\n";
        } else {
            print $this->errorBody;
        }
    }

    private function buildRoutePattern($pattern)
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = preg_replace('/\\\:([a-zA-Z0-9_]+)/', '(?P<$1>[^\/]+)', $pattern);
        return "/" . $pattern . "/";
    }

    public function seeother($path)
    {
        $newpath = str_replace('//', '/', $this->basePath . $path);
        header('Location: ' . $newpath);
    }

    public function serve()
    {
        $url = $_SERVER['REQUEST_URI'];
        $bpLen = strlen($this->basePath);
        if ($bpLen > 0 && strpos($url, $this->basePath) == 0)
            $url = substr($url, $bpLen - 1);
        if ($url == '')
            $url = '/';
        switch ($_SERVER['REQUEST_METHOD'])
        {
            case 'GET':
                $tRoutes = $this->gets;
                break;
            case 'POST':
                $tRoutes = $this->posts;
                break;
            default:
                $this->returnError();
                return;
        }

        foreach($tRoutes as $targetPattern => $callback)
        {
            $targetPattern = $this->buildRoutePattern($targetPattern);
            if (preg_match($targetPattern, $url, $matches))
            {
                $argnames = array_filter(array_keys($matches), 'is_string');
                $args = array();
                foreach ($argnames as $arg)
                {
                    $args[$arg] = $matches[$arg];
                }
                $result = $callback($args);
                if (is_string($result))
                    print $result;
                return;
            }
        }
        $this->notFound();
    }

    public function get($pattern, $callback)
    {
        $this->gets[$pattern] = $callback;
    }

    public function post($pattern, $callback)
    {
        $this->posts[$pattern] = $callback;
    }
}


