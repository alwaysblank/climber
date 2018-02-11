<?php

namespace Livy\Climber\API;

/**
 * This defines the API for the Livy\Tree to help insure consistent behavior
 * in future versions of the software.
 * @package Livy\Climber\API
 */
interface TreeAPI
{
    /**
     * When a Tree is created, this processes the input from a Spotter to
     * generated a prepped, trustable $tree.
     *
     * @param [type] $spotter
     * @throws \Exception
     */
    public function __construct($spotter);

    /**
     * Plants the tree.
     *
     * This is used to make sure the data we're going to use came from an actual
     * Spotter, and to complain if it didn't.
     *
     * It's a public function so that we can pass a new Spotter and generate
     * a new tree after this Tree has been instantiated. That's not really
     * recommended, though.
     *
     * @param object $spotter An object in a class that extends
     *                          Spotter\Spotter.
     * @return void
     * @throws \Exception
     */
    public function nursery($spotter);

    /**
     * Publicly return the tree, as it exists.
     *
     * In many ways, the core function of this class.
     *
     * @return array
     */
    public function grow();

    /**
     * Get a leaf by its id.
     *
     * @param integer $id
     * @return array|null
     */
    public function getLeaf(int $id);

    /**
     * Get data from within a leaf.
     *
     * The point of this is to avoid having to do ugly stuff like
     * `$this->getLeaf(2)[1]['order']`.
     *
     * Pass $data to get something from the 2 slot (data) on a leaf. Method will
     * return `null` if you pass a value to data other than `null` when
     * accessing any slot other than 2.
     *
     * @param integer $id
     * @param int|string $slot
     * @param int|string $data
     * @return mixed
     */
    public function getLeafContent(int $id, $slot, $data = null);

    /**
     * Find the ancestors of a particular leaf.
     *
     * @param integer $id
     * @param array $ancestors
     * @return array
     */
    public function getLeafPath(int $id, array $ancestors = []);

    /**
     * Find the siblings of a particular leaf.
     *
     * @param integer $id
     * @param boolean $exclude If true, exclude queried leaf from return.
     * @return array
     */
    public function getLeafSiblings(int $id, bool $exclude = null);

    /**
     * Set a leaf prop, safely.
     *
     * This method will determine which slot you are targeting and act
     * accordingly. For the actual behavior of setting various slots, see
     * the methods that deal with them.
     *
     * @see Tree::setParent()           Set a parent.
     * @see Tree::setChildren()         Set children.
     * @see Tree::setData()             Set data.
     * @see Tree::setActive()           Set active state.
     *
     *
     * @param integer $id
     * @param integer|string $slot
     * @param mixed $value
     * @return mixed|boolean    Returns the value set if successful, `false` if
     *                          not.
     */
    public function setLeafProp(int $id, $slot, $value);

    /**
     * Set many values on a leaf.
     *
     * Pass as many arrays as you like to the method; it will apply them all in
     * order. The template for an array is:
     * [<slot>, <value>]             // top-level slots
     * [<slot>, [<key>, <value>]]      // values for slot 2 (data)
     * So if you wanted to change the target to `google.com` and the parent to
     * `2`, that call would look like this:
     * ```
     *  $Tree->setLeaf(
     *      55,
     *      [0, 2],
     *      ['data', ['target', 'https://google.com']]
     *  );
     * ```
     * The method will return the changed leaf if it is successful. If it
     * encounters any problems, it will instead return `false`. If any of the
     * `$actions` fail, all actions are considered to have failed.
     *
     * This method operates on a clone of the current Tree, so changes are only
     * applied if all actions are successful.
     *
     * @param integer $id
     * @param array[] ...$actions
     * @return array|boolean     Returns the new leaf if successful, `false` if
     *                           not.
     */
    public function setLeaf(int $id, array...$actions);
}
