<?php
/**
 * Tests for the widget in /includes/widget.php
 */
class KM_RPBT_Widget_Tests extends WP_UnitTestCase {

	/**
	 * Utils object to create posts with terms.
	 *
	 * @var object
	 */
	private $utils;


	/**
	 * Set up.
	 */
	function setUp() {
		parent::setUp();

		// Use the utils class to create posts with terms.
		$this->utils = new RPBT_Test_Utils( $this->factory );
	}

	function tearDown() {
		// use tearDown for WP < 4.0
		remove_filter( 'related_posts_by_taxonomy_widget_hide_empty', array( $this->utils, 'return_bool' ) );
		remove_filter( 'related_posts_by_taxonomy_widget_hide_empty', '__return_false' );
		parent::tearDown();
	}


	/**
	 * Test if the widget exists.
	 */
	function test_rpbt_widget_exists() {
		global $wp_widget_factory;

		$widget_class = 'Related_Posts_By_Taxonomy';
		$this->assertArrayHasKey( $widget_class, $wp_widget_factory->widgets );
	}


	/**
	 * Test if the widget_hide_empty filter is set to true (by default).
	 */
	function test_widget_hide_empty_filter_set_to_true() {
		$create_posts = $this->utils->create_posts_with_terms();
		$posts        = $create_posts['posts'];

		add_filter( 'related_posts_by_taxonomy_widget_hide_empty', array( $this->utils, 'return_bool' ) );
		$widget = new Related_Posts_By_Taxonomy( 'related-posts-by-taxonomy', __( 'Related Posts By Taxonomy', 'related-posts-by-taxonomy' ) );

		// run the widget
		ob_start();
		$args = array(
			'before_widget' => '<section>',
			'after_widget'  => '</section>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array( 'post_id' => $posts[0] );
		$widget->_set( 2 );
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertTrue( $this->utils->boolean  );
		$this->utils->boolean = null;
	}

	/**
	 * Test if the widget_hide_empty filter if set to false.
	 */
	function test_widget_hide_empty_filter_set_to_false() {
		$create_posts = $this->utils->create_posts_with_terms();
		$posts        = $create_posts['posts'];

		add_filter( 'related_posts_by_taxonomy_widget_hide_empty', '__return_false' );
		$widget = new Related_Posts_By_Taxonomy( 'related-posts-by-taxonomy', __( 'Related Posts By Taxonomy', 'related-posts-by-taxonomy' ) );

		// run the widget
		ob_start();
		$args = array(
			'before_widget' => '<section>',
			'after_widget'  => '</section>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array( 'post_id' => $posts[4] );
		$widget->_set( 2 );
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertContains( '<p>No related posts found</p>', $output );
	}


	/**
	 * Test output from widget.
	 *
	 * @depends KM_RPBT_Misc_Tests::test_create_posts_with_terms
	 */
	function test_rpbt_widget_output() {

		$create_posts = $this->utils->create_posts_with_terms();
		$posts        = $create_posts['posts'];

		$widget = new Related_Posts_By_Taxonomy( 'related-posts-by-taxonomy', __( 'Related Posts By Taxonomy', 'related-posts-by-taxonomy' ) );

		ob_start();
		$args = array(
			'before_widget' => '<section>',
			'after_widget'  => '</section>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);

		$instance = array( 'post_id' => $posts[0] );
		$widget->_set( 2 );
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertContains( '<h2>Related Posts</h2>', $output );
		$this->assertContains( '<section>', $output );
		$this->assertContains( '</section>', $output );

		// get post ids array and permalinks array
		$_posts     = get_posts( array( 'posts__in' => $posts, 'order' => 'post__in' ) );
		$ids        = wp_list_pluck( $_posts, 'ID' );
		$permalinks = array_map( 'get_permalink', $ids );

		// expected related posts are post 1,2,3
		$expected = <<<EOF
<section><h2>Related Posts</h2>
<ul>
<li><a href="{$permalinks[1]}" title="{$_posts[1]->post_title}">{$_posts[1]->post_title}</a></li>
<li><a href="{$permalinks[2]}" title="{$_posts[2]->post_title}">{$_posts[2]->post_title}</a></li>
<li><a href="{$permalinks[3]}" title="{$_posts[3]->post_title}">{$_posts[3]->post_title}</a></li>
</ul>
</section>
EOF;

		$this->assertEquals( strip_ws( $expected ), strip_ws( $output ) );
	}

}