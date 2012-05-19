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
     * A list of options.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Element html attributes.
     *
     * @var array
     */
    protected $attributes = array('value' => array());

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
     * Set selected values.
     *
     * @param array|\Traversable $value Selected values.
     *
     * @return AbstractElement Self instance.
     */
    public function setValue($value)
    {
        if ($value instanceof \Traversable) {
            $value = \iterator_to_array($value, true);
        }

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
        unset($attributes['value']);

        $result = '<select name="' . $this->name . '[]" ';
        $result .= $this->_implodeAttributes($attributes) . '>';

        foreach ($this->options as $value => $label) {
            $selected = isset($this->attributes['value'][$value])
                ? ' selected="selected"'
                : '';

            $result .= '<option value="' . $value . '"' . $selected . '>'
                     . $label . '</option>';
        }

        return $result . '</select>';
    }
}