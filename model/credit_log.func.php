<?php

function credit_log_table_supported() {
	static $supported = NULL;
	if($supported !== NULL) return $supported;
	$db = $_SERVER['db'];
	$table = $db->tablepre.'credit_log';
	$r = db_sql_find_one("SHOW TABLES LIKE '$table'");
	$supported = empty($r) ? 0 : 1;
	return $supported;
}

function credit_log_types() {
	return array('credits'=>'积分', 'golds'=>'金币', 'rmbs'=>'人民币');
}

function credit_log_type_name($type) {
	return array_value(credit_log_types(), $type, '积分');
}

function credit_log_create($uid, $type, $change_num, $after_num, $reason = '', $action = '', $related_id = 0, $operator_uid = 0) {
	global $time;
	if(!credit_log_table_supported()) return TRUE;
	$types = credit_log_types();
	if(!isset($types[$type])) return FALSE;
	$change_num = intval($change_num);
	if($change_num == 0) return TRUE;
	$arr = array(
		'uid'=>intval($uid),
		'type'=>$type,
		'change_num'=>$change_num,
		'after_num'=>intval($after_num),
		'reason'=>substr($reason, 0, 64),
		'action'=>substr($action, 0, 32),
		'related_id'=>intval($related_id),
		'operator_uid'=>intval($operator_uid),
		'create_date'=>$time,
		'create_ip'=>ip2long(ip()),
	);
	return db_insert('credit_log', $arr);
}

function credit_log_find_by_uid($uid, $page = 1, $pagesize = 20) {
	if(!credit_log_table_supported()) return array();
	return db_find('credit_log', array('uid'=>intval($uid)), array('logid'=>-1), $page, $pagesize);
}

function credit_log_count_by_uid($uid) {
	if(!credit_log_table_supported()) return 0;
	return db_count('credit_log', array('uid'=>intval($uid)));
}

?>
