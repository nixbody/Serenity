<?php

namespace Serenity\Application;

/**
 * The entry point of a MVC driven application.
 *
 * Loads and instantiates an action controller and runs action both specified
 * in the given request.
 *
 * @category Serenity
 * @package  Application
 */
class FrontController
{
    /**
     * @var string Controller directory.
     */
    private $controllerDir = './private/Application/Controllers';

    /**
     * Dependency injector for an action controllers.
     *
     * @var callable|null
     */
    private $dependencyInjector = null;

    /**
     * Set controllers directory.
     *
     * @param string $controllerDir Controllers directory.
     */
    public function setControllerDir($controllerDir)
    {
        $this->controllerDir = (string) $controllerDir;
    }

    /**
     * Set a dependency injector for an action controllers.
     *
     * @param callable $dependencyInjector A dependency injecting callback.
     *
     * @return FrontController Self instance.
     */
    public function setDependencyInjector($dependencyInjector)
    {
        $this->dependencyInjector = $dependencyInjector;

        return $this;
    }

    /**
     * Load and instantiate the specified action controller.
     *
     * @param string $controllerName Action controller name.
     *
     * @throws \InvalidArgumentException If the controller file not found.
     */
    protected function _getController($controllerName)
    {
        $controllerName = (string) $controllerName;
        $controllerClass = "{$controllerName}Controller";
        $filePath =
            \realpath($this->controllerDir .  "/$controllerClass.php");

        if (false === $filePath) {
            $message = "Controller '$controllerName' not found.";
            throw new \InvalidArgumentException($message);
        }

        require_once $filePath;

        return new $controllerClass($this);
    }

    /**
     * Get a list of arguments for the given controller and action.
     *
     * @param object $controller Controller instance.
     * @param string $action     Action name.
     * @param array  $params     Parameters from request.
     *
     * @return array List of arguments.
     *
     * @throws \InvalidArgumentException If the required argument is not
     *                                   specified in the request.
     */
    protected function _getActionArguments($controller, $action, array $params)
    {
        $action = (string) $action;
        $reflection = new \ReflectionMethod($controller, $action);

        $arguments = array();
        foreach ($reflection->getParameters() as $arg) {
            $argName = $arg->getName();
            if (isset($params[$argName])) {
                $arguments[] = $params[$argName];
            } elseif ($arg->isOptional()) {
                $arguments[] = $arg->getDefaultValue();
            } else {
                $message = "Required action argument '$argName' not specified.";
                throw new \InvalidArgumentException($message);
            }
        }

        return $arguments;
    }

    /**
     * Run controller action.
     *
     * @param object $controller Controller instance.
     * @param string $action     Action name.
     * @param array  $params     Parameters from request.
     *
     * @throws \InvalidArgumentException If action does not exist.
     */
    protected function _runAction($controller, $action, array $params)
    {
        $actionName = (string) $action;
        $action = "{$actionName}Action";

        if (!\method_exists($controller, $action)) {
            $message = "Action '$actionName' does not exist.";
            throw new \InvalidArgumentException($message);
        }

        $arguments = $this->_getActionArguments($controller, $action, $params);

        return \call_user_func_array(array($controller, $action), $arguments);
    }

    /**
     * Run application.
     */
    public function execute(array $request)
    {
        if (empty($request['controller']) || empty($request['action'])) {
            $message = 'Controller and action must be specified in the request';
            throw new \InvalidArgumentException($message);
        }

        $controller = $this->_getController($request['controller']);

        if (null !== $this->dependencyInjector) {
            \call_user_func($this->dependencyInjector, $controller);
        }

        $action = $request['action'];
        unset($request['controller'], $request['action']);

        if (\method_exists($controller, 'init')) {
            $controller->init();
        }

        echo $this->_runAction($controller, $action, $request);

        if (\method_exists($controller, 'end')) {
            $controller->end();
        }
    }
}
