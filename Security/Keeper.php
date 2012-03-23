<?php

namespace Serenity\Security;
/**
 * Keeper purpose is to manage a permissions and to controll the access to
 * a resources and their actions.
 *
 * @category Serenity
 * @package  Security
 */
class Keeper
{
    /**
     * @var array A list of a roles and their permissions.
     */
    private $permissions = array();

    /**
     * Add an permission for specified resource (and actions) to specified
     * user role.
     *
     * @param string $role User role.
     * @param string $resource A resource.
     * @param array  $actions A list of an actions.
     *
     * @return Keeper Self instance.
     */
    public function addPermission($role, $resource, array $actions = null)
    {
        $role = (string) $role;
        $resource = (string) $resource;
        if (!isset($this->permissions[$role][$resource])) {
            $this->permissions[$role][$resource] = array();
        }
        $this->permissions[$role][$resource] += \array_flip((array) $actions);

        return $this;
    }

    /**
     * Set a list of a roles and their permissions.
     *
     * @param array $permissions A list of a roles and their permissions.
     *
     * @return Keeper Self instance.
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = array();
        foreach ($permissions as $role => $resources) {
            foreach ($resources as $resource => $actions) {
                $this->addPermission($role, $resource, $actions);
            }
        }

        return $this;
    }

    /**
     * Remove an permission for specified resource or actions from specified
     * user role.
     *
     * @param string     $role User role.
     * @param string     $resource A resource.
     * @param array|null $actions List of an actions.
     *
     * @return Keeper Self instance.
     */
    public function removePermission($role, $resource, array $actions = null)
    {
        $role = (string) $role;
        $resource = (string) $resource;

        if ($action === null) {
            unset($this->permissions[$role][$resource]);
        } elseif (isset($this->permissions[$role][$resource])) {
            foreach ($actions as $action => $val) {
                unset($this->permissions[$role][$resource][$action]);
            }
        }

        return $this;
    }

    /**
     * Check if specified user role has an permission for specified resource
     * or resource and action.
     *
     * @param string      $role     User role.
     * @param string      $resource A resource.
     * @param string|null $action   An action.
     *
     * @return bool Has a permission?
     */
    public function hasPermission($role, $resource, $action = null)
    {
        $role = (string) $role;
        $resource = (string) $resource;

        if ($action === null) {
            return isset($this->permissions[$role][$resource]);
        } else {
            $action = (string) $action;
            return isset($this->permissions[$role][$resource][$action]);
        }
    }
}
