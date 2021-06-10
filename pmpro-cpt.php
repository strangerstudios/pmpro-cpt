<?php
/**
 * Plugin Name: Paid Memberships Pro - Custom Post Type Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/custom-post-type-membership-access/
 * Description: Add the PMPro meta box to CPTs and redirects non-members to a selected page.
 * Version: 1.0
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com/
 * Text Domain: pmpro-cpt
 * Domain Path: /languages
 */

define( 'PMPRO_CPT_BASENAME', plugin_basename( __FILE__ ) );

function pmprocpt_load_plugin_text_domain() {
	load_plugin_textdomain( 'pmpro-cpt', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'pmprocpt_load_plugin_text_domain');

/**
 * pmprocpt_page_meta_wrapper Wrapper to add meta boxes
 *
 * @return [type] [description]
 */
function pmprocpt_page_meta_wrapper() {
	$selected_cpts = pmprocpt_getCPTs();
	foreach ( $selected_cpts as $selected_cpt ) {
		add_meta_box( 'pmpro_page_meta', esc_html__('Require Membership', 'pmpro-cpt'), 'pmpro_page_meta', $selected_cpt, 'side' );
	}
}

/**
 * pmprocpt_template_redirect Redirect the restricted CPTs to the selected redirect page.
 *
 * @return [type] [description]
 */
function pmprocpt_template_redirect() {
	if ( ! function_exists( 'pmpro_has_membership_access' ) ) {
		return;
	}
	
	$selected_cpts = pmprocpt_getCPTs();
	if ( empty( $selected_cpts ) ) {
		return;
	}

	$options = get_option( 'pmprocpt_options' );
	$redirect_to = isset( $options['redirect_to'][0] ) ? intval( $options['redirect_to'][0] ) : '';
	if ( ! empty( $redirect_to ) ) {
		$redirect_to = get_permalink( $redirect_to );
	}

	/**
	 * Filter the URL redirected to when accessing a restricted CPT
	 *
	 * @since  .2
	 */
	$redirect_to = apply_filters( 'pmprocpt_redirect_to', $redirect_to, $selected_cpts, $options );

	if ( ! pmpro_has_membership_access() && is_singular( $selected_cpts ) && ! empty( $redirect_to ) ) {
		wp_redirect( $redirect_to );
		exit;
	}
}
add_action( 'template_redirect', 'pmprocpt_template_redirect' );

/**
 * pmprocpt_getCPTs Get the array of selected CPTs from the settings page.
 *
 * @return array An arry of the selected Custom Post Type names to restrict and redirect from.
 */
function pmprocpt_getCPTs() {
	$options = get_option( 'pmprocpt_options' );
	if ( isset( $options['cpt_selections'] ) && is_array( $options['cpt_selections'] ) ) {
		return $options['cpt_selections'];
	} else {
		return array();
	}
}

/**
 * pmprocpt_init Add Settings Page to WordPress admin.
 *
 * @return [type] [description]
 */
function pmprocpt_init() {
	if ( is_admin() ) {
		add_action( 'admin_menu', 'pmprocpt_page_meta_wrapper' );
	}
}
add_action( 'init', 'pmprocpt_init', 20 );

/**
 * pmprocpt_admin_init Register settings page and fields for the plugin.
 *
 * @return [type] [description]
 */
function pmprocpt_admin_init() {
	// setup settings
	register_setting( 'pmprocpt_options', 'pmprocpt_options', 'pmprocpt_options_validate' );
	add_settings_section( 'pmprocpt_section_general', 'Settings', 'pmprocpt_section_general', 'pmprocpt_options' );
	add_settings_field( 'pmprocpt_option_cpt_selections', 'Select CPTs', 'pmprocpt_option_cpt_selections', 'pmprocpt_options', 'pmprocpt_section_general' );
	add_settings_field( 'pmprocpt_option_redirect_to', 'Redirect to', 'pmprocpt_option_redirect_to', 'pmprocpt_options', 'pmprocpt_section_general' );
}
add_action( 'admin_init', 'pmprocpt_admin_init' );

/**
 * pmprocpt_option_cpt_selections Display the multi-select settings field to select CPTs for restriction.
 *
 * @return [type] [description]
 */
function pmprocpt_option_cpt_selections() {
	global $pmprocpt_cpts;
	$options = get_option( 'pmprocpt_options' );
	$selected_cpts = pmprocpt_getCPTs();

	if ( ! empty( $pmprocpt_cpts ) ) {
		echo "<select style='min-width: 30%; height: 200px;' multiple='yes' name=\"pmprocpt_options[cpt_selections][]\">";
		foreach ( $pmprocpt_cpts as $cpt ) {
			if ( in_array( $cpt, array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'forum', 'topic', 'reply', 'product_variation', 'shop_order', 'shop_order_refund', 'shop_coupon', 'shop_webhook', 'plugin_filter', 'plugin_group' ) ) ) {
				continue;
			}
			
			echo "<option value='" . esc_attr( $cpt ) . "' ";
			selected( in_array( $cpt, $selected_cpts ) );
			echo '>' . esc_html( $cpt ) . '</option>';			
		}
		echo '</select>';
	} else {
		echo '<p>';
		esc_html_e('No CPTs found.', 'pmpro-cpt');
		echo '</p>';
	}
	
	echo '<p class="description">';
	esc_html_e( 'Setting membership access restrictions for a single CPT will not necessarily hide it from archives, search, or other custom template built into your theme.', 'pmpro-cpt' );
	echo '</p>';
}

/**
 * pmprocpt_option_redirect_to Display the dropdown settings field to select the redirection page for restricted CPTs.
 *
 * @return [type] [description]
 */
function pmprocpt_option_redirect_to() {
	$options = get_option( 'pmprocpt_options' );
	if ( isset( $options['redirect_to'] ) ) {
		$redirect_to = $options['redirect_to'][0];
	} else {
		$redirect_to = '';
	}
	wp_dropdown_pages(
		array(
			'name' => 'pmprocpt_options[redirect_to]',
			'echo' => 1,
			'show_option_none' => '&mdash; ' . esc_html__( 'Do Not Redirect' ) . ' &mdash;',
			'option_none_value' => '0',
			'selected' => $redirect_to,
		)
	);
	
	echo '<p class="description">';
	esc_html_e( 'This redirection will also apply to a search engine indexing your site.', 'pmpro-cpt' );
	echo '</p>';
}

/**
 * pmprocpt_section_general Display an information message at the top of the settings page.
 *
 * @return string Paragraph description
 */
function pmprocpt_section_general() {
	echo '<p>';
	esc_html_e( 'Select the CPTs (custom post types) from the box below to add the "Require Membership" meta box. Then, select the page to redirect to if a non-member attempts to access a protected CPT.', 'pmpro-cpt' );
	echo '</p>';
}

/**
 * pmprocpt_options_validate Validate our options
 *
 * @param  [type] $input [description]
 *
 * @return [type]        [description]
 */
function pmprocpt_options_validate( $input ) {
	// selected CPTs
	if ( ! empty( $input['cpt_selections'] ) && is_array( $input['cpt_selections'] ) ) {
		$count = count( $input['cpt_selections'] );
		for ( $i = 0; $i < $count; $i++ ) {
			$newinput['cpt_selections'][] = trim( preg_replace( '[^a-zA-Z0-9\-]', '', $input['cpt_selections'][ $i ] ) );
		};
	}
	if ( ! empty( $input['redirect_to'] ) ) {
		$newinput['redirect_to'][] = trim( preg_replace( '[^a-zA-Z0-9\-]', '', $input['redirect_to'] ) );
		;
	}

	return $newinput;
}

/**
 * pmprocpt_admin_add_page Add the admin options page
 *
 * @return [type] [description]
 */
function pmprocpt_admin_add_page() {
	add_submenu_page( 'pmpro-dashboard', esc_html__('PMPro Custom Post Type Membership Access', 'pmpro-cpt'), esc_html__('CPT Access', 'pmpro-cpt'), 'manage_options', 'pmprocpt_options', 'pmprocpt_options_page' );
}
add_action( 'admin_menu', 'pmprocpt_admin_add_page' );

/**
 * pmprocpt_options_page HTML for options page
 *
 * @return [type] [description]
 */
function pmprocpt_options_page() {
	global $pmprocpt_cpts;

	// get options
	$options = get_option( 'pmprocpt_options' );
	$pmprocpt_cpts = get_post_types(
		array(
			'public'   => true,
			'_builtin' => false,
		), 'names'
	);
	$cpt_selections = pmprocpt_getCPTs();
	require_once( PMPRO_DIR . "/adminpages/admin_header.php" );
?>
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Custom Post Type Membership Access', 'pmpro-courses' ); ?>
	</h1>	
		
	<form action="options.php" method="post">
		
		<p><?php esc_html_e('This plugin will add the PMPro "Require Membership" meta box to all CPTs selected. If a non-member visits that single CPT (either a logged out visitor or a logged in user without membership access) they will be redirected to the selected page.', 'pmpro-cpt'); ?></p>
		<hr />
		
		<?php settings_fields( 'pmprocpt_options' ); ?>
		<?php do_settings_sections( 'pmprocpt_options' ); ?>
						
		<p class="submit">
			<input type="hidden" name="pmprocpt_options[set]" value="1" />
			<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings' ); ?>">
		</p>
	</form>
<?php
	require_once( PMPRO_DIR . "/adminpages/admin_footer.php" );
}

/**
 * Register activation hook.
 */
register_activation_hook( PMPRO_CPT_BASENAME, 'pmprocpt_admin_notice_activation_hook' );

/**
 * pmprocpt_admin_notice_activation_hook Runs only when the plugin is activated.
 *
 * @since 0.1.0
 *
 * @return [type] [description]
 */
function pmprocpt_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmprocpt-admin-notice', true, 5 );
}

/**
 * pmprocpt_admin_notice Admin Notice on Activation.
 *
 * @since 0.1.0
 *
 * @return [type] [description]
 */
function pmprocpt_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmprocpt-admin-notice' ) ) {
	?>
		<div class="updated notice is-dismissible">
			<p><?php 
			/* translators: The placeholder is for a URL. */			
			esc_html_e( 'Thank you for activating.', 'pmpro-cpt' );
			echo ' <a href="' . esc_url( get_admin_url( null, 'admin.php?page=pmprocpt_options' ) ) . '">';
			esc_html_e( 'Visit the settings page to get started with the CPT Add On.', 'pmpro-cpt' );
			echo '</a>';
			?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmprocpt-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmprocpt_admin_notice' );

/**
 * pmprocpt_add_action_links Add links to the plugin action links
 *
 * @param  array $links Array of existing plugin action links.
 *
 * @return array $links Array of links to be shown in plugin action links.
 */
function pmprocpt_add_action_links( $links ) {

	$new_links = array(
		'<a href="' . esc_url( get_admin_url( null, 'admin.php?page=pmprocpt_options' ) ) . '">' . esc_html__( 'Settings', 'pmpro-cpt' ) . '</a>',
	);
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmprocpt_add_action_links' );


/**
 * pmprocpt_plugin_row_meta Function to add links to the plugin row meta
 *
 * @param array  $links Array of existing links in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 *
 * @return array  $links Array of links to be shown in plugin meta.
 */
function pmprocpt_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-cpt.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/custom-post-type-membership-access/' ) . '" title="' . esc_attr__( 'View Documentation', 'pmpro-cpt' ) . '">' . esc_html__( 'Docs', 'pmpro-cpt' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr__( 'Visit Customer Support Forum', 'pmpro-cpt' ) . '">' . esc_html__( 'Support', 'pmpro-cpt' ) . '</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmprocpt_plugin_row_meta', 10, 2 );

