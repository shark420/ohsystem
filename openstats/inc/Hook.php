<?php
class hooking{
	private $hooks;
	function __construct()
	{
		$this->hooks=array();
	}
	function AddEvent($where,$callback,$priority=50)
	{
		if(!isset($this->hooks[$where]))
		{
			$this->hooks[$where]=array();
		}
		$this->hooks[$where][$callback]=$priority;
	}
	function RemoveAction($where,$callback)
	{
		if(isset($this->hooks[$where][$callback]))
			unset($this->hooks[$where][$callback]);
	}
	function execute($where,$args=array())
	{
		if(isset($this->hooks[$where]) && is_array($this->hooks[$where]))
		{
			arsort($this->hooks[$where]);
			foreach($this->hooks[$where] as $callback=>$priority)
			{
				call_user_func_array($callback,$args);
			}
		}
	}
}

$hooking_daemon=new hooking;

function AddEvent($where,$callback,$priority=50)
{
	global $hooking_daemon;
	if(isset($hooking_daemon))
		$hooking_daemon->AddEvent($where,$callback,$priority = 1);
}
function RemoveAction($where,$callback)
{
	global $hooking_daemon;
	if(isset($hooking_daemon))
	$hooking_daemon->RemoveAction($where,$callback);
}
function execute_action($where,$args=array())
{
	global $hooking_daemon;
	if(isset($hooking_daemon))
	$hooking_daemon->execute($where,$args);
}

//os_init    - before OpenStats basic function (can be used to handle POST or GET variables)
//os_start   - before HTML
//os_head    - before close head tag
//os_content - content after main menu
//os_footer  - footer
//os_after_footer - content after OS footer

//os_init: Not working yet - plugins not loaded...in progress
function os_init() { 
  execute_action("os_init");
}

function os_start() {
  execute_action("os_start");
}
//Reserved for javascript (from v4)
function os_js() {
  execute_action("os_js");
}

function os_head() {
  execute_action("os_head");
}

function os_top_menu() {
  execute_action("os_top_menu");
}

function os_main_menu() {
  execute_action("os_main_menu");
}

function os_content() {
  execute_action("os_content");
}

function os_comment_form() {
  execute_action("os_comment_form");
}

function os_after_comment_form() {
  execute_action("os_after_comment_form");
}

function os_after_content() {
  execute_action("os_after_content");
}

function os_footer() {
  execute_action("os_footer");
}

function os_after_footer() {
  execute_action("os_after_footer");
}

//more hooks
function os_custom_user_fields() {
  execute_action("os_custom_user_fields");
}

function os_display_custom_fields() {
  execute_action("os_display_custom_fields");
}

//add to MISC menu link
function os_add_menu_misc() {
  execute_action("os_add_menu_misc");
}

function os_login_fields() {
  execute_action("os_login_fields");
}

function os_registration_fields() {
  execute_action("os_registration_fields");
}

function os_add_meta() {
  execute_action("os_add_meta");
}
?>