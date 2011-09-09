<?php

class App
{
    private $gets = array();
    private $posts = array();

    public function notFound()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        if (!$this->notFoundBody)
        {
            return "404 Not Found\n";
        } else {
            return $this->notFoundBody;
        }
    }

    public function returnError()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        if (!$this->errorBody)
        {
            return "500 Internal Server Error\n";
        } else {
            return $this->errorBody;
        }
    }

    public function serve()
    {
        $url = $_SERVER['REQUEST_URI'];
        foreach($this->routes as $targetPattern => $className)
        {
            if (preg_match($targetPattern, $url, $matches))
            {
                $argnames = array_filter(array_keys($matches), 'is_string');
                $args = array();
                foreach ($argnames as $arg)
                {
                    $args[$arg] = $matches[$arg];
                }
                switch ($_SERVER['REQUEST_METHOD'])
                {
                    case 'GET':
                        $this->gets[$url]($args);
                        break;
                    case 'POST':
                        $this->posts[$url]($args);
                        break;
                    default:
                        return $this->returnError();
                }
            }
        }
        return $this->notFound();
    }

    public function get($pattern, $callback)
    {
        $this->gets[pattern] = $callback;
    }

    public function post($pattern, $className)
    {
        $this->posts[pattern] = $callback;
    }
}
