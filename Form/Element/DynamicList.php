<?php

namespace Serenity\Form\Element;

/**
 * This class represents form element multi-select.
 *
 * @category   Serenity
 * @package    Form
 * @subpackage Element
 */
class DynamicList extends AbstractElement
{
    /**
     * @var string Remove button label.
     */
    private $removeButtonLabel = 'ˣ';

    /**
     * @var string Add button label.
     */
    private $addButtonLabel = '+';

    /**
     * @var array Element html attributes.
     */
    protected $attributes = array('value' => array());

    /**
     * Set selected values.
     *
     * @param array $value Selected values.
     *
     * @return DynamicList Self instance.
     */
    public function setValue($value)
    {
        $values = \array_unique(\array_filter((array) $value));
        $this->attributes['value'] = $values;

        return $this;
    }

    /**
     * Get selected values.
     *
     * @return array Selected values.
     */
    public function getValue()
    {
        return $this->attributes['value'];
    }

    /**
     * Set item remove button label.
     *
     * @param string $label Item remove button label.
     *
     * @return DynamicList Self instance.
     */
    public function setRemoveButtonLabel($label)
    {
        $this->removeButtonLabel = (string) $label;

        return $this;
    }

    /**
     * Set item add button label.
     *
     * @param string $label Item add button label.
     *
     * @return DynamicList Self instance.
     */
    public function setAddButtonLabel($label)
    {
        $this->addButtonLabel = (string) $label;

        return $this;
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
        $attributes = $this->_implodeAttributes($attributes);

        $values = $this->getValue();
        $result = '<ol ' . $attributes . '>';

        $remove = 'this.parentNode.parentNode.removeChild(this.parentNode);'
                . 'return false;';

        foreach ($values as $value) {
            $result .= '<li class="item">'
                     . ' <input class="value" name="' . $this->name . '[]"'
                     . ' value="' . $value . '" type="text" />'
                     . ' <input class="button" type="submit"'
                     . ' value="' . $this->removeButtonLabel . '"'
                     . ' onclick="' . $remove . '" /></li>';
        }

        $add = 'if (this.parentNode.firstChild.value) {'
             . 'var clone = this.parentNode.cloneNode(true);'
             . "clone.firstChild.value = '';"
             . 'this.parentNode.parentNode.appendChild(clone);'
             . "this.parentNode.className = 'item';"
             . "this.value = '$this->removeButtonLabel';"
             . "this.setAttribute('onclick', '$remove') };"
             . 'return false;';

        $result .= '<li class="add">'
                 . '<input class="value"'
                 . ' name="' . $this->name . '[]" type="text" />'
                 . ' <input class="button" type="submit"'
                 . ' value="' . $this->addButtonLabel . '"'
                 . ' onclick="' . $add . '" /></li>';

        return $result . '</ol>';
    }
}