<?php

/**
 * Class custom_menu_items
 *
 * This class is for creating and managing dynamic menu items in a WordPress
 * menu.
 */
class custom_menu_items {
	/**
	 * Track that hooks have been registered w/ WP
	 * @var bool
	 */
	protected $has_registered = false;

	/**
	 * Internal list of menus affected
	 * @var array
	 */
	public $menus = array();

	/**
	 * Internal list of new menu items
	 * @var array
	 */
	public $menu_items = array();

	private function __construct(){}
	private function __wakeup() {}
	private function __clone() {}

	/**
	 * Singleton
	 *
	 * @return \custom_menu_items
	 */
	static public function get_instance(){
		static $instance = null;

		if ( is_null( $instance ) ){
			$instance = new self;
		}

		$instance->register();
		return $instance;
	}

	/**
	 * Hook up plugin with WP
	 */
	private function register(){
		if ( ! is_admin() && ! $this->has_registered ){
			$this->has_registered = true;

			add_filter( 'wp_get_nav_menu_items', array( $this, 'wp_get_nav_menu_items' ), 20, 2 );
			add_filter( 'wp_get_nav_menu_object', array( $this, 'wp_get_nav_menu_object' ), 20, 2 );
		}
	}

	/**
	 * Update the menu items count when building the menu
	 *
	 * @param $menu_obj
	 * @param $menu
	 *
	 * @return mixed
	 */
	function wp_get_nav_menu_object( $menu_obj, $menu ){
		if ( is_a( $menu_obj, 'WP_Term' ) && isset( $this->menus[ $menu_obj->slug ] ) ){
			$menu_obj->count += $this->count_menu_items( $menu_obj->slug );
		}
		return $menu_obj;
	}

	/**
	 * Get the menu items from WP and add our new ones
	 *
	 * @param $items
	 * @param $menu
	 *
	 * @return mixed
	 */
	function wp_get_nav_menu_items( $items, $menu ){
		if ( isset( $this->menus[ $menu->slug ] ) ) {
			$new_items = $this->get_menu_items( $menu->slug );

			if ( ! empty( $new_items ) ) {
				foreach ( $new_items as $new_item ) {
					$items[] = $this->make_item_obj( $new_item );
				}
			}

			$items = $this->fix_menu_orders( $items );
		}

		return $items;
	}

	/**
	 * Entry point.
	 * Add a new menu item to the list of custom menu items
	 *
	 * @param $menu_slug
	 * @param $title
	 * @param $url
	 * @param $order
	 * @param $parent
	 * @param null $ID
	 */
	static public function add_item( $menu_slug, $title, $url, $order = 0, $parent = 0, $ID = null, $classes = array() ){
		$instance = custom_menu_items::get_instance();
		$instance->menus[ $menu_slug ] = $menu_slug;
		$instance->menu_items[] = array(
			'menu'    => $menu_slug,
			'title'   => $title,
			'url'     => $url,
			'order'   => $order,
			'parent'  => $parent,
			'ID'      => $ID,
			'classes' => $classes,
		);
	}

	/**
	 * Add a WP_Post or WP_Term to the menu using the object ID.
	 *
	 * @param $menu_slug
	 * @param $object_ID
	 * @param string $object_type
	 * @param $order
	 * @param $parent
	 * @param null $ID
	 */
	static public function add_object( $menu_slug, $object_ID, $object_type = 'post', $order = 0, $parent = 0, $ID = NULL, $classes = array() ) {
		$instance = custom_menu_items::get_instance();
		$instance->menus[ $menu_slug ] = $menu_slug;

		if ($object_type == 'post' && $object = get_post( $object_ID ) ) {
			$instance->menu_items[] = array(
				'menu'        => $menu_slug,
				'order'       => $order,
				'parent'      => $parent,
				'post_parent' => $object->post_parent,
				'title'       => get_the_title($object),
				'url'         => get_permalink($object),
				'ID'          => $ID,
				'type'        => 'post_type',
				'object'      => get_post_type($object),
				'object_id'   => $object_ID,
				'classes'     => $classes,
			);
		}
		else if ($object_type == 'term') {
			global $wpdb;
			$sql = "SELECT t.*, tt.taxonomy, tt.parent FROM {$wpdb->terms} as t LEFT JOIN {$wpdb->term_taxonomy} as tt on tt.term_id = t.term_id WHERE t.term_id = %d";
			$object = $wpdb->get_row($wpdb->prepare($sql, $object_ID));

			if ( $object ) {
				$instance->menu_items[] = $tmp = array(
					'menu'        => $menu_slug,
					'order'       => $order,
					'parent'      => $parent,
					'post_parent' => $object->parent,
					'title'       => $object->name,
					'url'         => get_term_link((int)$object->term_id, $object->taxonomy),
					'ID'          => $ID,
					'type'        => 'taxonomy',
					'object'      => $object->taxonomy,
					'object_id'   => $object_ID,
					'classes'     => $classes,
				);
			}
		}
	}

	/**
	 * Get an array of new menu items for a specific menu slug
	 *
	 * @param $menu_slug
	 *
	 * @return array
	 */
	private function get_menu_items( $menu_slug ){
		$items = array();

		if ( isset( $this->menus[ $menu_slug ] ) ) {
			$items = array_filter( $this->menu_items, function ( $item ) use ( $menu_slug ) {
				return $item['menu'] == $menu_slug;
			} );
		}
		return $items;
	}

	/**
	 * Count the number of new menu items we are adding to an individual menu
	 *
	 * @param $menu_slug
	 *
	 * @return int
	 */
	private function count_menu_items( $menu_slug ){
		if ( ! isset( $this->menus[ $menu_slug ] ) ) {
			return 0;
		}

		$items = $this->get_menu_items( $menu_slug );

		return count( $items );
	}

	/**
	 * Helper to create item IDs
	 *
	 * @param $item
	 *
	 * @return int
	 */
	private function make_item_ID( $item ){
		return 1000000 + $item['order'] + $item['parent'];
	}

	/**
	 * Make a stored item array into a menu item object
	 *
	 * @param array $item
	 *
	 * @return mixed
	 */
	private function make_item_obj( $item ) {
		// generic object made to look like a post object
		$item_obj                   = new stdClass();
		$item_obj->ID               = ( $item['ID'] ) ? $item['ID'] : $this->make_item_ID( $item );
		$item_obj->title            = $item['title'];
		$item_obj->url              = $item['url'];
		$item_obj->menu_order       = $item['order'];
		$item_obj->menu_item_parent = $item['parent'];
		$item_obj->post_parent      = !empty( $item['post_parent'] ) ? $item['post_parent'] : '';

		// menu specific properties
		$item_obj->db_id            = $item_obj->ID;
		$item_obj->type             = !empty( $item['type'] ) ? $item['type'] : '';
		$item_obj->object           = !empty( $item['object'] ) ? $item['object'] : '';
		$item_obj->object_id        = !empty( $item['object_id'] ) ? $item['object_id'] : '';

		// output attributes
		$item_obj->classes          = $item['classes'];
		$item_obj->target           = '';
		$item_obj->attr_title       = '';
		$item_obj->description      = '';
		$item_obj->xfn              = '';
		$item_obj->status           = '';

		return $item_obj;
	}

	/**
	 * Menu items with the same menu_order property cause a conflict. This
	 * method attempts to provide each menu item with its own unique order value.
	 * Thanks @codepuncher
	 *
	 * @param $items
	 *
	 * @return mixed
	 */
	private function fix_menu_orders( $items ){
		$items = wp_list_sort( $items, 'menu_order' );

		for( $i = 0; $i < count( $items ); $i++ ){
			$items[ $i ]->menu_order = $i;
		}

		return $items;
	}
}
