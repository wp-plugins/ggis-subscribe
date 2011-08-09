<?php
/*
Plugin Name: ggis Subscribe
Plugin URI: http://dvector.com/oracle/category/ggissubscribe/
Description: Manages subscriptions to email lists. Simply add [-ggis-subscribe-] to your post.
Author: Gary Dalton
Version: 1.0.1
Author URI: http://dvector.com/oracle/
*/

/*  Copyright 2008-2010 Gary Dalton  (email : PLUGIN AUTHOR EMAIL)

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

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

/*
*	START CLASS
*/
if (!class_exists("ggisSubscribe")) {
	class ggisSubscribe {
		/*
			formAction - URL to post form to.
			maillist - Which mailing list the subscription is for. [validated]
				listname@npexchange.org
			nickname - Nickname for the mailing list [optional]
				List Name
			action - Subscribe or unsubscribe [validated]
			email - Email address to manage [validated
			name - Subscriber's name [optional] [validated]
			nextpage - Go to this URL after processing subscription.[validated]
				http://example.com
			formurl - URL with full subscribe/unsubscribe form[validated]
				http://example.com
			listmanager - Software managing the mailing list
				ezmlm, mailman
		*/

		var $formAction = '';
		var $maillist;
		var $nextpage;
		var $formurl;
		var $managerdefault = 'ezmlm';
		var $managertypes = array('ezmlm', 'mailman');

		var $adminOptionsName = 'ggisSubscribeAdminOptions';
		

		function ggisSubscribe() { //constructor
//			$this->formAction = WP_PLUGIN_URL . '/ggis_wp-subscribe.php';
		}
		function addHeaderCode(){
			echo '<!-- ggisSubscribe Plugin - Copyright 2008-2010 - GGIS -->';
		}
		
		// OUTPUT FUNCTIONS
		var $processing_unit_tag;
		var $processing_within;
		var $unit_count;
		var $widget_count;
		var $regex;
		function the_content_filter($content) {
			$this->processing_within = 'p' . get_the_ID();
			$this->unit_count = 0;
			
			
			$regex = '/\[-\s*ggis-subscribe\s*(\d+)?\s*(".*")?\s*-\]/';
//			$regex = '/\[-\s*ggis-subscribe\s+(\d+)(?:\s+.*?)?\s*-\]/'; // [-ggis-subscribe 1 -]
//			$regex = '/<!--\s*ggis-subscribe\s+(\d+)\s+(.*)\s*-->/';
			return preg_replace_callback($regex, array(&$this, 'the_content_filter_callback'), $content);
			
			$this->processing_within = null;
		}
		
		function widget_text_filter($content) {
			$this->widget_count += 1;
			$this->processing_within = 'w' . $this->widget_count;
			$this->unit_count = 0;
	
			$regex = '/\[-\s*ggis-subscribe\s*(\d+)?\s*(".*")?\s*-\]/';	
			return preg_replace_callback($regex, array(&$this, 'the_content_filter_callback'), $content);
			
			$this->processing_within = null;
		}
		
		function the_content_filter_callback($matches) {

			// GET AND SET VARIABLES
			$list_select = '';
			$listonly = NULL;
			$options = unserialize( get_option( 'ggis-Subscribe'));
			$options['maillists'] = explode( ',', $options['maillists'] );
			if ( isset($options['nicknames']) )
				$options['nicknames'] = explode( ',', $options['nicknames'] );
			// Set nicknames - starting in v 1.0
			foreach( $options['maillists'] as $key=>$val ){
				if ( !(isset($options['nicknames'][$key]) && !empty($options['nicknames'][$key])) ){
					$options['nicknames'][$key] = $val;
				}
				unset($key);
				unset($val);
			}
			$id = (int) $matches[1];
			
			// DETERMINE FORM TYPE
			if ( $id == 1 ){
				$listonly = str_replace( '"', '', $matches[2]);
				if ( !is_email( $listonly) ){
					$id = 0;
				}
			}else {
				$id = 0;
			}
			
			// FORM HEADERS
			$this->unit_count += 1;
			$unit_tag = 'ggis-subscribe-f' . $id . '-' . $this->processing_within . '-o' . $this->unit_count;
			$this->processing_unit_tag = $unit_tag;

			$form = '<div class="ggis-subscribe-form" id="' . $unit_tag . '">';
			$form .= '<form action="' . $this->formAction . '" method="post" class="ggis-subscribe-form">';
			$form .= '<input type="hidden" name="formtype" id="formtype" value="ggis-subscribe-form">';
			$form .= '<input type="hidden" name="nextpage" id="nextpage" class="ggis-subscribe-form" value="'. $options['nextpage'] . '">';
			$form .= '<input type="hidden" name="formurl" id="formurl" class="ggis-subscribe-form" value="'. $options['formurl'] . '">';
			
			// CREATE EITHER A HIDDEN FIELD OR SELECT BOX FOR MAILING LIST
			if ( $id == 1 ){
				$list_select .= '<input type="hidden" name="maillist" id="maillist" class="ggis-subscribe-form" value="'. $listonly . '">';
			}else{
				if ( count( $options['maillists']) == 1 ){
					$list_select .= '<input type="hidden" name="maillist" id="maillist" class="ggis-subscribe-form" value="'. $options['maillists'][0] . '">';
				} else{
					$list_select .= 'Which list?<br>
									<select name="maillist">';
					foreach( $options['maillists'] as $key=>$list ){
						$list_select .= '<option value="' . $list . '"';
						if ( $key == 0 ){
							$list_select .= ' SELECTED';
						}
						$list_select .= '>'.$options['nicknames'][$key].'</option>';
					}
					$list_select .= '</select>';
				}
			}

			if ( $id == 0){
				if ( !isset($options['formtitle']) ){
					$options['formtitle'] = 'Subscription Management';
				}
				// LONG FORM
				$form .= '<fieldset><legend>' . $options['formtitle'] . '</legend>
						<p class="ggis-subscribe-form">';
				$form .= $list_select;
				$form .= '</p><p>&nbsp;Action:<br>
						&nbsp;&nbsp;<input type="radio" name="action" id="subscribe" value="subscribe" checked="checked" /> Subscribe<br>
						&nbsp;&nbsp;<input type="radio" name="action" id="unsubscribe" value="unsubscribe" /> Unsubscribe</p>
						<p>&nbsp;Your Email:<br>
						&nbsp;<input type="text" name="ggis-subscribe-email" id="ggis-subscribe-email" class="ggis-subscribe-email" size="40" maxlength="100" /></p>
						</fieldset>
						<input type="submit" name="Submit" value="Submit" />
						</p>';
			} elseif ( $id == 1 ) {
				// SHORT FORM
				$form .= $list_select;
				$form .= '<input type="hidden" name="action" id="subscribe" class="ggis-subscribe-form" value="subscribe">';
				$form .= '<p><input type="text" name="ggis-subscribe-email" id="ggis-subscribe-email" maxlength="100" class="ggis-subscribe-email" value="your email"/>
						<input type="submit" name="Subscribe" value="Subscribe" /></p>';
			}
			
			// FORM FOOTERS
			$form .= '</form>';			
			$form .= '</div>';
			
			$this->processing_unit_tag = null;
			return $form;
		}
				
		// ADMIN MENUS
		var $adminform = '';
		function ggis_admin_add_pages() {
			add_options_page('ggisSubscribe', 'ggisSubscribe', 'manage_options', basename(__FILE__), array(&$this, 'ggissubscribe_options'));
		}
		
		function form_ggissubscribe_options(){
			$form = '';
			$options = unserialize( get_option( 'ggis-Subscribe'));
			$options['maillists'] = explode( ',', $options['maillists'] );
			$options['nicknames'] = explode( ',', $options['nicknames'] );
			$nicklist = '';
			foreach ( $options['maillists'] as $key=>$val ){
				if ( $key > 0 ) $nicklist .= ',';
				if ( isset($options['nicknames'][$key]) && !empty($options['nicknames'][$key]) ){	// Not set until version 1.0
					$nicklist .= $options['nicknames'][$key] . ':';
				}
				$nicklist .= $val;
			}
			unset($key);
			unset($val);
			
			$list_select .= 'Which list manager?<br>
							<select name="ggissubscribe_managertype">';
			foreach( $this->managertypes as $manager ){
				$list_select .= '<option value="' . $manager . '"';
				if ( isset( $options['managertype']) ){
					if ( $manager == $options['managertype'] ){
						$list_select .= ' SELECTED';
					}
				}elseif ( $manager == $managerdefault ){
					$list_select .= ' SELECTED';
				}
				$list_select .= ">$manager</option>";
			}
			$list_select .= '</select>';
		
			
			$form .= '<form method="post">';
			$form .= wp_nonce_field('ggis-subscribe-update-options_base');
			$form .= '<fieldset><legend>Mailing List Options</legend>
						<p>All fields are required.</p>';
			$form .= '<p>
						Title of the Subscription Maangement form<br />
						(leave blank for no title)<br />';
			$form .= '<input type="text"  size="60" name="ggissubscribe_formtitle" value="' . $options['formtitle'] . '">';
			$form .= '</p>';
			
			$form .= '<p>';
			$form .= $list_select;
			$form .= '</p>
						<p>
						Mailing List Nickname and Address (nickname is optional)(colon and comma separated)<br />
						example: Mail List:maillist@npexchange.org,List 2:list2@npexchange.org<br />';
			$form .= '<textarea cols="60" rows="4" name="ggissubscribe_maillists">'. $nicklist .'</textarea>';
			$form .= '</p>
						<p>
						URL of page to go to upon success<br />
						example: http://your.blog/name/thank_you/<br />';
			$form .= '<input type="text"  size="60" name="ggissubscribe_nextpage" value="'. $options['nextpage'] . '" />';
			$form .= '</p>
						<p>
						URL of page containing the full subscription management form<br />
						example: http://your.blog/name/manage_subscriptions/<br />';
			$form .= '<input type="text"  size="60" name="ggissubscribe_formurl" value="' . $options['formurl'] . '">';
			$form .= '</p>
						<input type="hidden" name="action" value="update" />
						<input type="hidden" name="page_options" value="ggissubscribe_maillists,ggissubscribe_nextpage,ggissubscribe_formurl" />
						<p class="submit">';
			$form .= '<input type="submit" name="submit" value="Save Changes" /></p>
						</fieldset></form>';
			$plugin_info = '
<h3>plugin Usage and Information</h3>
<p>version 1.0.0</p>
<h4>Usage</h4>
<p>A subscription form may be inserted on a post, page, or text widget by including the following code in your text.
<br><br>[-ggis-subscribe %formtype &quot;%listname&quot;-]
<br><br>Here is an explanation of the fields:</p>
<ol>
	<li><p>ggis-subscribe - identifies the code (required)</p>
	<li><p>formtype - identifies the form type</p>
	<ul>
		<li><p>0, default - full subscription management form</p>
		<li><p>1 - subscribe only form, requires &quot;listname&quot;</p>
	</ul>
	<li><p>listname - identifies the list to  include in a subscription only form</p>
</ol>
<h4>In a Widget?</h4>
<p>A subscription form may be placed into the standard text widget using the methods above. For widget use, I suggest using only formtype=1, the short form.</p>
<h4>More information</h4>
<p>Visit the following for more information:</p>
<ul>
	<li><p><a href="http://wordpress.org/extend/plugins/ggis-subscribe/">http://wordpress.org/extend/plugins/ggis-subscribe/</a></p>
	<li><p><a href="http://dvector.com/oracle/ggis-subscribe/">http://dvector.com/oracle/ggis-subscribe/</a></p>
</ul>';			
			$form .= $plugin_info;
						
			return $form;
		}

		
		function ggissubscribe_options() {
			$this->adminform .= '<div class="wrap"><h2>ggis Subscribe</h2>';
			if ( $_POST['submit'] ){
				$this->adminform .= $this->update_ggissubscribe_options();
			}
			$this->adminform .= $this->form_ggissubscribe_options();
			$this->adminform .= '</div>';
			echo $this->adminform;
		}
		
		function update_ggissubscribe_options(){
			$maillists = NULL;
			$msg = '';
			$options = NULL;
			
			check_admin_referer('ggis-subscribe-update-options_base');
			
			if ( $_POST['ggissubscribe_managertype'] ) {
				$options['managertype'] = $_POST['ggissubscribe_managertype'];
			} else {
				$msg .= '<p>Manager type is required.</p>';
			}
			
			if ( $_POST['ggissubscribe_formtitle'] ) {
				$options['formtitle'] = htmlentities($_POST['ggissubscribe_formtitle']);
			}else {
				$options['formtitle'] = '';
			}
			
			if ( $_POST['ggissubscribe_maillists'] ) {
				$maillists = explode(',', $_POST['ggissubscribe_maillists']);
				foreach ( $maillists as $key => $val ){
					$pair = explode(':', $val);
					if ( count($pair) == 2){
						$maillists[$key] = trim(strtolower($pair[1]));
						if ( !is_email( $maillists[$key]) ){
							$msg .= "<p>Please enter a valid email address for the mailing list. Your invalid entry was $val</p>";
							unset( $maillists[$key]);
						}else{
							$nicknames[$key] = trim($pair[0]);
						}
					}else{
						$val = trim( strtolower( $val));
						$maillists[$key] = $val;
						if ( !is_email( $val) ){
							$msg .= "<p>Please enter a valid email address for the mailing list. Your invalid entry was $val</p>";
							unset( $maillists[$key]);
						}else{
							$nicknames[$key] = NULL;
						}
					}
				}
				$options['maillists'] = implode( ',', $maillists);
				$options['nicknames'] = implode( ',', $nicknames);
			} else {
				$msg .= '<p>Mailing list entries are required.</p>';
			}
			
			if ( $_POST['ggissubscribe_nextpage'] ) {
				if ( !$this->is_url_valid( $_POST['ggissubscribe_nextpage'])){
					$msg .= '<p>Please enter a valid next page. Your invalid entry was ' . $_POST['ggissubscribe_nextpage'] .'</p>';
					unset( $_POST['ggissubscribe_nextpage']);
				}
				$options['nextpage'] = $_POST['ggissubscribe_nextpage'];
			} else {
				$msg .= '<p>Next page is required.</p>';
			}
			
			if ( $_POST['ggissubscribe_formurl'] ) {
				if ( !$this->is_url_valid( $_POST['ggissubscribe_formurl'])){
					$msg .= '<p>Please enter a valid form URL. Your invalid entry was ' . $_POST['ggissubscribe_formurl'] .'</p>';
					unset( $_POST['ggissubscribe_formurl']);
				}
				$options['formurl'] = $_POST['ggissubscribe_formurl'];
			} else {
				$msg .= '<p>Form URL is required.</p>';
			}
			if ( $msg <> '' ){
				$msg = '<div id="message" class="error fade">'. $msg . '</div>';
			}
			if ( !is_null( $options) ){
				update_option( 'ggis-Subscribe', serialize( $options));
				$msg .= '<div id="message" class="updated fade"><p>Options saved.</p></div>';
			}
			return $msg;
		}
		
		
		// Verify string for valid URL format
		function is_url_valid($url){			
		    $url = @parse_url($url);
		    if (!$url) {
		        return false;
		    }
		
		    $url = array_map('trim', $url);
		    $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];
		
		    $path = (isset($url['path'])) ? $url['path'] : '/';
		    $path .= (isset($url['query'])) ? "?$url[query]" : '';
		
		    if (isset($url['host']) AND $url['host'] != gethostbyname($url['host'])) {
		        if (PHP_VERSION >= 5) {
		            $headers = implode('', get_headers("$url[scheme]://$url[host]:$url[port]$path"));
		        }
		        else {
		            $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);
		
		            if (!$fp)
		            {
		                return false;
		            }
		            fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n");
		            $headers = fread($fp, 4096);
		            fclose($fp);
		        }
		        return (bool)preg_match('#^HTTP/.*\s+[(200)]+\s#i', $headers);
		    }
		    return false;
		}
		
	/*
	*	START formSubmitProcess FUNCTION
	*/
		function formSubmitProcess(){
		/*
		*	Handles form subscription submissions to ezmlm-idx.
		*	These are sent via email.
		*		formtype - indicates that form is for this plugin [validated]
		*			ggis-subscribe-form
		*		maillist - Which mailing list the subscription is for. [validated]
		*			listname@npexchange.org
		*		action - Subscribe or unsubscribe [validated]
		*		email - Email address to manage [validated
		*		name - Subscriber's name [optional] [validated]
		*		nextpage - Go to this URL after processing subscription.[validated]
		*			http://example.com
		*		formurl - URL with full subscribe/unsubscribe form[validated]
		*			http://example.com
		*		message - Any error messages plus comments to be output for visitor. [internal]
		*		mailto - Fully formatted email address to process request. [internal]
		*		errmsg - Message array for the user when some of the input valuse are invalid. [internal]
		*/
		
			if ( !isset($_POST['formtype']) ){
				return;
			}
						
			// SET INITIAL VALUES
			$_GET = array();	//flush GET
			$formtype = 'ggis-subscribe-form';
			$maillist = NULL;
			$action = NULL;
			$email = NULL;
			$name = NULL;
			$nextpage = NULL;
			$formurl = NULL;
			$message = '';
			$mailto = NULL;
			$errmsg = array(
				'action'	=> '<p>That action does not exist. Please contact the site owner.</p>',
				'email'	=> '<p>The email address you entered is not valid.</p>',
				'name'	=> '<p>The name you entered is not valid.</p>',
				'nexturl'	=> '<p>The Next URL is not valid. Please contact the site owner.</p>',
				'formurl'	=> '<p>The Form URL is not valid. Please contact the site owner.</p>'
				);
			$ar_mailing = NULL;
			$options = unserialize( get_option( 'ggis-Subscribe'));
			
			if ( $_POST['formtype'] != $formtype ) return;
			
			// POPULATE VALUES FROM POST
			if( isset( $_POST['maillist'] ) )	$maillist = strtolower($_POST['maillist']);
			if( isset( $_POST['action'] ) )	$action	=	strtolower($_POST['action']);
			if( isset( $_POST['ggis-subscribe-email'] ) )	$email	=	strtolower($_POST['ggis-subscribe-email']);
			if( isset( $_POST['name'] ) )	$name	=	$_POST['name'];
			if( isset( $_POST['nextpage'] ) )	$nextpage	=	$_POST['nextpage'];
			if( isset( $_POST['formurl'] ) )	$formurl	=	$_POST['formurl'];
			
			$_POST = array();	// flush POST
			
			// VALIDATION
			if (!(in_array($action, array('subscribe', 'unsubscribe')))){
				$message .= $errmsg['action'];
			}
			if (!(is_email($email))){
				$message .= $errmsg['email'];
			}
			if (!(empty($name) || is_name_valid($name))){
				$message .= $errmsg['name'];	
			}
			if (!($this->is_url_valid($nextpage))){
				$message .= $errmsg['nexturl'];
			}
			if (!($this->is_url_valid($formurl))){
				$message .= $errmsg['formurl'];
			}
			
			// PROCESS SUBSCRIPTION
			if ( $message === '' ){	// no errors
				$ar_mailing = serialize( array(
								'maillist'		=>	$maillist,
								'action'		=>	$action,
								'email'			=>	$email,
								'name'			=>	$name
							) );
							
				if ( $options['managertype'] == 'ezmlm' ) {
					$ar_mailing = $this->formatEzmlm($ar_mailing);
				}else if ( $options['managertype'] == 'mailman' ) {
					$ar_mailing = $this->formatMailman($ar_mailing);
				}else{
					print_r ($options);
					exit;
				}
			
				// BUILD LIST ACTION EMAIL
				$ar_mailing = unserialize($ar_mailing);
			
				// Do it
				//mail($ar_mailing['to'], $ar_mailing['subject'], $ar_mailing['message']);
				wp_mail( $ar_mailing['to'], $ar_mailing['subject'], $ar_mailing['message'] );
				
				// Go to next page
				header("Location: $nextpage");
				exit;
			}
			
			// ERRORS ON FORM
			// Doctype setup
			$doctype = '<?xml version="1.0" encoding="utf-8"?>';
			$doctype .= "\n";
			$doctype .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
			$doctype .= "\n";
			$doctype .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
			$doctype .= "\n";
				
			?>
			<?php echo $doctype; ?>
			
			<head>
				<title>Subscription Management Errors - Please Correct</title>
				<meta http-equiv="content-type" content="text/html;charset=utf-8" />
				<meta http-equiv="Content-Style-Type" content="text/css" />
			</head>
			
			<body>
				<h1>There were some errors with your subscription management.</h1>
				<?php echo $message; ?>
				<p>To try again, please visit this page</p>
				<blockquote><a id="subscription" name="subscription" title="Subscription Form" href="<?php echo $formurl; ?>"><?php echo $formurl; ?></a></blockquote>
			
			</body>
			</html>
			
			<?php exit; ?>
			<?php			
			
		}
		/*
		*	END formSubmitProcess FUNCTION
		*/
		
		/*
		*	START formatMailman FUNCTION
		*/
		function formatMailman($serialized_string){
			$body = NULL;
			$ar_mailing = unserialize( $serialized_string );
			
			// Properly format email
			$mailto = str_replace('@', '-request@', $ar_mailing['maillist']);
			// Format body
			$body = $ar_mailing['action'] . ' address=' . $ar_mailing['email'];
			$ar_mailing = serialize( array(
								'to'		=>	$mailto,
								'subject'		=>	$ar_mailing['action'],
								'message'			=>	$body
							) );
			return $ar_mailing;
		}
		/*
		*	END formatMailman FUNCTION
		*/
		
		/*
		*	START formatEzmlm FUNCTION
		*/
		function formatEzmlm($serialized_string){
			$ar_mailing = unserialize( $serialized_string );
			
			// Properly format email
			$email = str_replace('@', '=', $ar_mailing['email']);
			// Format body
			$body = $ar_mailing['action'] . ' address=' . $ar_mailing['email'];
			// Create mailto
			$mailto = str_replace('@', '-'. $ar_mailing['action'] .'-'. $email . '@', $ar_mailing['maillist']);
			$ar_mailing = serialize( array(
								'to'		=>	$mailto,
								'subject'		=>	$ar_mailing['action'],
								'message'			=>	$body
							) );
			return $ar_mailing;
		}
		/*
		*	END formatEzmlm FUNCTION
		*/
		
		// Verify string for valid name format
		function is_name_valid($strname) { 
		  if(preg_match('/^[a-zA-Z][a-zA-Z\',\.\- \s]*$/', $strname)) return TRUE; 
		  else return FALSE; 
		}

	}	//End Class ggisSubscribe
	
	// SUBSTANTIATE AND ACT USING CLASS	
	if (class_exists("ggisSubscribe")) {
		$ggisSubscribe = new ggisSubscribe();
	}
	
	//Actions and Filters   
	if (isset($ggisSubscribe)) {
		//Actions
		add_action('init', array(&$ggisSubscribe, 'formSubmitProcess'));
		add_action('wp_head', array(&$ggisSubscribe, 'addHeaderCode'));
		add_action('admin_menu', array(&$ggisSubscribe, 'ggis_admin_add_pages'));
		//Filters
		add_filter('the_content', array(&$ggisSubscribe, 'the_content_filter')); 
		add_filter('widget_text', array(&$ggisSubscribe, 'widget_text_filter'));
	}

}	// End ggis Subscribe

?>