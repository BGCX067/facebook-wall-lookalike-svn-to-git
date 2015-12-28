<?php

error_reporting(E_ALL);
require_once('../fb-lookalike.php');

$profilePic = 'gfx/profilepicture.jpg';
$root_lib = '../';
$options = array('language' => 'EN');
$fb = new FacebookWallLookalike($root_lib, $options);

/* MANUAL DATA */

$data = array(
	'from' => 'Random User',
	'message' => 'I\'m posting some data!',
	'profile_link' => '', // where the profile picture and name lead to
	'profile_image' => $profilePic,
	'entry_link' => 'http://www.google.com', // where the 'comment' link leads to
	'time' => time(),
);
$comments = array(
	array(
		'from' => 'Another User',
		'message' => 'wow, really cool!',
		'profile_image' => $profilePic,
		'profile_link' => '',
		'time' => time(),
	),
	array(
		'from' => 'Super Uschi',
		'message' => 'kewl, n1!',
		'profile_image' => $profilePic,
		'profile_link' => '',
		'time' => time(),
	)
);
$fb->addEntryManual($data, $fb->STATUS, 3, $comments);


$data = array(
	'from' => 'Random User 2',
	'message' => 'I\'m posting a link!',
	'profile_link' => '', // where the profile picture and name lead to
	'profile_image' => $profilePic,
	'entry_link' => 'www.google.com', // where the 'comment' link leads to
	'caption' => 'some url', // used for the link type
	'link' => 'www.google.com',
	'picture' => 'http://cn20081602.p-client.net/Images/google_64.png',
	'time' => time(),
);
$fb->addEntryManual($data, $fb->LINK);

/* GRAPH DATA */

require_once('fb/facebook.php');

$APP_ID = 'APP ID';
$APP_SECRET = 'APP SECRET';

$facebook = new Facebook(array(
  'appId'  => $APP_ID,
  'status' => true,
  'secret' => $APP_SECRET,
));

$fb->setFacebookObject($facebook);

$limit = 5;
$page_id = 'coca-cola';
$page_id = '215621341811350';
$messages = $facebook->api("/{$page_id}/feed?limit={$limit}", 'get');
$entries = $messages['data'];

foreach ($entries as $entry) {
	$fb->addEntryGraph($entry);
}

?>

<html>
	<head>
		<title>facebook wall</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
		<link rel="stylesheet" type="text/css" href="<?php echo $root_lib; ?>fb-format.css" />
	</head>
	<body>
		<?php $fb->printout(); ?>
	</body>
</html>
