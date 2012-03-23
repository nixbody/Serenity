<?php

namespace Serenity\Form\Element;

/**
 * This class represents form element multi-select.
 *
 * @category   Serenity
 * @package    Form
 * @subpackage Element
 */
class MultiSelect extends Select
{
    /**
     * @var array Element html attributes.
     */
    protected $attributes = array('value' => array());

    /**
     * Set selected values.
     *
     * @param array $value Selected values.
     *
     * @return AbstractElement Self instance.
     */
    public function setValue($value)
    {
        $value = \array_flip((array) $value);
        $value = \array_intersect_key($value, $this->options);
        $this->attributes['value'] = $value;

        return $this;
    }

    /**
     * Get selected values.
     *
     * @return array Selected values.
     */
    public function getValue()
    {
        return \array_keys($this->attributes['value']);
    }

    /**
     * Render element.
     *
     * @return string Rendered element.
     */
    public function render()
    {
        $attributes = $this->attributes;
        $selectValues = $attributes['value'];
        unset($attributes['value']);

        $attributes = $this->_implodeAttributes($attributes);
        $result = '<ul ' . $attributes . '>';
        foreach ($this->options as $value => $label) {
            $id = \uniqid('serenity_form_select_element_', true);
            $id = \str_replace('.', '', $id);

            $input = '<input id="' . $id . '"'
                   . ' name="' . $this->name . '[]"'
                   . ' value="' . $value . '"'
                   . ' type="checkbox"';
            if (isset($selectValues[$value])) {
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