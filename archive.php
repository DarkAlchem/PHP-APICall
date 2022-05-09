<?php wp_head();
    $word_limit=50;
    $obj = get_queried_object();
    $archive_name = $obj->name;
    $quote = get_database_category($archive_name);
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
        <h2>Post Type <?php echo ucwords($archive_name); ?></h2>
        <?php if (have_posts()): while (have_posts()) : the_post(); 
            $trimmed_content = wp_trim_words(get_the_content(), $word_limit, '... [<a href="' . get_permalink() . '">Read More</a>]'); ?>
            <article>
                <h3><?php echo the_title(); ?></h3>
                <p><?php echo $trimmed_content; ?></p>
            </article>
        <?php endwhile; ?>

        <?php else: ?>

            <!-- article -->
            <article>
                <h2><?php echo 'No Post Currently Available.'; ?></h2>
            </article>
            <!-- /article -->

        <?php endif; ?>
        
    <div>
    </div>
<?php wp_footer();?>