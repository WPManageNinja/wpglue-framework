<?php

namespace Alpha\Framework\Http;

use Alpha\Framework\Support\Arr;

class Router
{
    protected $app = null;
    
    protected $name = [];
    
    protected $prefix = [];

    protected $routes = [];

    protected $routeGroups = [];
    
    protected $groupStack = [];
    
    protected $policyHandler = null;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function prefix($prefix)
    {
        $this->prefix[] = $prefix;

        return $this;
    }

    public function name($name)
    {
        $this->name[] = $name;

        return $this;
    }

    public function group($attributes = [], \Closure $callback = null)
    {
        if ($attributes instanceof \Closure) {
            $callback = $attributes;
            $attributes = [];
        }

        if (isset($attributes['name'])) {
            $this->name($attributes['name']);
        }

        if (isset($attributes['prefix'])) {
            $this->prefix($attributes['prefix']);
        }

        call_user_func($callback, $this);
        array_pop($this->prefix);
        array_pop($this->name);
    }

    public function withPolicy($handler)
    {
        $this->policyHandler = $handler;

        return $this;
    }

    public function get($uri, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $uri, $handler, \WP_REST_Server::READABLE
        );

        return $route;
    }

    public function post($uri, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $uri, $handler, \WP_REST_Server::CREATABLE
        );

        return $route;
    }

    public function put($uri, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $uri, $handler, \WP_REST_Server::EDITABLE
        );

        return $route;
    }

    public function patch($uri, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $uri, $handler, \WP_REST_Server::EDITABLE
        );

        return $route;
    }

    public function delete($uri, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $uri, $handler, \WP_REST_Server::DELETABLE
        );

        return $route;
    }

    public function any($uri, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $uri, $handler, \WP_REST_Server::ALLMETHODS
        );

        return $route;
    }

    protected function newRoute($uri, $handler, $method)
    {
        $route = Route::create(
            $this->app,
            $this->getRestNamespace(),
            $this->buildUriWithPrefix($uri),
            $handler,
            $method,
            implode('', $this->name)
        );

        if ($this->policyHandler) {
            $route->withPolicy($this->policyHandler);
        }

        return $route;
    }

    protected function getRestNamespace()
    {
        $version = $this->app->config->get('app.rest_version');

        $namespace = trim($this->app->config->get('app.rest_namespace'), '/');

        return "{$namespace}/{$version}";
    }

    protected function buildUriWithPrefix($uri)
    {
        $uri = trim($uri, '/');

        $prefix = array_map(function($prefix) {
            return trim($prefix, '/');
        }, $this->prefix);

        $prefix = implode('/', $prefix);

        return trim($prefix, '/') . '/' . trim($uri, '/');
    }

    public function registerRoutes()
    {
        foreach ($this->routes as $route) $route->register();
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}
