<?php

namespace Serenity\Form\Element;

class TextArea extends AbstractElement
{
    public function render()
    {
        $attributes = $this->attributes;
        if (isset($this->attributes['value'])) {
            $value = $this->attributes['value'];
            unset($attributes['value']);
        } else {
            $value = '';
        }

        $textArea = '<textarea name="' . $this->name . '" ';
        $result = $textArea .  $this->_implodeAttributes($attributes)
                . ">$value</textarea>";

        return $result;
    }
}