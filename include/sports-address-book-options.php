<?php
/**
 * Settings page
 *
 * @since      1.0.0
 *
 * @package    Sports_Address_Book
 */
if(isset($_GET['post_type']) && isset($_GET['page']) && $_GET['post_type'] == 'address-book' && $_GET['page'] == 'options')
{
	add_action( 'init', 'sports_address_book_settings_section_init' );
}
function sports_address_book_settings_section_init(){
	$options = get_option( 'sports_address_book_settings' );
	$filds = array(
		'sports_address_book_select_field_0' => 'date',
		'sports_address_book_select_field_1' => 'asc',
		'sports_address_book_text_field_2' => '10',
		'sports_address_book_text_field_3' => __( 'Select Institution',WP_CF_SAB ),
		'sports_address_book_text_field_4' => __( 'Select City',WP_CF_SAB ),
		'sports_address_book_text_field_5' => __( 'Select Sport',WP_CF_SAB ),
		'sports_address_book_text_field_6' => __( 'Keywords',WP_CF_SAB ),
		'sports_address_book_text_field_7' => __( 'Search',WP_CF_SAB ),
		'sports_address_book_text_field_8' => __( 'No Search Results',WP_CF_SAB ),
		'sports_address_book_text_field_9' => __( 'Name',WP_CF_SAB ),
		'sports_address_book_text_field_10' => __( 'Address',WP_CF_SAB ),
		'sports_address_book_text_field_11' => __( 'Phone',WP_CF_SAB ),
		'sports_address_book_text_field_12' => __( 'E-mail',WP_CF_SAB ),
		'sports_address_book_text_field_13' => __( 'Web',WP_CF_SAB ),
		'sports_address_book_text_field_14' => __( 'More Info',WP_CF_SAB ),
		'sports_address_book_text_field_15' => __( '< prev',WP_CF_SAB ),
		'sports_address_book_text_field_16' => __( 'next >',WP_CF_SAB ),
		'sports_address_book_text_field_17' => '',
	);
	$save = array();
	if($options===false)
	{
		$save=array();
		foreach($filds as $key=>$val)
		{
			$save[$key]=$val;
		}
		if(count($save)>0)
		{
			update_option('sports_address_book_settings',$save);
		}
	}
	else
	{
		$save=array();
		foreach($filds as $key=>$val)
		{
			if(isset($options[$key]) && empty($options[$key]))
				$save[$key]=$val;
			else
				$save[$key]=$options[$key];
		}
		if(count($save)>0)
		{
			update_option('sports_address_book_settings',$save);
		}
	}
}

add_action( 'admin_menu', 'sports_address_book_add_admin_menu' );
add_action( 'admin_init', 'sports_address_book_settings_init' );

function sports_address_book_add_admin_menu(  ) { 
	add_submenu_page( 'edit.php?post_type=address-book', __( 'Settings', WP_CF_SAB ), __( 'Settings', WP_CF_SAB ), 'manage_options', 'options', 'sports_address_book_options_page' );
}


function sports_address_book_settings_init(  ) { 

	register_setting( 'sports_address_book_page', 'sports_address_book_settings' );

	add_settings_section(
		'sports_address_book_pluginPage_section', 
		'', 
		'sports_address_book_settings_section_callback', 
		'sports_address_book_page'
	);

	add_settings_field( 
		'sports_address_book_select_field_0', 
		__( 'Results Ordered By', WP_CF_SAB ), 
		'sports_address_book_select_field_0_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_select_field_1', 
		__( 'Order', WP_CF_SAB ), 
		'sports_address_book_select_field_1_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_2', 
		__( 'Posts Per Page', WP_CF_SAB ), 
		'sports_address_book_text_field_2_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_3', 
		__( 'Label Institution', WP_CF_SAB ), 
		'sports_address_book_text_field_3_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_4', 
		__( 'Label City', WP_CF_SAB ), 
		'sports_address_book_text_field_4_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_5', 
		__( 'Label Sport', WP_CF_SAB ), 
		'sports_address_book_text_field_5_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_6', 
		__( 'Label Search', WP_CF_SAB ), 
		'sports_address_book_text_field_6_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_7', 
		__( 'Label Button', WP_CF_SAB ), 
		'sports_address_book_text_field_7_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_8', 
		__( 'Label No Results', WP_CF_SAB ), 
		'sports_address_book_text_field_8_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_9', 
		__( 'Label Name', WP_CF_SAB ), 
		'sports_address_book_text_field_9_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_10', 
		__( 'Label Address', WP_CF_SAB ), 
		'sports_address_book_text_field_10_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_11', 
		__( 'Label Phone', WP_CF_SAB ), 
		'sports_address_book_text_field_11_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_12', 
		__( 'Label Email', WP_CF_SAB ), 
		'sports_address_book_text_field_12_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_13', 
		__( 'Label URL', WP_CF_SAB ), 
		'sports_address_book_text_field_13_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_14', 
		__( 'Label More Info', WP_CF_SAB ), 
		'sports_address_book_text_field_14_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_15', 
		__( 'Label Prev', WP_CF_SAB ), 
		'sports_address_book_text_field_15_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);

	add_settings_field( 
		'sports_address_book_text_field_16', 
		__( 'Label Next', WP_CF_SAB ), 
		'sports_address_book_text_field_16_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);
	
	add_settings_field( 
		'sports_address_book_text_field_17', 
		__( 'Custom CSS', WP_CF_SAB ), 
		'sports_address_book_text_field_17_render', 
		'sports_address_book_page', 
		'sports_address_book_pluginPage_section' 
	);


}


function sports_address_book_select_field_0_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<select name='sports_address_book_settings[sports_address_book_select_field_0]'>
		<option value='date' <?php selected( $options['sports_address_book_select_field_0'], 'date' ); ?>><?php echo __('Date',WP_CF_SAB ); ?></option>
		<option value='post_title' <?php selected( $options['sports_address_book_select_field_0'], 'post_title' ); ?>><?php echo __('Title',WP_CF_SAB ); ?></option>
	</select>

<?php

}


function sports_address_book_select_field_1_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<select name='sports_address_book_settings[sports_address_book_select_field_1]'>
		<option value='asc' <?php selected( $options['sports_address_book_select_field_1'], 'asc' ); ?>><?php echo __('Ascending order',WP_CF_SAB ); ?></option>
		<option value='desc' <?php selected( $options['sports_address_book_select_field_1'], 'desc' ); ?>><?php echo __('Descending order',WP_CF_SAB ); ?></option>
	</select>

<?php

}


function sports_address_book_text_field_2_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input type='number' name='sports_address_book_settings[sports_address_book_text_field_2]' value='<?php echo !empty($options['sports_address_book_text_field_2'])?(int)$options['sports_address_book_text_field_2']:10; ?>' min="1" max="999" maxlength="3">
	<?php

}


function sports_address_book_text_field_3_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_3]' value='<?php echo $options['sports_address_book_text_field_3']; ?>'>
	<?php

}


function sports_address_book_text_field_4_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_4]' value='<?php echo $options['sports_address_book_text_field_4']; ?>'>
	<?php

}


function sports_address_book_text_field_5_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_5]' value='<?php echo $options['sports_address_book_text_field_5']; ?>'>
	<?php

}


function sports_address_book_text_field_6_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_6]' value='<?php echo $options['sports_address_book_text_field_6']; ?>'>
	<?php

}


function sports_address_book_text_field_7_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_7]' value='<?php echo $options['sports_address_book_text_field_7']; ?>'>
	<?php

}


function sports_address_book_text_field_8_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_8]' value='<?php echo $options['sports_address_book_text_field_8']; ?>'>
	<?php

}


function sports_address_book_text_field_9_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_9]' value='<?php echo $options['sports_address_book_text_field_9']; ?>'>
	<?php

}


function sports_address_book_text_field_10_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_10]' value='<?php echo $options['sports_address_book_text_field_10']; ?>'>
	<?php

}


function sports_address_book_text_field_11_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_11]' value='<?php echo $options['sports_address_book_text_field_11']; ?>'>
	<?php

}


function sports_address_book_text_field_12_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_12]' value='<?php echo $options['sports_address_book_text_field_12']; ?>'>
	<?php

}


function sports_address_book_text_field_13_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_13]' value='<?php echo $options['sports_address_book_text_field_13']; ?>'>
	<?php

}


function sports_address_book_text_field_14_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_14]' value='<?php echo $options['sports_address_book_text_field_14']; ?>'>
	<?php

}


function sports_address_book_text_field_15_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_15]' value='<?php echo $options['sports_address_book_text_field_15']; ?>'>
	<?php

}


function sports_address_book_text_field_16_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<input style="width:100%; max-width:800px;" type='text' name='sports_address_book_settings[sports_address_book_text_field_16]' value='<?php echo $options['sports_address_book_text_field_16']; ?>'>
	<?php

}

function sports_address_book_text_field_17_render(  ) { 

	$options = get_option( 'sports_address_book_settings' );
	?>
	<textarea style="width:100%; max-width:800px; height:400px;" name='sports_address_book_settings[sports_address_book_text_field_17]'><?php echo $options['sports_address_book_text_field_17']; ?></textarea>
	<?php

}


function sports_address_book_settings_section_callback(  ) { 

	echo __( 'Here you can setup global options for Address Book plugin', WP_CF_SAB );

}

function sports_address_book_options_page(  ) { 

	?>
	<div class="wrap">
		<h1><?php echo __( 'Address Book Settings', WP_CF_SAB ); ?></h1>
		<form action='options.php' method='post'>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-2" class="postbox-container">
						<?php
						settings_fields( 'sports_address_book_page' );
						do_settings_sections( 'sports_address_book_page' );
						submit_button(__( 'Save Address Book Settings', WP_CF_SAB ));
						?>
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<div id="info" class="postbox">
							<button type="button" class="handlediv button-link" aria-expanded="false">
								<span class="screen-reader-text">Toggle panel: Special Promotion</span><span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<h2 class="hndle ui-sortable-handle"><span><?php echo __( 'Special Promotion', WP_CF_SAB ); ?></span></h2>
							<div class="inside" style="text-align:center">
								<h3><?php echo __( 'Increase Conversions Attaching Geographical Informations To WordPress', WP_CF_SAB ); ?></h3>
								<p><a href="<?php echo admin_url( 'plugin-install.php?s=CF+GeoPlugin&tab=search&type=term' ); ?>" target="_blank"><img src="http://ps.w.org/cf-geoplugin/assets/icon-128x128.png"></a></p>
								<p><?php echo __( 'Create Dynamic Content, Banners and Images on Your Website Based On Visitor Geo Location By Using Shortcodes With CF GeoPlugin.', WP_CF_SAB ); ?></p>
								<p><a href="<?php echo admin_url( 'plugin-install.php?s=CF+GeoPlugin&tab=search&type=term' ); ?>" target="_blank"><?php echo __( 'Learn More', WP_CF_SAB ); ?>!</a></p>
							</div>
						</div>
						
						<p style="text-align:center;"><a href="https://infinitumform.com" target="_blank"><img style="margin:0 auto;" src="https://infinitumform.com/shared/infinitumform-powered-128x37.png" alt="INFINITUM FORM"></a></p>
						
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php

}

?>