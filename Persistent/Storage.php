<?php

namespace Serenity\Persistent;

/**
 * Provides access to a persistent storage (database) and basic ORM
 * functionalities. The Storage object uses PDO for access to a database so it
 * supports every SQL database system supported by PDO.
 *
 * @category Serenity
 * @package  Persistent
 */
class Storage
{
    /**
     * A database adapter.
     *
     * @var \PDO
     */
    private $db;

    /**
     * A dependency injector for an objects created by the storage.
     *
     * @var callable|null
     */
    private $dependencyInjector = null;

    /**
     * The database query log.
     *
     * @var array
     */
    private $log = array();

    /**
     * An object classes namespace.
     *
     * @var string
     */
    protected $namespace = '';

    /**
     * Name of a class which instance to return.
     *
     * @var string
     */
    protected $class = '\stdClass';

    /**
     * Name of a database table from which select the records.
     *
     * @var string
     */
    protected $table;

    /**
     * Name of a table column which is the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * When some object is loaded or saved all it's data are stored in this
     * array and next time when object is saved only changed data are actually
     * sent to the database.
     *
     * @var array
     */
    private $recordCache = array();

    /**
     * Every loaded or saved object is stored in this cache.
     *
     * @var array
     */
    private $objectCache = array();

    /**
     * Constructor.
     *
     * @param \PDO $db A database adapter.
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Set a dependency injector for an objects created by the storage.
     *
     * @param callable $dependencyInjector A dependency injecting callback.
     *
     * @return Storage Self instance.
     */
    public function setDepenecyInjector($dependencyInjector)
    {
        $this->dependencyInjector = $dependencyInjector;

        return $this;
    }

    /**
     * Set an object classes namespace.
     *
     * @param string $namespace An object classes namespace.
     *
     * @return Storage Self instance.
     */
    public function setNamespace($namespace)
    {
        $this->namespace = (string) $namespace;

        return $this;
    }

    /**
     * Get an object classes namespace.
     *
     * @return string An object classes namespace.
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Set a name of a class which instances to return.
     *
     * @param string $class Name of a class which instances to return.
     *
     * @return Storage Self instance.
     */
    public function select($class)
    {
        $this->class = (string) $class;

        $object = $this->create($this->class);
        if (\method_exists($object, 'getMetaData')) {
            $metaData = (array) $object->getMetaData();

            if (!empty($metaData['table'])) {
                $this->table($metaData['table']);
            }

            if (!empty($metaData['primaryKey'])) {
                $this->setPrimaryKey($metaData['primaryKey']);
            }
        }

        return $this;
    }

    /**
     * Get a name of a class which instances to return.
     *
     * @return string A name of a class which instances to return.
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set a name of a table from which to select the records.
     *
     * @param string $table Name of a table from which to select the records.
     *
     * @return Storage Self instance.
     */
    public function table($table)
    {
        $this->table = (string) $table;

        return $this;
    }

    /**
     * Get a name of a table from which to select the records.
     *
     * @return string A name of a table from which to select the records.
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set a name of a table column which is the primary key.
     *
     * @param string $primaryKey Name of a table column which is
     *                           the primary key.
     *
     * @return Storage Self instance.
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = (string) $primaryKey;

        return $this;
    }

    /**
     * Get a name of a table column which is the primary key.
     *
     * @return string A name of a table column which is the primary key.
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Begin transaction.
     *
     * @return Storage Self instance.
     */
    public function beginTransaction()
    {
        $this->db->beginTransaction();

        return $this;
    }

    /**
     * Commit transaction.
     *
     * @return Storage Self instance.
     */
    public function commit()
    {
        $this->db->commit();

        return $this;
    }

    /**
     * Create new object.
     *
     * @param string $class Object class.
     * @param array  $data  Object data.
     *
     * @return mixed Newly created object.
     */
    public function create($class, array $data = array())
    {
        $class = (string) $class;
        $class = ($class[0] != '\\')
            ? "$this->namespace\\$class"
            : \substr($class, 1);

        $object = new $class($this);
        if (null !== $this->dependencyInjector) {
            \call_user_func($this->dependencyInjector, $object);
        }

        if (\method_exists($object, 'init')) {
            $object->init();
        }

        return (!empty($data))
            ? $this->importObjectData($object, $data)
            : $object;
    }

    /**
     * Get an object(s) from the storage by primary key.
     *
     * @param mixed $pkValues Value or array of values of the primary key.
     *
     * @return mixed List of requested objects or the object itself.
     */
    public function get($pkValues)
    {
        $isList = \is_array($pkValues);
        if (empty($pkValues)) {
            return ($isList) ? new \ArrayObject() : null;
        }

        if ($isList) {
            $objects = \array_flip($pkValues);
            foreach ($pkValues as $pkValue) {
                $objectId = $this->class . '_' . $pkValue;
                if (isset($this->objectCache[$objectId])) {
                    $objects[$objectId] = $this->objectCache[$objectId];
                }
            }

            $objects = \array_filter($objects, 'is_object');
            if (\count($pkValues) === \count($objects)) {
                return $objects;
            }

            $condition = "`$this->primaryKey` IN (?)";
        } else {
            $objects = array();
            $objectId = $this->class . '_' . $pkValues;
            if (isset($this->objectCache[$objectId])) {
                return $this->objectCache[$objectId];
            }

            $condition = "`$this->primaryKey` = ?";
        }

        $query = "SELECT * FROM `$this->table` WHERE $condition";
        $statement = $this->query($query, $pkValues);
        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $object = $this->create($this->class, $record);

            $recordId = $this->table . '_' . $record[$this->primaryKey];
            $objectId = $this->class . '_' . $recordId;
            $this->recordCache[$recordId] = $record;
            $this->objectCache[$objectId] = $object;

            $objects[$record[$this->primaryKey]] = $object;
        }

        return ($isList)
            ? new \ArrayObject($objects)
            : \current($objects) ?: null;
    }

    /**
     * Search and get an objects from the storage.
     *
     * @param string $options An SQL options.
     *
     * @return \ArrayObject List of found objects (could be empty).
     */
    public function search($options = '')
    {
        $options = \trim((string) $options);
        $pattern = '/^(?:GROUP BY|HAVING|ORDER BY|LIMIT)/i';

        if ($options && !\preg_match($pattern, $options)) {
            $options = 'WHERE ' . $options;
        }

        $args = \func_get_args();
        $args[0] =
            "SELECT `$this->primaryKey` FROM `$this->table` $options";
        $statement = \call_user_func_array(array($this, 'query'), $args);

        return $this->get($statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Search and get an objects from the storage.
     *
     * @param string $options An SQL options.
     * @param array  $params  A list of parameters.
     *
     * @return \ArrayObject List of found objects (could be empty).
     */
    public function vSearch($options = '', array $params = array())
    {
        \array_unshift($params, $options);

        return \call_user_func_array(array($this, 'search'), $params);
    }

    /**
     * Count records in the storage acording to specified options.
     *
     * @param string $options An SQL options.
     *
     * @return int A number of found records.
     */
    public function count($options = '1')
    {
        $args = \func_get_args();
        $args[0] = "SELECT COUNT(*) FROM `$this->table` WHERE $options";

        $statement = \call_user_func_array(array($this, 'query'), $args);

        return (int) $statement->fetchColumn();
    }

    /**
     * Execute an SQL query and get the result as an array of objects.
     *
     * @param string $query     An SQL query.
     * @param mixed  $query,... Arguments to be bound into the statement.
     *
     * @return \ArrayObject List of requested objects (could be empty).
     */
    public function request($query)
    {
        $args = \func_get_args();
        $statement = \call_user_func_array(array($this, 'query'), $args);

        $objects = new \ArrayObject();
        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $objects->append($this->create($this->class, $data));
        }

        return $objects;
    }

    /**
     * Execute an SQL query and get the result as an array of objects.
     *
     * @param string $query An SQL query.
     * @param array  $args  A list of arguments to be bound into the statement.
     *
     * @return \ArrayObject List of requested objects (could be empty).
     */
    public function vRequest($query, array $args = array())
    {
        \array_unshift($args, $query);

        return \call_user_func_array(array($this, 'request'), $args);
    }

    /**
     * Save an object to the storage.
     *
     * @param mixed $object An object to be saved.
     *
     * @return Storage Self instance.
     */
    public function save($objects)
    {
        if (!\is_array($objects)) {
            $objects = array($objects);
        }

        foreach ($objects as $object) {
            $data = $this->exportObjectData($object);
            $recordId = $this->getRecordId($data);

            if (null !== $recordId) {
                $newData = (isset($this->recordCache[$recordId]))
                    ? \array_diff_assoc($data, $this->recordCache[$recordId])
                    : $data;

                if (!empty($newData)) {
                    $set = \implode('` = ?, `', \array_keys($newData));
                    $query = "UPDATE `$this->table` SET `$set` = ?"
                           . " WHERE `$this->primaryKey` = ?";

                    $newData = \array_values($newData);
                    $newData[] = $data[$this->primaryKey];

                    $this->vQuery($query, $newData);
                }
            } else {
                $columns = \implode('`, `', \array_keys($data));
                $query = "INSERT INTO `$this->table` (`$columns`) VALUES (?)";

                $this->query($query, $data);

                if (empty($data[$this->primaryKey])) {
                    $data[$this->primaryKey] = $this->db->lastInsertId();
                    $this->importObjectData($object, array(
                        $this->primaryKey => $data[$this->primaryKey]
                    ));
                }

                $recordId = $this->table . '_' . $data[$this->primaryKey];
            }

            $this->recordCache[$recordId] = $data;
            $this->objectCache[\get_class($object) . "_$recordId"] = $object;
        }

        return $this;
    }

    /**
     * Check if a record exists in the storage and if so get it's unique ID.
     *
     * @param array $data The record data.
     *
     * @return string|null The record unique ID or null if the record does not
     *                     exist in the storage.
     */
    public function getRecordId(array $data)
    {
        if (empty($data[$this->primaryKey])) {
            return null;
        }

        $pkValue = $data[$this->primaryKey];
        $recordId = $this->table . '_' . $pkValue;

        if (!isset($this->recordCache[$recordId])) {
            $query = "SELECT COUNT(*) FROM `$this->table`"
                   . " WHERE `$this->primaryKey` = ?";

            if (!$this->query($query, $pkValue)->fetchColumn()) {
                return null;
            }
        }

        return $recordId;
    }

    /**
     * Try to determine specified object property type from doc comment and
     * set it's value. All native PHP data types are supported plus DateTime.
     *
     * @param object              $object   An object which property set.
     * @param \ReflectionProperty $property The object property reflection.
     * @param mixed               $value    A value to be set.
     */
    protected function _setPropertyValue($object, \ReflectionProperty $property,
        $value)
    {
        $property->setAccessible(true);
        \preg_match('/@var\s+([^|\s]+)/', $property->getDocComment(), $type);

        if (isset($type[1])) {
            $type = \ltrim($type[1], '\\');
            if (null === $value && false !== \stripos($type, 'null')) {
                $value = null;
            } elseif ('DateTime' === $type) {
                $value = new \DateTime($value);
            } elseif ('array' === $type) {
                $value = @\unserialize($value);
                if (false === $value) {
                    $value = array();
                }
            } elseif (!@\settype($value, $type)) {
                $value = @\unserialize($value);
                if (false === $value) {
                    $value = new $type();
                }
            }
        }

        $property->setValue($object, $value);
    }

    public function getObjectReflection($object)
    {
        static $reflections = array();

        $class = \get_class($object);
        if (isset($reflections[$class])) {
            return $reflections[$class];
        }

        return $reflections[$class] = new \ReflectionClass($class);
    }

    /**
     * Set an object data.
     *
     * @param mixed $object
     * @param array $data
     *
     * @return Storage Self instance.
     */
    public function importObjectData($object, array $data)
    {
        if ($object instanceof \stdClass) {
            foreach ($data as $key => $value) {
                $object->$key = $value;
            }
        } elseif (\method_exists($object, 'import')) {
            $object->import($data);
        } else {
            $reflection = $this->getObjectReflection($object);
            foreach ($data as $key => $value) {
                if ($reflection->hasProperty($key)) {
                    $this->_setPropertyValue(
                        $object,
                        $reflection->getProperty($key),
                        $value
                    );
                }
            }
        }

        return $object;
    }

    /**
     * Get an object data as array. The keys are the property names.
     * If specified object implements public method export then the result of
     * this method will be converted to an array and returned.
     *
     * @param mixed $object An object which data get.
     *
     * @return array An object data.
     */
    public function exportObjectData($object)
    {
        if ($object instanceof \stdClass) {
            $data = (array) $object;
        } elseif (\method_exists($object, 'export')) {
            $data = (array) $object->export();
        } else {
            $data = array();
            $properties = $this->getObjectReflection($object)->getProperties();
            foreach ($properties as $property) {
                $key = $property->getName();
                if ('_' !== $key[0]) {
                    $property->setAccessible(true);
                    $data[$key] = $property->getValue($object);
                }
            }
        }

        foreach ($data as &$value) {
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (!\is_scalar($value)) {
                $value = \serialize($value);
            }
        }

        return $data;
    }

    /**
     * Execute an SQL query.
     *
     * @param string $query     An SQL query.
     * @param mixed  $query,... Arguments to be bound into the statement.
     *
     * @return \PDOStatement The result of a query.
     */
    public function query($query)
    {
        $query = (string) $query;
        $parts = \explode('?', $query);
        $args = \func_get_args();
        unset($args[0]);

        if (\count($args) != \count($parts) - 1) {
            $message =
                'Number of arguments does not match number of placeholders.';
            throw new StorageException($message);
        }

        $params = array();
        foreach (\array_values($args) as $i => $arg) {
            if (\is_array($arg)) {
                $parts[$i] .= \implode(', ', \array_fill(0, \count($arg), '?'));
                $params = \array_merge($params, \array_values($arg));
            } else {
                $parts[$i] .= '?';
                $params[] = $arg;
            }
        }

        $query = \implode($parts);
        $time = \microtime(true);
        $statement = $this->db->prepare($query);
        $statement->execute($params);

        if (null !== $this->log) {
            $this->_logQuery($query, \microtime(true) - $time, $params);
        }

        return $statement;
    }

    /**
     * Execute an SQL query.
     *
     * @param string $query An SQL query.
     * @param array  $args  A list of arguments to be bound into the statement.
     *
     * @return \PDOStatement The result of a query.
     */
    public function vQuery($query, array $args = array())
    {
        \array_unshift($args, $query);

        return \call_user_func_array(array($this, 'query'), $args);
    }

    /**
     * Store a query into the query log.
     *
     * @param string $query  A query to store.
     * @param float  $time   How long it took to execute the query.
     * @param array  $params An array of parameters to replace query
     *                       placeholders with.
     */
    protected function _logQuery($query, $time, array $params = array())
    {
        foreach ($params as $param) {
            $query = \preg_replace('/\?/', $this->db->quote($param), $query, 1);
        }

        $this->log[] = array($query, $time);
    }

    /**
     * Get the query log.
     *
     * @return array The query log.
     */
    public function getLog()
    {
        return $this->log;
    }

    public function getLogAsString()
    {
        $string = '';
        foreach ($this->log as $query) {
            $query[1] *= 1000;
            $string .= "{$query[0]} {$query[1]} ms\n";
        }

        return $string;
    }
}
