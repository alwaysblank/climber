# 🧗 Climber
### Why [walk](https://codex.wordpress.org/Class_Reference/Walker) when you can climb? 

An alternative to WordPress's built-in Nav_Walker, 🧗 Climber creates a more 
reasonable data structure, which can be interacted with directly or used to 
generate the HTML for a navigation menu.

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

Eventually this document will include more in-depth instructions,
but for now just check out the methods in `src/Climber.php`.