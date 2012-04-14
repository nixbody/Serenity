<?php

namespace Serenity\Persistent;

/**
 * Represents persistent data object.
 *
 * @category Serenity
 * @package  Persistent
 */
abstract class DataObject
{
    /**
     * The object belongs to another persistent object.
     */
    const BELONGS_T0 = 1;

    /**
     * The object owns many other persistent objects.
     */
    const HAS_MANY = 2;

    /**
     * The object owns only one other persistent object.
     */
    const HAS_ONE = 3;

    /**
     * Many to many type relation.
     */
    const MANY_MANY = 4;

    /**
     * @var array The global data cache.
     */
    private static $_cache = array();

    /**
     * @var Storage A storage to use for loading/saving the object's data.
     */
    private $_storage;

    /**
     * @var array The object's meta-data.
     */
    private $_metaData = array();

    /**
     * @var array A list of related objects.
     */
    private $_relatedObjects = array();

    /**
     * Constructor.
     *
     * @param Storage $storage A storage to use for loading/saving
     *                         the object's data.
     */
    public function __construct(Storage $storage)
    {
        $this->_storage = $storage;
        $this->init();
    }

    /**
     * Initialize the object.
     */
    public function init()
    {

    }

    /**
     * Process and sanitize specified meta-data.
     *
     * @param array $metaData A meta-data to process.
     *
     * @return array Processed and sanitized meta-data.
     */
    private function _processMetaData(array $metaData)
    {
        if (!isset($metaData['primaryKey'])) {
            $metaData['primaryKey'] = 'id';
        }

        if (!empty($metaData['relatedObjects'])) {
            $metaData['relatedObjects'] = \array_map(
                array($this, '_processRelationalMetaData'),
                (array) $metaData['relatedObjects']
            );
        }

        return $metaData;
    }

    /**
     * Process and sanitize specified relational meta-data.
     *
     * @param array $metaData A meta-data to process.
     *
     * @return array Processed and sanitized meta-data.
     */
    private function _processRelationalMetaData(array $metaData)
    {
        $reference = $this->_parseReferenceString($metaData['reference']);
        $property = $this->_storage->getObjectReflection($this)
                         ->getProperty($reference[0]);

        $property->setAccessible(true);
        $value = $property->getValue($this);

        $relatedMeta = $this->_storage
            ->create($metaData['class'])
            ->getMetaData();

        return $metaData + array(
            'column' => $reference[1],
            'value' => $value,
            'table' => $reference[2],
            'primaryKey' => (!empty($relatedMeta['primaryKey']))
                ? $relatedMeta['primaryKey']
                : 'id',
            'cacheKey' => $metaData['class']
                . '_' . $reference[1]
                . '_' . $value
        );
    }

    /**
     * Parse the reference string.
     *
     * @param string $reference The reference string.
     *
     * @return array Parsed data.
     */
    protected function _parseReferenceString($reference)
    {
        $reference = \trim((string) $reference);

        if (\preg_match('/^\w+$/', $reference)) {
            return array('id', $reference, null);
        }

        if (\preg_match('/^(\w+)\s*\((\w+)\)$/', $reference, $matches)) {
            return array($matches[1], $matches[2], null);
        }

        $pattern = '/^(\w+)\s*\((\w+)\s*,\s*(\w+)\)$/';
        if (\preg_match($pattern, $reference, $matches)) {
            $table = array($matches[1], $matches[2], $matches[3]);
            return array('id', 'id', $table);
        }
    }

    /**
     * Get the object's meta-data.
     *
     * @return array The object's meta-data.
     */
    public function getMetaData()
    {
        return array();
    }

    /**
     * Get the object's processed and sanitized meta-data.
     *
     * @return array The object's processed and sanitized meta-data.
     */
    public function getProcessedMetaData()
    {
        if (empty($this->_metaData)) {
            $this->_metaData = $this->_processMetaData($this->getMetaData());
        }

        return $this->_metaData;
    }

    /**
     * Get the object reflection.
     *
     * @return \ReflectionClass The object reflection.
     */
    public function getReflection()
    {
        return $this->_storage->getObjectReflection($this);
    }

    public function toArray()
    {
        static $properties = null;

        if (!$properties) {
            $properties =
                function($object) { return \get_object_vars($object); };
        }

        return $properties($this);
    }

    public function import(array $data)
    {
        $data = \array_intersect_key($data, $this->toArray());

        return $this->_storage->setObjectData($this, $data);
    }

    /**
     * Set the object`s related object(s).
     *
     * @param string $name    A name of related object(s) being set.
     * @param array  $objects An object(s) to set.
     */
    public function __set($name, $objects)
    {
        $this->_relatedObjects[(string) $name] = $objects;
    }

    /**
     * A shortcut for calling getRelated method.
     *
     * @param string $name A name of related object(s) to get.
     *
     * @return mixed A related object(s).
     */
    public function __get($name)
    {
        return $this->getRelated($name);
    }

    public function getRelated($name, $forceReload = false)
    {
        $name = (string) $name;
        if (!$forceReload && isset($this->_relatedObjects[$name])) {
            return $this->_relatedObjects[$name];
        }

        $metaData = $this->getProcessedMetaData();
        if (!isset($metaData['relatedObjects'][$name])) {
            $message = "Object does not have any related object(s) '$name'.";
            throw new DataObjectException($message);
        }

        $metaData = $metaData['relatedObjects'][$name];
        $key = $metaData['cacheKey'];

        $objects = ($forceReload || !isset(self::$_cache[$key]))
            ? $this->_loadRelated($metaData)
            : self::$_cache[$key];

        $this->_relatedObjects[$name] = $objects;

        return $objects;
    }

    protected function _loadRelated(array $metaData)
    {
        $oldClass = $this->_storage->getClass();
        $oldTable = $this->_storage->getTable();
        $oldPrimaryKey = $this->_storage->getPrimaryKey();

        $this->_storage->select($metaData['class']);

        switch ($metaData['relation']) {
            case self::HAS_MANY:
                $objects = $this->_loadMany($metaData);
                break;

            case self::HAS_ONE:
            case self::BELONGS_T0:
                $objects = $this->_loadOne($metaData);
                break;

            case self::MANY_MANY:
                $objects = $this->_loadManyMany($metaData);
                break;
        }

        self::$_cache[$metaData['cacheKey']] = $objects;

        $this->_storage
             ->select($oldClass)
             ->table($oldTable)
             ->setPrimaryKey($oldPrimaryKey);

        return $objects;
    }

    protected function _loadMany(array $metaData)
    {
        $options = "`{$metaData['column']}` = ?";

        return $this->_storage->search($options, $metaData['value']);
    }

    protected function _loadOne(array $metaData)
    {
        if ($metaData['primaryKey'] === $metaData['column']) {
            return $this->_storage->get($metaData['value']);
        }

        $options = "`{$metaData['column']}` = ?";

        return \current($this->_storage->search($options, $metaData['value']));
    }

    protected function _loadManyMany(array $metaData)
    {
        $table = $metaData['table'];

        $query = 'SELECT `' . $table[2] . '`, `' . $table[1] . '` '
               . 'FROM `' . $table[0] . '` '
               . 'WHERE `' . $table[1] . '` = ?';

        $bindings = $this->_storage->query($query, $metaData['value']);

        return $this->_storage->get($bindings->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Save the object into the persistent storage.
     * All related child objects with are saved as well.
     */
    public function save()
    {
        $oldTable = $this->_storage->getTable();
        $oldPrimaryKey = $this->_storage->getPrimaryKey();

        $metaData = $this->getProcessedMetaData();

        $this->_storage
             ->table($metaData['table'])
             ->setPrimaryKey($metaData['primaryKey'])
             ->save($this);

        if (!empty($metaData['relatedObjects'])) {
            $relMeta = $metaData['relatedObjects'];

            foreach ($this->_relatedObjects as $name => $related) {
                if (self::BELONGS_T0 !== $relMeta[$name]['relation']) {
                    $column = $relMeta[$name]['column'];

                    $value = $this->getReflection()
                                  ->getProperty($metaData['primaryKey']);

                    $value->setAccessible(true);
                    $value = $value->getValue($this);
                    foreach ($related as $object) {
                        $reflection = $this->_storage
                                           ->getObjectReflection($object);

                        $property = $reflection->getProperty($column);
                        $property->setAccessible(true);
                        $property->setValue($object, $value);

                        (!$reflection->hasMethod('save'))
                            ? $this->_storage->save($object)
                            : $object->save();
                    }
                }
            }
        }

        $this->_storage->table($oldTable)->setPrimaryKey($oldPrimaryKey);
    }
}