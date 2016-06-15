<?php
	// Set the timezone independant of the server
	date_default_timezone_set("UTC");

	// Return something to indicate it's working
	header('Content-Type: application/json');
	echo json_encode(array(
		'text' => 'Calculating...'
	));

	// Get the channel ID
	$channelID = $_POST['channel_id'];

	// Include slack library from https://github.com/10w042/slack-api
	include 'Slack.php';

	// Create new Slack instance
	$Slack = new Slack('[API KEY]');

	// Ge the cuurent channel and user
	$channel = $Slack->call('channels.info', array('channel' => $channelID));

	// Get the time right now
	$now = time();

	// Create an empty array
	$times = array();

	// Loop through chanel members
	foreach($channel['channel']['members'] as $mid) {

		// Create member instance with ID
		$member = $Slack->call('users.info', array('user' => $mid));

		// if the user is a bot, or doesn't have a timezone offset, skip them
		if(($member['user']['is_bot'] == true) || (!isset($member['user']['tz_offset']))) {
			continue;
		}

		// Work out offset in hours
		$userOffset = ($member['user']['tz_offset'] / 60) / 60;

		// Create an array key based on offset (so we can sort) and add 12 (as it could be -11 at worst)
		$key = $userOffset + 12;

		// Get the name of the user
		$name = ($member['user']['real_name']) ? $member['user']['real_name'] : $member['user']['name'];

		// Append the details to the array as the key
		$times[$key][] = '*' . $name . '*: ' .
		date('h:ia jS M', $now + $member['user']['tz_offset']) .
		' (' . sprintf("%+d", $userOffset) . ' hours _' . $member['user']['tz_label'] . '_)';

	}

	// Sort array items by key
	ksort($times);

	// Flatten the array and implode it - seperated by new lines
	$text = implode("\n", call_user_func_array('array_merge', $times));

	// Post back to the channel
	$Slack->call('chat.postMessage', array(
		'channel' => $channelID,
		'text' => $text,
		'username' => 'Times',
		'icon_emoji' => ':timer_clock:'
	));