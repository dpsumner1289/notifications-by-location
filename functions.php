<?php
function show_notices(){
	get_template_part('template-parts/notices', 'section');
}
add_shortcode('show_notices', 'show_notices');	