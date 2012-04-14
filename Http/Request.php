<?php

namespace Serenity\Http;

/**
 * An object that represents user request sent via HTTP protocol.
 *
 * @category Serenity
 * @package  Http
 */
class Request
{
    /**
     * The request parameters cache.
     *
     * @var array
     */
    private $paramCache = array();

    /**
     * Get a list of the request parameters from the given source.
     * The given request may be get, post, cookie, server or env.
     *
     * @param string $source The source of the parameters.
     *
     * @return \ArrayObject A list of the request parameters
     *                      from the given source.
     */
    public function __get($source)
    {
        $source = (string) $source;
        $key = \strtolower($source);

        if (!isset($this->paramCache[$key])) {
            $source = \strtoupper($source);
            $params = ('FILES' !== $source)
                ? (array) \filter_input_array(\constant('INPUT_' . $source))
                : $_FILES;

            $this->paramCache[$key] = $this->_arrayToObject($params);
        }

        return $this->paramCache[$key];
    }

    /**
     * Convert recursively the given array to an array-like object.
     *
     * @param array $array The array to be converted.
     *
     * @return \ArrayObject Converted array.
     */
    protected function _arrayToObject(array $array)
    {
        foreach ($array as &$value) {
            if (\is_array($value)) {
                $value = $this->_arrayToObject($value);
            }
        }

        return new \ArrayObject($array, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Determine if the request was made through XML HTTP Request protocol also
     * known as AJAX.
     *
     * @return bool True if the request was made through XHR, false otherwise.
     */
    public function isXhr()
    {
        $param = \filter_input(\INPUT_SERVER, 'HTTP_X_REQUESTED_WITH');

        return 'XMLHttpRequest' === $param;
    }
}
