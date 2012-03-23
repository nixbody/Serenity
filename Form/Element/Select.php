<?php

namespace Serenity\Form\Element;

/**
 * This class represents form element select.
 *
 * @category   Serenity
 * @package    Form
 * @subpackage Element
 */
class Select extends AbstractElement
{
    /**
     * @var array A list of options.
     */
    protected $options = array();

    /**
     * Constructor.
     *
     * @param string $name       Name of element.
     * @param string $label      Element label.
     * @param array  $options    A list of options.
     * @param array  $attributes Element html attributes.
     */
    public function __construct($name, $label = '', array $options = array(),
        array $attributes = array())
    {
        parent::__construct($name, $label, $attributes);
        $this->addOptions($options);
    }

    /**
     * Add an option into the select.
     *
     * @param string $label Option label.
     * @param string $value Option value.
     *
     * @return Select Self instance.
     */
    public function addOption($label, $value)
    {
        $this->options[(string) $value] = (string) $label;

        return $this;
    }

    /**
     * Add a list of options into the select.
     *
     * @param array $options A list of options.
     *
     * @return Select Self instance.
     */
    public function addOptions(array $options)
    {
        foreach ($options as $value => $label) {
            $this->addOption($label, $value);
        }

        return $this;
    }

    /**
     * Set the list of options to select from.
     *
     * @param array $options The list of options.
     *
     * @return Select Self instance.
     */
    public function setOptions(array $options)
    {
        $this->options = array();

        return $this->addOptions($options);
    }

    /**
     * Get the list of options from the select.
     *
     * @return array The list of options.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Render element.
     *
     * @return string Rendered element.
     */
    public function render()
    {
        $result = '<select name="' . $this->name . '" ';
        $result .= $this->_implodeAttributes($this->attributes) . '>';

        foreach ($this->options as $value => $label) {
            $selected = ($value == $this->attributes['value'])
                ? ' selected="selected"'
                : '';

            $result .= '<option value="' . $value . '"' . $selected . '>'
                     . $label . '</option>';
        }

        return $result . '</select>';
    }
}