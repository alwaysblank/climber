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

    protected $hookable = [
        'top',
        'menu',
        'item',
        'itemOutput',
        'link',
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
    protected $topHooks = [];
    protected $menuHooks = [];
    protected $itemHooks = [];
    protected $itemOutputHooks = [];
    protected $linkHooks = [];

    /**
     * All this does is set the tree.
     *
     * @param Tree $tree
     */
    public function __construct(Tree $tree, $currentUrl = null)
    {
        $this->tree = $tree;

        if ($currentUrl) {
            $this->activate(
                $this->getLeafByTarget($currentUrl)
            );

            /**
             * Add 'active' classes to items that are active (i.e. the
             * contain the url we're at, or its ancestors.)
             */
            $this->hook(
                'item',
                function ($data) {
                    if (isset($data['bud'][3])) {
                        $data['class'] = Z\Strings::addNew(
                            sprintf(
                                "%s--%s",
                                $this->itemClass,
                                $data['bud'][3]
                            ),
                            $data['class']
                        );
                    }

                    return $data;
                }
            );
            
            /**
             * Add an 'active' class to menus that contain an active item (i.e.
             * an item containing the url we're at.)
            */
            $this->hook(
                'menu',
                function ($data) {
                    if ($branch = Z\Arrays::pluck($data['bud'], [2, 'id'], true)
                    ) {
                        // if leaf has $branch as parent and active == true
                        foreach ($this->tree->grow() as $key => $leaf) {
                            $data['class'] = Z\Strings::addNew(
                                sprintf(
                                    "%s--%s",
                                    $this->menuClass,
                                    'active'
                                ),
                                $data['class']
                            );
                            break;
                        }
                    }

                    return $data;
                }
            );
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

            return $this->$property;
        }

        return null;
    }

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
    public function activate(int $hint)
    {
        if ($leaf = $this->tree->getLeaf($hint)) {
            $path = $this->tree->getLeafPath($hint);
            $path[] = $hint;
            foreach (array_reverse($path) as $order => $id) {
                switch ($order) {
                    case 0:
                        $distance = 'current';
                        break;
                    
                    case 1:
                        $distance = 'parent';
                        break;
                    
                    default:
                        $distance = 'ancestor';
                        break;
                }
                $this->tree->setLeafProp($id, 3, $distance);
                unset($distance);
            }
        }
    }

    /**
     * Get a leaf from the value of its target.
     *
     * This is primarily useful when you want to set active leaves for the
     * current page.
     *
     * If `$strict` is `true`, then it just does a direct string match test. If
     * `$strict` is `false`, then it tests the path, queries, and fragments
     * against one another with `parse_url`.
     *
     * @param string $target
     * @param boolean $strict
     * @return void
     */
    public function getLeafByTarget(string $target, bool $strict = true)
    {
        foreach ($this->tree->grow() as $id => $leaf) {
            $testTarget = Z\Arrays::pluck($leaf, [2, 'target']);
            if ($testTarget === $target) {
                return $id;
            } elseif (!$strict) {
                $parsedTarget = parse_url($target);
                $parsedTestTarget = parse_url($testTarget);
                $matchPath = isset($parsedTarget['path'])
                    && isset($parsedTestTarget['path'])
                    ? ($parsedTarget['path'] === $parsedTestTarget['path'])
                    : true;
                $matchQueries = isset($parsedTarget['query'])
                    && isset($parsedTestTarget['query'])
                    ? ($parsedTarget['query'] === $parsedTestTarget['query'])
                    : true;
                $matchHashes = isset($parsedTarget['fragment'])
                    && isset($parsedTestTarget['fragment'])
                    ? ($parsedTarget['fragment'] === $parsedTestTarget['fragment'])
                    : true;

                if ($matchPath && $matchQueries && $matchHashes) {
                    return $id;
                }
            }
        }

        return false;
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
            return ' ' . array_reduce($attrs, function ($carry, $current) {
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
                '<li class="%1$s"%2$s>%3$s</li>',
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
            'link' => Z\Arrays::pluck($bud, [2, 'target']),
            'class' => $this->linkClass,
            'attrs' => $this->attrs($this->linkAttr),
            'content' => Z\Arrays::pluck($bud, [2, 'name']),
        ]);

        return sprintf(
            '<a href="%1$s" class="%2$s"%3$s>%4$s</a>',
            $linkData['link'],
            $linkData['class'],
            $linkData['attrs'],
            $linkData['content']
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

        // Make sure children are properly sorted.
        usort($bud[1], function ($a, $b) {
            $a_order = $this->tree->getLeafContent($a, 'data', 'order');
            $b_order = $this->tree->getLeafContent($b, 'data', 'order');

            if ($a_order == $b_order) {
                return 0;
            }
            return ($a_order < $b_order) ? -1 : 1;
        });
        
        $menuData = $this->runHook('menu', [
            'class' => $level > 0
                ? sprintf('%1$s %1$s--submenu', $this->menuClass)
                : $this->menuClass,
            'level' => $level,
            'attrs' => $this->attrs($this->menuAttr),
            'bud' => $bud,
        ]);

        return sprintf(
            '<ul class="%1$s level-%2$s"%3$s>%4$s</ul>',
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
     * @param Tree $tree
     * @return string
     */
    protected function harvest(Tree $tree)
    {
        $treeLeaves = $tree->grow();
        return [
            0 => null,
            1 => array_keys(array_filter($treeLeaves, function ($leaf) {
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
            'tree' => $this->tree,
            'echo' => $echo,
        ]);

        $menu = sprintf(
            '<nav class="%1$s"%2$s>%3$s</nav>',
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
