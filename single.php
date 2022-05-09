<?php /*Template Page: Category page*/
wp_head();

$post = get_queried_object();
$postType = get_post_type_object(get_post_type($post));
$post_name = '';
if ($postType) {
    $post_name = esc_html($postType->labels->singular_name);
};
$quote = get_database_category($post_name);
?>
    <div class='banner'>
        <img src="<?php echo $quote['images']; ?>">
        <div class='header_text'>
            <h1><?php echo $quote['quotes']; ?></h1>
            <h4>–<?php echo $quote['authors']; ?></h4>
        </div>
    </div>
    <div class='container'>
    <div class='sidebar'>
        <ul>
        <?php
            $args=get_database_categories();
            for ($i = 0; $i < count($args); $i++) {
                 $str=$args[$i];
                 echo "<li><a href='".get_post_type_archive_link( $str )."'><h4>".strtoupper($str)."<h4></a></li>";
            }
        ?>
        </ul>
    </div>
    <div class='main'>
        <h2>Post Type <?php echo $post_name; ?></h2>    
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

        <h3><?php the_title(); ?></h3>
        <p><?php echo get_the_content(); ?></p>
        
        <?php endwhile; ?>
        <?php endif; ?>
    <div>
    </div>
<?php wp_footer();?>