<?php 
   $alternateType=false;
   $site_url='https://theysaidso.com';
   $cate_url='https://quotes.rest/qod.json?category=';
   $fetch_url='https://quotes.rest/qod/categories.json?language=en&detailed=true';  
   
   //Set Up Theme
   add_action('wp_enqueue_scripts', 'createStyles'); // Add Theme Stylesheet
   add_action('init', 'project_create_db', 1);
   add_action('init', 'create_quote_day_post');

   function createStyles(){
      wp_register_style('project', get_template_directory_uri() . '/style.css', array(), '1.0', 'all');
      wp_enqueue_style('project'); // Enqueue it!
   }
    
   //Create Our Database Table if one does not Exist
   function project_create_db() {
      global $wpdb;
      $table_name = $wpdb->prefix . "day_quote";
      $charset_collate = $wpdb->get_charset_collate();

      $sql = "CREATE TABLE IF NOT EXISTS $table_name (
         id bigint(20) NOT NULL AUTO_INCREMENT,
         time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
         categories VARCHAR(40) NOT NULL,
         quotes VARCHAR(21844) NOT NULL,
         images VARCHAR(5000) NOT NULL,
         authors VARCHAR(5000) NOT NULL,
         PRIMARY KEY id (id)
      ) $charset_collate;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);

      //Check Date for API Call
      check_datetime_for_quotes();

      //Create Post
      create_post_loop();
   }

      //Function to get the Quote Categories
      function get_database_categories(){
         global $wpdb;
         $array;
         $query = "SELECT * FROM `wp_day_quote`";
         $query_results = $wpdb->get_results($query);
         if (count($query_results)!=0){
            foreach($query_results as $row){
               $array[]=$row->categories;
            }
            return $array;
         }
      }

      //Function to get a single row based on category
      function get_database_category($cat){
         global $wpdb;
         $array;
         $query = "SELECT * FROM `wp_day_quote` WHERE `categories`= '$cat'";
         $query_results = $wpdb->get_row($query);
         if (count($query_results)!=0){
            $array['quotes']=$query_results->quotes;
            $array['images']=$query_results->images;
            $array['authors']=$query_results->authors;
            $array['categories']=$query_results->categories;
            return $array;
         }
      }

   //  Checks Datetime to be null OR if the current date is 
   //  the next day of whatever is saved in the Database.
   function check_datetime_for_quotes(){
      global $wpdb;
      $date = date("Y-m-d");
      $args;
      $category = '';
      $quote = '';
      $image = '';

      $checkIfTime = $wpdb->get_var( "SELECT time FROM wp_day_quote WHERE id = 1" );
      if ($checkIfTime == null || $date > date(strtotime($checkIfTime)) ){
         update_quotes_api();
      } 
   }

      // Determines if an entry exists and if it does then 
      // Update. if not, then insert a new entry into the database
      function update_quotes_database($args,$force=false){
         global $wpdb;
         $category = $args['categories'];
         $quote = $args['quotes'];
         $image = $args['images'];
         $author = $args['authors'];
         $query = "SELECT * FROM `wp_day_quote` WHERE `categories`= '$category'";
         $query_results = $wpdb->get_results($query);
         $new_date = date('Y-m-d 00:00:00',strtotime('+1 days'));
            
         if (count($query_results)==0){
            $wpdb->insert('wp_day_quote', array('time'=> $new_date,'categories'=> $category, 'quotes'=>$quote, 'authors'=>$author, 'images'=>$image));
         } else {
            if ($force) $wpdb->update('wp_day_quote', array('time'=> $new_date,'quotes'=>$quote, 'authors'=>$author, 'images'=>$image),array('categories'=>$category));
         }
      }

      //  Get the Quotes API and returns the data for the 
      //  Categories to store in the Database
      function update_quotes_api() {
         $html = '';
         $args;
         
         $base_url=$site_url;
         $url = $fetch_url;
         $json_url = fetch_JSON_Data($url);
         $json = json_decode($json_url,true);
         foreach($json as $repo){
            if (is_array($repo) && array_key_exists('categories',$repo)){
               foreach($repo['categories'] as $data){
                  //Save our Variables to an Array
                  $args['categories']=$data['name'];
                  $args['quotes']=$data['title'];
                  $args['images']=$base_url.$data['background'];
                  $args['authors']='';
                  //Pass Array to update the Quotes Database
                  update_quotes_category($args['categories']);
               }
            }
         }
      }

      //  Get the  API and returns the data for the 
      //  Categories to store in the Database
      function update_quotes_category($cat='inspire') {
         $html = '';
         $args;
         
         //$base_url='https://theysaidso.com';
         $curr_url = $cate_url.$cat;
         $json_url = fetch_JSON_Data($curr_url);
         $json = json_decode($json_url,true);
         
         foreach($json as $repo){
            if (is_array($repo) && array_key_exists('quotes',$repo)){
               foreach($repo['quotes'] as $data){
                  //Save our Variables to an Array
                  $args['categories']=$data['category'];
                  $args['quotes']=$data['quote'];
                  $args['images']=$data['background'];
                  $args['authors']=$data['author'];
                  //Pass Array to update the Quotes Database
                  update_quotes_database($args,true);
               }
            }
         }
      }

         function fetch_JSON_Data($url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
         }

   //Create Loop for post Creation - See Below
   function create_post_loop(){
      $args=get_database_categories();
      for ($i = 0; $i < count($args); $i++) {
          $str=$args[$i];
          create_quote_day_post($str);
      }
   }

      //Creating Custom Quote Day Posts
      function create_quote_day_post($category){
         $ucstr=ucwords($category);

         if (strlen($category)>0 && strlen($category)<20){
            register_taxonomy_for_object_type('category', $category); // Register Taxonomies for Category
            register_post_type($category, // Register Custom Post Type
               array(
               'labels' => array(
                   'name' => __( $ucstr.' Post', $category), // Rename these to suit
                   'singular_name' => __($ucstr, $category),
                   'add_new' => __('Add New '.$ucstr, $category),
                   'add_new_item' => __('Add New '.$ucstr, $category),
                   'edit' => __('Edit', $category),
                   'edit_item' => __('Edit '.$ucstr, $category),
                   'new_item' => __('New '.$ucstr, $category),
                   'view' => __('View '.$ucstr, $category),
                   'view_item' => __('View '.$ucstr, $category),
                   'search_items' => __('Search '.$ucstr, $category),
                   'not_found' => __('No '.$ucstr, $category),
                   'not_found_in_trash' => __('No '.$ucstr.' found in Trash', $category)
               ),
               'public' => true,
               'hierarchical' => true,
               'has_archive' => true,
               'supports' => array(
                  'title',
                  'editor',
                  'excerpt',
                  'thumbnail',
               ), // Go to Dashboard Custom HTML5 Blank post for supports
               'can_export' => true,
               'taxonomies' => array(
                  'category'
               )
            ));
         }
      }
        
?>