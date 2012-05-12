<?php

namespace Serenity\Form;

use Serenity\Form\Element\AbstractElement;

/**
 * Object representation of html form.
 *
 * @category Serenity
 * @package  Form
 */
class Form
{
    /**
     * @var array List of allowed form methods.
     */
    private $allowedMethods = array('get', 'post', 'put', 'delete');

    /**
     * @var string Form action.
     */
    private $action;

    /**
     * @var string Form method.
     */
    private $method;

    /**
     * @var array List of form elements.
     */
    private $elements = array();

    /**
     * Constructor.
     *
     * @param string $action Form action.
     * @param string $method Form method.
     */
    public function __construct($action = '', $method = 'post')
    {
        $this->setAction($action);
        $this->setMethod($method);
        $this->init();
    }

    /**
     * Form initialization.
     */
    public function init()
    {

    }

    /**
     * Set form action.
     *
     * @param string $action
     *
     * @return Form Self instance.
     */
    public function setAction($action)
    {
        $this->action = (string) $action;

        return $this;
    }

    /**
     * Get form action.
     *
     * @return string Form action.
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set form submission method.
     * Method must be one of listed in $allowedMethods.
     *
     * @param string $method Form method.
     *
     * @return Form Self instance.
     *
     * @throws \InvalidArgumentException If method is not allowed.
     */
    public function  setMethod($method)
    {
        $method = \strtolower((string) $method);
        if (!\in_array($method, $this->allowedMethods)) {
            $message = "Method '$method' is not allowed.";
            throw new \InvalidArgumentException($message);
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Get form submission method.
     *
     * @return string Form submission method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Add element into form.
     *
     * @param AbstractElement $element Form element.
     *
     * @return Form Self instance.
     */
    public function addElement(AbstractElement $element)
    {
        $this->elements[$element->getName()] = $element;

        return $this;
    }

    /**
     * Get element from form.
     *
     * @param string $element Name of element.
     *
     * @return AbstractElement Element.
     */
    public function getElement($element)
    {
        $element = (string) $element;
        if (!isset($this->elements[$element])) {
            $message = "Element '$element' does not exist.";
            throw new \InvalidArgumentException($message);
        }

        return $this->elements[$element];
    }

    /**
     * Add elements into form.
     *
     * @param array $elements Array of form elements.
     *
     * @return Form Self instance.
     */
    public function addElements(array $elements)
    {
        foreach ($elements as $element) {
            $this->addElement($element);
        }

        return $this;
    }

    /**
     * Get all form elements.
     *
     * @return array All form elements.
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Set form element value.
     *
     * @param string $name  Name of element.
     * @param string $value Value to set.
     *
     * @return Form Self instance.
     */
    public function setValue($name, $value)
    {
        $this->getElement($name)->setValue($value);

        return $this;
    }

    /**
     * Get form element value.
     *
     * @param string $name  Name of element.
     *
     * @return string Value.
     */
    public function getValue($name)
    {
        return $this->getElement($name)->getValue();
    }

    /**
     * Set form values.
     *
     * @param array $values List of values.
     */
    public function setValues(array $values)
    {
        foreach ($values as $name => $value) {
            if (isset($this->elements[(string) $name])) {
                $this->setValue($name, $value);
            }
        }

        return $this;
    }

    /**
     * Get form values.
     *
     * @return array $values List of values.
     */
    public function getValues()
    {
        $values = array();
        foreach ($this->elements as $name => $element) {
            $values[$name] = $element->getValue();
        }

        return $values;
    }

    public function isValid()
    {
        foreach ($this->elements as $element) {
            if (!$element->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Render form. Returns string containing xhtml representation of form.
     *
     * @return string Xhtml representation of form.
     */
    public function render()
    {
        $output = '<form action="' . $this->action . '" method="'
                . $this->method . '"><div>';

        foreach ($this->elements as $element) {
            try {
                $id = $element->getAttribute('id');
            } catch(ElementException $exp) {
                $id = \uniqid('serenity_form_element_', true);
                $id = \str_replace('.', '', $id);
                $element->setAttribute('id', $id);
            }

            $label = "<label for=\"$id\">" . $element->getLabel() . '</label>';
            $output .= "<div>$label " . $element->render() . '</div>';
        }

        return $output . '</div></form>';
    }
}
