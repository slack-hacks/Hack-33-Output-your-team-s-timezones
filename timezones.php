<?php

	// Your oAuth Token
	$token = '[AUTH TOKEN]';

	// Set the timezone independent of the server
	date_default_timezone_set("UTC");

	// Get the channel ID the script was triggered from
	$channelID = $_POST['channel_id'];

	// Include slack library from https://github.com/10w042/slack-api
	include 'Slack.php';

	// Create new Slack instance
	$Slack = new Slack($token);

	// Get the cuurent channel and user
	$channel = $Slack->call('channels.info', array('channel' => $channelID));

	// Get the time right now
	$now = time();

	// Create an empty array
	$times = array();

	// Loop through chanel members
	foreach($channel['channel']['members'] as $mid) {

		// Create member instance with ID
		$user = $Slack->call('users.info', array('user' => $mid));

		// If the user is a bot, or doesn't have a timezone offset, skip them
		if(($user['user']['is_bot'] == true) || (!isset($user['user']['tz_offset']))) {
			continue;
		}

		// Work out offset in hours from seconds
		$userOffset = ($user['user']['tz_offset'] / 60) / 60;

		// Get the name of the user
		$name = ($user['user']['real_name']) ? $user['user']['real_name'] : $user['user']['name'];

		// Add the timezeone offset to current time
		$userTime = $now + $user['user']['tz_offset'];

		// Create an array key based on offset (so we can sort) and add 12 (as it could be -11 at worst)
		$key = $userOffset + 12;

		// Append the details to the array as the key
		$times[$key][] = '*' . $name . '*: ' .
		date('h:i A', $userTime) . ' local time on ' . date('jS M', $userTime) .
		' (UTC' . sprintf("%+d", $userOffset) . ' hours _' . $user['user']['tz_label'] . '_)';

	}

	// Sort array items by key
	ksort($times);

	// Flatten the array and implode it - separated by new lines
	$text = implode("\n", call_user_func_array(
		'array_merge', $times
	));

	// Post back to the channel
	$Slack->call('chat.postMessage', array(
		'channel' => $channelID,
		'text' => $text,
		'username' => 'Times',
		'icon_emoji' => ':timer_clock:'
	));