<?php

namespace App;

class Routing
{
    private array $routes = [];

    public function register(string $routeMethod, string $route, callable | array $action): static
    {
        $this->routes[$routeMethod][$route] = $action;
        return $this;
    }

    public function get(string $route, callable | array $action): Routing
    {
        return $this->register('GET', $route, $action);
    }
    public function post(string $route, callable | array $action): Routing
    {
        return $this->register('POST', $route, $action);
    }

    public function allRoutes(): array
    {
        return $this->routes;
    }

    public function resolve(string $requestURI, string $requestMethod)
    {
        $route = explode('?', $requestURI)[0];
        $action = $this->routes[$requestMethod][$route]?? null;

        if(! $action){
            throw new RouteNotFoundException();
        }

        if(is_callable($action)){
            return call_user_func_array($action, []);
        }

        if(is_array($action)){
            [$class , $method] = $action;

            if(class_exists($class)){
                $class = new $class();

                return call_user_func_array([$class, $method], []);
            }
        }

    }
}