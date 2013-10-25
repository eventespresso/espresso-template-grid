<?php
/*
  Plugin Name: Event Espresso Template - Grid View
  Plugin URI: http://www.eventespresso.com
  Description: This template creates a grid style view of events with images. [EVENT_CUSTOM_VIEW template_name="grid" max_days="30" category_identifier="concerts"]
  Version: 1.0
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2013 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA

*/

//Requirements: CSS skills to customize styles, HTML/PHP to restructure.
//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "grid",

// IMPORTANT you may need to tweak the box or title sizes if your events have long titles.


add_action('action_hook_espresso_custom_template_grid','espresso_custom_template_grid');

function espresso_custom_template_grid(){

	//Defaults
	global $org_options, $this_event_id, $events, $ee_attributes;

		//Load the css file
		wp_register_style( 'espresso_custom_template_grid', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/grid/style.css" );
		wp_enqueue_style( 'espresso_custom_template_grid');
		$columnwidth = '';


	//Uncomment to view the data being passed to this file
	//echo '<h4>$events : <pre>' . print_r($events,true) . '</pre> <span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';

	?>
	<div id="mainwrapper" class="espresso-grid">

		<?php
		foreach ($events as $event){
			//Debug
			$this_event_id		= $event->id;
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			$event_status 		= __('Register Now!', 'event_espresso');

			//use the wordpress date format.
			$date_format = get_option('date_format');


			$att_num = get_number_of_attendees_reg_limit($event->id, 'num_attendees');
			//Uncomment the below line to hide an event if it is maxed out
			//if ( $att_num >= $event->reg_limit  ) { continue; $live_button = 'Closed';  }
			if ( $att_num >= $event->reg_limit ) { $event_status = __('Sold Out', 'event_espresso');  } elseif ( event_espresso_get_status($event->id) == 'NOT_ACTIVE' ) { $event_status = 'Closed';}

			//waitlist
			if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
				$registration_url 	= espresso_reg_url($event->overflow_event_id);
				$event_status 		= __('Sold Out - Join Waiting List', 'event_espresso');
			}

			//Gets the member options, if the Members add-on is installed.
			$member_options = get_option('events_member_settings');

			if(!isset($default_image)) { $default_image = ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/grid/default.jpg';}
			$image = isset($event_meta['event_thumbnail_url']) ? $event_meta['event_thumbnail_url'] : $default_image;

			//uncomment this and comment out the above line if you want to use the Organisation logo
			//if($image == '') { $image = $org_options['default_logo_url']; }

				?>


            <div class="ee_grid_box item" style="width:<?php echo $columnwidth; ?>px">
                <a id="a_register_link-<?php echo $event->id; ?>" href="<?php echo $registration_url; ?>" class="darken">
                    <img src="<?php echo $image; ?>" alt="" />
                    <span>
                        <h2>
                        <span>

                            <?php if ( function_exists('espresso_members_installed') && espresso_members_installed() == true && !is_user_logged_in() && ($member_only == 'Y' || $member_options['member_only_all'] == 'Y') ) {
                            echo __('Member Only', 'event_espresso'); } else { ?>

                            <?php echo stripslashes($event->event_name); ?><br />

                            <?php if($event->event_cost === "0.00") { echo __('FREE', 'event_espresso'); } else { echo $org_options['currency_symbol'] . $event->event_cost;  } ?><br />

                            <?php echo date($date_format, strtotime($event->start_date)) ?><br />

                            <?php echo $event_status; ?>

                            <?php 			}// close is_user_logged_in	 ?>

                        </span>
                        </h2>
                    </span>
                </a>
            </div>

		<?php
		 } //close foreach ?>
	</div>

<?php }

/**
 * hook into PUE updates
 */
//Update notifications
add_action('action_hook_espresso_template_grid_update_api', 'espresso_template_grid_load_pue_update');
function espresso_template_grid_load_pue_update() {
	global $org_options, $espresso_check_for_updates;
	if ( $espresso_check_for_updates == false )
		return;
		
	if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php')) { //include the file 
		require(EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php' );
		$api_key = $org_options['site_license_key'];
		$host_server_url = 'http://eventespresso.com';
		$plugin_slug = array(
			'premium' => array('p'=> 'espresso-template-grid'),
			'prerelease' => array('b'=> 'espresso-template-grid-pr')
			);
		$options = array(
			'apikey' => $api_key,
			'lang_domain' => 'event_espresso',
			'checkPeriod' => '24',
			'option_key' => 'site_license_key',
			'options_page_slug' => 'event_espresso',
			'plugin_basename' => plugin_basename(__FILE__),
			'use_wp_update' => FALSE
		);
		$check_for_updates = new PluginUpdateEngineChecker($host_server_url, $plugin_slug, $options); //initiate the class and start the plugin update engine!
	}
}