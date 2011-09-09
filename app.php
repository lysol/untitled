<?php

class App
{
    private $gets = array();
    private $posts = array();
    public $notFoundBody = '';
    public $errorBody = '';
    private $basePath = '';

    public function notFound()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        if ($this->notFoundBody == '')
        {
            return "404 Not Found\n";
        } else {
            return $this->notFoundBody;
        }
    }

    public function basePath($basePath)
    {
        $this->basePath = $basePath;
    }

    public function returnError()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        if ($this->errorBody == '')
        {
            return "500 Internal Server Error\n";
        } else {
            return $this->errorBody;
        }
    }

    public function serve()
    {
        $url = $_SERVER['REQUEST_URI'];
        $bpLen = strlen($this->basePath);
        if ($bpLen > 0 && strpos($url, $this->basePath) == 0)
            $url = substr($url, $bpLen);
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
                return $this->returnError();
        }

        foreach($tRoutes as $targetPattern => $callback)
        {
            if (preg_match($targetPattern, $url, $matches))
            {
                $argnames = array_filter(array_keys($matches), 'is_string');
                $args = array();
                foreach ($argnames as $arg)
                {
                    $args[$arg] = $matches[$arg];
                }
                return $callback($args);
            }
        }
        return $this->notFound();
    }

    public function get($pattern, $callback)
    {
        $this->gets[$pattern] = $callback;
    }

    public function post($pattern, $className)
    {
        $this->posts[$pattern] = $callback;
    }
}
