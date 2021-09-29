<?php

add_action('init', 'eco_register_custom_style');

function eco_register_custom_style (){
    
    wp_register_style(
		'astra.css',
		'/wp-content/themes/storefront-child/assets/css/astra/style.min747d.css',
		null,
		'1.0.0'
	);

	wp_register_style(
		'global',
		'/wp-content/themes/storefront-child/assets/css/global.css',
		'1.0.0',
		false
	);

    wp_register_style(
		'custom-static',
		'/wp-content/themes/storefront-child/assets/css/custom-static.css',
		'1.0.0',
		false
	);

	wp_register_style(
		'woocommerce.min747d.css',
		'/wp-content/themes/storefront-child/assets/css/woocommerce/woocommerce.min747d.css',
		null,
		'1.0.0'
	);

	wp_register_style(
		'custom.css',
		'/wp-content/themes/storefront-child/assets/css/custom.css',
		null,
		'1.0.2'
	);

	wp_register_style(
		'product.css',
		'/wp-content/themes/storefront-child/assets/css/product.css',
		null,
		'1.0.0'
	);

	wp_register_style(
		'custom-cart',
		'/wp-content/themes/storefront-child/assets/css/custom-cart.css',
		null,
		'1.0.0'
	);

	wp_register_style(
		'custom-blog',
		'/wp-content/themes/storefront-child/assets/css/custom-blog.css',
		null,
		'1.0.0'
	);
}

/** 
 * custom style output start
 */
add_action('wp_head', 'home_page_styles');

function home_page_styles() {
	if(!is_admin()):
		wp_enqueue_style('woocommerce.min747d.css');
		wp_enqueue_style('custom-static');
		wp_enqueue_style('custom-cart');
		wp_enqueue_style('custom-blog');
	endif;
}

add_action('wp_head', 'astra_enqueue_styles');

function astra_enqueue_styles() {	
	$page = get_the_title();
	
	if($page != "Home" && $page != "About" && $page != "Contact" && $page != "What is Biomaster?" && $page != "Blog") {
		wp_enqueue_style('global');
		wp_enqueue_style('astra');
		wp_enqueue_style('custom.css');
		wp_enqueue_style('product.css');
	}
}

if ( ! function_exists( 'wc_get_rating_html_custom' ) ) {

	function wc_get_rating_html_custom( $rating, $count = 0 ) {
		$html = '';

		/* translators: %s: rating */
		$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );
		$html  = '<div class="star-rating" role="img" aria-label="' . esc_attr( $label ) . '">' . wc_get_star_rating_html( $rating, $count ) . '</div>';

		return apply_filters( 'woocommerce_product_get_rating_html', $html, $rating, $count );
	}
}
// display breadcrumb in storefront child theme.
add_action('storefront-child_before_content', 'woocommerce_breadcrumb', 10);

// remove actions in storefront child theme
add_action('init', 'remove_storefront_sorting_child');

if ( ! function_exists( 'remove_storefront_sorting_child' ) ) {

	function remove_storefront_sorting_child( ) {
		// remove top-pagination in products page
		remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );

		// remove all of bottom except bottom-pagination in products page
		remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper', 9 );
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper_close', 31 );

		// remove related on single-product page
		// remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

		// remove storefront-product-pagination on single-product page
		remove_action( 'woocommerce_after_single_product_summary', 'storefront_single_product_pagination', 30 );
	}
}

add_action('woocommerce_shop_loop_item_category_title', 'get_the_term_list_custom');

if ( ! function_exists( 'get_the_term_list_custom' ) ) {

	function get_the_term_list_custom( $id, $taxonomy = 'product_cat') {
		$terms = get_the_terms( $id, $taxonomy );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( empty( $terms ) ) {
			return false;
		}
		$categories = '';
		foreach ( $terms as $term ) {
			$categories .= $term->name . ', ';
		}
		
		echo '<h2 class="woo-product-category" rel="tag">' . substr($categories, 0 , strlen($categories) - 2) . '</h2>';
	}
}


if( !function_exists('custom_single_post_page')) {
	function custom_single_post_page() {
		//category name
		remove_action( 'storefront_single_post_bottom', 'storefront_post_taxonomy', 5 );
		//remove button
		remove_action( 'storefront_single_post_bottom', 'storefront_edit_post_link', 5 );
		//nav link
		remove_action( 'storefront_single_post_bottom', 'storefront_post_nav', 10 );
	}
}

add_action('init', 'custom_single_post_page'); 

if ( !function_exists('custom_social_sharing')) {
	function custom_social_sharing () {
		?>
			<div class="social_sharing_box">
				<h4>Share This Story, Choose Your Platform!</h4>
				<div class="social_icons">
					<a class="facebook fab fa-facebook-f" href="https://www.facebook.com/sharer.php?"></a>
					<a class="twitter fab fa-twitter" href="https://twitter.com/share?"></a>
					<a class="linkedin fab fa-linkedin-in" href="https://www.linkedin.com/shareArticle?"></a>
					<a class="reddit fab fa-reddit-alien" href="http://reddit.com/submit?url=http://localhost:8085/fab-little-bag-and-eco-bin/"></a>
					<a class="whatsapp fab fa-whatsapp" href="https://api.whatsapp.com/send?text"></a>
					<a class="tumbir fab fa-tumblr" href="http://www.tumblr.com/share/link?"></a>
					<a class="pinterest fab fa-pinterest-p" href="http://pinterest.com/pin/create/button/?url="></a>
				</div>
			</div>

		<?php
	}
}

add_action('storefront_post_content_after', 'custom_social_sharing');

if ( !function_exists('custom_related_posts')) {
	function custom_related_posts () {
	?>
		<h2>Related Posts</h2>
	<?php

		$id = wp_get_post_terms( get_the_ID() );

		$cur_post_id = get_the_id();

		$args = array(
				'tag__in' => $id,
				'post__not_in' =>array( $post->ID ), 
				'posts_per_page'=> 5,
				'ignore_sticky_posts' => 1 
		); 


		$relatedPosts = new WP_Query( $args );

			if( $relatedPosts->have_posts() ) {

				$post_index = 0;
			?>
			<div class="blog_row">
			<?php
				//loop through related posts based on the tag
				while ( $relatedPosts->have_posts() ) : 
				
					$relatedPosts->the_post(); 

					$comment_count = $relatedPosts->posts[$post_index]->comment_count;

					$post_date = $relatedPosts->posts[$post_index]->post_date;

					$temp = $relatedPosts->posts[$post_index]->ID;

					if ( $temp != $cur_post_id) :
				?>

					<div class="blog_col-md-3 related_post">
							<div class="blog_element image">
									<a href="<?php the_permalink(); ?>"><?php echo the_post_thumbnail(); ?></a>
							</div>
							<div class="blog_element title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</div>
							<div class="blog_element date_comment">
								<span><?php echo $post_date?></span> | 
								<a href="<?php the_permalink(); ?>"><?php echo $comment_count;?> Comments</a>
							</div>
					</div>

					<?php
				endif;

				$post_index++;
				endwhile;
			} 
	?> 
		</div>
	<?php
	
	}
}

add_action('storefront_post_content_after', 'custom_related_posts');

add_filter('wpcf7_skip_spam_check', '__return_true');

