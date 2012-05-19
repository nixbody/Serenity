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
     * Name of element.
     *
     * @var string
     */
    protected $name;

    /**
     * Element label.
     *
     * @var string
     */
    protected $label;

    /**
     * Element html attributes.
     *
     * @var array
     */
    protected $attributes = array('value' => '');

    /**
     * List of validation callbacks.
     *
     * @var array
     */
    protected $validators = array();

    /**
     * A message attached to the element.
     *
     * @var string
     */
    protected $message = '';

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
     * Get a value of the given attribute.
     *
     * @param string $attribute Name of the attribute of which to get a value.
     *
     * @return string A value of the given attribute.
     */
    public function __get($attribute)
    {
        switch ((string) $attribute) {
            case 'name':
                return $this->name;

            case 'label':
                return $this->label;

            case 'value':
                return $this->getValue();

            case 'message':
                return $this->message;

            default:
                return $this->getAttribute($attribute);
        }
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
     * Add a validator to the element.
     *
     * @param callable $callback    The validation callback.
     * @param string   $message     A message to be shown if validation fails.
     * @param mixed    $message,... Optional arguments to be passed to the given
     *                              callback.
     *
     * @return AbstractElement Self instance.
     *
     * @throws \InvalidArgumentException If the given callback is not callable.
     */
    public function addValidator($callback, $message)
    {
        if (!\is_callable($callback)) {
            $message = 'Validator must be callable.';
            throw new \InvalidArgumentException($message);
        }

        $this->validators[] = array(
            'callback' => $callback,
            'message' => (string) $message,
            'args' => \array_slice(\func_get_args(), 2)
        );

        return $this;
    }

    /**
     * Validate element's value through the list of
     * previously added validators.
     *
     * @return bool True if the element's value is valid, false otherwise.
     */
    public function isValid()
    {
        $value = $this->getValue();
        foreach ($this->validators as $validator) {
            $callback = $validator['callback'];
            \array_unshift($validator['args'], $value);

            if (!\call_user_func_array($callback, $validator['args'])) {
                $this->message = $validator['message'];

                return false;
            }
        }

        return true;
    }

    /**
     * Get a message attached to the element.
     *
     * @return string A message attached to the element.
     */
    public function getMessage()
    {
        return $this->message;
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
