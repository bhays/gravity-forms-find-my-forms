<?php /*

Plugin Name: Gravity Forms Find My Forms
Plugin URI: http://github.com/bhays/gravity-forms-find-my-forms
Description: Generate a list of all your forms and which pages they're being used on
Version: 1.0
Author: Ben Hays
Author URI: http://benhays.com
License: GPLv2
*/

add_action('init',  array('GFFindem', 'init'));

class GFFindem {

	private static $path = "gravity-forms-find-my-forms/gravity-forms-find-my-forms.php";
	private static $url = "http://www.gravityforms.com";
	private static $slug = "gravity-forms-find-my-forms";
	private static $version = "1.0";
	private static $min_gravityforms_version = "1.5";

	//Plugin starting point. Will load appropriate files
	public static function init(){

		//supports logging
		add_filter("gform_logging_supported", array("GFFindem", "set_logging_supported"));

        add_action('after_plugin_row_' . self::$path, array('GFFindem', 'plugin_row') );

		if(!self::is_gravityforms_supported())
		{
			return;
		}

		//creates the subnav left menu
        add_filter('gform_addon_navigation', array('GFFindem', 'create_menu'));
		
	}
	
	public static function create_menu($menus)
	{
		$menus[] = array("name" => "gf_findem", "label" => __("Find My Forms", "gravity-forms-findem"), "callback" =>  array("GFFindem", "findem_page"));
		
		return $menus;
	}
    
	public static function findem_page()
	{
		global $wpdb;
		$pattern = get_shortcode_regex();
		$forms = array();
		
		// Query forms
		$table = $wpdb->prefix.'posts';
		$sql = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_content LIKE %s;", '%'.like_escape('[gravityform').'%');
		$results = $wpdb->get_results($sql);
		
		// Loop through results
		foreach( $results as $res )
		{
			preg_match_all('/'.$pattern.'/s', $res->post_content, $matches);
			
			if( is_array($matches) && $matches[2][0] == 'gravityform' )
			{
				//$matches[3] contains string of values	
				preg_match('/id="([^"]+)"/', $matches[3][0], $m);
				// Form is $m[1]
				$form_data = RGFormsModel::get_form($m[1]);
				$forms[] = array(
					'form_id' => $m[1], 
					'form_title' => $form_data->title,
					'post_id' => $res->ID,
					'post_title' => $res->post_title,
					'post_type' => $res->post_type,
				);
			}
		}
		
		// Display page	
	?>
		<div class="wrap">
			<h2 style="margin-bottom:10px;"><?php _e("Find My Forms", "gravity-forms-findem"); ?></h2>
			<table class="widefat fixed">
				<thead>
					<th scope="col">Form Name</th>
					<th scope="col">Post Name</th>
					<th scope="col" width="100">Edit Form</th>
					<th scope="col" width="100">Edit Post</th>
					<th scope="col" width="100">View Post</th>
				</thead>
				<tbody>
					<?php foreach($forms as $f): ?>
					<tr>
						<td><a title="Edit form" href="admin.php?page=gf_edit_forms&id=<?php echo $f['form_id'] ?>"><?php echo $f['form_title'] ?></a></td>
						<td><a title="Edit <?php echo $f['post_type'] ?>" href="post.php?post=<?php echo $f['post_id'] ?>&action=edit"><?php echo $f['post_title'] ?></a> (<?php echo $f['post_type'] ?>)</td>
						<td><a href="admin.php?page=gf_edit_forms&id=<?php echo $f['form_id'] ?>">Edit form</a></td>
						<td><a href="post.php?post=<?php echo $f['post_id'] ?>&action=edit">Edit <?php echo $f['post_type'] ?></a></td>
						<td><a href="<?php echo get_permalink($f['post_id']) ?>">View <?php echo $f['post_type'] ?></a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php
	}
    
	public static function plugin_row()
	{
		if(!self::is_gravityforms_supported())
		{
			$message = sprintf(__("%sGravity Forms%s 1.7 is required. Activate it now or %spurchase it today!%s"), "<a href='http://benjaminhays.com/gravityforms'>", "</a>", "<a href='http://benjaminhays.com/gravityforms'>", "</a>");
			self::display_plugin_message($message, true);
		}
    }

	public static function display_plugin_message($message, $is_error = false)
	{
		$style = '';
		if($is_error)
		{
			$style = 'style="background-color: #ffebe8;"';
		}
		echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
	}
    
	private static function is_gravityforms_installed(){
		return class_exists("RGForms");
	}

	private static function is_gravityforms_supported(){
		if(class_exists("GFCommon")){
			$is_correct_version = version_compare(GFCommon::$version, self::$min_gravityforms_version, ">=");
			return $is_correct_version;
		}
		else{
			return false;
		}
	}
}
