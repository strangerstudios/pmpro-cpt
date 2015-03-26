<?php
/*
Plugin Name: Paid Memberships Pro - Custom Post Type Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-cpt/
Description: Add the PMPro meta box to CPTs and redirects non-members to a selected page.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

function pmprocpt_page_meta_wrapper()
{
	$selected_cpts = pmprocpt_getCPTs();
	foreach($selected_cpts as $selected_cpt)
	{
		add_meta_box('pmpro_page_meta', 'Require Membership', 'pmpro_page_meta', $selected_cpt, 'side');
	}
}

function pmprocpt_template_redirect()
{
	$selected_cpts = pmprocpt_getCPTs();
	$options = get_option('pmprocpt_options');
	$redirect_to = intval($options['redirect_to'][0]);
	if(!pmpro_has_membership_access() && is_singular($selected_cpts) && !empty($redirect_to))
	{
		//wp_redirect(pmpro_url('levels');
		wp_redirect(get_permalink($redirect_to));
		exit;
	}
}
add_action('template_redirect', 'pmprocpt_template_redirect');

function pmprocpt_getCPTs()
{
	$options = get_option('pmprocpt_options');
	if(isset($options['cpt_selections']) && is_array($options['cpt_selections']))
		return $options['cpt_selections'];
	else
		return array();
}

function pmprocpt_init()
{
	if (is_admin())
	{
		add_action('admin_menu', 'pmprocpt_page_meta_wrapper');
	}
}
add_action("init", "pmprocpt_init", 20);

//admin init. registers settings
function pmprocpt_admin_init()
{
	//setup settings
	register_setting('pmprocpt_options', 'pmprocpt_options', 'pmprocpt_options_validate');	
	add_settings_section('pmprocpt_section_general', 'Settings', 'pmprocpt_section_general', 'pmprocpt_options');	
	add_settings_field('pmprocpt_option_cpt_selections', 'Select CPTs', 'pmprocpt_option_cpt_selections', 'pmprocpt_options', 'pmprocpt_section_general');
	add_settings_field('pmprocpt_option_redirect_to', 'Redirect to', 'pmprocpt_option_redirect_to', 'pmprocpt_options', 'pmprocpt_section_general');
}
add_action("admin_init", "pmprocpt_admin_init");

function pmprocpt_option_cpt_selections()
{	
	global $pmprocpt_cpts;
	$options = get_option('pmprocpt_options');
	$selected_cpts = pmprocpt_getCPTs();
	
	if(!empty($pmprocpt_cpts))
	{
		echo "<select style='min-width: 30%; height: 200px;' multiple='yes' name=\"pmprocpt_options[cpt_selections][]\">";
		foreach($pmprocpt_cpts as $cpt)
		{
			if(in_array($cpt, array('post','page','attachment','revision','nav_menu_item','forum','topic','reply','product_variation','shop_order','shop_order_refund','shop_coupon','shop_webhook','plugin_filter','plugin_group')))
				continue;
			else
			{
				echo "<option value='" . $cpt . "' ";
				if(in_array($cpt, $selected_cpts))
					echo "selected='selected'";
				echo ">" . $cpt . "</option>";
			}
		}
		echo "</select>";
	}
	else
	{
		echo "No CPTs found.";
	}	
}

function pmprocpt_option_redirect_to()
{
	$options = get_option('pmprocpt_options');
	if(isset($options['redirect_to']))
		$redirect_to = $options['redirect_to'][0];
	else
		$redirect_to = '';
	wp_dropdown_pages(
		array(
			'name' => 'pmprocpt_options[redirect_to]',
			'echo' => 1,
			'show_option_none' => __( '&mdash; Do Not Redirect &mdash;' ),
			'option_none_value' => '0',
			'selected' => $redirect_to
		)
    );
}

//options sections
function pmprocpt_section_general()
{	
?>
<p>Select the custom post types from the box below to add the "Require Membership" meta box. Then, select the page to redirect to if a non-member attempts to access a protected CPT.</p>
<?php
}

// validate our options
function pmprocpt_options_validate($input) 
{
	//selected CPTs
	if(!empty($input['cpt_selections']) && is_array($input['cpt_selections']))
	{
		$count = count($input['cpt_selections']);
		for($i = 0; $i < $count; $i++)
			$newinput['cpt_selections'][] = trim(preg_replace("[^a-zA-Z0-9\-]", "", $input['cpt_selections'][$i]));	;
	}
	if(!empty($input['redirect_to']))
	{
		$newinput['redirect_to'][] = trim(preg_replace("[^a-zA-Z0-9\-]", "", $input['redirect_to']));	;
	}
	
	return $newinput;
}
	
// add the admin options page	
function pmprocpt_admin_add_page() 
{
	add_options_page('PMPro CPTs', 'PMPro CPTs', 'manage_options', 'pmprocpt_options', 'pmprocpt_options_page');
}
add_action('admin_menu', 'pmprocpt_admin_add_page');

//html for options page
function pmprocpt_options_page()
{
	global $pmprocpt_cpts, $msg, $msgt;
	
	//get options
	$options = get_option("pmprocpt_options");
	$pmprocpt_cpts = get_post_types( '', 'names' );
	$cpt_selections = pmprocpt_getCPTs();
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Paid Memberships Pro - Custom Post Type Membership Access</h2>		
	
	<?php if(!empty($msg)) { ?>
		<div class="message <?php echo $msgt; ?>"><p><?php echo $msg; ?></p></div>
	<?php } ?>
	
	<form action="options.php" method="post">
		
		<p>This plugin will add the PMPro "Require Membership" meta box to all CPTs selected. If a non-member visits that single CPT (either a logged out visitor or a logged in user without membership access) they will be redirected to the selected page.</p>
		<hr />
		
		<?php settings_fields('pmprocpt_options'); ?>
		<?php do_settings_sections('pmprocpt_options'); ?>

		<p><br /></p>
						
		<div class="bottom-buttons">
			<input type="hidden" name="pmprocpt_options[set]" value="1" />
			<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Settings'); ?>">
		</div>
		<p><br /></p>
		<hr />
		<p><strong>Notes:</strong></p>
		<p>This redirection will also apply to a search engine indexing your site.</p>
		<p>Setting membership access restrictions for a single CPT will not necessarily hide it from archives, search, or other custom template built into your theme.</p>
	</form>
</div>
<?php
}

/*
Function to add links to the plugin action links
*/
function pmprocpt_add_action_links($links) {
	
	$new_links = array(
			'<a href="' . get_admin_url(NULL, 'options-general.php?page=pmprocpt_options') . '">Settings</a>',
	);
	return array_merge($new_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pmprocpt_add_action_links');

/*
Function to add links to the plugin row meta
*/
function pmprocpt_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-cpt.php') !== false)
	{
		$new_links = array(
			//'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/third-party-integration/pmpro-aweber-integration/') . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprocpt_plugin_row_meta', 10, 2);
