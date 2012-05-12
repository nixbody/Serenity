<?php

namespace Serenity\Form\Element;

/**
 * This class represents form element.
 *
 * @category   Serenity
 * @package    Form
 * @subpackage Element
 */
abstract class AbstractElement
{
    /**
     * @var string Name of element.
     */
    protected $name;

    /**
     * @var string Element label.
     */
    protected $label;

    /**
     * @var array Element html attributes.
     */
    protected $attributes = array('value' => '');

    /**
     * @var array List of validation callbacks.
     */
    protected $validators = array();

    /**
     * Constructor.
     *
     * @param string $name       Name of element.
     * @param string $label      Element label.
     * @param array  $attributes Element html attributes.
     */
    public function __construct($name , $label = '',
        array $attributes = array())
    {
        $this->name = (string) $name;
        $this->setLabel($label);
        $this->setAttributes($attributes);
    }

    /**
     * Get name of element.
     *
     * @return string Name of element.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set element label.
     *
     * @param string $label
     *
     * @return AbstractElement Self instance.
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;

        return $this;
    }

    /**
     * Get element label.
     *
     * @return string Element label.
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set element attribute value.
     *
     * @param string $attribute Name of attribute.
     * @param string $value     Value of attribute.
     *
     * @return AbstractElement Self instance.
     */
    public function setAttribute($attribute, $value)
    {
        $this->attributes[(string) $attribute] = (string) $value;

        return $this;
    }

    /**
     * Get element attribute value.
     *
     * @param string $attribute Name of attribute.
     *
     * @return string Element attribute value.
     */
    public function getAttribute($attribute)
    {
        $attribute = (string) $attribute;
        if (!isset($this->attributes[$attribute])) {
            $message = "Form element does not have attribute '$attribute'.";
            throw new \InvalidArgumentException($message);
        }

        return $this->attributes[$attribute];
    }

    /**
     * Set attributes values.
     *
     * @param array $attributes Values of attributes.
     *
     * @return AbstractElement Self instance.
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        return $this;
    }

    /**
     * Get all element's attributes.
     *
     * @return array List of attributes.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Remove specified attribute from the element.
     *
     * @param string $attribute Attribute name.
     *
     * @return AbstractElement Self instance.
     */
    public function removeAttribute($attribute)
    {
        unset($this->attributes[(string) $attribute]);

        return $this;
    }

    /**
     * Set element's value.
     *
     * @param string $value A value.
     *
     * @return AbstractElement Self instance.
     */
    public function setValue($value)
    {
        return $this->setAttribute('value', $value);
    }

    /**
     * Get element's value.
     *
     * @return string Element`s value.
     */
    public function getValue()
    {
        return $this->getAttribute('value');
    }

    /**
     * Add validator.
     *
     * @param mixed $callback Validation callback.
     *
     * @return AbstractElement Self instance.
     *
     * @throws \InvalidArgumentException If the given callback is not callable.
     */
    public function addValidator($callback)
    {
        if (!\is_callable($callback)) {
            $message = 'Validator must be callable.';
            throw new \InvalidArgumentException($message);
        }

        $this->validators[] = $callback;

        return $this;
    }

    /**
     * Validates element's value through list of validators.
     *
     * @return bool True if valid false otherwise.
     */
    public function isValid()
    {
        foreach ($this->validators as $validator) {
            if (!$validator($this->getValue())) {
                return false;
            }
        }

        return true;
    }

    protected function _implodeAttributes($attributes)
    {
        $result = array();
        foreach ($attributes as $attribute => $value) {
            $result[] = $attribute . '="' . $value . '"';
        }

        return \implode(' ', $result);
    }

    public abstract function render();
}
