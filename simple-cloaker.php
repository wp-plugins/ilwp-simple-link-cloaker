<?php
/**
 * Plugin Name: ILWP Simple Link Cloaker
 * Plugin URI: http://ilikewordpress.com/simple-link-cloaker/
 * Description: Maintains a list of 'cloaked' URLs for redirection to outside URLs
 * Version: 1.1
 * Author: Steve Johnson
 * Author URI: http://ilikewordpress.com/
 */

/*  Copyright 2009  Steve Johnson  (email : steve@ilikewordpress.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
/* 
	Changelog:
		5-28-09, v. 1.1
		Fixed bug where existing redirects were deleted upon addition of new ones.
		Caused by duplicate keys in options
*/

	define( 'SLC_VERSION', '1.1' );		
	
	function prepare_insertion( $redirects ) {
		if ( '' == $redirects )
			return false;
		foreach ( $redirects as $redirect ) :
			## check for beginning slash
			$slashed = stripos( $redirect['from'], "/" );
			if ( 0 === $slashed )
				$from = $redirect['from'];
			else
				$from = "/" . $redirect['from'];
			$insertion[] = "RedirectMatch 302 " . $from . "(.*) " . $redirect['to'] . "$1";
		endforeach;
		return $insertion;
	}
	
	function write_slc_htaccess( $redirects ) {
		$home_path = get_home_path();
		$htaccess_file = $home_path.'.htaccess';
		if ( "delete" == $redirects ) :
			$redirects = '';
		endif;
		return insert_with_markers( $htaccess_file, 'SLC', $redirects );
	}
	
	function slc_redirect_options_page() {
		global $wpdb;
		$home_path = get_home_path();
		$htaccess_file = $home_path.'.htaccess';
		$blogurl = get_bloginfo('url');
		
		if ( isset( $_POST['slc-options-reset'] ) && '1' == $_POST['slc-options-reset'] ) :
			delete_option('slc_redirect');
			write_slc_htaccess("delete");
		endif;
		
		if ( !get_option('slc_redirect') )
			add_option('slc_redirect');
		
		$options = get_option('slc_redirect');
		
		## resets stray numeric array keys from last insert
		if ( isset( $options['redirects'] ) ) :
			sort( $options['redirects'] );
			update_option('slc_redirect', $options);
		endif;
		
		$newoptions = $options;
		
		if ( isset( $_POST['new-redirect-submit'] ) && $_POST['new-redirect-submit'] != '' ) {
			$new_redirects = $_POST['slc-redirect-new'];
			$i = sizeof( $newoptions['redirects'] );
			foreach ( $new_redirects as $redirect ) :
				if ( '/' != $redirect['from'] && '' != $redirect['from'] && '' != $redirect['to'] ) :
					$i++;
					$newoptions['redirects'][$i]['from'] = str_replace( $blogurl, '', $redirect['from'] );
					$newoptions['redirects'][$i]['to'] = $redirect['to'];
				endif;
			endforeach;
		}
		
		if ( isset( $_POST['slc-redirect-manage-submit'] ) && $_POST['slc-redirect-manage-submit'] != '' ) {
			$manage_redirects = $_POST['slc-redirect-manage'];
			unset( $newoptions['redirects'] );
			foreach ( $manage_redirects as $key => $redirect ) :
				if ( '' != $redirect['from'] && '' != $redirect['to'] && '1' != $redirect['delete']) :
					$newoptions['redirects'][$key]['from'] = $redirect['from'];
					$newoptions['redirects'][$key]['to'] = $redirect['to'];
				endif;
			endforeach;
		}

		if ( $options != $newoptions ) {
			$options = $newoptions;
			$redirects = $options['redirects'];
			$insertion = prepare_insertion( $redirects );
			update_option('slc_redirect', $options);
			write_slc_htaccess( $insertion );
		}

		if ( $options ) :
			sort( $options['redirects'] );
			$redirects = $options['redirects'];
			$insertion = prepare_insertion( $redirects );
		endif;
?>		
		<div class="wrap">
			<div style="padding: 10px; border: 1px dotted #ccc; width: 250px; float: right; margin-right: 10px; margin-left: 30px; margin-top: 10px; text-align: center;">
				<h3>Like <acronym title="I Like WordPress!">ILWP</acronym> Simple Link Cloaker?</h3>
				<h4>Consider making a donation!</h4>
				<p><small>Donations help with the ongoing development and feature additions of <acronym title="I Like WordPress!">ILWP</acronym> Simple Link Cloaker. Thank you!</small></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="5663793">
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
			</div>
<?php
		if ( !is_writable( $htaccess_file ) )
			echo '<div class="error" style="width: 40%;">Your .htaccess file does not appear to be writable. If you are unable to make the file writable by the server, you will need to copy/paste the redirect rules to your htaccess file manually. See below for rules.</div>';
?>
			<h2>Simple Link Cloaker v. <?php echo SLC_VERSION ;?> ~ Redirect List</h2>
			<form method="post" action="">
				<h3>Add New Cloaked Links</h3>
			<?php wp_nonce_field('update-options');?>
				<p><small>The <acronym title="I Like WordPress!">ILWP</acronym> Simple Link Cloaker makes it easy to 'cloak' or hide your affiliate URLs. Using the link cloaker is easy. The left hand box below will contain the 'href' from the link in your post (without the domain name part), the right hand box will contain your affiliate URL. See further instructions and a short video tutorial on the plugin page at <a href="http://ilikewordpress.com/simple-link-cloaker/" title="ILWP Simple Link Cloaker">http://ilikewordpress.com/simple-link-cloaker</a></small></p>
				<table style="clear: none; width: inherit" class="form-table">
<?php
$i = 0;
while ( $i < 3) :
?>
					<tr valign="top">
						<td>							
							<small><?php echo $blogurl; ?></small><input type="text" name="slc-redirect-new[<?php echo $i; ?>][from]" value="/" />
							<small> to </small><input type="text" name="slc-redirect-new[<?php echo $i; ?>][to]" value="http://" />
						</td>
					</tr>
					
<?php
$i++;
endwhile;
?>
					</table>
				<input type="hidden" name="new-redirect-submit" id="new-redirect-submit" value="1" />
				<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e('Add Cloaked Links') ?>" /></p>
			</form>
<?php
	if ( $insertion && !is_writable( $htaccess_file ) ) :
?>
			<h3>Rewrite Rules</h3>
			<p><small>Copy/paste this ruleset to the top of your .htaccess file. Click in the textbox to select all, then copy/paste.</small></p>
			<textarea style="width: 500px;" onfocus="this.select();" rows="<?php echo sizeof($insertion) + 2; ?>">## BEGIN SLC
<?php
	foreach ( $insertion as $line ) :
		echo "$line\n";
	endforeach;
?>
## END SLC
</textarea>
<?php
	endif;
	if ( $redirects ) :
?>
			<h3 style="margin-top: 30px;">Manage Cloaked Links</h3>
			<form method="post" action="">
			<?php wp_nonce_field('update-options'); ?>
				<p><small>Change any field, or click the checkbox to delete the redirect. <span style="padding: 3px; background-color: #ffebe8;">Highlighted</span> titles indicate that the 'from' address matches an existing post address on your blog. This could cause problems, depending on your permalink structure. Best to change it.</small></p>
				<table style="clear: none; width: inherit" class="form-table">

<?php
		$table = $wpdb->prefix . 'posts';
		$all_post_slugs = $wpdb->get_results("SELECT post_name FROM $table WHERE post_type = 'post' AND post_status = 'publish'", ARRAY_N);
		foreach ( $all_post_slugs as $slug ) :
			$post_slugs[] = $slug[0];
		endforeach;
		foreach ( $redirects as $redirect ) :
			## now check to see if the redirect matches a post slug
			if ( in_array( trim( $redirect['from'], '/' ), $post_slugs ) )
				$alert = "class='error'";
			else
				$alert = "";
?>
					<tr>
						<td><small>from </small><input <?php echo $alert; ?> type="text" name="slc-redirect-manage[<?php echo $i; ?>][from]" value="<?php echo $redirect['from']; ?>" />
						</td>
						<td><small> to </small><input type="text" name="slc-redirect-manage[<?php echo $i; ?>][to]" value="<?php echo $redirect['to']; ?>" /></td>
						<td><small> delete </small><input type="checkbox" name="slc-redirect-manage[<?php echo $i; ?>][delete]" id="" value="1" /></td>
					</tr>
<?php
			$i++;
		endforeach;
?>
				</table>
				<input type="hidden" name="slc-redirect-manage-submit" id="slc-redirect-manage-submit" value="1" />
				<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e('Update Cloaked Links') ?>" /></p>
			</form>
			<h3 style="margin-top: 30px;">Delete Cloaked Links</h3>
			<form method="post" action="">
			<?php wp_nonce_field('update-options'); ?>
				<p><small>This button will <strong>permanently delete</strong> all stored Cloaked Links, from both the options table and from the .htaccess file.</small></p>
				<input type="hidden" name="slc-options-reset" id="slc-options-reset" value="1" />
				<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e('Delete all Simple Link Cloaker Redirects') ?>" onclick="return confirm('Are you sure you want to delete ALL?');" /></p>
			</form>
<?php
endif;
?>
		</div>
<?php
	} ## end options page

	function slc_options_page () {
		add_options_page('ILWP Simple Link Cloaker Redirects', 'Simple Link Cloaker', 8, __FILE__, 'slc_redirect_options_page');
	}
	
	add_action( 'admin_menu', 'slc_options_page' );
?>