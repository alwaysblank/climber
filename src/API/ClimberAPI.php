<?php

namespace Livy\Climber\API;

use Livy\Climber\Tree;

/**
 * This defines the API for the Livy\Climber to help insure consistent behavior
 * in future versions of the software.
 * @package Livy\Climber\API
 */
abstract class ClimberAPI
{
    /**
     * Sets up the `$tree`, and adds active classes to the appropriate leaves,
     * if `$currentUrl` matches to a leaf target.
     *
     * @param Tree $tree
     * @param string $currentUrl
     */
    abstract public function __construct(Tree $tree, $currentUrl = null);

    /**
     * If `Climber` is treated as a string, print out a <ul></ul>.
     *
     * @return string
     */
    abstract public function __toString();

    /**
     * Get the value of a property.
     *
     * Don't try to get non-existent properties. If you want to modify what someone
     * gets, do it here.
     *
     * @param string $property  Name of property to get.
     * @return mixed            Returns bool `false` if property does not exist.
     */
    abstract public function __get(string $property); a

    /**
     * Set a property, if that is possible.
     *
     * Requested property is only set if its name appears
     * in `Climber::setable`.
     *
     * If this object has a method named `set[Property]`
     * (i.e. for the property `tree` it would be `setTree`)
     * then `$value` is passed to it before being
     * added.
     *
     * @param string $property      Property we want to set.
     * @param mixed $value          Value we want to set $property to.
     * @return mixed                $value if $this->$property is setable, bool `false` otherwise.
     */
    abstract public function __set(string $property, $value = null);

    /**
     * Activate a leaf.
     *
     * This sets some stuff on leaves to make them 'active' in various ways.
     * The primary purpose of this is to highlight menu items when the user is
     * on that page.
     *
     * @param integer $hint
     * @return void
     */
    abstract public function activate(int $hint);

    /**
     * Gets zero or more leaves, based on their target.
     *
     * This is primarily useful when you want to set active leaves for the
     * current page. It returns an array containing all leaves with this
     * target. This means the array can contain no leaves!
     *
     * If `$strict` is `true`, then it just does a direct string match test. If
     * `$strict` is `false`, then it tests the path, queries, and fragments
     * against one another with `parse_url`.
     *
     * @param string $target
     * @param boolean $strict
     * @return array
     */
    abstract public function getLeafByTarget(string $target, bool $strict = true);

    /**
     * Set $this->topAttr.
     *
     * @see \Livy\Climber\Climber::appendArrayProp()
     *
     * @param $value
     * @param $property
     * @return array
     */
    abstract public function setTopAttr($value, $property);

    /**
     * Set $this->menuAttr.
     *
     * @see \Livy\Climber\Climber::appendArrayProp()
     *
     * @param $value
     * @param $property
     * @return array
     */
    abstract public function setMenuAttr($value, $property);

    /**
     * Set $this->itemAttr.
     *
     * @see \Livy\Climber\Climber::appendArrayProp()
     *
     * @param $value
     * @param $property
     * @return array
     */
    abstract public function setItemAttr($value, $property);

    /**
     * Set $this->linkAttr.
     *
     * @see \Livy\Climber\Climber::appendArrayProp()
     *
     * @param $value
     * @param $property
     * @return array
     */
    abstract public function setLinkAttr($value, $property);

    /**
     * Hook up a callback to a processing step.
     *
     * Ultimately callbacks are run through `call_user_func()`, so you
     * can pass anything to $callback that would be accepted as an
     * argument for `call_user_func()`.
     *
     * @param string $location
     * @param mixed $callback
     * @param int|boolean $order
     * @return void
     */
    abstract public function hook(string $location, $callback, $order = false);

    /**
     * Return (or optionally echo) the full HTML for the menu.
     *
     * @param boolean $echo
     * @return string
     */
    abstract public function element($echo = false);
}
