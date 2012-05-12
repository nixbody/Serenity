<?php

namespace Serenity\Application;

/**
 * Persistent data container which holds the stored data
 * during an application session.
 *
 * @category Serenity
 * @package  Application
 */
class Session extends \ArrayObject
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!\session_id()) {
            \session_name('sid');
            \session_start();
        }

        parent::__construct($_SESSION, \ArrayObject::ARRAY_AS_PROPS);
        \register_shutdown_function(array($this, 'commit'));
    }

    /**
     * Commit the changes.
     * Overwrite all session data with the data within the container.
     */
    public function commit()
    {
        \session_unset();
        foreach ($this as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
}
