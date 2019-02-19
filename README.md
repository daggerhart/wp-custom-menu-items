# Custom WordPress Menu Items

This is a utility class for WordPress developers that allow for adding and modifying menu links dynamically within a 
plugin or theme.

Originally this was hosted as a [Gist](https://gist.github.com/daggerhart/c17bdc51662be5a588c9).

### Example Use

```php
<?php
add_action( 'wp', function(){
    // Single item
    /**
     * @param $menu_slug
     * @param $title
     * @param $url
     * @param $order
     * @param $parent
     * @param null $ID
     */
    custom_menu_items::add_item('menu-1', 'My Profile', get_author_posts_url( get_current_user_id() ), 3  );

    // Item with children
    // note: the ID is manually set for the top level item
    custom_menu_items::add_item('menu-1', 'Top Level', '/some-url', 0, 0, 9876 ); 
    // note: this and other children know the parent ID
    custom_menu_items::add_item('menu-1', 'Child 1', '/some-url/child-1', 0, 9876 ); 
    custom_menu_items::add_item('menu-1', 'Child 2', '/some-url/child-2', 0, 9876 );
    custom_menu_items::add_item('menu-1', 'Child 3', '/some-url/child-3', 0, 9876 );

    /**
     * Add object by ID
     *
     * @param $menu_slug
     * @param $object_ID
     * @param string $object_type
     * @param $order
     * @param $parent
     * @param null $ID
     */
    // Add the post w/ ID 1 to the menu
    custom_menu_items::add_object('menu-1', 1, 'post');
    // Add the taxonomy term with ID "3" to the menu as a top-level item with the ID of 9876
    custom_menu_items::add_object('menu-1', 3, 'term', 0, 0, 9876);
    // Add the taxonomy term with ID "4" to the menu as a child of item 9876 
    custom_menu_items::add_object('menu-1', 4, 'term', 0, 9876);
} );
```

**References:**

* Blog post: [Dynamically add items to WordPress menus](https://www.daggerhart.com/dynamically-add-item-to-wordpress-menus/)
* [wp_get_nav_menu_items](https://developer.wordpress.org/reference/functions/wp_get_nav_menu_items/)

**Thanks:**

Here are some developers who helped make this class better when it was a Gist.

* @webdados
* @codepuncher
* @vandelio
* @damienbuchs
