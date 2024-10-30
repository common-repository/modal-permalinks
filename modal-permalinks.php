<?php
/**
 * Plugin Name: Modal Permalinks
 * Plugin URI: 
 * Description: This plugin opens a post/page in modal window using the post/page permalink in shortcode.
 * Version: 0.0.3
 * Author: George Lazarou
 * Author URI: http://georgelazarou.info
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/*  Copyright 2014  George Lazarou  (email : info@georgelazarou.info)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/* Load the textdomain */

function modalPermalinks_loadTextdomain() {
    
	load_plugin_textdomain('modal_permalinks', false, 
                           dirname(plugin_basename(__FILE__)));
    
}
add_action('init', 'modalPermalinks_loadTextdomain');


/* On activation */

function modalPermalinks_activate() {

    // For Single site
    if (!is_multisite()) {
        add_option('modalPermalinksBootstrapModal', '1');
        add_option('modalPermalinksModalWidth', '600');
    }
    // For Multisite
    else {
        // For regular options.
        global $wpdb;
        $blog_ids         = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();
        foreach ($blog_ids as $blog_id) 
        {
            switch_to_blog($blog_id);
            add_option('modalPermalinksBootstrapModal', '1');
            add_option('modalPermalinksModalWidth', '600');
        }
        switch_to_blog($original_blog_id);

        // For site options.
        add_site_option('modalPermalinksBootstrapModal', '1');
        add_site_option('modalPermalinksModalWidth', '600');
    }
    
}
register_activation_hook(__FILE__, 'modalPermalinks_activate');


/* On deactivation */

function modalPermalinks_deactivate() {
    
    wp_dequeue_script('modal-bootstrapJS');
    wp_dequeue_style('modal-bootstrapCSS');
    
}
register_deactivation_hook(__FILE__, 'modalPermalinks_deactivate');


/* On uninstallation */

function modalPermalinks_uninstall() {

    // For Single site
    if (!is_multisite()) {
        delete_option('modalPermalinksBootstrapModal');
        delete_option('modalPermalinksModalWidth');
    } 
    // For Multisite
    else {
        // For regular options.
        global $wpdb;
        $blog_ids         = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();
        foreach($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            delete_option('modalPermalinksBootstrapModal');
            delete_option('modalPermalinksModalWidth');
        }
        switch_to_blog($original_blog_id);

        // For site options.
        delete_site_option('modalPermalinksBootstrapModal');
        delete_site_option('modalPermalinksModalWidth');
        
    }
}
register_uninstall_hook(__FILE__, 'modalPermalinks_uninstall');


/* Register scripts */

function modalPermalinks_loadScripts() {
    
    $jQueryEnqueued                = wp_script_is('jquery', $list = 'enqueued');
    if($jQueryEnqueued == false)
        wp_enqueue_script('jquery');
    
    $modalPermalinksBootstrapModal = get_option('modalPermalinksBootstrapModal');
    $bootstrapModalJSEnqueued      = wp_script_is('modal-bootstrapJS',
                                                  $list = 'enqueued');
    $bootstrapModalCSSEnqueued     = wp_style_is('modal-bootstrapCSS',
                                                 $list = 'enqueued');
    if($modalPermalinksBootstrapModal == '1') {
        if($bootstrapModalJSEnqueued == false) {
            wp_enqueue_script(
                'modal-bootstrapJS', 
                plugins_url('bootstrap_modal.js', __FILE__),
                array('jquery'), '3.1.1', true 
            );
        }
        if($bootstrapModalCSSEnqueued == false) {
            wp_enqueue_style(
                'modal-bootstrapCSS', 
                plugins_url('bootstrap_modal.css', __FILE__),
                '', '3.1.1', 'all' 
            );
        }
    } else {
        wp_dequeue_script('modal-bootstrapJS');
        wp_dequeue_style('modal-bootstrapCSS');
    }
    
}
add_action('wp_enqueue_scripts', 'modalPermalinks_loadScripts');


/* Ajax call */

function modalPermalinks_ajaxGetPost_javascript() { ?>
<script type="text/javascript" >
jQuery(function(){
    
    jQuery('a.modalPermalinks').on('click', function(e){
        
        e.preventDefault();

        jQuery.ajax({
            type     : 'POST',
            url      : '<?php echo admin_url('admin-ajax.php'); ?>',
            data     : {
                     action                 : 'modalPermalinks_ajaxGetPost',
                     modalPermalinks_postURL: jQuery(this).attr('href')
            },
            dataType : 'json'
        }).done(function(data) {
            jQuery('#modalPermalinks')
            .find('.modal-header .entry-title')
            .html(data.postTitle)
            .end()
            .find('.modal-body .entry-content p')
            .html(data.postContent)
            .end()
            .fadeIn();
        });
        
    });

    jQuery('#modalPermalinks button[data-dismiss="modal"]').on('click', function(e){
        
        e.preventDefault();
        jQuery('#modalPermalinks').fadeOut();
        
    });
    
});
</script>
<?php }
add_action('wp_footer', 'modalPermalinks_ajaxGetPost_javascript');


/* Ajax callback */

function modalPermalinks_ajaxGetPost_callback() {

    global $shortcode_tags;
    
    $permalink         = explode('/', $_POST['modalPermalinks_postURL']);
    $permalinkEnd      = end($permalink);
    $permalinkStart    = $permalink[0];
    $permalinkSettings = get_option('permalink_structure', '');
    $permalinkSize     = count($permalink);
    
    if(empty($permalinkEnd))
        array_pop($permalink);
    
    if(empty($permalinkStart))
        array_shift($permalink);
    
    switch($permalinkSettings) {
        // Default
        case '':
            $url       = end($permalink);
            $postType  = get_post_type(url_to_postid($url));
            break;
        // Day and name
        case '/%year%/%monthnum%/%day%/%postname%/':
            $permalink = array_splice($permalink, $permalinkSize-4);
            $url       = implode('/', $permalink);
            $postType  = get_post_type(url_to_postid($url));
            break;
        // Month and name
        case '/%year%/%monthnum%/%postname%/':
            $permalink = array_splice($permalink, $permalinkSize-3);
            $url       = implode('/', $permalink);
            $postType  = get_post_type(url_to_postid($url));
            break;
        // Numeric
        case '/archives/%post_id%':
            $permalink = array_splice($permalink, $permalinkSize-2);
            $url       = implode('/', $permalink);
            $postType  = get_post_type(url_to_postid($url));
            break;
        // Post name
        case '/%postname%/':
            $url       = end($permalink);
            $postType  = get_post_type(url_to_postid($url));
            break;
        default:
            $postType  = 'custom_permalinks';
            break;
    }

    switch($postType) {
        // Post
        case 'post':
            $post      = get_post(url_to_postid($url)); 
            $data      = array('postTitle'   => $post->post_title,
                               'postContent' => do_shortcode($post->post_content));
            break;
        // Page
        case 'page':
            $page      = get_page(url_to_postid($url)); 
            $data      = array('postTitle'   => $page->post_title,
                               'postContent' => do_shortcode($page->post_content));
            break;
        // Attachment
        case 'attachment':
            $data      = array('postTitle'   => __('Error',
                                                   'modal_permalinks'),
                               'postContent' => __('Modal Permalinks plugin works for posts and pages only!',
                                                   'modal_permalinks'));
            break;
        // Revision
        case 'revision':
            $data      = array('postTitle'   => __('Error',
                                                   'modal_permalinks'),
                               'postContent' => __('Modal Permalinks plugin works for posts and pages only!',
                                                   'modal_permalinks'));
            break;
        // Navigation Menu Item
        case 'nav_menu_item':
            $data      = array('postTitle'   => __('Error',
                                                   'modal_permalinks'),
                               'postContent' => __('Modal Permalinks plugin works for posts and pages only!',
                                                   'modal_permalinks'));
            break;
        case 'custom_permalinks':
            $data      = array('postTitle'   => __('Error',
                                                   'modal_permalinks'),
                               'postContent' => __('Modal Permalinks plugin is NOT working with custom permalinks',
                                                   'modal_permalinks'));
            break;
        default:
            $data      = array('postTitle'   => __('Error',
                                                   'modal_permalinks'),
                               'postContent' => __('Something is going wrong here... Please check in mind that <br /> Modal Permalinks plugin works for posts and pages only! and that <br /> Modal Permalinks plugin is NOT working with custom permalinks',
                                                   'modal_permalinks'));
            break;
    }
    
    echo json_encode($data);
	die();
}
add_action('wp_ajax_modalPermalinks_ajaxGetPost',
           'modalPermalinks_ajaxGetPost_callback');
add_action('wp_ajax_nopriv_modalPermalinks_ajaxGetPost',
           'modalPermalinks_ajaxGetPost_callback');


/* Add modal markup */

function modalPermalinks_modalMarkup() {
    
    echo '
    <div id="modalPermalinks" class="modal fade" tabindex="-1" 
    role="dialog" aria-labelledby="myLargeModalLabel" 
    aria-hidden="true">
        <div class="modal-dialog" style="width:'.get_option("modalReadmoreModalWidth").'px">
            <div class="modal-content" style="width:inherit">
                <div class="modal-header">
                    <h1 class="entry-title"></h1>
                </div>
                <div class="modal-body">
                    <div class="entry-content">
                        <p></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" 
                    data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    ';
    
}
add_action('wp_footer', 'modalPermalinks_modalMarkup');


/* Shortcode */

function modalPermalinks_shortcode($atts, $content = null) {
    
	extract(shortcode_atts(array('link' => ''), $atts));
	return '<a class="modalPermalinks" href="'.esc_attr($link) .'">'.$content.'</a>';
    
}
add_shortcode('modalPermalinks', 'modalPermalinks_shortcode');


/* Add settings link */

function modalPermalinks_setLink($links, $file) {
    
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '
        <a href="'.
        get_bloginfo('wpurl').
        '/wp-admin/admin.php?page=Modal_Permalinks">Settings</a>
        ';
        array_unshift($links, $settings_link);
    }

    return $links;
    
}
add_filter('plugin_action_links', 'modalPermalinks_setLink', 10, 2);


/* Add menu in admin settings */

function modalPermalinks_admin_actions() {
    
    // ADD THE MODAL READ MORE SUBMENU IN SETTINGS
    add_options_page("Modal Permalinks", "Modal Permalinks", 
                     1, "Modal_Permalinks", "modalPermalinks_admin");
    
}
add_action('admin_menu', 'modalPermalinks_admin_actions');


/* Include admin file */

function modalPermalinks_admin() {
    
    // INCLUDE THE FORM
    include('modal-permalinks-admin.php');
    
}
