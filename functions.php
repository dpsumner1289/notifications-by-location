<?php
	// theme functions
	require('inc/theme-functions.php');

	// custom image sizes
	if(function_exists('add_image_size')){
		add_image_size('medium-cropped', 360, 270, true); //(cropped)
		add_image_size('medium-portrait-cropped', 225, 300, true); //(cropped)
		add_image_size('large-portrait-cropped', 300, 400, true); //(cropped)
		add_image_size('medium-thumb', 300, 300, true); //(cropped)
	}   

	// add ACF options pages
	if(function_exists('acf_add_options_page')) { 
		acf_add_options_page();
		acf_add_options_sub_page('Header');
		acf_add_options_sub_page('Footer');
		acf_add_options_sub_page('404 Page');
		// acf_add_options_sub_page('Supply Chain');		
	}

	// enqueue fancybox
	function dte_header_scripts(){
		wp_enqueue_style('fancybox', get_template_directory_uri() . '/inc/js/fancybox-3.0/dist/jquery.fancybox.min.css');
		wp_enqueue_script('fancybox', get_template_directory_uri() . '/inc/js/fancybox-3.0/dist/jquery.fancybox.min.js', array('jquery'));
	}
	add_action('init', 'dte_header_scripts');
	

	// custom ACF Color palette
	add_action('admin_enqueue_scripts', 'acf_custom_colors');
	function acf_custom_colors(){
		wp_enqueue_script('acf-custom-colors', get_template_directory_uri() . '/js/acf-custom-colors.js', 'acf-input', '1.0', true);
	}

	// ACF add google api key
	function my_acf_init(){
		acf_update_setting('google_api_key', 'AIzaSyDgRZB20JgCArP8ANNKezqjdm4jTiIb1to');
	 	return ;
	};
	add_action('acf/init', 'my_acf_init');
	
	// First, create a function that includes the path to your favicon
	function add_favicon() {
	  	$favicon_url = get_stylesheet_directory_uri() . '/images/favicon.png';
		echo '<link rel="shortcut icon" href="' . $favicon_url . '" />';
	}
	  
	// Now, just make sure that function runs when you're on the login page and admin pages  
	add_action('admin_head', 'add_favicon');

	// place custom functions here
	function geocode_address($post_id){
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){return;}
		// Check post type
		if(isset($_POST['post_type']) && 'projects' == $_POST['post_type']){		
			// Check the user's permissions.
			if(!current_user_can('edit_page', $post_id)){return;}
			if(!current_user_can('edit_post', $post_id)){return;}
			// Build address string from meta
			$address = get_post_meta($post_id, 'street_address', true) . ' ' . get_post_meta($post_id, 'city', true) . ' ' . get_post_meta($post_id, 'state', true) . ' ' . get_post_meta($post_id, 'zipcode', true);
			$string = str_replace (" ", "+", urlencode($address));
			$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $details_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = json_decode(curl_exec($ch), true);
			// If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
			if ($response['status'] != 'OK') {
				//$error = new WP_Error();
				//$error->add(__('geocode_fail', 'Address failed to geocode, please check that it is entered correctly and try again.', 'ctgfr'));
				return null;
			}
		    $longitude = $response['results'][0]['geometry']['location']['lng'];
		    $latitude = $response['results'][0]['geometry']['location']['lat'];
			$latlng = '('.$latitude.','.$longitude.')';
		    update_post_meta($post_id, 'geocoded_address', $latlng);
		}
	}
	add_action('save_post', 'geocode_address', 99);			

	// Add accordion functionality to the_content
	// [dte type="title|content"][/dte]
	// requires 2 shortcodes to function. 1 on a trigger and 1 on the content.
	// @param type="title|content"
	function dte_accordion($atts, $content = ''){
		if(isset($atts['type']) && $atts['type'] == 'title'){
			$output = '<div class="dte-accordion-title" style="cursor:pointer;">'.trim(do_shortcode($content)).'&nbsp;&nbsp;<i class="icon-angle-up"></i></div>';
		}elseif(isset($atts['type']) && $atts['type'] == 'content'){
			$output = '<div class="dte-accordion-content" style="display:none;">'.trim(do_shortcode($content)).'<span class="clearer"></span></div>';
		}
		return $output;
	}
	add_shortcode('dte', 'dte_accordion');	

	function show_notices(){
		get_template_part('template-parts/notices', 'section');
	}
	add_shortcode('show_notices', 'show_notices');	

	function dte_accordion_js(){
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$(".dte-accordion-title").on("click",function(){
					$("i", this).toggleClass("icon-angle-down icon-angle-up");
					$(this).nextAll('.dte-accordion-content').first().stop().slideToggle("fast");
				});
			});
		</script>
		<?php
	}
	add_action('wp_head', 'dte_accordion_js');

	//////////////// THINGS HAVE CHANGED!! REDO THIS!! ///////////////
	// dynamic supply chain form notifications
	function send_supply_chain_to($notification, $form, $entry) {
		// get hidden field
		$project_types = $entry['5'];

		// get and set default send to
		$contacts = get_field('p_i_group_contact', 'option');
		$send_to = $contacts[0]['email'];

		// check for industrial energy
		if(strpos($project_types,'industrial-energy') !== false){
			$project_type = 'industrial-energy';	
			if(strpos($project_types,'on-site-energy-projects') !== false){
				$project_type = 'on-site-energy-projects';	
			}else if(strpos($project_types,'steel-related') !== false){
				$project_type = 'steel-related';
			}
			$contacts = get_field('industrial_energy_contact', 'option');
			if($contacts){
				foreach($contacts as $contact){
					if($contact['project_type']->slug == $project_type){
						$send_to = $contact['email'];
					}
				}
			}
		}		

		// check for renewable energy
		if(strpos($project_types,'renewable-energy') !== false){
			if(strpos($project_types,'wastewood') !== false){
				$project_type = 'wastewood';	
			}else if(strpos($project_types,'landfill-gas') !== false){
				$project_type = 'landfill-gas';
			}
			if($contacts){
				$contacts = get_field('renewable_energy_contact', 'option');
				foreach($contacts as $contact){
					if($contact['project_type']->slug == $project_type){
						$send_to = $contact['email'];
					}
				}
			}
		}		

		// check for environmental controls
		if(strpos($project_types,'environmental-controls') !== false){
			$contacts = get_field('environmental_controls_contact', 'option');
			if($contacts){
				foreach($contacts as $contact){
					if($contact['project_type']->slug == $project_type){
						$send_to = $contact['email'];
					}
				}
			}
		}

		// set who the notification goes to
		$notification['to'] = $send_to;
		return $notification;
	}
	//add_filter("gform_notification_2", "send_supply_chain_to", 10, 3);

	// Register Custom Status
	function jc_custom_post_status(){
	     register_post_status('archived', array(
	          'label'                     => _x( 'Archived', 'projects' ),
	          'public'                    => true,
	          'show_in_admin_all_list'    => false,
	          'show_in_admin_status_list' => true,
	          'label_count'               => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' )
		 ) );
		 register_post_status('archived', array(
			'label'                     => _x( 'Archived', 'notification' ),
			'public'                    => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' )
	   ) );
	}
	add_action( 'init', 'jc_custom_post_status' );

	
	function jc_append_post_status_list(){
	     global $post;
	     $complete = '';
	     $label = '';
	     if($post->post_type == 'projects' || $post->post_type == 'notifications'){
	          if($post->post_status == 'archived'){
	               $complete = ' selected="selected"';
	               $label = '<span id="post-status-display"> Archived</span>';
	          }
	          echo '
	          <script>
	          jQuery(document).ready(function($){
	               $("select#post_status").append("<option value=\"archived\" '.$complete.'>Archived</option>");
	               $(".misc-pub-section label").append("'.$label.'");
	          });
	          </script>
	          ';
	     }
	}
	add_action('admin_footer-post.php', 'jc_append_post_status_list');

	function jc_append_post_status_bulk_edit() {
		echo '
		<script>
			jQuery(document).ready(function($){
				$(".inline-edit-status select ").append("<option value=\"archived\">Archived</option>");
			});
		</script>
		';
	}
	add_action( 'admin_footer-edit.php', 'jc_append_post_status_bulk_edit' );


	// add embed container to iframes
	function embed_container_filter($value, $post_id = null, $field = null){
        $content = preg_replace('/(<iframe.*?<\/iframe>)/','<div class="embed-container">$1</div>',$value);
	    return $content;
	}

	// acf/load_field - filter for every field
	if(!is_admin()){
		// add_filter('acf/load_value', 'embed_container_filter',10,3);
		add_filter('the_content', 'embed_container_filter',10,1);
	}
	
	// add option to hide form field labels in Gravity Forms
	add_filter( 'gform_enable_field_label_visibility_settings', '__return_true' );

?>