<?php
/**
 * Plugin Name: ILWP Simple Link Cloaker
 * Plugin URI: http://ilikewordpress.com/simple-link-cloaker/
 * Description: Maintains a list of 'cloaked' URLs for redirection to outside URLs
 * Version: 1.4
 * Author: Steve Johnson
 * Author URI: http://ilikewordpress.com/
 */

/*  Copyright 2009-2010  Steve Johnson  (email : steve@ilikewordpress.com)

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
		7-12-2010 v 1.4
		Added javascript URL validation in an attempt to cut down on htaccess errors
		that would cause the server to throw 500 Server errors. While the method is not
		foolproof, it should help.
		
		7-23-2009 v 1.3.2
		Use of WP's sanitize_title_with_dashes filter inadvertently removed slashes
		within entered redirect slugs. Copied and modified WP's function to preserve
		entered slashes
		
		7-22-09 v 1.3.1
		Added facility to sanitize entered URL (post) slugs for redirect. Replaces
		illegal URL chars with -; allows only underscore and dash.
		
		7-4-09, v.1.3
		Fixed slight bug that caused PHP notice on initial installation
		Fixed PHP warning caused by absence of posts
		Updated for WP 2.8

		5-29-09, v. 1.2
		Fixed redirect to work with multi-value query strings
		
		5-28-09, v. 1.1
		Fixed bug where existing redirects were deleted upon addition of new ones.
		Caused by duplicate keys in options
		
*/

	define( 'SLC_VERSION', '1.4' );
	
	
	/**
	* Sanitizes title, replacing whitespace with dashes.
	*
	* Limits the output to alphanumeric characters, underscore (_) and dash (-).
	* Whitespace becomes a dash.
	*
	* MODIFIED FOR ILWP-COLORED-TAG-CLOUD to allow for slashes within title
	*
	* @since 1.2.0
	*
	* @param string $title The title to be sanitized.
	* @return string The sanitized title.
	*/
	function ilwp_sanitize_title($title) {
		$title = strip_tags($title);
		// Preserve escaped octets.
		$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
		// Remove percent signs that are not part of an octet.
		$title = str_replace('%', '', $title);
		// Restore octets.
		$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

		$title = remove_accents($title);
		if (seems_utf8($title)) {
			if (function_exists('mb_strtolower')) {
				$title = mb_strtolower($title, 'UTF-8');
			}
			$title = utf8_uri_encode($title, 200);
		}
	 
		$title = strtolower($title);
		$title = preg_replace('/&.+?;/', '', $title); // kill entities
		$title = preg_replace('/[^%\/a-z0-9 _-]/', '', $title);
		$title = preg_replace('/\s+/', '-', $title);
		$title = preg_replace('|-+|', '-', $title);
		$title = trim($title, '-');

		return $title;
	}

	function update_version() {
		$options = get_option('slc_redirect');
		$options['slc_redirect_version'] = SLC_VERSION;
		update_option('slc_redirect', $options);
		
		## writes updated htaccess rules for
		## previous installs of v1.1
		if ( isset( $options['redirects'] ) && $options['slc_redirect_version'] < '1.2' ) {
			$redirects = $options['redirects'];
			$insertion = prepare_insertion( $redirects );
			write_slc_htaccess( $insertion );
		}
	}
	
	function prepare_insertion( $redirects ) {
		if ( '' == $redirects )
			return false;
		foreach ( $redirects as $redirect ) :
			## check for beginning slash
			$from = $redirect['from'];
			$slashed = stripos( $from, "/" );
			if ( 0 !== $slashed )
				$from = "/" . $from;			
			$insertion[] = "Redirect 302 " . $from . " " . $redirect['to'];
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
		
		## handle 1.1 to 1.2 update
		if ( !isset( $options['slc_redirect_version'] ) ) :
			update_version();
			$options = get_option('slc_redirect');
		endif;
		
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
					$from = ilwp_sanitize_title( str_replace( $blogurl, '', $redirect['from'] ), '', 'link' );
					$newoptions['redirects'][$i]['from'] = $from;
					$newoptions['redirects'][$i]['to'] = $redirect['to'];
				endif;
			endforeach;
		}
		
		if ( isset( $_POST['slc-redirect-manage-submit'] ) && $_POST['slc-redirect-manage-submit'] != '' ) {
			$manage_redirects = $_POST['slc-redirect-manage'];
			unset( $newoptions['redirects'] );
			foreach ( $manage_redirects as $key => $redirect ) :
				if ( '' != $redirect['from'] && '' != $redirect['to'] && '1' != $redirect['delete']) :
					$newoptions['redirects'][$key]['from'] = ilwp_sanitize_title( $redirect['from'], '', 'link' );
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
			if ( $options['redirects'] )
				sort( $options['redirects'] );
			$redirects = $options['redirects'];
			$insertion = prepare_insertion( $redirects );
		endif;
		
		
		## due to popular demand and a number
		## of user problems, the following javascript
		## attempts to determine if destination URL
		## contains illegal chars that will cause
		## 500 Server error and breaking of site
?>		
		<script type="text/javascript">
		//<![CDATA[
			function valid_url(x) {
				var tomatch=new RegExp("(https?)\://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\-\._\?\,\'/\\\+&amp;%\$#\=~])*");
				if (tomatch.test(x)) {
					return true;
				}
				else {
					window.alert("URL appears to be invalid. Try again.");
					return false; 
				}
			}
		//]]>
		</script>
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
				<p><strong>Important! </strong>In order to avoid .htaccess errors, the plugin will construct a proper permalink structure for your redirect. Please refer to the center section 'Manage Cloaked Links' to make sure you use the correct form of the permalink within your posts.</p>
				<table style="clear: none; width: inherit" class="form-table">
<?php
$i = 0;
while ( $i < 3) :
?>
					<tr valign="top">
						<td>							
							<small><?php echo $blogurl; ?></small><input type="text" name="slc-redirect-new[<?php echo $i; ?>][from]" value="/" />
							<small> to </small><input type="text" name="slc-redirect-new[<?php echo $i; ?>][to]" onchange="return valid_url(this.value)" value="http://" />
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
		## on the faint chance that there are no posts, this avoids throwing an error
		$post_slugs = array();
		if ( $all_post_slugs ) :
			foreach ( $all_post_slugs as $slug ) :
				$post_slugs[] = $slug[0];
			endforeach;
		endif; 
		foreach ( $redirects as $redirect ) :
			## now check to see if the redirect matches a post slug
			if ( !empty( $post_slugs )) {
				if ( in_array( trim( $redirect['from'], '/' ), $post_slugs ) )
					$alert = "class='error'";
				else
					$alert = "";
			}
?>
					<tr>
						<td><small>from </small><input <?php echo $alert; ?> type="text" name="slc-redirect-manage[<?php echo $i; ?>][from]" value="<?php echo $redirect['from']; ?>" />
						</td>
						<td><small> to </small><input type="text" name="slc-redirect-manage[<?php echo $i; ?>][to]" onchange="return valid_url(this.value)" value="<?php echo $redirect['to']; ?>" /></td>
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