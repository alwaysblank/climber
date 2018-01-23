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
 *          [...]       // Other data about this menu item. This data
 *                      // is likely relevant to Climber, but isn't 
 *                      // important to Tree.
 *      ],
 *      44 => [
 *          22,         // The id of the parent (22 in this case).
 * 
 *          [],         // An empty array, because this menu item has
 *                      // no children.
 * 
 *          [...]       // Other data, as above.
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

    // Processed data
    protected $tree;

    public function __construct($spotter)
    {
        $this->nursery($spotter);
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
            if ($data['parent'] !== null && isset($temp[$data['parent']])) {
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
        // Only allow certain terms to be converted.
        $allowed_terms = [
            'parent' => 0,
            'children' => 1,
            'data' => 2,
        ];

        // Build a query from valid terms.
        if (isset($allowed_terms[$slot])) {
            $query = (int) $allowed_terms[$slot];
        } else {
            $query = (int) $slot;
        }

        // Make sure $data is valid.
        if ((null !== $data && !(is_string($data) || is_int($data)))
            || (null !== $data && 2 !== $query)) {
            $data = null;
        }

        $leaf = $this->getLeaf($id);

        if ($leaf) {
            if (null !== $data && isset($leaf[$query][$data])) {
                return $leaf[$query][$data];
            } elseif (null === $data && isset($leaf[$query])) {
                return $leaf[$query];
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
        if ($this->getLeaf($id) === null || $this->getLeafContent($id, 'parent') === null) {
            return array_reverse($ancestors); // This item doesn't exist or has no ancestors
        }

        array_push($ancestors, $this->getLeafContent($id, 'parent'));

        return $this->getLeafPath($this->getLeafContent($id, 'parent'), $ancestors);
    }

  /**
   * Find the siblings of a particular leaf.
   *
   * @param integer $id
   * @param boolean $exclude   If true, exclude queried leaf from return.
   * @return array
   */
    function getLeafSiblings(int $id, bool $exclude = null)
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
}