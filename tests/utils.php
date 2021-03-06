<?php
class RPBT_Test_Utils {

	private $factory;
	public $boolean;

	function __construct( $factory = null ) {
		$this->factory = $factory;
	}


	/**
	 * Creates 5 posts and assigns terms from two taxonomies.
	 *
	 * @param string  $post_type Post type.
	 * @param string  $tax1      First taxonomy name.
	 * @param string  $tax2      Second taxonomy name
	 * @return array             Array with post ids and term ids from both taxonomies
	 */
	function create_posts_with_terms( $post_type = 'post', $tax1 = 'post_tag', $tax2 = 'category' ) {

		$posts = $this->create_posts( $post_type, 5 );

		// bail if no posts were created
		if ( count( $posts ) !== 5 ) {
			return array();
		}

		// create terms taxonomy 1
		$tax1_terms = $this->assign_taxonomy_terms( $posts, $tax1, 1 );

		// create terms taxonomy 2
		$tax2_terms = $this->assign_taxonomy_terms( $posts, $tax2, 2 );

		return compact( 'posts', 'tax1_terms', 'tax2_terms' );
	}


	/**
	 * Creates posts with decreasing timestamps a day apart.
	 *
	 * @param string  $post_type      Post type.
	 * @param integer $posts_per_page How may posts to create.
	 * @return array                  Array with post ids.
	 */
	function create_posts( $post_type = 'post', $posts_per_page = 5 ) {

		// create posts with decreasing timestamp
		$posts = array();
		$now = time();
		foreach ( range( 1, $posts_per_page ) as $i ) {
			$this->factory->post->create(
				array(
					'post_date' => date( 'Y-m-d H:i:s', $now - ( $i * DAY_IN_SECONDS ) ),
					'post_type' => $post_type
				) );
		}

		// Return posts by desc date.
		$posts = get_posts(
			array(
				'posts_per_page' => 5,
				'post_type'      => $post_type,
				'fields'         => 'ids',
				'order'          => 'DESC',
				'orderby'        => 'date'
			) );

		return $posts;
	}


	/**
	 * Assings terms to posts.
	 *
	 * @param array   $posts    Array with 5 post ids.
	 * @param string  $taxonomy Taxonomy name.
	 * @return array            Array with created term ids.
	 */
	function assign_taxonomy_terms( $posts, $taxonomy, $schema = 1 ) {
		// create terms taxonomy 1
		$tax_terms = $this->factory->term->create_many( 5, array( 'taxonomy' => $taxonomy ) );

		// bail if no terms were created
		if ( ( count( $tax_terms ) !== 5 ) && ( count( $posts ) !== 5 ) ) {
			return array();
		}

		if ( $schema === 1 ) {
			// assign terms to posts
			$post_terms =  array(
				array( $tax_terms[0], $tax_terms[1], $tax_terms[2] ), // post 0
				array( $tax_terms[2] ),                               // post 1
				array( $tax_terms[0], $tax_terms[2] ),                // post 2
				array( $tax_terms[3], $tax_terms[4], $tax_terms[2] ), // post 3
				array(),                                              // post 4
			);
		}

		if ( $schema === 2 ) {
			// assign terms to posts
			$post_terms = array(
				array( $tax_terms[4] ),                // post 0
				array( $tax_terms[4], $tax_terms[3] ), // post 1
				array(),                               // post 2
				array( $tax_terms[3] ),                // post 3
				array( $tax_terms[2] ),                // post 4
			);

		}

		foreach ( $post_terms as $key => $terms ) {
			if ( !empty( $terms ) ) {
				wp_set_post_terms ( $posts[ $key ], $terms, $taxonomy );
			}
		}

		return $tax_terms;
	}

	function return_bool( $bool ) {
		return $this->boolean = $bool;
	}

	function get_cache_meta_key() {
		global $wpdb;
		$cache_query = "SELECT $wpdb->postmeta.meta_key FROM $wpdb->postmeta WHERE meta_key LIKE '_rpbt_related_posts%' LIMIT 1";
		return $wpdb->get_var( $cache_query );
	}

	function create_image() {
		add_theme_support( 'post-thumbnails' );

		// create attachment
		$filename = ( DIR_TESTDATA.'/images/test-image.jpg' );
		$contents = file_get_contents( $filename );
		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		$this->assertTrue( empty( $upload['error'] ) );

		$attachment = array(
			'post_title' => 'Post Thumbnail',
			'post_type' => 'attachment',
			'post_mime_type' => 'image/jpeg',
			'guid' => $upload['url']
		);

		return wp_insert_attachment( $attachment, $upload['file'] );
	}

}