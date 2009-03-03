<?php
/**
* LG Member List extension file
* 
* This file must be placed in the
* /system/extensions/ folder in your ExpressionEngine installation.
*
* @package LgMemberList
* @version 1.2.1
* @author Leevi Graham <http://leevigraham.com>
* @copyright 2007
* @see http://leevigraham.com/cms-customisation/expressionengine/addon/lg-member-list/
* @copyright Copyright (c) 2007-2008 Leevi Graham
* @license {@link http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported} All source code commenting and attribution must not be removed. This is a condition of the attribution clause of the license.
*/

if ( ! defined('EXT')) exit('Invalid file request');

/**
* This extension adds a new custom field type to {@link http://expressionengine.com ExpressionEngine} that displays a drop down list of members from selected groups
*
* @package LgMemberList
* @version 1.2.1
* @author Leevi Graham <http://leevigraham.com>
* @copyright 2007
* @see http://leevigraham.com/cms-customisation/expressionengine/addon/lg-member-list/
* @copyright Copyright (c) 2007-2008 Leevi Graham
* @license {@link http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported} All source code commenting and attribution must not be removed. This is a condition of the attribution clause of the license.
* @todo Add per field custom settings
*
*/
class Lg_member_list {

	/**
	* Extension settings
	* @var array
	*/
	var $settings			= array();

	/**
	* Extension name
	* @var string
	*/
	var $name				= 'LG Member List';

	/**
	* Extension version
	* @var string
	*/
	var $version			= '1.2.1';

	/**
	* Extension description
	* @var string
	*/
	var $description		= 'Creates a member list custom field type';

	/**
	* If $settings_exist = 'y' then a settings page will be shown in the ExpressionEngine admin
	* @var string
	*/
	var $settings_exist 	= 'y';

	/**
	* Link to extension documentation
	* @var string
	*/
	var $docs_url			= 'http://leevigraham.com/cms-customisation/expressionengine/addon/lg-member-list/';

	/**
	* Custom field type id
	* @var string
	*/
	var $type 				= "member_list";



	/**
	* PHP4 Constructor
	*
	* @see __construct()
	*/
	function Lg_member_list($settings='')
	{
		$this->__construct($settings);
	}



	/**
	* PHP 5 Constructor
	*
	* @param	array|string $settings Extension settings associative array or an empty string
	* @since	Version 1.2.0
	*/
	function __construct($settings='')
	{
		$this->settings = $settings;
	}



	/**
	* Configuration for the extension settings page
	*
	* @return	array The settings array
	*/
	function settings()
	{
		global $LANG;
		$settings = array();
		$settings['member_groups'] 	= "";
		$settings['size'] 			= "";
		$settings['multiple']		= array('s', array(1 => $LANG->line('yes'), 0 => $LANG->line('no')), 1);
		
		return $settings;
	}



	/**
	* Activates the extension
	*
	* @return	bool Always TRUE
	*/
	function activate_extension()
	{
		global $DB;
		
		$default_settings = serialize(
								array(
									'member_groups' => '1',
									'size' 			=> '1',
									'multiple'		=> '0',
								)
							);

		$hooks = array(
			'publish_admin_edit_field_extra_row'	=> 'publish_admin_edit_field_extra_row',
			'publish_form_field_unique'				=> 'publish_form_field_unique',
			'show_full_control_panel_end' 			=> 'show_full_control_panel_end',
			'submit_new_entry_start'				=> 'submit_new_entry_start'
		);

		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
											array('extension_id' 	=> '',
												'class'			=> get_class($this),
												'method'		=> $method,
												'hook'			=> $hook,
												'settings'		=> $default_settings,
												'priority'		=> 10,
												'version'		=> $this->version,
												'enabled'		=> "y"
											)
										);
		}

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}



	/**
	* Updates the extension
	*
	* If the exisiting version is below 1.2 then the update process changes some
	* method names. This may cause an error which can be resolved by reloading
	* the page.
	*
	* @param	string $current If installed the current version of the extension otherwise an empty string
	* @return	bool FALSE if the extension is not installed or is the current version
	*/
	function update_extension($current = '')
	{
		global $DB;
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		if ($current < '1.2.0')
	    {
			$sql[] = "UPDATE `exp_extensions` SET `method` = 'publish_admin_edit_field_extra_row' WHERE `method` = 'edit_custom_field' AND `class` =  '" . get_class($this) ."' LIMIT 1";
			$sql[] = "UPDATE `exp_extensions` SET `method` = 'publish_form_field_unique' WHERE `method` = 'publish' AND `class` =  '" . get_class($this) ."' LIMIT 1";
			$sql[] = "UPDATE `exp_extensions` SET `method` = 'show_full_control_panel_end' WHERE `method` = 'edit_field_groups' AND `class` =  '" . get_class($this) ."' LIMIT 1";
			$sql[] = $DB->insert_string( 'exp_extensions', 
											array('extension_id' 	=> '',
												'class'			=> get_class($this),
												'method'		=> 'submit_new_entry_start',
												'hook'			=> 'submit_new_entry_start',
												'settings'		=> '',
												'priority'		=> 10,
												'version'		=> $this->version,
												'enabled'		=> "y"
											)
										);
		}

		$sql[] = "UPDATE exp_extensions SET version = '" . $DB->escape_str($this->version) . "' WHERE class = '" . get_class($this) . "'";

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
	}



	/**
	* Disables the extension the extension and deletes settings from DB
	*/
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}



	/**
	* Modifies the input of the select box if multiples are selected
	*
	* @param	array $data The data about this field from the database
	* @return	string $r The page content
	* @since 	Version 1.2.0
	*/
	function submit_new_entry_start()
	{
		global $DB, $IN;

		// get all the member list fields
		$query = $DB->query("SELECT field_id FROM `exp_weblog_fields` WHERE field_type = '".$this->type."'");

		// for each of the fields
		foreach ($query->result as $row)
		{
			// if this one has been used
			if($members = $IN->GBL('field_id_' . $row['field_id'], 'POST'))
			{
				// unset all of the extra post values like 'field_id_13_0'
				foreach ($_POST['field_id_' . $row['field_id']] as $key => $value)
				{
					unset($_POST['field_id_' . $row['field_id']. "_" . $key]);
				}
				// implode the array of multiple values into a string
				$_POST['field_id_' . $row['field_id']] = implode(",", $members);
			}
		}
	}



	/**
	* Takes the control panel html and replaces the drop down
	*
	* @param	string $out The control panel html
	* @return	string The modified control panel html
	* @since 	Version 1.2.0
	*/
	function show_full_control_panel_end( $out )
	{
		global $DB, $EXT, $IN, $REGX, $SESS;

		// -- Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE)
			$out = $EXT->last_call;

		// if we are displaying the custom field list
		if($IN->GBL('M', 'GET') == 'blog_admin' && ($IN->GBL('P', 'GET') == 'field_editor' || $IN->GBL('P', 'GET') == 'update_weblog_fields')  || $IN->GBL('P', 'GET') == 'delete_field')
		{
			// get the table rows
			if( preg_match_all("/C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=(\d*).*?<\/td>.*?<td.*?>.*?<\/td>.*?<\/td>/is", $out, $matches) )
			{
				// for each field id
				foreach($matches[1] as $key=>$field_id)
				{
					// get the field type
					$query = $DB->query("SELECT field_type FROM exp_weblog_fields WHERE field_id='" . $DB->escape_str($field_id) . "' LIMIT 1");

					// if the field type is wysiwyg
					if($query->row["field_type"] == $this->type)
					{
						$out = preg_replace("/(C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=" . $field_id . ".*?<\/td>.*?<td.*?>.*?<\/td>.*?)<\/td>/is", "$1" . $REGX->form_prep($this->name) . "</td>", $out);
					}
				}
			}
		}
		return $out;
	}



	/**
	* Adds the custom field option to the {@link http://expressionengine.com/docs/cp/admin/weblog_administration/custom_fields_edit.html Custom Weblog Fields - Add/Edit page}.
	*
	* @param	array $data The data about this field from the database
	* @return	string $r The page content
	* @since 	Version 1.2.0
	*/
	function publish_admin_edit_field_extra_row( $data, $r )
	{
		global $EXT, $REGX;

		// -- Check if we're not the only one using this hook
		if($EXT->last_call !== false){$r = $EXT->last_call;}

		// set the options for the cell
		$items = array(
			"date_block" => "block",
			"select_block" => "none",
			"pre_populate" => "none",
			"text_block" => "none",
			"textarea_block" => "none",
			"rel_block" => "none",
			"relationship_type" => "none",
			"formatting_block" => "none",
			"formatting_unavailable" => "block",
			"direction_available" => "none",
			"populate_block_man" => "block",
			"direction_unavailable" => "none"
		);

		// is this field type equal to this type
		$selected = ($data["field_type"] == $this->type) ? " selected='true'" : "";

		// Add the option to the select drop down
		$r = preg_replace("/(<select.*?name=.field_type.*?value=.select.*?[\r\n])/is", "$1<option value='" . $REGX->form_prep($this->type) . "'" . $selected . ">" . $REGX->form_prep($this->name) . "</option>\n", $r);

		$js = "$1\n\t\telse if (id == '".$this->type."'){";

		foreach ($items as $key => $value)
		{
			$js .= "\n\t\t\tdocument.getElementById('" . $key . "').style.display = '" . $value . "'";
		}

		// automatically make this field have no formatting
		$js.= "\ndocument.field_form.field_fmt.selectedIndex = 0;\n";

		$js .= "\t\t}";

		 // -- Add the JS
		$r = preg_replace("/(id\s*==\s*.rel.*?})/is", $js, $r);

		// -- If existing field, select the proper blocks
		if(isset($data["field_type"]) && $data["field_type"] == $this->type)
		{

			foreach ($items as $key => $value)
			{
				preg_match('/(id=.' . $key . '.*?display:\s*)block/', $r, $match);

				// look for a block
				if(count($match) > 0 && $value == "none")
				{
					$r = str_replace($match[0], $match[1] . $value, $r);
				}
				elseif($value == "block")
				{ // no block matches

					preg_match('/(id=.' . $key . '.*?display:\s*)none/', $r, $match);

					if(count($match) > 0)
					{
						$r = str_replace($match[0], $match[1] . $value, $r);
					}
				}
			}
		}
		return $r;
	}



	/**
	* Renders the custom field in the publish / edit form and sets a $SESS->cache array element so we know the field has been rendered
	*
	* @param	array $row Parameters for the field from the database
	* @param	string $field_data If entry is not new, this will have field's current value
	* @return	string The custom field html
	* @since 	Version 1.2.0
	*/
	function publish_form_field_unique( $row, $field_data )
	{
		global $DB, $DSP, $EXT, $IN, $LANG, $REGX;

		// load the lang file
		$LANG->fetch_language_file('lg_member_list');
	
		// -- Check if we're not the only one using this hook
		$r = ($EXT->last_call !== false) ? $EXT->last_call : "";
		
		// if we have a match on field types
		if($row["field_type"] == $this->type)
		{
			$members = FALSE;

			// new entry
			if(isset($field_data) === FALSE)
			{
				$selected_members = ($IN->GBL('field_id_'.$row['field_id'], 'POST') === FALSE) ? array() : explode(",", $IN->GBL('field_id_'.$row['field_id'], 'POST'));
			}
			// edit
			else
			{
				$selected_members = explode(",", $field_data);
			}

			// check that the members have not been loaded before on this page 
			if ( ! isset($SESS->cache['lg_member_list']['members']))
			{
				// create the DB query
				$query = $DB->query("
					SELECT
						exp_members.member_id AS member_id,
						exp_members.screen_name AS screen_name,
						exp_member_groups.group_title AS group_title, 
						exp_member_groups.group_id AS group_id
					FROM
						exp_members
					INNER JOIN
						exp_member_groups
					ON
						exp_members.group_id = exp_member_groups.group_id
					WHERE 
						exp_member_groups.group_id IN (" . $DB->escape_str($this->settings['member_groups']) . ") 
					ORDER BY 
						exp_member_groups.group_id ASC , exp_members.screen_name ASC ");
				
				// if rows returned
				if ($query->num_rows > 0)
				{
					// for each result as member
					foreach ($query->result as $member)
					{
						// add the record to the members cache
						$SESS->cache['lg_member_list']['members'][$member['member_id']] = $member;
					}
					$members = $SESS->cache['lg_member_list']['members'];
				}
			}

			// if there are members
			if($members !== FALSE)
			{
				// we dont know what the group is
				$group = null;
				$groups = explode(",", $this->settings['member_groups']);
				
				$multiple = ($this->settings['multiple']) ? 'multiple="multiple"' : '';
				
				// create the select drop down
				$r .= "<input type='hidden' name='field_ft_" . $row['field_id'] . "' value='none' />";
				$r .= "\n<select id='field_id_" . $row['field_id'] . "' name='field_id_" . $row['field_id'] . "[]' size='" . $this->settings['size'] . "' " . $multiple ." >";

				// create empty option
				// check to see if this one is selected
				$selected = (in_array('-1', $selected_members)) ? " selected='selected' " : "";
				$r .= "\n\t<option value='-1'" . $selected . ">" . $LANG->line('choose_member') . "</option>";
				
				// for each of our members in the array
				foreach($members as $member)
				{
					// if the member group is in the groups settings array
					if (in_array($member['group_id'], $groups))
					{
						// check to see if the ID of this member matches our field data
						$selected = (in_array($member['member_id'], $selected_members)) ? " selected='selected' " : "";

						// if the current group does not equal the group title
						if($group != $member['group_title'])
						{
							// if this is not the first group
							if($group != null)
							{
								// close the optgroup
								$r .= "\n\t</optgroup>";
							}
							// set the current group
							$group = $member['group_title'];
							// open another opt group
							$r .= "\n\t<optgroup label='" . $REGX->form_prep($member['group_title']) ."'>";
						}
						// add the member option
						$r .= "\n\t\t<option value='" . $member['member_id'] . "'" . $selected . " > " . $REGX->form_prep($member['screen_name']) . "</option>";
					}
				}
				// close the last opt group and select box
				$r .= "\n\t</optgroup>\n</select>";
			}
			// no members in the selected groups
			else
			{
				// add error instead of select box
				$r .= "<div class='highlight_alt'>" . $LANG->line('no_members') . "</div>";
			}
		}
		return $r;
	}

}

?>