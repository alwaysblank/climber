<?php

namespace Livy\Climber;

use Livy\Climber\Spotter\Spotter;
use \Zenodorus as Z;

/**
 * This class generates data trees for processing by Climber.
 * The idea is to make the data structure agnostic and easy to
 * move around in.
 *
 * Data trees ultimately look like this:
 * ```
 * [
 *      22 => [         // This is the unique id of the menu item
 *
 *          null,       // The parent of this item; null in this
 *                      // case as this is a top-level item.
 *
 *          [44, 55],   // The children of this menu item.
 *
 *          [...],      // Other data about this menu item. This data
 *                      // is likely relevant to Climber, but isn't
 *                      // important to Tree.
 *          'parent',   // This is the "active" key, which contains a
 *                      // value when leaf is active, or the parent/
 *                      // ancestor of an active leaf. "Active" state
 *                      // is set by Climber.
 *      ],
 *      44 => [
 *          22,         // The id of the parent (22 in this case).
 *
 *          [],         // An empty array, because this menu item has
 *                      // no children.
 *
 *          [...]       // Other data, as above.
 *
 *          'current'   // The "active" key, as above. In this case, it
 *                      // says "current" because we're currently on
 *                      // this page.
 *      ]
 * ]
 * ```
 *
 * They exist in a flat list, but provide all the information that
 * Tree needs to parse and move around them.
 *
 * Most operations that make changes to `$tree` will carry out those operations
 * on a clone of the current Tree, and then apply them to the original Tree
 * only if those operations are successful. This means that you should generally
 * be able to trust the data in `$tree`. The major caveat to this is the use of
 * `dangerouslySetLeafProp()` which is not careful or delicate and acts directly
 * on the current Tree.
 *
 * @param Spotter/[ClassName] $spotter      An instance of a class that
 *                                          extends Spotter/Spotter.
 */
class Tree implements API\TreeAPI
{
    /**
     * Normalized data returned from Spotter.
     *
     * @var array
     */
    protected $germinated;

    /**
     * Result of processing $germinated. The core data set used for everything
     * Tree does.
     *
     * @var array
     */
    protected $tree;

    /**
     * Legend for array keys.
     *
     * Internally, Tree uses positional keys for simplicity and speed, but this
     * allows us to alias them to more human-readable keys.
     *
     * @var array
     */
    protected $map = [
        'parent' => 0,
        'children' => 1,
        'data' => 2,
        'active' => 3,
    ];

    /**
     * Sanity check.
     *
     * This is set to `true` when Tree is cloned (usually for a workspace). It
     * helps prevents infinite recursion/clone generation.
     *
     * @var boolean
     */
    protected $clone = false;

    public function __construct($spotter)
    {
        $this->nursery($spotter);
    }

    public function nursery($spotter)
    {
        try {
            if (is_subclass_of($spotter, __NAMESPACE__ . '\\Spotter\\Spotter')) {
                $this->germinated = $spotter->germinate();
            } else {
                throw new \Exception("You have to give me a Spotter.", 1);
            }
        } catch (\Exception $exception) {
            $exception->getMessage();
            return false;
        }

        $this->tree = $this->plant();
    }

    /**
     * Process the seed to generate a data tree.
     *
     * This expects normalized data from a Spotter.
     *
     * @return array|null
     */
    protected function plant()
    {
        if (is_array($this->germinated)) {
            $temp = $this->germinated;
            $planted = [];
            foreach ($temp as $id => $data) {
                // Set the parent for this child
                $planted[$id][0] = $data['parent'];

                // Set this as a child on its parent
                if (null !== $data['parent'] && isset($temp[$data['parent']])) {
                    if (isset($planted[$data['parent']])
                        && isset($planted[$data['parent']][1])) {
                        array_push($planted[$data['parent']][1], $id);
                    } else {
                        $planted[$data['parent']][1] = [$id];
                    }
                }

                // Remove this: we no longer need it.
                unset($temp[$id]['parent']);

                // If children is not set, set an empty array.
                // Otherwise, assume set at leave alone.
                if (!isset($planted[$id][1])) {
                    $planted[$id][1] = [];
                }

                // Remove $data['parent']. We want leaf slot 0 to be the single
                // source of truth re: parentage.
                unset($data['parent']);

                $planted[$id][2] = $data;
            }

            return $planted;
        }

        return null;
    }

    public function getLeafPath(int $id, array $ancestors = [])
    {
        if (null === $this->getLeaf($id)
            || null === $this->getLeafContent($id, 0)) {
            // This item doesn't exist or has no ancestors.
            // Either we've reached the last step in our path, we were passed
            // a bad leaf, or this leaf has no parent.
            return array_reverse($ancestors);
        }

        array_push($ancestors, $this->getLeafContent($id, 0));

        return $this->getLeafPath($this->getLeafContent($id, 0), $ancestors);
    }

    public function getLeaf(int $id)
    {
        return isset($this->tree[$id]) ? $this->tree[$id] : null;
    }

    public function getLeafContent(int $id, $slot, $data = null)
    {
        $query = $this->query($slot);

        if (false !== $query) {
            $leaf = $this->getLeaf($id);

            if ($leaf) {
                if (2 === $query && null !== $data && isset($leaf[2][$data])) {
                    return $leaf[2][$data];
                } elseif (null === $data && isset($leaf[$query])) {
                    return $leaf[$query];
                }
            }
        }

        return null;
    }

    /**
     * Get an appropriate query for string/integer slot requests.
     *
     * This allows us to easily use aliases for numeric slot keys, and easily
     * expand that list through `$map` if necessary.
     *
     * @param string|integer $slot
     * @return integer|boolean  Returns an integer if viable, boolean `false`
     *                          otherwise.
     */
    protected function query($slot)
    {
        if (is_int($slot)) {
            return $slot;
        } elseif (isset($this->map[$slot])) {
            return (int)$this->map[$slot];
        }

        return false;
    }

    public function getLeafSiblings(int $id, bool $exclude = null)
    {
        $parent = $this->getLeafContent($id, 'parent');
        $tree = $this->tree;
        $siblings = array_filter($tree, function ($item) use ($parent) {
            return $item[0] == $parent;
        });

        if ($exclude) {
            unset($siblings[$id]);
        }

        return $siblings;
    }

    public function setLeaf(int $id, array...$actions)
    {
        if ($this->getLeaf($id) && count($actions) > 0) {
            $workspace = $this->workspace();

            $success = array_reduce(
                $actions,
                function ($carry, $current) use ($id, $workspace) {
                    // If $carry is false, we've already failed.
                    if ($carry === false) {
                        return false;
                    }

                    // If these aren't set, we can't proceed.
                    if (!isset($current[0]) || !isset($current[1])) {
                        return false;
                    }

                    // setLeafProp() will handle different prop types for us.
                    $result = $workspace->setLeafProp(
                        $id,
                        $current[0],
                        $current[1]
                    );

                    if ($result && $carry === null) {
                        // The first iteration, so set as `true` if it is.
                        return true;
                    } elseif ($result === false) {
                        // Otherwise, only change if `false`.
                        return false;
                    } else {
                        return $carry;
                    }
                },
                null
            );

            if ($success) {
                $this->tree = $workspace->grow();
                unset($workspace);
                return $this->getLeaf($id);
            }
        }

        return false;
    }

    /**
     * Get a workspace.
     *
     * This clones the Tree if called from the original; otherwise it returns
     * the current Tree. This is so that we can isolate changes until we're
     * sure they're successful—but also prevent our scripts from generating
     * hundreds of recursive clones and eating up memory.
     *
     * @return Tree
     */
    protected function workspace()
    {
        // Only clone if we're not a clone; too many clones is bad.
        return $this->clone ? $this : clone $this;
    }

    public function setLeafProp(int $id, $slot, $value)
    {
        if ($this->getLeaf($id)) {
            $query = $this->query($slot);

            if (false !== $query) {
                switch ($query) {
                    case 0: // parent
                        return $this->setParent($id, $value);
                        break;

                    case 1: // children
                        return $this->setChildren($id, $value);

                    case 2: // data
                        return $this->setData($id, $value);

                    case 3: // active
                        return $this->setActive($id, $value);

                    default: // only set known slots
                        return false;
                        break;
                }
            }
        }

        return false;
    }

    /**
     * Set a leaf parent.
     *
     * By default, this method will also modify all other leaves affected by
     * this change (i.e. the new and previous parents). To disable this
     * behavior, set `$cascade` to `false`.
     *
     * **WARNING!!**
     * Setting `$cascade` to `false` may create undesirable behavior, as
     * relationships between leaves will no longer be internally consistent.
     *
     * This is essentially a wrapper for `changeParent()` with some additional
     * logic. You should probably call this instead of `changeParent()`.
     *
     * @see Tree::changeParent()
     *
     * @param integer $id
     * @param integer $parent
     * @param boolean $cascade
     * @return array|boolean    Returns changed leaf if successful, false
     *                          otherwise.
     */
    protected function setParent(int $id, int $parent, bool $cascade = true)
    {
        if (!$cascade) {
            return $this->dangerouslySetLeafProp($parent, 0, $id);
        } elseif ($cascade) {
            return $this->changeParent($id, $parent);
        }

        return false;
    }

    /**
     * Forceably sets a leaf property.
     *
     * **Probably Don't Use This Directly!**
     *
     * If you need to set a prop, try using one of these instead:
     * - `setLeafProp()`
     * - `setParent()`
     * - `setChildren()`
     * - `setData()`
     *
     * This method should only ever be called by other methods that really know
     * what they're doing: It makes no checks beyond making sure that the leaf
     * actually exists. You can easily break or overwrite things you shouldn't
     * by calling this directly.
     *
     * @param integer $id
     * @param integer|string $slot
     * @param mixed $value
     * @return array|boolean    Returns the changed leaf if successful, boolean
     *                          `false` otherwise.
     */
    protected function dangerouslySetLeafProp(int $id, $slot, $value)
    {
        if ($this->getLeaf($id)) {
            $query = $this->query($slot);

            if (false !== $query) {
                $this->tree[$id][$query] = $value;
                return $this->getLeaf($id);
            }
        }

        return false;
    }

    /**
     * Change the parent of a leaf, and cascade those changes out.
     *
     * This sets a new parent on the leaf represented by `$child`, but it also
     * modifies the new and old parents to reflect that change. Since all leaves
     * are only aware of their direct parents and children, this change allows
     * for significant structure modifications with only minimal change to the
     * `$tree` structure.
     *
     * This method is actually the core method for both `setChildren()` and
     * `setParent()`, they just use it in slightly different ways. You should
     * probably use one of those methods instead of calling this one directly.
     *
     * @see Tree::setChildren()
     * @see Tree::setParent()
     *
     * @param integer $child
     * @param integer $newParent
     * @return array|boolean    Returns the new child leaf if successful,
     *                          boolean `false` otherwise.
     */
    protected function changeParent(int $child, int $newParent)
    {
        $workspace = $this->workspace();

        $childLeaf = $workspace->getLeaf($child);
        $newParentLeaf = $workspace->getLeaf($newParent);
        $oldParent = $childLeaf[0];

        /**
         * If $newParent is a child of $child, we don't want to create
         * a loop, so remove it from $child's children.
         */
        if (in_array($newParent, $childLeaf[1])) {
            $removedRecursiveChild = $workspace->dangerouslySetLeafProp(
                $child,
                1,
                Z\Arrays::removeByValue(
                    $workspace->getLeafContent($child, 1),
                    $newParent
                )
            );
        } else {
            $removedRecursiveChild = true;
        }

        /**
         * Remove $child from $oldParent's children, if $child has an existing
         * parent.
         */
        if (null !== $oldParent) {
            $removedFromOldParent = $workspace->dangerouslySetLeafProp(
                $oldParent,
                1,
                Z\Arrays::removeByValue(
                    $workspace->getLeafContent($child, 1),
                    $newParent
                )
            );
        } else {
            $removedFromOldParent = true;
        }

        /**
         * Set $child's new parent to $newParent.
         */
        $setNewParent = $workspace->dangerouslySetLeafProp(
            $child,
            0,
            $newParent
        );

        /**
         * Add $child to $newParent's children.
         */
        $setNewChild = $workspace->dangerouslySetLeafProp(
            $newParent,
            1,
            array_merge($newParentLeaf[1], [$child])
        );

        if ($removedRecursiveChild
            && $removedFromOldParent
            && $setNewParent
            && $setNewChild) {
            $this->tree = $workspace->grow();
            unset($workspace);
            return $this->getLeaf($child);
        }

        return false;
    }

    public function grow()
    {
        return $this->tree;
    }

    /**
     * Set leaf children.
     *
     * By default, this will also modify all leaves that are affected by this
     * change (namely, new and removed children of `$id`). To disable this
     * behavior, set `$cascade` to `false`.
     *
     * **WARNING!!**
     * Setting `$cascade` to `false` may create undesirable behavior, as
     * relationships between leaves will no longer be internally consistent.
     *
     * This is essentially a wrapper for `changeParent()` with some additional
     * logic. You should probably call this instead of `changeParent()`.
     *
     * @see Tree::changeParent()
     *
     * @param integer $id
     * @param array $children
     * @param boolean $cascade
     * @return array|bool
     */
    protected function setChildren(int $id, array $children, $cascade = true)
    {
        if (!$cascade) {
            return $this->dangerouslySetLeafProp($id, 1, $children);
        } elseif ($cascade) {
            $workspace = $this->workspace();
            $setChildren = array_reduce(
                $children,
                function ($carry, $current) use ($id, $workspace) {
                    // If $carry is false, we've already failed.
                    if ($carry === false) {
                        return false;
                    }

                    $result = $workspace->changeParent($current, $id);

                    if ($result && $carry === null) {
                        // Set `true` if first iteration and succeeded.
                        return true;
                    } elseif ($result === false) {
                        return false;
                    } else {
                        return $carry;
                    }
                },
                null
            );

            if ($setChildren) {
                $this->tree = $workspace->grow();
                unset($workspace);
                return $this->getLeaf($id);
            }
        }

        return false;
    }

    /**
     * Set some data in the 2 (data) slot on a leaf.
     *
     * Prevents user from setting the `id` value of a leaf, because ids should
     * be immutable.
     *
     * @param integer $id
     * @param array $action
     * @return mixed|boolean    Returns the value set if successful, `false` if
     *                          not.
     */
    protected function setData(int $id, array $action)
    {

        if (isset($action[0])
            && isset($action[1])
            && (is_int($action[0]) || is_string($action[0]))) {
            if ($action[0] == 'id') {
                // Don't change the id.
                return false;
            }

            if ($data = $this->getLeafContent($id, 2)) {
                $data[$action[0]] = $action[1];
                return $this->dangerouslySetLeafProp($id, 2, $data);
            }
        }

        return false;
    }

    /**
     * Set the active slot on a leaf.
     *
     * @param integer $id
     * @param string $value
     * @return array|bool
     */
    protected function setActive(int $id, string $value)
    {
        return $this->dangerouslySetLeafProp(
            $id,
            3,
            $value
        );
    }

    /**
     * We want to know if we're the original or not, so set `$clone`.
     *
     * @return void
     */
    protected function __clone()
    {
        $this->clone = true;
    }
}
