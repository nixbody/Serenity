<?php

namespace Serenity\Form\Element;

class Input extends AbstractElement
{
    private $type;

    public function __construct($name , $type = 'text', $label = '',
        array $attributes = array())
    {
        parent::__construct($name, $label, $attributes);
        $this->type = \strtolower((string) $type);
    }

    public function setValue($value)
    {
        $oldValue = $this->attributes['value'];
        if ($this->type == 'checkbox' || $this->type == 'radio') {
            $this->removeAttribute('checked');
            if ($oldValue === '') {
                parent::setValue($value);
            } elseif ($oldValue == $value || $value == 'on') {
                $this->setAttribute('checked', 'checked');
            }
        } else {
            parent::setValue($value);
        }

        return $this;
    }

    public function render()
    {
        $input = '<input name="' . $this->name . '" type="' . $this->type . '"';
        $result =
            $input . ' ' . $this->_implodeAttributes($this->attributes) . ' />';

        return $result;
    }
}