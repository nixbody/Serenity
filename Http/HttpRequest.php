<?php

namespace Serenity\Http;

class HttpRequest
{
    private $paramCache = array();

    public function __get($source)
    {
        $source = (string) $source;
        $key = \strtolower($source);

        if (!isset($this->paramCache[$key])) {
            $source = \strtoupper($source);
            $params = ('FILES' != $source)
                ? (array) \filter_input_array(\constant('INPUT_' . $source))
                : $_FILES;

            $this->paramCache[$key] = $this->_arrayToObject($params);
        }

        return $this->paramCache[$key];
    }

    protected function _arrayToObject(array $array)
    {
        foreach ($array as &$value) {
            if (\is_array($value)) {
                $value = $this->_arrayToObject($value);
            }
        }

        return new \ArrayObject($array, \ArrayObject::ARRAY_AS_PROPS);
    }

    public function isXhr()
    {
        $param = \filter_input(\INPUT_SERVER, 'HTTP_X_REQUESTED_WITH');

        return 'XMLHttpRequest' === $param;
    }
}
