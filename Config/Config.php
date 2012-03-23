<?php

namespace Serenity\Config;

/**
 * Object that represents the configuration file.
 *
 * @category Serenity
 * @package  Config
 */
class Config
{
    /**
     * @var string|null Absolute path to the configuration data cache directory.
     */
    private static $cacheDir = null;

    /**
     * @var string Absolute path to the configuration file.
     */
    private $filePath;

    /**
     * @var mixed Content parser. Must be a valid callback or null.
     */
    private $parser;

    /**
     * @var mixed Configuration data.
     */
    private $data = null;

    /**
     * Constructor.
     *
     * @param string $filePath Path to the configuration file.
     * @param mixed  $parser   Content parser. Must be a valid callback or null.
     *                         If null then the default one is used.
     *
     * @throws ConfigException If file does not exist.
     */
    public function __construct($filePath, $parser = null)
    {
        $this->filePath = \realpath((string) $filePath);
        if ($this->filePath === false) {
            $message = "Configuration file '$filePath' not found.";
            throw new ConfigException($message);
        }

        if ($parser !== null && !\is_callable($parser)) {
            $message = 'Specified content parser is not a valid callback.';
            throw new ConfigException($message);
        }

        $this->parser = $parser;
    }

    /**
     * Set a path to the configuration data cache directory.
     *
     * @param string $cacheDir A path to the configuration data cache directory.
     */
    public static function setCacheDir($cacheDir)
    {
        self::$cacheDir = \realpath((string) $cacheDir);
        if (self::$cacheDir === false) {
            $message = "Directory '$cacheDir' not found.";
            throw new ConfigException($message);
        }
    }

    /**
     * Load the configuration file.
     *
     * @return \stdClass Configuration data.
     *
     * @throws ConfigException If configuration data are not valid.
     */
    protected function _loadData()
    {
        $cacheName = \crc32($this->filePath);
        $cacheFile = \realpath(self::$cacheDir . "/$cacheName.cache");
        if ($cacheFile
            && \filemtime($cacheFile) >= \filemtime($this->filePath)) {
            return \unserialize(\file_get_contents($cacheFile));
        }

        $data = ($this->parser !== null)
            ? $this->parser(\file_get_contents($this->filePath))
            : include $this->filePath;

        if ($data === false) {
            $message = 'Configuration data are not valid.';
            throw new ConfigException($message);
        } else {
            $data = $this->_toArrayObject($data);
        }

        if (self::$cacheDir !== null) {
            $cacheFile =
                self::$cacheDir . DIRECTORY_SEPARATOR . "$cacheName.cache";
            @\file_put_contents($cacheFile, \serialize($data));
        }

        return $data;
    }

    /**
     * Convert the given data to an ArrayObject recursively.
     *
     * @param array|object $data The data to be converted.
     *
     * @return \ArrayObject Converted data.
     */
    protected function _toArrayObject($data)
    {
        foreach ($data as &$value) {
            if (\is_array($value) || $value instanceof \stdClass) {
                $value = $this->_toArrayObject($value);
            }
        }

        return new \ArrayObject($data, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Check if specified key exists.
     *
     * @param string $key A key.
     *
     * @return bool True if specified key exists, false otherwise.
     */
    public function __isset($key)
    {
        return property_exists($this->getData(), (string) $key);
    }

    /**
     * Get value stored under specified key.
     *
     * @param string $key Key.
     *
     * @return mixed Value.
     *
     * @throws ConfigException If specified key does not exist.
     */
    public function __get($key)
    {
        $data = $this->getData();
        $key = (string) $key;

        if (!\property_exists($data, $key)) {
            $message = "Key '$key' does not exist.";
            throw new ConfigException($message);
        }

        return $data->$key;
    }

    /**
     * Set value under specified key.
     *
     * @param string $key   Key.
     * @param mixed  $value Value.
     */
    public function __set($key, $value)
    {
        $data = $this->getData();
        $key = (string) $key;

        $data->$key = $value;
    }

    /**
     * Get all data stored in the configuration file.
     *
     * @return \stdClass All data stored in the configuration file.
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = $this->_loadData();
        }

        return $this->data;
    }

    /**
     * Get all data stored in the configuration file as array.
     * The method is not recursive.
     *
     * @return array All data stored in the configuration file as array.
     */
    public function getArrayCopy()
    {
        return (array) $this->getData();
    }
}
