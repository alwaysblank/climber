<?php

namespace Livy\Climber;

use \Zenodorus as Z;

/**
 * Class Climber
 * @property Tree   $tree
 * @property string $topClass
 * @property string $menuClass
 * @property string $itemClass
 * @property string $linkClass
 * @property array  $topAttr
 * @property array  $menuAttr
 * @property array  $itemAttr
 * @property array  $linkAttr
 *
 * @package Livy\Climber
 */
class Climber implements API\ClimberAPI
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

    /**
     * These props determine CSS class names. Modifying them from outside the
     * class will overwrite them—there is no special handling via
     * `Climber::__set()`;
     *
     * @var string
     */
    protected $topClass  = 'simpleMenu';
    protected $menuClass = 'simpleMenu__menu';
    protected $itemClass = 'simpleMenu__item';
    protected $linkClass = 'simpleMenu__link';

    /**
     * These props determine element attributes. Modifying them from outside the
     * class will append values to the arrays by `Climber::__set()`. When these
     * arrays are processed through `Climber::attr()`, values can override each
     * other and remove other values.
     *
     * @see Climber::attr()
     *
     * @var array
     */
    protected $topAttr  = [];
    protected $menuAttr = [];
    protected $itemAttr = [];
    protected $linkAttr = [];

    /**
     * These props contain hoooks that will be run at the appropriate stage.
     * They are set by `Climber::hook()` and should **not* be modified directly.
     *
     * @var array
     */
    protected $topHooks        = [];
    protected $menuHooks       = [];
    protected $itemHooks       = [];
    protected $itemOutputHooks = [];
    protected $linkHooks       = [];

    public function __construct(Tree $tree, $activeUrl = null)
    {
        $this->tree = $tree;

        if (null !== $activeUrl) {
            $this->setCurrentUrl($activeUrl);
        }

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
                        if (isset($leaf[3])
                            && (int)$branch === (int)$leaf[0]) {
                            $data['class'] = Z\Strings::addNew(
                                sprintf(
                                    "%s--%s",
                                    $this->menuClass,
                                    'active'
                                ),
                                $data['class']
                            );
                        }
                    }
                }

                return $data;
            }
        );
    }

    public function getLeafByTarget(string $target, bool $strict = true)
    {
        $leaves = [];
        foreach ($this->tree->grow() as $id => $leaf) {
            $testTarget = Z\Arrays::pluck($leaf, [2, 'target']);
            if ($testTarget === $target) {
                $leaves[] = $id;
            } elseif (!$strict) {
                $parsedTarget     = parse_url($target);
                $parsedTestTarget = parse_url($testTarget);
                $matchPath        = isset($parsedTarget['path'])
                && isset($parsedTestTarget['path'])
                    ? ($parsedTarget['path'] === $parsedTestTarget['path'])
                    : true;
                $matchQueries     = isset($parsedTarget['query'])
                && isset($parsedTestTarget['query'])
                    ? ($parsedTarget['query'] === $parsedTestTarget['query'])
                    : true;
                $matchHashes      = isset($parsedTarget['fragment'])
                && isset($parsedTestTarget['fragment'])
                    ? ($parsedTarget['fragment'] === $parsedTestTarget['fragment'])
                    : true;

                if ($matchPath && $matchQueries && $matchHashes) {
                    $leaves[] = $id;
                }
            }
        }

        return $leaves;
    }

    public function setCurrentUrl($url, $strict = true)
    {
        if (count($currentLeaves = $this->getLeafByTarget($url, $strict)) > 0) {
            foreach ($currentLeaves as $leaf) {
                $this->activate($leaf);
            }
        }
        return $this;
    }

    public function isActivated()
    {
        return count(array_column($this->tree->grow(), 3)) > 0;
    }

    public function activate(int $hint)
    {
        if ($leaf = $this->tree->getLeaf($hint)) {
            $path   = $this->tree->getLeafPath($hint);
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

    public function hook(string $location, $callback, $order = false)
    {
        $propName = sprintf("%sHooks", $location);
        if (property_exists($this, $propName)) {
            if ($order === false) {
                return $this->{$propName}[] = $callback;
            } else {
                return $this->{$propName}[(int)$order] = $callback;
            }
        }

        return null;
    }

    public function __toString()
    {
        return $this->element();
    }

    public function element($echo = false)
    {
        if (null != $this->tree->grow()) {
            $topData = $this->runHook('top', [
                'class' => $this->topClass,
                'attrs' => $this->attrs($this->topAttr),
                'tree'  => $this->tree,
                'echo'  => $echo,
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

        return null;
    }

    /**
     * Run all hooks attached to a particular location.
     *
     * $data can be any type of data, but will usually be an array.
     *
     * @param string $location
     * @param mixed  $data
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
     * Creates a string from an array of attribute pairs.
     *
     * Arrays passed to this method should use the following format:
     *
     * ```
     *   [
     *      ['target', '_blank'],
     *      ['disabled'],
     *      ['data-menu', '#primary'],
     *   ]
     * ```
     *
     * You can remove an attribute by passing `false` as the second value:
     *
     * ```
     *    [
     *       ['target', '_blank'],
     *       ['target', false],
     *    ]
     * ```
     *
     * This would result in *no* `target` attribute appearing on the element.
     *
     * Subsequent attributes will override previousl ones:
     *
     * ```
     *    [
     *       ['data-star', 'wars'],
     *       ['data-star', 'trek'],
     *    ]
     * ```
     *
     * This would result in:
     *
     * ```
     * <element data-star="trek"></element>
     * ```
     *
     * @param array $attrs Collection of attribute pairs in an array.
     * @return string|null          Returns the complete string if viable, null otherwise.
     */
    protected function attrs(array $attrs)
    {
        if (!Z\Arrays::isEmpty($attrs)) {
            /**
             * Do a little logic on our attrs so we know they're correct.
             */
            $processed = [];
            foreach ($attrs as $key => $value) {
                if (!is_string($value[0])) {
                    // Only strings can be attributes.
                    continue;
                }

                if (isset($value[1])) {
                    if ($value[1] === false && isset($processed[$value[0]])) {
                        // If this attr is set to false, remove it.
                        unset($processed[$value[0]]);
                    } elseif (is_string($value[1])) {
                        // Otherwise, process it
                        $processed[$value[0]] = $value[1];
                    }
                } else {
                    $processed[$value[0]] = true;
                }
            }

            /**
             * Generate a string of all our attrs.
             */
            $return = null;
            foreach ($processed as $attr => $value) {
                if (is_string($value)) {
                    $return .= ' ' . sprintf(
                        '%s="%s"',
                        Z\Strings::clean($attr, "-", "/[^[:alnum:]-]/u"),
                        htmlspecialchars($value, ENT_QUOTES)
                    );
                } elseif (true === $value) {
                    $return .= ' ' . Z\Strings::clean(
                        $attr,
                        "-",
                        "/[^[:alnum:]-]/u"
                    );
                }
            }

            return $return;
        }

        return null;
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
            'bud'   => $bud,
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
     * @param Tree $tree
     * @return array
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

    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return null;
    }

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

    public function setTopAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

    /**
     * Safely return a value for $property with $value
     * appended to the end of the array.
     *
     * @param mixed  $value
     * @param string $property
     * @return array
     */
    protected function appendArrayProp($value, string $property)
    {
        $temp = $this->$property;
        array_push($temp, $value);
        return $temp;
    }

    public function setMenuAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

    public function setItemAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

    public function setLinkAttr($value, $property)
    {
        return $this->appendArrayProp($value, $property);
    }

    /**
     * This takes a numeric id for a leaf, and expands it out. It will create
     * an `<li>` wrapper, and organize the link and optional submenu inside
     * of it.
     *
     * @param integer $hint
     * @return string
     */
    protected function bud(int $hint)
    {
        $bud = $this->tree->getLeaf($hint);

        $itemData = $this->runHook('item', [
            'class' => $this->itemClass,
            'attrs' => $this->attrs($this->itemAttr),
            'bud'   => $bud,
        ]);

        $itemOutput = $this->runHook('itemOutput', [
            'format' => '%1$s%2$s',
            'args'   => [
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
     * @return string
     */
    protected function fruit(array $bud)
    {
        $linkData = $this->runHook('link', [
            'link'    => Z\Arrays::pluck($bud, [2, 'target']),
            'class'   => $this->linkClass,
            'attrs'   => $this->attrs($this->linkAttr),
            'content' => Z\Arrays::pluck($bud, [2, 'name']),
        ]);

        return sprintf(
            /** @lang text
            * Interpret this as text so PHPStorm doesn't try to find the file. */
            '<a href="%1$s" class="%2$s" %3$s>%4$s</a>',
            $linkData['link'],
            $linkData['class'],
            $linkData['attrs'],
            $linkData['content']
        );
    }
}
