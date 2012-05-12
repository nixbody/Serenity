<?php

namespace Serenity\Output;

/**
 * View object represents output layer of Serenity MVC based application.
 *
 * @category Serenity
 * @package  Output
 */
class View
{
    /**
     * @var string View templates directory.
     */
    private $templateDir = './private/Application/Templates';

    /**
     * @var string Path to view layout file.
     */
    private $layout = '';

    /**
     * @var array List of view plugins.
     */
    private $plugins = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_addDefaultPlugins();
    }

    /**
     * Return an empty string.
     *
     * @param string $property A non-existing property.
     *
     * @return string An empty string.
     */
    public function __get($property)
    {
        return '';
    }

    /**
     * Call a plugin callback.
     *
     * @param string $method Plugin name.
     * @param array  $args   Arguments to pass to a plugin callback.
     *
     * @return mixed A plugin callback result.
     *
     * @throws ViewException In case of calling unregistered plugin.
     */
    public function __call($method, array $args)
    {
        if (!isset($this->plugins[$method])) {
            $message = "Unregistered plugin '$method'.";
            throw new ViewException($message);
        }

        return \call_user_func_array($this->plugins[$method], $args);
    }

    /**
     * Set view templates directory.
     *
     * @param string $templateDir Templates directory.
     *
     * @return View Self instance.
     */
    public function setTemplateDir($templateDir)
    {
        $this->templateDir = (string) $templateDir;

        return $this;
    }

    /**
     * Set view layout.
     *
     * @param string $layout View template file. Relative path to view directory
     *                       set by front controller.
     *
     * @return View Self instance.
     */
    public function setLayout($layout)
    {
        $this->layout = (string) $layout;

        return $this;
    }

    /**
     * Add the plugin callback.
     *
     * @param string $name   Plugin name.
     * @param mixed  $plugin The plugin callback.
     *
     * @return View Self instance.
     */
    public function addPlugin($name, $plugin)
    {
        if (!\is_callable($plugin)) {
            $message = 'View plugin must be callable.';
            throw new ViewException($message);
        }

        $this->plugins[(string) $name] = $plugin;

        return $this;
    }

    /**
     * Get a list of assigned variables.
     *
     * @return array A list of assigned variables.
     */
    public function getVarialbes()
    {
        $callback = function($object) { return \get_object_vars($object); };

        return $callback($this);
    }

    /**
     * Output string. Escapes and outputs specified string.
     *
     * @param string $output Output string.
     */
    public function out($output)
    {
        echo $this->escape($output);
    }

    /**
     * Render view using specified template unless rendering is disabled.
     *
     * @param string $template Template file.
     *
     * @return string Rendered content. Empty string if rendering is disabled.
     *
     * @throws ViewException If template file not found.
     */
    public function render($template)
    {
        $template = (string) $template;
        $path = ($this->templateDir)
            ? \realpath($this->templateDir . "/$template")
            : $path = \realpath($template);

        if (!$path) {
            throw new ViewException("View template '$template' not found.");
        }

        $layout = $this->layout;
        $this->layout = '';

        \ob_start();
        \extract($this->getVarialbes());
        require $path;
        $content = \ob_get_contents();
        \ob_end_clean();

        if ($layout) {
            $this->_content = $content;
            $content = $this->render($layout);
            $this->layout = $layout;
        }

        return $content;
    }

    /**
     * Add default plugins.
     */
    protected function _addDefaultPlugins()
    {
        $this->addPlugin('escape', array($this, '_escape'))
             ->addPlugin('translit', array($this, '_translit'))
             ->addPlugin('uriEncode', array($this, '_uriEncode'))
             ->addPlugin('uri', array($this, '_uri'));
    }

    /**
     * Escape string. Converts string using php htmlspecialchars function.
     *
     * @param $string String to escape.
     *
     * @return string Escaped string.
     */
    protected function _escape($string)
    {
        return \htmlspecialchars((string) $string, \ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Translit string.
     *
     * @param string $string A string to translit.
     *
     * @return string Translited string.
     */
    protected function _translit($string)
    {
        $input = 'áäčďéěíľĺňóöőôřŕšťúůüűýžÁÄČĎÉĚÍĽĹŇÓÖŐÔŘŔŠŤÚŮÜŰÝŽ';
        $output = 'aacdeeillnoooorrstuuuuyzAACDEEILLNOOOORRSTUUUUYZ';
        $table = \array_combine(
            \preg_split('//u', $input, -1, \PREG_SPLIT_NO_EMPTY),
            \preg_split('//u', $output, -1, \PREG_SPLIT_NO_EMPTY)
        );

        return \strtr((string) $string, $table);
    }

    /**
     * Encode the given string to a format valid for URI.
     *
     * @param string $string A string to encode.
     *
     * @return string Encoded string.
     */
    protected function _uriEncode($string)
    {
        $string = \preg_replace('/\s+/', '-', $this->translit($string));

        return \strtolower($string);
    }

    /**
     * Create a URI from specified parameters.
     *
     * @param array|string $params A parameters from which to create a URI.
     *
     * @return string Created URI.
     */
    protected function _uri($params)
    {
        if (\is_string($params)) {
            $string = $params;
            $params = array();
            foreach (\preg_split('/\s*,\s*/', $string) as $pair) {
                list($key, $value) = \preg_split('/\s*:\s*/', $pair);
                $params[$key] = $value;
            }
        }

        return '?' . \rawurldecode(\http_build_query((array) $params));
    }
}
