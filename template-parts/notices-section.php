<?php
$postID = get_the_ID();
$location = get_the_title($postID);
$location = preg_replace('/[^A-Za-z0-9-]+/', '-', $location);
$types = get_terms(array('taxonomy' => 'notification_type'));
$num_types = count($types);
// query notifications post type
$args = array(
    'post_type'     =>      'notification',
    'post_status'   =>      'publish',
);
$tax_query[0] = array( 'taxonomy' => 'location', 'field' => 'slug', 'terms' => $location);
?>
<section class="notices">
    <div class="container flex row">
        <?php
        foreach($types as $type) {
            $tax_query[1] = array( 'taxonomy' => 'notification_type', 'terms' => $type);
            $args['tax_query'] = $tax_query;
            $notices = new WP_Query($args);
            ?>
            <div class="notice_type item_1_<?php echo $num_types; ?>">
                <?php
                if($notices->found_posts):
                    foreach($notices->posts as $notice):
                        $nID = $notice->ID;
                        $this_type = wp_get_post_terms($nID, 'notification_type', 'name');
                        $type_class = $this_type[0]->slug;
                        $type_name = $this_type[0]->name;
                        $title = get_the_title($nID);
                        $content = get_post_field('post_content', $nID);
                        $expiration = get_post_meta($nID, 'expiration_date', true);
                        $date = new DateTime($expiration);
                        $now = new DateTime();
                        if($date > $now) {
                        ?>
                        <div class="notice <?php echo $type_class; ?>">
                            <div class="dte-accordion-title flex col" style="cursor:pointer;">
                                <div class="flex row type-title">
                                    <?php echo $type_name; ?>
                                    <i class="icon-angle-up"></i>
                                </div>
                                <div class="notif-title"><h3><?php echo $title; ?></h3></div>
                            </div>
                            <div class="dte-accordion-content" style="display:none;">
                                <p><?php echo $content; ?></p>
                            </div>
                        </div><!-- /.notice -->
                        <?php
                        } else {
                            $update_post = array(
                                'post_type' => 'notification',
                                'ID' => $nID,
                                'post_status' => 'archived'
                            );
                            wp_update_post($update_post);
                        }
                    endforeach;
                endif;
            ?>
            </div><!-- /.notice_type -->
            <?php
        }
        ?>
    </div><!-- /.container -->
</section><!-- /.notices -->