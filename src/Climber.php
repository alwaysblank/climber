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
     * All this does is set the tree.
     *
     * @param Tree $tree
     */
    public function __construct(Tree $tree)
    {
        $this->tree = $tree;
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
     * Returns some data about the leaf in a 
     * slightly more accessible format.
     *
     * @param array $leaf
     * @return void
     */
    protected function examine(array $leaf)
    {
        return (object) array_merge(
            [
                'parent' => $leaf[0] ?? false,
                'children' => $leaf[1] ?? false,
            ],
            $leaf[2]
        );
    }
    
    protected function bud(int $hint)
    {
        $bud = $this->tree->getLeaf($hint);

        $itemData = $this->runHook('item', [
            'class' => $this->itemClass,
            'attrs' => $this->attrs($this->itemAttr),
            'bud' => $bud,
        ]);

        $itemOutput = $this->runHook('itemOutput', [
            'format' => '%1$s%2$s',
            'args' => [
                $this->fruit($bud),
                $this->branch($bud),
            ],
        ]);

        return vsprintf(
            sprintf(
                '<li class="%1$s" %2$s>%3$s</li>',
                $itemData['class'],
                $itemData['attrs'],
                $itemOutput['format']
            ),
            $itemOutput['args']
        );
    }

  /**
   * Sprout a link.
   *
   * @param array $bud
   * @return void
   */
    protected function fruit(array $bud)
    {
        $linkData = $this->runHook('link', [
            'link' => $this->url(Z\Arrays::pluck($bud, [2, 'target'])),
            'class' => $this->linkClass,
            'attrs' => $this->attrs($this->linkAttr),
            'text' => Z\Arrays::pluck($bud, [2, 'name']),
        ]);

        return sprintf(
            '<a href="%1$s" class="%2$s" %3$s>%4$s</a>',
            $linkData['link'],
            $linkData['class'],
            $linkData['attrs'],
            $linkData['text']
        );
    }

    /**
     * Branch out a submenu.
     *
     * @param array $bud
     * @return string
     */
    protected function branch(array $bud)
    {
        // If this bud has no children, there is no branch.
        if (empty($bud[1])) {
            return null;
        }

        $id = Z\Arrays::pluck($bud, [2, 'id'], true);
        // Buds return a path length 1 less than their actual level
        // (because their 'actual' level has to do with the depth of
        // their children), so we need to manually increase the value.
        $level = $id ? count($this->tree->getLeafPath($id)) + 1 : 0;

        $menuData = $this->runHook('menu', [
            'class' => $level > 0
                ? sprintf('%1$s %1$s--submenu', $this->menuClass)
                : $this->menuClass,
            'level' => $level,
            'attrs' => $this->attrs($this->menuAttr),
            'bud' => $bud,
        ]);

        return sprintf(
            '<ul class="%1$s level-%2$s" %3$s>%4$s</ul>',
            $menuData['class'],
            $menuData['level'],
            $menuData['attrs'],
            join('', array_filter(array_map([$this, 'bud'], $bud[1])))
        );
    }

    /**
     * Convert the tree into something we can pass to Climber::branch().
     * 
     * All it does is trip out items from the $tree that have no parents (since
     * all top-level items have no parents).
     *
     * @param array $tree
     * @return string
     */
    protected function harvest(array $tree)
    {
        return [
            0 => null,
            1 => array_keys(array_filter($tree, function($leaf) {
                return $leaf[0] === null;
            })),
        ];
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
            'tree' => $this->tree->grow(),
            'echo' => $echo,
        ]);

        $menu = sprintf(
            '<nav class="%1$s" %2$s>%3$s</nav>',
            $topData['class'],
            $topData['attrs'],
            $this->branch($this->harvest($topData['tree']))
        );

        if ($topData['echo']) {
            echo $menu;
        } else {
            return $menu;
        }
    }
}
