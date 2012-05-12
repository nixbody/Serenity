<?php

namespace Serenity\Dependency;

/**
 * Object dependency injector.
 *
 * @category Serenity
 * @package  Dependency
 */
class Injector
{
    /**
     * A dependency providing callback.
     *
     * @var callable|null
     */
    private $dependencyProvider = null;

    /**
     * Set a dependency providing callback.
     *
     * @param callable $dependencyProvider A dependency providing callback.
     *
     * @return Injector Self instance.
     */
    public function setDependencyProvider($dependencyProvider)
    {
        $this->dependencyProvider = $dependencyProvider;

        return $this;
    }

    /**
     * Inject a dependencies into the given object by its property annotations.
     *
     * @param object $object The object to which inject its dependencies.
     *
     * @return object The given object with injected dependencies.
     *
     * @throws \UnexpectedValueException If the given argument is not an object.
     */
    public function injectByAnnotations($object)
    {
        if (!\is_object($object)) {
            $message = 'The given argument is not an object.';
            throw new \UnexpectedValueException($message);
        }

        $properties = $this->_getObjectReflection($object)->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            if (null === $property->getValue($object)) {
                $docComment = $property->getDocComment();
                if (\preg_match('/@dependency\s+/', $docComment)) {
                    \preg_match('/@var\s+([^|\s]+)/', $docComment, $class);

                    $class = \ltrim($class[1], '\\');
                    $dependency = (null !== $this->dependencyProvider)
                        ? \call_user_func($this->dependencyProvider, $class)
                        : new $class();

                    $property->setValue($object, $dependency);
                }
            }
        }

        return $object;
    }

    /**
     * Get the reflection of the given object.
     *
     * @param object $object The object to get a reflection of.
     *
     * @return \ReflectionClass The reflection of the given object.
     */
    protected function _getObjectReflection($object)
    {
        static $reflections = array();

        $class = \get_class($object);

        if (isset($reflections[$class])) {
            return $reflections[$class];
        } else {
            return $reflections[$class] = new \ReflectionClass($class);
        }
    }
}
