<?php

namespace Serenity\Form\Element;

/**
 * This class represents form element radio select.
 *
 * @category   Serenity
 * @package    Form
 * @subpackage Element
 */
class RadioSelect extends Select
{
    /**
     * Render element.
     *
     * @return string Rendered element.
     */
    public function render()
    {
        $attributes = $this->attributes;
        $selectValue = $attributes['value'];
        unset($attributes['value']);

        $attributes = $this->_implodeAttributes($attributes);
        $result = '<ul ' . $attributes . '>';
        foreach ($this->options as $value => $label) {
            $id = \uniqid('serenity_form_select_element_', true);

            $input = '<input id="' . $id . '"'
                   . ' name="' . $this->name . '"'
                   . ' value="' . $value . '"'
                   . ' type="radio"';
            if ($value == $selectValue) {
                $input .= ' checked="checked"';
            }
            $input .= ' />';

            $label = '<label for="' . $id . '">' . $input
                   . '<span>' . $label . '</span></label>';

            $result .= '<li>' . $label . '</li>';
        }

        return $result . '</ul>';
    }
}