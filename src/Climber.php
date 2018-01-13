<?php

namespace Livy\Climber;

use \Zenodorus as Z;

class Climber
{
    protected $setable = [
      'tree',
      'topClass',
      'menuClass',
      'itemClass',
      'linkClass',
      'topAttr',
      'menuAttr',
      'itemAttr',
      'linkAttr',
    ];
    public static $expected = [
        'ID',
        'menu_item_parent',
        'menu_order',
        'object_id',
        'title',
    ];
    protected $hookable = [
        'top',
        'menu',
        'item',
        'link',
    ];

    protected $seed;
    protected $tree = [];

    protected $topClass = 'simpleMenu';
    protected $menuClass = 'simpleMenu__menu';
    protected $itemClass = 'simpleMenu__item';
    protected $linkClass = 'simpleMenu__link';
    protected $topAttr = [];
    protected $menuAttr = [];
    protected $itemAttr = [];
    protected $linkAttr = [];
    protected $topHooks = [];
    protected $menuHooks = [];
    protected $itemHooks = [];
    protected $linkHooks = [];

    /**
     * Class constructor.
     *
     * This expects to be given an array that is either the result of
     * `wp_get_nav_menu_items()` or as the same structure.
     *
     * The tree can be planted later with a call to the `nursery()` method.
     *
     * @param array $seed
     */
    public function __construct($seed)
    {
        if (is_array($seed)) {
            $this->seed = $seed;
            $this->nursery($this->seed);
        }
    }

  /**
   * If $this is treated as a string, print out a <ul></ul>.
   *
   * @return string
   */
    public function __toString()
    {
        return $this->element();
    }

  /**
   * Get the value of a property.
   *
   * @param string $property  Name of property to get.
   * @return mixed            Returns bool `false` if property does not exist.
   */
    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return null;
    }

  /**
   * Set a property, if that is possible.
   *
   * Requested property is only set if its name appears
   * in `$this->setable` array.
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
    public function __set(string $property, $value = null)
    {
        if (in_array($property, $this->setable)) {
            $setMethod = sprintf("set%s", ucfirst($property));
            if (method_exists($this, $setMethod)) {
                $value = call_user_func([$this, $setMethod], $value, $property);
            }

            $this->$property = $value;
            $this->nursery($this->seed);

            return $this->$property;
        }

        return null;
    }

    /**
     * Safely return a value for $property with $value
     * appended to the end of the array.
     *
     * @param mixed $value
     * @param string $property
     * @return array
     */
    protected function appendArrayProp($value, string $property)
    {
        $temp = $this->$property;
        array_push($temp, $value);
        return $temp;
    }

    /**
     * Set $this->topAttr.
     *
     * @see Livy\Climber\Climber::appendArrayProp()
     */
    public function setTopAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

    /**
     * Set $this->menuAttr.
     *
     * @see Livy\Climber\Climber::appendArrayProp()
     */
    public function setMenuAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

    /**
     * Set $this->itemAttr.
     *
     * @see Livy\Climber\Climber::appendArrayProp()
     */
    public function setItemAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

    /**
     * Set $this->linkAttr.
     *
     * @see Livy\Climber\Climber::appendArrayProp()
     */
    public function setLinkAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

  /**
   * Creates a string from an array of attribute pairs.
   *
   * Arrays passed to this method should use the following format:
   *
   * ```
   *   [
   *      ['target', '_blank'],
   *      ['disabled']
   *      ['data-menu', '#primary']
   *   ]
   * ```
   *
   * @param array $attrs          Collection of attribute pairs in an array.
   * @return string|null          Returns the complete string if viable, null otherwise.
   */
    protected function attrs(array $attrs)
    {
        if (!Z\Arrays::isEmpty($attrs)) {
            return array_reduce($attrs, function ($carry, $current) {
                if (isset($current[0])) {
                    $return = false;
                    if (!isset($current[1])) {
                        $return = Z\Strings::clean($current[0], "-", "/[^[:alnum:]-]/u");
                    } else {
                        $return = sprintf(
                            '%s="%s"',
                            Z\Strings::clean($current[0], "-", "/[^[:alnum:]-]/u"),
                            htmlspecialchars($current[1], ENT_QUOTES)
                        );
                    }

                    return $return ? Z\Strings::addNew($return, $carry) : $carry;
                }

                return $carry;
            }, '');
        }

        return null;
    }

    /**
     * Gets the URL for a given leaf.
     *
     * This is essentially a wrapper for `get_permalink()`. By encapsulating
     * this call in such a way, we can account for situations (i.e. testing)
     * where `get_permalink()` is unavailable.
     *
     * WARNING
     *
     * As currently written, the non-`get_permalink()` url is not intended
     * to be *at all* functional—it is only there so something is returned.
     *
     * @param int $id
     * @return void
     */
    protected function url(int $id)
    {
        if (function_exists('get_permalink')) {
            return get_permalink($id);
        } else {
            return '/' . $id;
        }
    }

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
    public function hook(string $location, $callback, $order = false)
    {
        $propName = sprintf("%sHooks", $location);
        if (property_exists($this, $propName)) {
            if ($order === false) {
                return $this->{$propName}[] = $callback;
            } else {
                return $this->{$propName}[(int) $order] = $callback;
            }
        }

        return null;
    }

    /**
     * Run all hooks attached to a particular location.
     *
     * $data can be any type of data, but will usually be an array.
     *
     * @param string $location
     * @param mixed $data
     * @return mixed
     */
    protected function runHook(string $location, $data)
    {
        $propName = sprintf("%sHooks", $location);
        if (property_exists($this, $propName) && count($this->{$propName}) > 0) {
            foreach ($this->{$propName} as $callback) {
                $data = call_user_func($callback, $data);
            }
        }

        return $data;
    }

    /**
     * Plants the tree.
     *
     * This expects to be given an array that is either the result of
     * `wp_get_nav_menu_items()` or as the same structure.
     *
     * @param array $seed
     * @return void
     */
    public function nursery(array $seed)
    {
        $this->tree = $this->prune(
            $this->plant($seed)
        );
    }

  /**
   * Add children to items.
   *
   * This iterates through all items and adds each items children
   * to it by creating a `child` property.
   *
   * @param array $seed
   * @return array
   */
    protected function plant(array $seed)
    {
        $return = [];

        foreach ($seed as $id => $item) {
            // Get menu items from $seed that are children of the current item.
            $item->children = array_filter($seed, function ($child) use ($item) {
                return (string) $item->ID === (string) $child->menu_item_parent;
            });

            // Make sure children are sorted by menu_order.
            usort($item->children, function ($a, $b) {
                if ($a->menu_order == $b->menu_order) {
                    return 0;
                }
                return ($a->menu_order < $b->menu_order) ? -1 : 1;
            });

            // Add this item to the array we'll eventually return.
            $return[] = $item;

                /** PERFORMANCE
                 * On large menus this loop could get really long so, lets clean
                 * things up a bit.
                 *
                 * Filter out all the children we just added; we know who's children
                 * they are now, so we don't need to iterate through them in the
                 * future.
                 */
                $seed = array_filter($seed, function ($child) use ($item) {
                    return (string) $item->ID !== (string) $child->menu_item_parent;
                });
        }

        return $return;
    }

  /**
   * Remove children from top level.
   *
   * Once children have been added to their parents, they should no longer
   * appear in the top-level array, or we won't be able to iterate over it
   * properly. This removes them with a simple filter.
   *
   * @param array $planted
   * @return array
   */
    protected function prune($planted)
    {
        return array_filter($planted, function ($leaf) {
            return (int) $leaf->menu_item_parent === 0;
        });
    }

  /**
   * Process a leaf and possibly sprout branches.
   *
   * This generates HTML for an individual <li> in the menu. If this
   * leaf has children, then it calls `$this->sprout()` to create a
   * <ul> container all the children.
   *
   * @param [type] $leaf
   * @param integer $level
   * @return void
   */
    protected function leaf(\WP_Post $leaf, $level = 0)
    {
        $itemData = $this->runHook('item', [
            'class' => $this->itemClass,
            'attrs' => $this->attrs($this->itemAttr),
            'leaf' => $leaf,
        ]);

        $linkData = $this->runHook('link', [
            'link' => $this->url($leaf->object_id),
            'class' => $this->linkClass,
            'attrs' => $this->attrs($this->linkAttr),
            'text' => $leaf->title,
            'leaf' => $leaf,
        ]);

        return sprintf(
            '<li class="%1$s" %2$s>%3$s%4$s</li>',
            $itemData['class'],
            $itemData['attrs'],
            sprintf(
                '<a href="%1$s" class="%2$s" %3$s>%4$s</a>',
                $linkData['link'],
                $linkData['class'],
                $linkData['attrs'],
                $linkData['text']
            ),
            count($leaf->children) > 0
            ? $this->sprout($leaf->children, $level)
            : null
        );
    }

    /**
     * Sprouts a new branch.
     *
     * This creates a new menu (or, more often, a submenu). $level reflects
     * how 'deep' in the menu we are. The very top level is 0; the next level
     * of submenus is 1, etc.
     *
     * @param array $children
     * @param integer $level
     * @return string
     */
    protected function sprout(array $children, $level = 0)
    {
        $menuData = $this->runHook('menu', [
            'class' => $level > 0
                ? sprintf('%1$s %1$s--submenu', $this->menuClass)
                : $this->menuClass,
            'level' => $level,
            'attrs' => $this->attrs($this->menuAttr),
            'children' => $children,
        ]);

        return sprintf(
            '<ul class="%1$s level-%2$s" %3$s>%4$s</ul>',
            $menuData['class'],
            $menuData['level'],
            $menuData['attrs'],
            array_reduce($menuData['children'], function ($carry, $child) use ($level) {
                return $carry . $this->leaf($child, $level + 1);
            })
        );
    }

    /**
     * Return (or optionally echo) the full HTML for the menu.
     *
     * @param boolean $echo
     * @return string
     */
    public function element($echo = false)
    {
        $topData = $this->runHook('top', [
            'class' => $this->topClass,
            'attrs' => $this->attrs($this->topAttr),
            'tree' => $this->tree,
            'echo' => $echo,
        ]);

        $menu = sprintf(
            '<nav class="%1$s" %2$s>%3$s</nav>',
            $topData['class'],
            $topData['attrs'],
            $this->sprout($topData['tree'])
        );

        if ($topData['echo']) {
            echo $menu;
        } else {
            return $menu;
        }
    }
}
