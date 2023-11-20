<?php

include_once("common.php");

$userId = $_GET["id"];

if(isset($_GET["active"])){
	$active = $_GET["active"];

	$wp_user_object = new WP_User($userId);

    $wp_user_object->set_role('coadmin');	

}

//	Get all users
$users = get_users(array(
	'meta_key' => 'apiKey',
	'meta_value' => getApiKey(),
	'orderby' => 'display_name',
    'order' => 'ASC'
));

$activeUsers = [];
$inactiveUsers = [];

foreach($users as $user){
	$u = new StdClass();
	$u->id = $user->ID;
	$u->fullName = $user->data->display_name;
	$u->admin = isAdmin($user);
	$u->active = count($user->caps) > 0;
	
	if($u->active){
		array_push($activeUsers, $u);
	}
	else{
		array_push($inactiveUsers, $u);
	}
}

foreach($inactiveUsers as $user){
	array_push($activeUsers, $user);
}
echo json_encode($activeUsers);