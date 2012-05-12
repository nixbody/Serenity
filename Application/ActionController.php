<?php

namespace Serenity\Application;

/**
 * This class provides basic template for an action controllers.
 *
 * @category Serenity
 * @package  Application
 */
class ActionController
{
    /**
     * Parent front controller.
     *
     * @var FrontController
     */
    private $frontController;

    /**
     * Constructor.
     *
     * @param FrontController $frontController Parent front controller.
     */
    public function __construct(FrontController $frontController)
    {
        $this->frontController = $frontController;
    }

    /**
     * Initialize action controller.
     */
    public function init()
    {

    }

    /**
     * This is the last method called in front controller dispatch.
     */
    public function end()
    {

    }

    /**
     * Forward to another controller and/or action.
     *
     * @param string $controller Controller name.
     * @param string $action     Action name.
     * @param array  $params     List of parameters.
     */
    protected function forward($controller, $action, array $params = array())
    {
        $this->end();

        $params['controller'] = (string) $controller;
        $params['action'] = (string) $action;
        $this->frontController->execute($params);
        exit(0);
    }

    /**
     * Redirect to the given URI.
     *
     * @param string $uri A URI to which redirect.
     */
    protected function redirect($uri)
    {
        $this->end();

        \header("Location: $uri");
        exit(0);
    }

    /**
     * Redirect to previous URI.
     */
    protected function redirectBack()
    {
        $this->redirect(\filter_input(\INPUT_SERVER, 'HTTP_REFERER') ?: '/');
    }

    /**
     * Refresh current page.
     */
    protected function refresh()
    {
        $this->redirect(\filter_input(\INPUT_SERVER, 'REQUEST_URI'));
    }
}
