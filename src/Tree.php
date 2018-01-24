<?php

namespace Livy\Climber;

use Spotter\Spotter;
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
 * @param Spotter/[ClassName] $spotter      An instance of a class that
 *                                          extends Spotter/Spotter
 */
class Tree
{
    // Normalized data returned from Spotter.
    protected $germinated;

    // Processed data.
    protected $tree;
    
    // Field name map.
    protected $map = [
        'parent' => 0,
        'children' => 1,
        'data' => 2,
        'active' => 3,
    ];

    // Sanity check.
    protected $clone = false;

    public function __construct($spotter)
    {
        $this->nursery($spotter);
    }

    /**
     * We want to know if we're the original or not.
     *
     * @return void
     */
    protected function __clone()
    {
        $this->clone = true;
    }

    /**
     * Get an appropriate query for string/integer slot requests.
     *
     * @param string|integer $slot
     * @return integer|boolean  Returns an integer if viable, boolean `false`
     *                          otherwise.
     */
    protected function query($slot) {
        if (is_int($slot)) {
            return $slot;
        } elseif (isset($this->map[$slot])) {
            return (int) $this->map[$slot];
        }

        return false;
    }

    /**
     * Get a workspace.
     * 
     * This clones the Tree if called from the original; otherwise it returns
     * the current Tree. This is so that we can isolate changes until we're
     * sure they're successful—bit also prevent our scripts from generating
     * hundreds of recursive clones and eating up memory.
     *
     * @return Tree
     */
    protected function workspace()
    {
        // Only clone if we're not a clone; too many clones is bad.
        return $this->clone ? $this : clone $this;
    }

    /**
     * Process the seed to generate a data tree.
     *
     * @return void
     */
    protected function plant()
    {
        $temp = $this->germinated;
        $planted = [];
        foreach ($temp as $id => $data) {
            // Set the parent for this child
            $planted[$id][0] = $data['parent'];

            // Set this as a child on its parent
            if (null !== $data['parent'] && isset($temp[$data['parent']])) {
                if (isset($planted[$data['parent']]) && isset($planted[$data['parent']][1])) {
                    array_push($planted[$data['parent']][1], $id);
                } else {
                    $planted[$data['parent']][1] = [$id];
                }
            }

            // Remove this: we no longer need it
            unset($temp[$id]['parent']);

            // If children is not set, set an empty array.
            // Otherwise, assume set at leave alone.
            if (!isset($planted[$id][1])) {
                $planted[$id][1] = [];
            }

            // Remove $data['parent'] since it's now redundant.
            unset($data['parent']);

            $planted[$id][2] = $data;
        }

        return $planted;
    }

    /**
     * Plants the tree.
     *
     * @param object $spotter   An object in a class that extends Spotter\Spotter.
     * @return void
     */
    public function nursery($spotter)
    {
        if (is_subclass_of($spotter, __NAMESPACE__.'\\Spotter\\Spotter')) {
            $this->germinated = $spotter->germinate();
        } else {
            throw new \Exception("You have to give me a Spotter.", 1);
        }

        $this->tree = $this->plant();
    }

    /**
     * Publically return the tree, as it exists.
     *
     * @return array
     */
    public function grow()
    {
        return $this->tree;
    }

    /**
     * Get a leaf by its id.
     *
     * @param integer $id
     * @return array
     */
    public function getLeaf(int $id)
    {
        return isset($this->tree[$id]) ? $this->tree[$id] : null;
    }

    /**
     * Get data from within a leaf.
     * 
     * The point of this is to avoid having to do ugly stuff like
     * `$this->getLeaf(2)[1]['order']`.
     * 
     * Passing $data allows you to
     *
     * @param integer $id
     * @param int|string $slot
     * @param int|string $data
     * @return mixed
     */
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
   * Find the ancestors of a particular leaf.
   *
   * @param integer $id
   * @param array $ancestors
   * @return array
   */
    public function getLeafPath(int $id, array $ancestors = [])
    {
        if (null === $this->getLeaf($id) || null === $this->getLeafContent($id, 0)) {
            return array_reverse($ancestors); // This item doesn't exist or has no ancestors
        }

        array_push($ancestors, $this->getLeafContent($id, 0));

        return $this->getLeafPath($this->getLeafContent($id, 0), $ancestors);
    }

  /**
   * Find the siblings of a particular leaf.
   *
   * @param integer $id
   * @param boolean $exclude   If true, exclude queried leaf from return.
   * @return array
   */
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
     * Set some data on a leaf.
     * 
     * This only sets "top-level" data—in other words, it does not treat
     * values fir the 2 (data) slot any differently than other data, and doesn't
     * allow you to target specific keys in the 2 (data) slot. For that, see
     * `Tree::setLeafPropDeep()`.
     * 
     * @see Tree::setLeafPropDeep()
     *
     * @param integer $id
     * @param integer|string $slot
     * @param mixed $value
     * @return mixed|boolean    Returns the value set if successful, `false` if
     *                          not.
     */
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

                    default:
                        return $this->dangerouslySetLeafProp(
                            $id, 
                            $slot, 
                            $value
                        );
                        break;
                }
                return $this->tree[$id];
            }
        }

        return false;
    }

    /**
     * Set some data in the 2 (data) slot on a leaf.
     *
     * @param integer $id
     * @param integer|string $key
     * @param mixed $value
     * @return mixed|boolean    Returns the value set if successful, `false` if
     *                          not.
     */
    protected function setData(int $id, array $action)
    {

        if (isset($action[0])
            && isset($action[1])
            && (is_int($action[0]) || is_string($action[0]))) {
            if ($data = $this->getLeafContent($id, 2)) {
                $data[$action[0]] = $value;
                return $this->dangerouslySetLeafProp($id, 2, $action[1]);
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
     * Setting `$cascade` to `false` may have undesirable behavior, as 
     * relationships between leaves will no longer be internally consistent.
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
            $workspace = $this->workspace();

            $previous_parent = $workspace->getLeafContent($id, 0);

            // If this already had a parent, remove this from its children.
            if (is_int($previous_parent)) {
                $previous_parent_children = $workspace->getLeafContent(
                    $previous_parent,
                    1
                ) ?? []; // If we get no content, return an empty array.

                $previous_parent_children = array_flip($previous_parent_children);
                unset($previous_parent_children[$id]);

                $workspace->setChildren(
                    $previous_parent, 
                    array_flip($previous_parent_children),
                    false  // We're going to be doing this later.
                );
            }

            $children = $workspace->getLeafContent($parent, 1);
            $children[] = $id;

            $populated = $workspace->setChildren(
                $parent,
                $children
            );

            $self = $workspace->dangerouslySetLeafProp($id, 0, $parent);

            if ($populated && $self) {
                $this->tree = $workspace->grow();
                unset($workspace, $success);
                return $this->getLeaf($id);
            }
        }

        return false;
    }

    /**
     * Set leaf children.
     * 
     * By default, this will also modify all leaves that are affected by this
     * changed (namely, new and removed children of `$id`). To disable this
     * behavior, set `$cascade` to `false`. 
     * 
     * **WARNING!!**
     * Setting `$cascade` to `false` may have undesirable behavior, as 
     * relationships between leaves will no longer be internally consistent.
     *
     * @param integer $id
     * @param array $children
     * @param boolean $cascade
     * @return void
     */
    protected function setChildren(int $id, array $children, $cascade = true)
    {
        if (!$cascade) {
            return $this->dangerouslySetLeafProp($id, 1, $children);
        } elseif ($cascade) {
            $workspace = $this->workspace();
            
            $previous_children = $workspace->getLeafContent($id, 1);
            $remove = array_diff($previous_children, $children);
            $add = array_diff($children, $previous_children);
            
            if (count($remove) > 0) {
                $removed = array_reduce($remove, 
                    function($carry, $current) use ($workspace) {
                        // If $carry is false, we've already failed.
                        if ($carry === false) {
                            return false;
                        }

                        $result = $workspace->setParent($current, null, false);

                        // The first iteration
                        if ($result && $carry === null) {
                            return true;
                        } elseif ($result === false) {
                            return false;
                        } else {
                            return $carry;
                        }
                    }, 
                    null
                );
            } else {
                $removed = true; // Don't need to remove anything
            }

            if (count($add) > 0) {
                $added = array_reduce($add, 
                    function($carry, $current) use ($id, $workspace) {
                        // If $carry is false, we've already failed.
                        if ($carry === false) {
                            return false;
                        }

                        $result = $workspace->setParent($current, $id, false);

                        // The first iteration
                        if ($result && $carry === null) {
                            return true;
                        } elseif ($result === false) {
                            return false;
                        } else {
                            return $carry;
                        }
                    }, 
                    null
                );
            } else {
                $added = true; // Don't need to add anything.
            }

            $self = $workspace->setChildren($id, $children, false);

            if ($removed && $added && $self) {
                $this->tree = $workspace->grow();
                unset($workspace, $removed, $added, $self);
                return $this->getLeaf($id);
            }
        }

        return false;
    }

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
     * @param array ...$actions  Several arrays.
     * @return array|boolean     Returns the new leaf if successful, `false` if
     *                           not.
     */
    public function setLeaf(int $id, array...$actions)
    {
        if ($this->getLeaf($id) && count($actions) > 0) {
            $workspace = $this->workspace();

            $success = array_reduce(
                $actions, 
                function($carry, $current) use ($id, $workspace) {
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
}