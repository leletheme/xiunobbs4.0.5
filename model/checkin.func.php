<?php

function checkin_table_supported() {
	static $supported = NULL;
	if($supported !== NULL) return $supported;
	$db = $_SERVER['db'];
	$table = $db->tablepre.'checkin';
	$r = db_sql_find_one("SHOW TABLES LIKE '$table'");
	$supported = empty($r) ? 0 : 1;
	return $supported;
}

function checkin_default_setting() {
	return array(
		'enable'=>1,
		'reward_types'=>array('credits'),
		'reward_values'=>array('credits'=>5, 'golds'=>0, 'rmbs'=>0),
		'continue_rewards'=>array('credits'=>1, 'golds'=>0, 'rmbs'=>0),
		'continue_max'=>7,
		'calendar_days'=>35,
	);
}

function checkin_reward_types() {
	return array('credits'=>'积分', 'golds'=>'金币', 'rmbs'=>'人民币');
}

function checkin_setting_format($setting) {
	$default = checkin_default_setting();
	!is_array($setting) AND $setting = array();
	if(!isset($setting['reward_types']) && isset($setting['reward_type'])) $setting['reward_types'] = array($setting['reward_type']);
	if(!isset($setting['reward_values']) && isset($setting['reward_value'])) $setting['reward_values'] = array(array_value($setting, 'reward_type', 'credits')=>intval($setting['reward_value']));
	if(!isset($setting['continue_rewards']) && isset($setting['continue_reward'])) $setting['continue_rewards'] = array(array_value($setting, 'reward_type', 'credits')=>intval($setting['continue_reward']));
	$setting += $default;
	$setting['enable'] = intval($setting['enable']);
	!is_array($setting['reward_types']) AND $setting['reward_types'] = array($setting['reward_types']);
	!is_array($setting['reward_values']) AND $setting['reward_values'] = array();
	!is_array($setting['continue_rewards']) AND $setting['continue_rewards'] = array();
	$types = checkin_reward_types();
	$reward_types = array();
	foreach($types as $_type=>$_name) {
		if(in_array($_type, $setting['reward_types'])) $reward_types[] = $_type;
		$setting['reward_values'][$_type] = max(0, intval(array_value($setting['reward_values'], $_type, array_value($default['reward_values'], $_type, 0))));
		$setting['continue_rewards'][$_type] = max(0, intval(array_value($setting['continue_rewards'], $_type, array_value($default['continue_rewards'], $_type, 0))));
	}
	empty($reward_types) AND $reward_types = array('credits');
	$setting['reward_types'] = $reward_types;
	$setting['continue_max'] = max(1, intval($setting['continue_max']));
	$setting['calendar_days'] = max(7, intval($setting['calendar_days']));
	return $setting;
}

function checkin_setting() {
	return checkin_setting_format(setting_get('checkin'));
}

function checkin_setting_set($setting) {
	return setting_set('checkin', checkin_setting_format($setting));
}

function checkin_reward_name($type) {
	return array_value(checkin_reward_types(), $type, '积分');
}

function checkin_ymd($time = 0) {
	$time = $time ? $time : $_SERVER['time'];
	return intval(date('Ymd', $time));
}

function checkin_read_by_uid_ymd($uid, $ymd) {
	if(!checkin_table_supported()) return array();
	return db_find_one('checkin', array('uid'=>intval($uid), 'ymd'=>intval($ymd)));
}

function checkin_find_by_uid_month($uid, $year, $month) {
	if(!checkin_table_supported()) return array();
	return db_find('checkin', array('uid'=>intval($uid), 'year'=>intval($year), 'month'=>intval($month)), array('ymd'=>1), 1, 31, 'ymd');
}

function checkin_recent_by_uid($uid, $days = 35) {
	if(!checkin_table_supported()) return array();
	$uid = intval($uid);
	$days = max(1, intval($days));
	return db_find('checkin', array('uid'=>$uid), array('ymd'=>-1), 1, $days, 'ymd');
}

function checkin_find($cond = array(), $page = 1, $pagesize = 20) {
	if(!checkin_table_supported()) return array();
	return db_find('checkin', $cond, array('ymd'=>-1, 'create_date'=>-1), $page, $pagesize);
}

function checkin_count($cond = array()) {
	if(!checkin_table_supported()) return 0;
	return db_count('checkin', $cond);
}

function checkin_record_reward_text($record) {
	$type = array_value($record, 'reward_type', '');
	$value = intval(array_value($record, 'reward_value', 0));
	if($type == 'multi') {
		$ymd = intval(array_value($record, 'ymd', 0));
		$uid = intval(array_value($record, 'uid', 0));
		if(function_exists('credit_log_table_supported') && credit_log_table_supported()) {
			$loglist = db_find('credit_log', array('uid'=>$uid, 'action'=>'checkin', 'related_id'=>$ymd), array('logid'=>1), 1, 20);
			if($loglist) {
				$arr = array();
				foreach($loglist as $_log) $arr[] = intval($_log['change_num']).credit_log_type_name($_log['type']);
				return implode('、', $arr);
			}
		}
		return $value.'奖励';
	}
	return $value.checkin_reward_name($type ? $type : 'credits');
}

function checkin_streak($uid, $today = 0) {
	if(!checkin_table_supported()) return 0;
	$uid = intval($uid);
	$today = $today ? $today : $_SERVER['time'];
	$n = 0;
	for($i = 0; $i < 365; $i++) {
		$ymd = checkin_ymd($today - $i * 86400);
		$r = checkin_read_by_uid_ymd($uid, $ymd);
		if(!$r) break;
		$n++;
	}
	return $n;
}

function checkin_month_calendar($uid, $year = 0, $month = 0) {
	$year = $year ? intval($year) : intval(date('Y'));
	$month = $month ? intval($month) : intval(date('n'));
	$first = strtotime(sprintf('%04d-%02d-01', $year, $month));
	$days = intval(date('t', $first));
	$week = intval(date('w', $first));
	$list = checkin_find_by_uid_month($uid, $year, $month);
	$calendar = array();
	for($i = 0; $i < $week; $i++) $calendar[] = array('day'=>0, 'ymd'=>0, 'checked'=>0, 'today'=>0);
	$todayymd = checkin_ymd();
	for($day = 1; $day <= $days; $day++) {
		$ymd = intval(sprintf('%04d%02d%02d', $year, $month, $day));
		$calendar[] = array('day'=>$day, 'ymd'=>$ymd, 'checked'=>isset($list[$ymd]) ? 1 : 0, 'today'=>$ymd == $todayymd ? 1 : 0);
	}
	while(count($calendar) % 7 != 0) $calendar[] = array('day'=>0, 'ymd'=>0, 'checked'=>0, 'today'=>0);
	return $calendar;
}

function checkin_do($uid) {
	global $time;
	if(!checkin_table_supported()) return array('code'=>-1, 'message'=>'请先升级数据库签到表');
	$setting = checkin_setting();
	if(empty($setting['enable'])) return array('code'=>-1, 'message'=>'签到功能未开启');
	$uid = intval($uid);
	if(!$uid) return array('code'=>-1, 'message'=>'请先登录后再签到');
	$ymd = checkin_ymd($time);
	$today = checkin_read_by_uid_ymd($uid, $ymd);
	if($today) return array('code'=>-1, 'message'=>'今天已经签到过了');
	$yesterday = checkin_read_by_uid_ymd($uid, checkin_ymd($time - 86400));
	$streak = $yesterday ? intval($yesterday['streak']) + 1 : 1;
	$continue = min($streak - 1, intval($setting['continue_max']));
	$rewards = array();
	$reward_text_arr = array();
	foreach($setting['reward_types'] as $_type) {
		$reward = intval($setting['reward_values'][$_type]) + $continue * intval($setting['continue_rewards'][$_type]);
		if($reward <= 0) continue;
		$rewards[$_type] = $reward;
		$reward_text_arr[] = $reward.checkin_reward_name($_type);
	}
	$type = implode(',', array_keys($rewards));
	$reward_value = array_sum($rewards);
	$arr = array(
		'uid'=>$uid,
		'ymd'=>$ymd,
		'year'=>intval(date('Y', $time)),
		'month'=>intval(date('n', $time)),
		'day'=>intval(date('j', $time)),
		'create_date'=>$time,
		'reward_type'=>$type ? 'multi' : '',
		'reward_value'=>$reward_value,
		'streak'=>$streak,
	);
	$r = db_insert('checkin', $arr);
	if($r === FALSE) return array('code'=>-1, 'message'=>'签到失败，请稍后再试');
	$update = array();
	foreach($rewards as $_type=>$_reward) $update[$_type.'+'] = $_reward;
	if($update) {
		user_update($uid, $update);
		$_user = user_read($uid);
		foreach($rewards as $_type=>$_reward) {
			function_exists('credit_log_create') AND credit_log_create($uid, $_type, $_reward, intval($_user[$_type]), '每日签到奖励', 'checkin', $ymd, 0);
		}
	}
	$reward_text = $reward_text_arr ? implode('、', $reward_text_arr) : '0奖励';
	return array('code'=>0, 'message'=>'签到成功，获得 '.$reward_text, 'rewards'=>$rewards, 'reward_text'=>$reward_text, 'streak'=>$streak, 'ymd'=>$ymd);
}

?>
