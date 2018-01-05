<?php

namespace Livy\Climber;

class Climber
{
    protected $setable = [
      'tree'
    ];

    protected $tree = [];

    protected $topClass = 'simpleMenu';
    protected $menuClass = 'simpleMenu__menu';
    protected $itemClass = 'simpleMenu__item';
    protected $linkClass = 'simpleMenu__link';
    protected $topAttr = [];
    protected $menuAttr = [];
    protected $itemAttr = [];
    protected $linkAttr = [];

    public function __construct(int $menuID)
    {
        $this->ID = $menuID;
        $this->tree = $this->prune(
            $this->plant(
                wp_get_nav_menu_items($this->ID)
            )
        );

        // var_dump($this->tree);
    }

  /**
   * If $this is treated as a string, print out a <ul></ul>.
   *
   * @return string
   */
    public function __toString()
    {
        return $this->sprout($this->tree);
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
            return $this->{$property};
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
        if (isset($this->setable[$property])) {
            $setMethod = sprintf("set%s", ucfirst($property));
            if (method_exists($this, $setMethod)) {
                $value = call_user_func([$this, $setMethod], $value);
            }

            return $this->{$property} = $value;
        }

        return null;
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
        if (!Zenodorus\Arrays::isEmpty($attrs)) {
            return array_reduce($attrs, function ($carry, $current) {
                if (isset($current[0])) {
                    $return = false;
                    if (!isset($current[1])) {
                        $return = Zenodorus\Strings::clean($current[0], "-", "/[^[:alnum:]-]/u");
                    } else {
                        $return = sprintf(
                            '%s="%s"',
                            Zenodorus\Strings::clean($current[0], "-", "/[^[:alnum:]-]/u"),
                            esc_attr($current[1])
                        );
                    }

                    return $return ? Zenodorus\Strings::addNew($return, $carry) : $carry;
                }

                return $carry;
            });
        }

        return null;
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
        return array_map(function ($item) use ($seed) {
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

            return $item;
        }, $seed);
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
    protected function leaf(WP_Post $leaf, $level = 0)
    {
        return sprintf(
            "<li>%s%s</li>",
            sprintf(
                "<a href='%s'>%s</a>",
                get_permalink($leaf->object_id),
                $leaf->title
            ),
            count($leaf->children) > 0
            ? $this->sprout($leaf->children, $level)
            : null
        );
    }

    protected function sprout($children, $level = 0)
    {
        return sprintf(
            "<ul data-level='%s'>%s</ul>",
            $level,
            array_reduce($children, function ($carry, $child) use ($level) {
                return $carry . $this->leaf($child, $level + 1);
            })
        );
    }
}
