<?php
/**
 * The template for displaying the blogpage.
 * Template name: Blogpage
 *
 * @package storefront
 */

get_header(); ?>

	<div id="blog_primary" class="content-area">
		<main id="main" class="site-main" role="main">
            <div class="blog_banner">
                <div class="banner_space">
                </div>
                <div class="banner_header">
                    <h1>BLOG</h1>
                </div>
                <div class="banner_image">
                    <img width="92" height="62" src="/wp-content/uploads/2020/06/page2image5811056.png" class="attachment-large size-large" alt="">
                </div>
            </div>
            <section id="content" style="width: 100%;">
                <div class="post-content">
                    <div class="blog_container">
                        <?php
                            $loop = new WP_Query( array(
                                'order' => 'ASC',
                                'posts_per_page' => '6'
                            ) );

                            $index = 0;
                        ?>
                        <div class="blog_row">
                        <?php if ( have_posts() ) : while ( $loop->have_posts()) : $loop->the_post(); ?> 
                            <?php
                                if($index % 3 == 0) : 
                            ?>
                                </div>
                                <div class="blog_row">
                            <?php
                                endif;
                            ?>

                                <div class="blog_col-md-3">
                                    <div class="blog_element image">
                                        <a href="<?php the_permalink(); ?>"><?php echo the_post_thumbnail(); ?></a>
                                    </div>
                                    <div class="blog_element title">
                                        <a href="<?php the_permalink(); ?>"><h3><?php the_title(); ?></h3></a>
                                    </div>
                                    <div class="blog_element content">
                                        <?php echo the_content(); ?>
                                    </div>
                                    <div class="blog_element read_more">
                                        <a href="<?php the_permalink(); ?>" class="read_more">ReadMore</a>
                                    </div>
                                </div>
                                
                            <?php $index++; ?>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
	        </section>
		</main><!-- #main -->
	</div><!-- #primary -->
<?php
get_footer();
