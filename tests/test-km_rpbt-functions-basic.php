<?php
class Related_Posts_by_Taxonomy_Tests extends WP_UnitTestCase {

	private $test_output = 1; // default = 1;
	private $utils;

	function setUp() {
		parent::setUp();
		$this->utils = new RPBT_Test_Utils( $this->factory );
	}

	function test_utils() {
		$create_posts = $this->utils->create_posts_with_terms();
		$this->assertCount( 5, $create_posts['posts'] );
		$this->assertCount( 5, $create_posts['tax1_terms'] );
		$this->assertCount( 5, $create_posts['tax2_terms'] );
	}

	function test_output() {
		if ( !$this->test_output ) {
			fwrite( STDERR, "NOTICE: some tests are disabled\n\n" );
		}
	}

	/**
	 * test if default taxonomies exist
	 */
	function test_get_post_taxonomies() {
		$this->assertEquals( array( 'category', 'post_tag', 'post_format' ), get_object_taxonomies( 'post' ) );
	}


	/**
	 * test output from get_posts_by_author_sql()
	 */
	function test_get_posts_by_author_sql() {
		$where  = get_posts_by_author_sql( 'post' );
		$this->assertTrue( (bool) preg_match( "/post_type = 'post'/", $where ) );
	}

	/**
	 * test output from functions
	 */
	function test_empty_output() {

		if ( !$this->test_output ) {
			return;
		}

		$create_posts = $this->utils->create_posts_with_terms();
		$posts = $create_posts['posts'];

		$args =  array( 'fields' => 'ids' );
		$taxonomies = array( 'category', 'post_tag' );

		ob_start();

		// these functions should not output anything.
		$_posts = km_rpbt_related_posts_by_taxonomy( $posts[0], $taxonomies, $args );
		$_template = km_rpbt_related_posts_by_taxonomy_template( 'excerpts' );
		$_ids = km_rpbt_related_posts_by_taxonomy_validate_ids( '1,2,1' );

		// shortcode has its own test for output
		// gallery has its own test for output

		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}


	/**
	 * test if template was found
	 */
	function test_km_rpbt_related_posts_by_taxonomy_template() {

		$path = pathinfo( dirname(  __FILE__  ) );

		$template = km_rpbt_related_posts_by_taxonomy_template( 'excerpts' );
		$path1 = $path['dirname'] . '/templates/related-posts-excerpts.php';
		$this->assertEquals( $path1 , $template );

		// should default to links template
		$template = km_rpbt_related_posts_by_taxonomy_template( 'not a template' );
		$path2 = $path['dirname'] . '/templates/related-posts-links.php';
		$this->assertEquals( $path2 , $template );
	}


	/**
	 * test output from shortcode
	 */
	function test_shortcode_output() {

		if ( !$this->test_output ) {
			return;
		}

		$create_posts = $this->utils->create_posts_with_terms();
		$posts = $create_posts['posts'];

		$_posts = get_posts( array( 'posts__in' => $posts, 'order' => 'post__in' ) );

		$expected = <<<EOF
Related Posts
<ul>
<li><a href="http://example.org/?p={$_posts[1]->ID}" title="{$_posts[1]->post_title}">{$_posts[1]->post_title}</a></li>
<li><a href="http://example.org/?p={$_posts[2]->ID}" title="{$_posts[2]->post_title}">{$_posts[2]->post_title}</a></li>
<li><a href="http://example.org/?p={$_posts[3]->ID}" title="{$_posts[3]->post_title}">{$_posts[3]->post_title}</a></li>
</ul>
EOF;
		ob_start();
		echo do_shortcode( '[related_posts_by_tax post_id="' . $posts[0] . '"]' );
		$shortcode = ob_get_clean();

		$this->assertEquals( strip_ws( $expected ), strip_ws( $shortcode ) );
	}

	/**
	 * test output from gallery
	 */
	function test_gallery_output() {

		if ( !$this->test_output ) {
			return;
		}

		$create_posts = $this->utils->create_posts_with_terms();
		$posts = $create_posts['posts'];
		$related_post = get_post( $posts[0] );

		// adds fake image <img>, otherwhise it will return nothing
		add_filter( 'related_posts_by_taxonomy_post_thumbnail', array( $this, 'add_image' ) );

		$args = array(
			'itemtag'    => 'dl',
			'icontag'    => 'dt',
			'captiontag' => 'dd',
		);

		ob_start();
		echo km_rpbt_related_posts_by_taxonomy_gallery( $args, array( $related_post )  );
		$gallery = ob_get_clean();

		$expected = <<<EOF
<div id='gallery-1' class='gallery related-gallery related-galleryid-0 gallery-columns-3 gallery-size-thumbnail'><dl class='gallery-item'>
<dt class='gallery-icon '>
<a href='http://example.org/?p={$related_post->ID}' title='{$related_post->post_title}'><img></a>
</dt>
<dd class='wp-caption-text gallery-caption'>
{$related_post->post_title}
</dd></dl>
<br style='clear: both' />
</div>
EOF;

		$this->assertEquals( strip_ws( $expected ), strip_ws( $gallery )  );
	}

	/**
	 * adds fake image
	 */
	function add_image( $image ) {
		return '<img>';
	}

}