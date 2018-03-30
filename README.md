# 🧗 Climber
### Why [walk](https://codex.wordpress.org/Class_Reference/Walker) when you can climb? 

An alternative to WordPress's built-in Nav_Walker, 🧗 Climber creates a more 
reasonable data structure, which can be interacted with directly or used to 
generate the HTML for a navigation menu.

[![Build Status](https://travis-ci.org/alwaysblank/climber.svg?branch=master)](https://travis-ci.org/alwaysblank/climber)

#### ☠️ Currently in Development ☠️
#### ⚡ Probably not ready for production ⚡

## Usage

The simplest implementation of 🧗 Climber looks like this:

```php
use Livy\Climber;

echo new Climber(
  new Tree(
    new Spotter\WordPress(wp_get_nav_menu_items($menuID))
  )
);

// <nav class="simpleMenu" >
//    <ul class="simpleMenu__menu level-0">
//        ...etc
```

...Maybe not quite so simple. There will be convenience functions eventually!

This document doesn't detail all the methods and ways of interaction with 
🧗 Climber—only the ones you're most likely to use. The methods themselves
are heavily documented inline, so feel free to dive into the code if you're
curious how something works.

## How Does It Work?

🧗 Climber has three basic components that it needs to work:

* Spotter
* Tree
* Climber

**Spotter** allows for 🧗 Climber to be platform agnostic: While it ships with a
WordPress Spotter, you can add whatever Spotters you like to collect and process
data. The Spotter's job is to return data in the format that Tree expects.

**Tree** processes the data recieved from Spotter and implements several methods
that can be used to quickly and easily interact with the data it contains. Its
organization allows Climber to quickly find the things it needs to generate a
navigation menu.

**Climber** collects a Tree and returns a properly-constructed navigation menu
in HTML.

Generally, you'll only be interacting directly with Climber, but you can easily
extend or modify the other components to suit your needs—or use them to modify
your data.

## Show Me

The **Usage** section at the top illustrates the simplest version, which will
return a simple menu. In most situations, though, you'll want to tell the menu
what page you're on (so it can highlight that section). Or you might want to
hook into some part of the process to add or modify elements, CSS classes, or
HTML attributes.

```php
/**
 * Instantiate our Climber, and tell it where we are.
 */
$Climber = new Climber(
  new Tree(new Spotter\WordPress(wp_get_nav_menu_items($menuID))),
  get_permalink(get_the_ID()))   // This returns the URL for the current page.
);

/**
 * Open all links in a new window.
 */
$Climber->hook(
  'link',
  function ($data) {
    $data['attrs'][] = ['target', '_blank'];
    return $data;
  }
);

/**
 * Put the link after the submenu, instead of before.
 */
$Climber->hook(
  'itemOutput',
  function($data) {
    $data['format'] = '%2$s%1$s';
    return $data;
  }
);

/**
 * Print out our menu
 */
echo $Climber;
```

The `Climber` object will return an HTML menu if treated as a string. Handy!

## Hooks

In order to allow you to modify the content and behavior of the menu without
having to extend the classes, Climber exposes several hooks. Each hook provides
a single variable—`$data`—to whatever function is hooked to them. The content of
that variable depends on the hook you attach it to. Generally, it is an array
with keyed values.

Any function that is passed to a hook **must return something similar to the
`$data` it recieved**. Otherwise things will break. See the **Show Me** section
of this document, or the contents of `Climber::__construct()` for examples of
how to properly construct hooks.

### `top`

This is the 'top' level of the navigation menu: The `<nav>` element. The array
it provides includes:

* `class` - *string* The CSS class(es) for this element.
* `attrs` - *array* An array of HTML attributes for this element.
* `tree` - *Tree* The Tree instance used for this menu.
* `element` - *string* Format for `sprintf`, to be used in element generation.
* `echo` - *boolean* Whether or not `Tree::element()` should echo the menu. Very
  few reasons to change this.

### `menu`

This hook is run for each submenu. If you want to target a *particular* submenu,
you'll need to run some sort of test in your passed function to target it. The
array it provides includes:

* `class` - *string* The CSS class(es) for this element.
* `attrs` - *array* An array of HTML attributes for this element.
* `level` - *integer* The current depth of this menu. Probably don't change it.
* `element` - *string* Format for `sprintf`, to be used in element generation.
* `bud` - *array* A full leaf, with all leaf data.

### `item`

An individual `<li>` inside a `<ul>`. Will ultimately contain a link to a part
of your site, and possibly a submenu. The array it provides includes:

* `class` - *string* The CSS class(es) for this element.
* `attrs` - *array* An array of HTML attributes for this element.
* `element` - *string* Format for `sprintf`, to be used in element generation.
* `bud` - *array* A full leaf, with all leaf data.

### `itemOutput`

This describes the actual *content* of `item`. It can be used to add new things
to an `item` (i.e. a `<button>` to open a submenu) or to modify the order of the
things appearing in the `item`. The array it provides includes:

* `format` - *string* A formatting string to be used in `vsprintf()`.
* `args` - *array* An array of values that will be passed to `vsprintf()`. Their
  order is important, because it corresponds to the `format`.

### `link`

This is a link element in an `item`. The array it provides includes:

* `link` - *string* The URL this link goes to.
* `class` - *string* The CSS class(es) for this element.
* `attrs` - *array* An array of HTML attributes for this element.
* `element` - *string* Format for `sprintf`, to be used in element generation.
* `content` - *string* The content of the link element. Usually the name of
  whatever it links to, i.e. "About".

## Settings

These settings will be applies to all elements of their type, so they can be
useful for setting styling or behavior across your menu.

| Name           | Type      | Default            |
|----------------|-----------|--------------------|
| `topClass`     | string    | 'simpleMenu'       |
| `menuClass`    | string    | 'simpleMenu__menu' |
| `itemClass`    | string    | 'simpleMenu__item' |
| `linkClass`    | string    | 'simpleMenu__link' |
| `topAttr`      | array     | `[]`               |
| `menuAttr`     | array     | `[]`               |
| `itemAttr`     | array     | `[]`               |
| `linkAttr`     | array     | `[]`               |

You can override or set Climber-wide properties for element classes and
attributes. Doing so is very simple:

```php
// For string-type properties...
$Climber->topClass = 'newMenuClass';

// For array-type properties...
$Climber->topAttrs = ['target', '_blank'];
```

Keep in mind:

* String-type properties are **overriden**.
* Array-type properties are **appended**.

This means that if you want to supplement the existing classes, you would do
the following:

```php
$Climber->topClass .= ' newMenuClass';

// <ul class="simpleMenu newMenuClass"> ...
```

You can override and remove array-type property entries as well:

```php
// override
$Climber->topAttr = ['data-star', 'wars'];
$Climber->topAttr = ['data-star', 'trek'];

// <nav data-star="trek"> ...

// remove
$Climber->topAttr = ['data-star', 'wars'];
$Climber->topAttr = ['data-star', false];

//<nav> ...
```

## That's Too Complicated

I agree! Fortunately there are some nice, clean, convenience functions to help
do what you want without filling your code up with all those Classes and stuff.

*(This list will expand as necessary. If you think there should be a function
here that isn't here, open an issue or submit a PR to add it!)*

All conveninece functions begin with `pulley__`. They follow a convention
(borrowed from WordPress): Functions named `get_something` will return a
value, while functions simply named `something` will echo that value. For this
reason, these functions often come in pairs. If they don't, it's probably
because the `get_` version returns something that can't be echoed.

### General

These functions will work in any context, but they require you to pass a valid
Spotter.

#### pulley__get_menu()

```php
pulley__get_menu(
  Spotter   $Spotter,
  string    $currentUrl = null
)
```

##### Arguments

- **$Spotter** - _Spotter_
  An instance of `Spotter` that applies to the context you're calling this in.
- **$currentUrl** - _string_
  The URL you're currently act, for activating the correct Tree branch.
  Default: `null`.

##### Returns

_Climber | false_ A Climber object if successful, boolean `false` otherwise.

_

#### pulley__menu()

```php
pulley__menu(
  Spotter    $Spotter,
  string     $currentUrl = null
)
```

The same as `pulley__get_menu()`, except that this will echo the menu
automatically.

##### Arguments

- **$Spotter** - _Spotter_
  An instance of `Spotter` that applies to the context you're calling this in.
- **$currentUrl** - _string_
  The URL you're currently act, for activating the correct Tree branch.
  Default: `null`.

##### Returns

_string | false_ HTML string of the menu if successful, boolean `false`
otherwise.


### WordPress

These functions will only work with WordPress (in fact, Climber will only load
them if it believes it is being used in a WordPress context). Because Climber
comes packaged with a WordPress Spotter, there's no need to manually pass in
a Spotter!

#### pulley__wp_get_menu()

```php
pulley__wp_get_menu(
  int|string|WP_Term     $menu,
  string                 $currentUrl = null
)
```

Returns a `Climber` for the `$menu` passed to it.

##### Arguments

- **$menu** - _int|string|WP_Term_
  The value of `$menu` can a menu ID, slug, name, or object. More specifically,
  it can be any value that [`wp_get_nav_menu_items()`](https://developer.wordpress.org/reference/functions/wp_get_nav_menu_items/)
  would accept as a menu identifier.
- **$currentUrl** - _string_
  The URL you're currently act, for activating the correct Tree branch.
  Default: `null`.

##### Returns

_Climber | false_ A Climber object if successful, boolean `false` otherwise.

#### pulley__wp_menu()

```php
pulley__wp_menu(
  int|string|WP_Term     $menu,
  string                 $currentUrl = null
)
```
Echoing version of `pully__wp_get_menu()`.

##### Arguments

- **$menu** - _int|string|WP_Term_
  The value of `$menu` can a menu ID, slug, name, or object. More specifically,
  it can be any value that [`wp_get_nav_menu_items()`](https://developer.wordpress.org/reference/functions/wp_get_nav_menu_items/)
  would accept as a menu identifier.
- **$currentUrl** - _string_
  The URL you're currently act, for activating the correct Tree branch.
  Default: `null`.

##### Returns

_string | false_ HTML string of the menu if successful, boolean `false`
otherwise.

#### pulley__wp_get_menu_by_location()

```php
function pulley__wp_get_menu_by_location(
    string    $location,
    string    $currentUrl = null
)
```

Get a menu based on its location.

##### Arguments

- **$location** - _string_
  The name of a $location, as defined in as defined in [`register_nav_menus()`](https://codex.wordpress.org/Function_Reference/register_nav_menus).
- **$currentUrl** - _string_
  The URL you're currently act, for activating the correct Tree branch.
  Default: `null`.

##### Returns

_Climber | false_ A Climber object if successful, boolean `false` otherwise.

#### pulley__wp_menu_by_location()

```php
function pulley__wp_menu_by_location(
    string    $location,
    string    $currentUrl = null
)
```

Same as `pulley__wp_get_menu_by_location()`, just echoes.

##### Arguments

- **$location** - _string_
  The name of a $location, as defined in as defined in [`register_nav_menus()`](https://codex.wordpress.org/Function_Reference/register_nav_menus).
- **$currentUrl** - _string_
  The URL you're currently act, for activating the correct Tree branch.
  Default: `null`.

##### Returns

_string | false_ HTML string of the menu if successful, boolean `false`
otherwise.

## That's Not Complicated Enough

All right, fair. Maybe you need something we haven't covered here: Maybe the menu you're passing to Climber only
includes the top level of your site, but you want parent items to still be highlighted when their children are being
visited. Since Climber is (by design) ignorant of your site's internal organization, it doesn't directly support this:
Climber is only aware of the things its aware of.

You can, however, use a bit of logic to tell Climber what it should consider "active".

### Set Current URL

The most common usage of Climber involves passing a URL to it on instantiation—either with the class directly, or
through one of the helper functions. In either case, internally Climber is using the same method to activate the
current URL, and it's a method you can use too!

`Climber::setCurrentUrl()` can be used to pass a URL directly to Climber. Climber will then look for a leaf (or leaves)
that point to that URL, and mark them as "active" (if you have a multi-level menu, it will also mark their ancestors as
well). Using it is very simple:

```php
$Climber = new Climber(
  new Tree(new Spotter\WordPress(wp_get_nav_menu_items($menuID)))
);
$Climber->setCurrentURL(get_permalink(get_the_ID())); // Now this is the current URL!
```

If the Climber you're working with doesn't have that URL, it will do nothing.

It is also worth noting that Climber does not assume there can be only one active leaf: Running `setCurrentUrl()` does
not remove previously active leaves.

Access to `setCurrentUrl()` means that with some elbow grease, you can make Climber think you're on any page you like!
Unfortunately, if you need to do this with a large number of URLs, `setCurrentUrl()` starts get a little less useful.

That's why there's another way!

### Surveyor

The Surveyor class provides you with a simple way of writing lookups for URL patterns that you want to match particular
URLs in Climber. It uses an array of regular expressions to determine what URLs match what. 

To use Surveyor, instantiate it with an array of regular expressions and URLs, and then call `Surveyor::evaluateUrl()`:

```php
$Surveyor = new Surveyord([
    ['/(?:stories\/bedtime\/[\w_-]*)/', 'https://example.com/stories/bedtime/'],
    ['/(?:stories(?:|\/[\w_-]*))/', 'https://example.com/stories/'],
]);
echo $Surveyor->evaluateUrl('https://example.com/stories/bedtime/goodnight-moon');
// Yields `https://example.com/stories/bedtime/`
```

Possible URLs are evaluated in sequence, and returned as soon as a match is found. If no match is found, then the URL
originally passe dto `evaluateUrl()` is returned. This means that you can easily use Surveyor inside a Climber call, and
it will fall back to whatever Climber wants to do with that URL if no matches are found. Like so:

```php
$Surveyor = new Surveyord([
    ['/(?:stories\/bedtime\/[\w_-]*)/', 'https://example.com/stories/bedtime/'],
    ['/(?:stories(?:|\/[\w_-]*))/', 'https://example.com/stories/'],
]);
$Climber = new Climber(
  new Tree(new Spotter\WordPress(wp_get_nav_menu_items($menuID))),
  $Surveyor->evaluateUrl(get_permalink(get_the_ID()))
);
```