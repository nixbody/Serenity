<?php

namespace Serenity\Application;

/**
 * Loads and instantiates an action controller and runs action specified by
 * user request.
 *
 * @category Serenity
 * @package  Application
 */
class FrontController
{
    /**
     * @var string Controller directory.
     */
    private $controllerDir = '../Application/Controllers';

    /**
     * Dependency provider for an action controllers.
     *
     * @var callable|null
     */
    private $dependencyProvider = null;

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
     * Set a dependency provider for an action controllers.
     *
     * @param callable $dependencyProvider A dependency providing callback.
     *
     * @return FrontController Self instance.
     */
    public function setDependencyProvider($dependencyProvider)
    {
        $this->dependencyProvider = $dependencyProvider;

        return $this;
    }

    /**
     * Load and instantiate the specified action controller.
     *
     * @param string $controllerName Action controller name.
     *
     * @throws FrontControllerException If the controller file not found.
     */
    protected function _getController($controllerName)
    {
        $controllerName = (string) $controllerName;
        $controllerClass = "{$controllerName}Controller";
        $filePath =
            \realpath($this->controllerDir .  "/$controllerClass.php");

        if (false === $filePath) {
            $message = "Controller '$controllerName' not found.";
            throw new FrontControllerException($message);
        }

        require_once $filePath;

        return new $controllerClass($this);
    }

    protected function _provideControllerDependencies($controller)
    {
        $reflection = new \ReflectionObject($controller);

        foreach ($reflection->getProperties() as $property) {
            $docComment = $property->getDocComment();

            if (\preg_match('/@dependency\s*/', $docComment)) {
                \preg_match('/@var\s+([^|\s]+)/', $docComment, $class);

                $class = \preg_replace('/^\\\/', '', $class[1], 1);
                $dependency = (null !== $this->dependencyProvider)
                    ? \call_user_func($this->dependencyProvider, $class)
                    : new $class();

                $property->setAccessible(true);
                $property->setValue($controller, $dependency);
            }
        }
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
     * @throws FrontControllerException If argument is not specified in request.
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
                throw new FrontControllerException($message);
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
     * @throws FrontControllerException If action does not exist.
     */
    protected function _runAction($controller, $action, array $params)
    {
        $actionName = (string) $action;
        $action = "{$actionName}Action";

        if (!\method_exists($controller, $action)) {
            $message = "Action '$actionName' does not exist.";
            throw new FrontControllerException($message);
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
            throw new FrontControllerException($message);
        }

        $controller = $this->_getController($request['controller']);
        $this->_provideControllerDependencies($controller);

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
