<?php

/*
* Copyright (C) 2015 xiuno.com
*/

!defined('DEBUG') AND exit('Access Denied.');

// hook index_start.php

$tab = param(1, 'dynamic');
$tabarr = array('dynamic', 'new', 'hot');
if(!in_array($tab, $tabarr)) {
	$page = max(1, intval($tab));
	$tab = 'dynamic';
} else {
	$page = param(2, 1);
}
$order = $tab == 'new' ? 'tid' : 'lastpid';
$pagesize = $conf['pagesize'];
$active = $tab;
$fids = arrlist_values($forumlist_show, 'fid');
$threads = arrlist_sum($forumlist_show, 'threads');
$hot_orderby = array('views'=>-1, 'posts'=>-1, 'lastpid'=>-1);

// 从默认的地方读取主题列表
$thread_list_from_default = 1;

// hook index_thread_list_before.php
if($thread_list_from_default) {
	$pagination_url = $tab == 'dynamic' ? url("$route-{page}") : url("$route-$tab-{page}");
	$pagination = pagination($pagination_url, $threads, $page, $pagesize);
	
	// hook thread_find_by_fids_before.php
	if($tab == 'hot') {
		$threadlist = thread_find(array('fid'=>$fids), $hot_orderby, $page, $pagesize);
	} else {
		$threadlist = thread_find_by_fids($fids, $page, $pagesize, $order, $threads);
	}
}

// 查找置顶帖
if($tab == 'dynamic' && $order == $conf['order_default'] && $page == 1) {
	$toplist3 = thread_top_find(0);
	$threadlist = $toplist3 + $threadlist;
}

// 过滤没有权限访问的主题 / filter no permission thread
thread_list_access_filter($threadlist, $gid);

$hot_threadlist = thread_find(array('fid'=>$fids), $hot_orderby, 1, 10);
thread_list_access_filter($hot_threadlist, $gid);

$side_tag_count = array();
$side_tag_source = $hot_threadlist;
if(!empty($threadlist)) $side_tag_source += $threadlist;
foreach($side_tag_source as $_thread) {
	if(empty($_thread['taglist'])) continue;
	foreach($_thread['taglist'] as $_tag) {
		$_tag = trim($_tag);
		if($_tag === '') continue;
		isset($side_tag_count[$_tag]) ? $side_tag_count[$_tag]++ : $side_tag_count[$_tag] = 1;
	}
}
arsort($side_tag_count);
$side_taglist = array_slice($side_tag_count, 0, 16, TRUE);

$recommend_thread = array();
if(function_exists('thread_recommend_supported') && thread_recommend_supported()) {
	$recommend_threadlist = thread_find(array('fid'=>$fids, 'recommend'=>1), array('top'=>-1, 'views'=>-1, 'posts'=>-1, 'lastpid'=>-1), 1, 1);
	thread_list_access_filter($recommend_threadlist, $gid);
	if($recommend_threadlist) $recommend_thread = reset($recommend_threadlist);
}
if($recommend_thread) {
	$recommend_firstpost = post_read($recommend_thread['firstpid']);
	$recommend_thread['brief'] = array_value($recommend_thread, 'brief', '');
	if(!$recommend_thread['brief']) {
		$recommend_thread['brief'] = $recommend_firstpost ? post_brief($recommend_firstpost['message_fmt'], 150) : $conf['sitebrief'];
	}
	$recommend_thread['image_url'] = '';
	if($recommend_firstpost && preg_match('#!\[[^\]]*\]\((https?://[^\s\)]+|[^\s\)]+\.(?:jpg|jpeg|png|gif|webp|bmp|svg)(?:\?[^\s\)]*)?)\)#is', $recommend_firstpost['message'], $m)) {
		$recommend_thread['image_url'] = $m[1];
	}
	if(!$recommend_thread['image_url'] && $recommend_firstpost && preg_match('#<img[^>]+src=["\']([^"\']+)["\']#is', $recommend_firstpost['message_fmt'], $m)) {
		$recommend_thread['image_url'] = $m[1];
	}
	if(!$recommend_thread['image_url'] && $recommend_thread['images'] > 0) {
		list($recommend_attachlist, $recommend_imagelist, $recommend_filelist) = attach_find_by_pid($recommend_thread['firstpid']);
		if(!empty($recommend_imagelist)) {
			$recommend_image = reset($recommend_imagelist);
			$recommend_thread['image_url'] = $recommend_image['url'];
		}
	}
}

if($threadlist) {
	$firstpidarr = arrlist_values($threadlist, 'firstpid');
	$firstpostlist = post_find_by_pids($firstpidarr);
	foreach($threadlist as &$thread) {
		$thread['allowtop'] = forum_access_mod($thread['fid'], $gid, 'allowtop');
		$firstpost = array_value($firstpostlist, $thread['firstpid'], array());
		$brief = array_value($thread, 'brief', '');
		if(!$brief && !empty($firstpost)) $brief = post_brief($firstpost['message_fmt'], 120);
		$thread['brief'] = $brief;
	}
	unset($thread);
}

$user_rank_cond = array('uid'=>array('>'=>0), 'gid'=>array('<>'=>7));
$user_rank_active = user_find($user_rank_cond, array('logins'=>-1, 'login_date'=>-1, 'uid'=>-1), 1, 7);
$user_rank_contribute = user_find($user_rank_cond, array('threads'=>-1, 'posts'=>-1, 'uid'=>-1), 1, 7);
$user_rank_wealth = user_find($user_rank_cond, array('credits'=>-1, 'uid'=>-1), 1, 7);
$user_ranklist = array(
	'active'=>array('title'=>'活跃榜', 'list'=>$user_rank_active, 'field'=>'登录', 'value'=>'logins', 'rank_icon'=>'icon-trophy'),
	'contribute'=>array('title'=>'贡献榜', 'list'=>$user_rank_contribute, 'field'=>'主题', 'value'=>'threads', 'field2'=>'回复', 'value2'=>'posts', 'rank_icon'=>'icon-trophy'),
	'wealth'=>array('title'=>'财富榜', 'list'=>$user_rank_wealth, 'field'=>'积分', 'value'=>'credits', 'rank_icon'=>'icon-trophy'),
);

$checkin_setting = checkin_setting();
$checkin_enabled = !empty($checkin_setting['enable']) && checkin_table_supported();
$checkin_today = array();
$checkin_streak = 0;
$checkin_calendar = array();
if($checkin_enabled && $uid) {
	$checkin_today = checkin_read_by_uid_ymd($uid, checkin_ymd());
	$checkin_streak = checkin_streak($uid);
	$checkin_calendar = checkin_month_calendar($uid);
} elseif($checkin_enabled) {
	$checkin_calendar = checkin_month_calendar(0);
}
$checkin_month_title = date('Y年n月');
$checkin_reward_preview = array();
$checkin_continue_preview = array();
foreach($checkin_setting['reward_types'] as $_type) {
	$checkin_reward_preview[] = '+'.intval($checkin_setting['reward_values'][$_type]).checkin_reward_name($_type);
	$checkin_continue_preview[] = '+'.intval($checkin_setting['continue_rewards'][$_type]).checkin_reward_name($_type);
}
$checkin_reward_preview_text = implode(' / ', $checkin_reward_preview);
$checkin_continue_preview_text = implode(' / ', $checkin_continue_preview);

// SEO
$header['title'] = $conf['sitename']; 				// site title
$header['keywords'] = ''; 					// site keyword
$header['description'] = $conf['sitebrief']; 			// site description
$_SESSION['fid'] = 0;

// hook index_end.php

include _include(APP_PATH.'view/htm/index.htm');

?>