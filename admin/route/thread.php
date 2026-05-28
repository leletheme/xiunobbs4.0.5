<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// hook admin_thread_start.php

$pagesize = 100;

if(empty($action) || $action == 'list') {

	$header['title'] = lang('thread_admin');
	$header['mobile_title'] = lang('thread_admin');
	$page = param(2, 1);
	$pagesize = 20;
	$fid = param('fid', 0);
	$uid = param('uid', 0);
	$userip = param('userip');
	$keyword = param('keyword');
	$closed = param('closed', -1);
	$recommend = param('recommend', -1);
	$create_date_start = param('create_date_start');
	$create_date_end = param('create_date_end');
	$cond = array();
	$fid AND $cond['fid'] = $fid;
	$uid AND $cond['uid'] = $uid;
	$userip AND $cond['userip'] = ip2long($userip);
	$keyword AND $cond['subject'] = array('LIKE'=>$keyword);
	$closed >= 0 AND $cond['closed'] = $closed;
	thread_recommend_supported() && $recommend >= 0 AND $cond['recommend'] = $recommend;
	$create_date_start AND $cond['create_date'] = array('>='=>strtotime($create_date_start));
	$create_date_end AND $cond['create_date'] = array('<='=>strtotime($create_date_end) + 86400 - 1);
	if($create_date_start && $create_date_end) {
		$cond['create_date'] = array('>='=>strtotime($create_date_start), '<='=>strtotime($create_date_end) + 86400 - 1);
	}
	$total = thread_count($cond);
	$pagination_url = url('thread-list-{page}');
	$filterarr = array(
		'fid'=>$fid,
		'keyword'=>$keyword,
		'uid'=>$uid,
		'userip'=>$userip,
		'closed'=>$closed,
		'recommend'=>$recommend,
		'create_date_start'=>$create_date_start,
		'create_date_end'=>$create_date_end,
	);
	foreach($filterarr as $_k=>$_v) {
		if($_v === '' || $_v === NULL || ($_k != 'closed' && $_k != 'recommend' && $_v == 0)) continue;
		$pagination_url .= '&'.$_k.'='.urlencode($_v);
	}
	$pagination = pagination($pagination_url, $total, $page, $pagesize);
	$threadlist = thread_find($cond, array('tid'=>-1), $page, $pagesize);
	if($threadlist) {
		$firstpidarr = arrlist_values($threadlist, 'firstpid');
		$firstpostlist = post_find_by_pids($firstpidarr);
		foreach($threadlist as &$thread) {
			$firstpost = array_value($firstpostlist, $thread['firstpid'], array());
			$brief = array_value($thread, 'brief', '');
			if(!$brief && !empty($firstpost)) $brief = post_brief($firstpost['message_fmt'], 80);
			$thread['brief'] = $brief;
		}
		unset($thread);
	}
	
	include _include(ADMIN_PATH."view/htm/thread_list.htm");
	
// 全表扫描，每次扫描 1000 条记录
/*
	搜索条件，并且关系：
	create_date (start, end) 
	last_date (start, end) 
	fid = 
	uid =
	userip =
	views (start, end)
	subject like '%keyword%'
*/
} elseif($action == 'scan') {
	
	$queueid = _SESSION('thread_find_queueid');
	empty($queueid) AND message(-1, lang('thread_queue_not_exists'));
	
	$fid = param('fid', 0);
	$cond = array();
	$cond['fid'] = $fid;
	$cond['create_date_start'] = strtotime(param('create_date_start'));
	$cond['create_date_end'] = strtotime(param('create_date_end'));
	$cond['uid'] = param('uid', 0);
	$userip = param('userip');
	$cond['userip'] = $userip ? ip2long($userip) : 0;
	$cond['keyword'] = param('keyword');
	$cond['page'] = param('page', 1);
	
	$page = $cond['page'];
	$threads = $cond['fid'] ? $forumlist[$fid]['threads'] : $runtime['threads'];
	$totalpage = ceil($threads / $pagesize);
	
	// hook admin_thread_scan_start.php
	$threadlist = thread_find_by_fid($fid, $page, $pagesize);
	
	if($page == 1) $queueid AND queue_destory($queueid);
	
	$tids = array();
	// 查找到的数据存到 cache，并且返回
	foreach($threadlist as $thread) {
		
		if($cond['fid'] && $thread['fid'] != $cond['fid']) continue; 
		if($cond['create_date_start'] && $thread['create_date'] < $cond['create_date_start']) continue; 
		if($cond['create_date_end'] && $thread['create_date'] > $cond['create_date_end']) continue; 
		if($cond['uid'] && $thread['uid'] != $cond['uid']) continue; 
		if($cond['userip'] && $thread['userip'] != $cond['userip']) continue; 
		//if($cond['views_start'] && $thread['views'] > $cond['views_start']) continue; 
		//if($cond['views_end'] && $thread['views'] > $cond['views_end']) continue; 
		//if($cond['posts_start'] && $thread['posts'] > $cond['posts_start']) continue; 
		//if($cond['posts_end'] && $thread['posts'] > $cond['posts_end']) continue; 
		if($cond['keyword'] && stripos($thread['subject'], $cond['keyword']) === FALSE) continue; 
		
		// hook admin_thread_scan_for.php
		
		$tids[] = $thread['tid'];
		queue_push($queueid, $thread['tid'], 86400);
	}
	
	// hook admin_thread_scan_end.php
	message(0, $tids);
	
// 操作
} elseif($action == 'operation') {
	
	if($method != 'POST') message(-1, lang('method_error'));
	$op = param(2);
	$tids = param('tids', array());
	!is_array($tids) AND $tids = array($tids);
	$n = 0;
	foreach($tids as $_tid) {
		$_tid = intval($_tid);
		if(!$_tid) continue;
		if($op == 'delete') {
			$r = thread_delete($_tid);
		} elseif($op == 'close') {
			$r = thread_update($_tid, array('closed'=>1));
		} elseif($op == 'open') {
			$r = thread_update($_tid, array('closed'=>0));
		} else {
			$r = FALSE;
		}
		if($r !== FALSE) $n++;
	}
	message(0, $n);
	
} elseif($action == 'recommend') {

	if(!thread_recommend_supported()) message(-1, '请先升级数据库 recommend 字段');
	$page = param(2, 1);
	$pagesize = 20;
	$fid = param('fid', 0);
	$uid = param('uid', 0);
	$userip = param('userip');
	$keyword = param('keyword');
	$closed = param('closed', -1);
	$create_date_start = param('create_date_start');
	$create_date_end = param('create_date_end');
	$cond = array('recommend'=>1);
	$fid AND $cond['fid'] = $fid;
	$uid AND $cond['uid'] = $uid;
	$userip AND $cond['userip'] = ip2long($userip);
	$keyword AND $cond['subject'] = array('LIKE'=>$keyword);
	$closed >= 0 AND $cond['closed'] = $closed;
	$create_date_start AND $cond['create_date'] = array('>='=>strtotime($create_date_start));
	$create_date_end AND $cond['create_date'] = array('<='=>strtotime($create_date_end) + 86400 - 1);
	if($create_date_start && $create_date_end) {
		$cond['create_date'] = array('>='=>strtotime($create_date_start), '<='=>strtotime($create_date_end) + 86400 - 1);
	}
	$total = thread_count($cond);
	$pagination_url = url('thread-recommend-{page}');
	$filterarr = array(
		'fid'=>$fid,
		'keyword'=>$keyword,
		'uid'=>$uid,
		'userip'=>$userip,
		'closed'=>$closed,
		'create_date_start'=>$create_date_start,
		'create_date_end'=>$create_date_end,
	);
	foreach($filterarr as $_k=>$_v) {
		if($_v === '' || $_v === NULL || ($_k != 'closed' && $_v == 0)) continue;
		$pagination_url .= '&'.$_k.'='.urlencode($_v);
	}
	$pagination = pagination($pagination_url, $total, $page, $pagesize);
	$threadlist = thread_find($cond, array('top'=>-1, 'views'=>-1, 'posts'=>-1, 'lastpid'=>-1), $page, $pagesize);
	if($threadlist) {
		$firstpidarr = arrlist_values($threadlist, 'firstpid');
		$firstpostlist = post_find_by_pids($firstpidarr);
		foreach($threadlist as &$thread) {
			$firstpost = array_value($firstpostlist, $thread['firstpid'], array());
			$brief = array_value($thread, 'brief', '');
			if(!$brief && !empty($firstpost)) $brief = post_brief($firstpost['message_fmt'], 80);
			$thread['brief'] = $brief;
		}
		unset($thread);
	}
	$header['title'] = '推荐内容管理';
	$header['mobile_title'] = '推荐内容管理';
	
	include _include(ADMIN_PATH."view/htm/thread_recommend.htm");

} elseif($action == 'recommend_cancel') {

	if($method != 'POST') message(-1, lang('method_error'));
	if(!thread_recommend_supported()) message(-1, '请先升级数据库 recommend 字段');
	$tids = param('tids', array(0));
	!is_array($tids) AND $tids = array($tids);
	$n = 0;
	foreach($tids as $_tid) {
		$_tid = intval($_tid);
		if(!$_tid) continue;
		$r = thread_update($_tid, array('recommend'=>0));
		if($r !== FALSE) $n++;
	}
	message(0, $n);

// 操作
} elseif($action == 'found') {	

	$queueid = _SESSION('thread_find_queueid');
	empty($queueid) AND message(-1, lang('thread_queue_not_exists'));
	
	$page = param(2, 1);
	$total = queue_count($queueid);
	$pagination = pagination(url('thread-found-{page}'), $total, $page, $pagesize);
	// hook admin_thread_found_start.php
	$tids = queue_find($queueid, $page, $pagesize);
	$threadlist = thread_find_by_tids($tids);
	
	// hook admin_thread_found_end.php
	include _include(ADMIN_PATH."view/htm/thread_found.htm");
}

// hook admin_thread_start.php

?>