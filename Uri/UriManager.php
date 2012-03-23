<?php

namespace Serenity\Uri;

class UriManager
{
    private $patterns = array();

    public function addPattern($pattern, array $defaults = array())
    {
        $pattern = (string) $pattern;

        $this->patterns[$pattern] = array(
            'defaults' => $defaults,
            'regex' => $this->_patternToRegex($pattern)
        );

        return $this;
    }

    public function matchUri($uri)
    {
        $uri = (string) $uri;

        foreach ($this->patterns as $pattern) {
            \preg_match($pattern['regex'], $uri, $matches);
            die(var_dump($matches));
        }
    }

    protected function _patternToRegex($pattern)
    {
        $regex = \preg_replace('/:([^:]+):/', '(?<_$1>[^/\$])', (string) $pattern);
        die(str_replace('?<_', '?<', $regex));

        return \preg_quote($regex, '`');
    }
}