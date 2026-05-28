<?php

!defined('DEBUG') AND exit('Access Denied.');

!function_exists('checkin_do') AND include _include(APP_PATH.'model/checkin.func.php');

$action = param(1, 'do');

if($action == 'do') {
	if($method != 'POST') message(-1, lang('method_error'));
	if(empty($uid)) message(-1, '请先登录后再签到');
	if(!function_exists('checkin_do')) message(-1, '签到模型未加载，请清理缓存后重试');
	$r = checkin_do($uid);
	if(empty($r) || !isset($r['code'])) message(-1, '签到接口返回异常，请稍后再试');
	if($r['code'] != 0) message(-1, $r['message']);
	$user = user_read($uid);
	$r['credits'] = intval($user['credits']);
	$r['golds'] = intval($user['golds']);
	$r['rmbs'] = intval($user['rmbs']);
	message(0, $r);
}

?>
