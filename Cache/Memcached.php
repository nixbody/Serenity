<?php

namespace Serenity\Cache;

/**
 * Cache adapter which provides an interface to memcached which is a
 * high performance caching daemon.
 *
 * @category Serenity
 * @package  Cache
 */
class Memcached
{
    /**
     * Indicates the end of a data stream.
     */
    const END = "END\r\n";

    /**
     * A list of servers (running memcached daemon) which connect to.
     *
     * @var array
     */
    private $servers = array();

    /**
     * The connection handle.
     *
     * @var resource
     */
    private $handle = null;

    /**
     * Write operations buffer.
     *
     * @var array
     */
    private $writeBuffer = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        \register_shutdown_function(array($this, 'commit'));
    }

    /**
     * Add a server (running memcached daemon) which connect to.
     *
     * @param string $host Host address.
     * @param int    $port A port on which memcached daemon is listening.
     *
     * @return Memcached Self instance.
     */
    public function addServer($host, $port)
    {
        $this->servers[] = array(
            'host' => (string) $host,
            'port' => (int) $port
        );

        return $this;
    }

    /**
     * Store the given value under the specified key.
     *
     * @param string $key    The key under which to store the given value.
     * @param mixed  $value  The value to store.
     * @param int    $flags  Flags which to store together with the given value.
     * @param int    $expire Unix timestamp (relative or absolute) when the item
     *                       will expire and no longer be available.
     *
     * @return Memcached Self instance.
     */
    public function set($key, $value, $flags = 0, $expire = 0)
    {
        return $this->_store('set', $key, $value, $flags, $expire);
    }

    /**
     * Store the given value under the specified key if and only if it is not
     * already stored.
     *
     * @param string $key    The key under which to store the given value.
     * @param mixed  $value  The value to store.
     * @param int    $flags  Flags which to store together with the given value.
     * @param int    $expire Unix timestamp (relative or absolute) when the item
     *                       will expire and no longer be available.
     *
     * @return Memcached Self instance.
     */
    public function add($key, $value, $flags = 0, $expire = 0)
    {
        $key = (string) $key;
        foreach ($this->writeBuffer as $data) {
            $op = $data['operation'];
            if ($key === $data['key'] && ('set' === $op || 'add' === $op)) {
                return $this;
            }
        }

        return $this->_store('add', $key, $value, $flags, $expire);
    }

    /**
     * Store the given value under the specified key if and only if it is
     * already stored.
     *
     * @param string $key    The key under which to store the given value.
     * @param mixed  $value  The value to store.
     * @param int    $flags  Flags which to store together with the given value.
     * @param int    $expire Unix timestamp (relative or absolute) when the item
     *                       will expire and no longer be available.
     *
     * @return Memcached Self instance.
     */
    public function replace($key, $value, $flags = 0, $expire = 0)
    {
        return $this->_store('replace', $key, $value, $flags, $expire);
    }

    /**
     * Append the given value to the value stored under specified key if and
     * only if the given key already exists.
     *
     * @param string $key    The key under which to store the given value.
     * @param mixed  $value  The value to store.
     * @param int    $expire Unix timestamp (relative or absolute) when the item
     *                       will expire and no longer be available.
     *
     * @return Memcached Self instance.
     */
    public function append($key, $value, $expire = 0)
    {
        return $this->_store('append', $key, $value, 0, $expire);
    }

    /**
     * Preppend the given value to the value stored under specified key if and
     * only if the given key already exists.
     *
     * @param string $key    The key under which to store the given value.
     * @param mixed  $value  The value to store.
     * @param int    $expire Unix timestamp (relative or absolute) when the item
     *                       will expire and no longer be available.
     *
     * @return Memcached Self instance.
     */
    public function prepend($key, $value, $expire = 0)
    {
        return $this->_store('prepend', $key, $value, 0, $expire);
    }

    /**
     * Get a value(s) stored under the given key(s).
     *
     * @param string|array $keys  A list of keys or the key itself under which
     *                            the requested value(s) is (are) stored.
     * @param int|array    $flags A list of flags or the flag itself stored
     *                            together with the requested value(s).
     *
     * @return mixed Value(s) stored under the given key(s).
     */
    public function get($keys, &$flags = null)
    {
        $isList = \is_array($keys);
        $keys = (array) $keys;
        $result = array();

        $buffer = \array_reverse($this->writeBuffer);
        foreach ($keys as $index => $key) {
            foreach ($buffer as $data) {
                if ($key === $data['key']) {
                    if ('set' === $data['operation']) {
                        $result[$key] = \unserialize($data['value']);
                        unset($keys[$index]);
                    } else {
                        $this->commit();
                        break 2;
                    }
                    break;
                }
            }
        }

        if (!empty($keys)) {
            $result += $this->_retrieve($keys, $flags);
        }

        if (!$isList) {
            $flags = $flags[0];
            return \current($result);
        }

        return $result;
    }

    /**
     * Delete a value stored under the given key.
     *
     * @param string $key A key under which the value should be stored.
     */
    public function delete($key)
    {
        $key = (string) $key;

        \fprintf($this->_getHandle(), "delete %s noreply\r\n", $key);
		foreach ($this->writeBuffer as $index => $data) {
            if ($key === $data['key']) {
                unset($this->writeBuffer[$index]);
            }
        }

        return $this;
    }

    /**
     * Instantly invalidate all stored items.
     *
     * @return Memcached Self instance.
     */
    public function flush()
    {
        \fwrite($this->_getHandle(), "flush_all noreply\r\n");
        $this->writeBuffer = array();

        return $this;
    }

    /**
     * Create a data stream from the contents of write operations buffer and
     * send it to the memcached server.
     *
     * This method is automatically called at the end of script execution.
     *
     * @return Memcached Self instance.
     */
    public function commit()
    {
        if (empty($this->writeBuffer)) {
            return $this;
        }

        $stream = '';
        foreach ($this->writeBuffer as $data) {
            $value = \base64_encode($data['value']);
            $length = \strlen($value);

            $command = "%s %s %d %d %d noreply\r\n%s\r\n";
            $stream .= \sprintf(
                $command,
                $data['operation'],
                $data['key'],
                $data['flags'],
                $data['expire'],
                $length,
                $value
            );
        }

        $handle = $this->_getHandle();
        \fwrite($handle, $stream);
        $this->writeBuffer = array();

        return $this;
    }

    protected function _store($operation, $key, $value, $flags, $expire)
    {
        $this->writeBuffer[(string) $key] = array(
            'operation' => (string) $operation,
            'key' => (string) $key,
            'value' => \serialize($value),
            'flags' => (int) $flags,
            'expire' => (int) $expire
        );

        return $this;
    }

    protected function _retrieve(array $keys, &$flags)
    {
        $result = array();
        $handle = $this->_getHandle();
        \fprintf($handle, "get %s\r\n", \implode(' ', $keys));

        while (self::END !== ($row = \fgets($handle))) {
            list(,$key, $flag) = \explode(' ', $row);
            $result[$key] = \unserialize(\base64_decode(\fgets($handle)));
            $flags[] = (int) $flag;
        }

        return $result;
    }

    /**
     * Randomly pick a server which connect to.
     *
     * @return array Server address and port.
     */
    protected function _pickServer()
    {
        return $this->servers[\array_rand($this->servers)];
    }

    /**
     * Get the connection handle.
     *
     * @return resource The connection handle.
     */
    protected function _getHandle()
    {
        if (null === $this->handle) {
            $server = $this->_pickServer();
            $this->handle =
                \fsockopen('tcp://' . $server['host'], $server['port']);
        }

        return $this->handle;
    }
}
