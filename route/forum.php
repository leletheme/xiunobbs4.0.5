<?php

!defined('DEBUG') AND exit('Access Denied.');

// hook forum_start.php
$fid = param(1, 0);
$page = param(2, 1);
$orderby = param('orderby');
$extra = array(); // 给插件预留

$active = 'default';
!in_array($orderby, array('tid', 'lastpid')) AND $orderby = 'lastpid';
$extra['orderby'] = $orderby;

$forum = forum_read($fid);
empty($forum) AND message(3, lang('forum_not_exists'));
forum_access_user($fid, $gid, 'allowread') OR message(-1, lang('insufficient_visit_forum_privilege'));
$pagesize = $conf['pagesize'];

// hook forum_top_list_before.php

$toplist = $page == 1 ? thread_top_find($fid) : array();

// 从默认的地方读取主题列表
$thread_list_from_default = 1;

// hook forum_thread_list_before.php

if($thread_list_from_default) {
	$pagination = pagination(url("forum-$fid-{page}", $extra), $forum['threads'], $page, $pagesize);
	$threadlist = thread_find_by_fid($fid, $page, $pagesize, $orderby);
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
	if($page == 1) {
		$threadlist = thread_special_sort($threadlist, $orderby);
	}
}

$forum_hot_threadlist = thread_find(array('fid'=>$fid), array('views'=>-1, 'posts'=>-1, 'lastpid'=>-1), 1, 8);
thread_list_access_filter($forum_hot_threadlist, $gid);

$side_tag_count = array();
$side_tag_source = $forum_hot_threadlist;
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

$header['title'] = $forum['seo_title'] ? $forum['seo_title'] : $forum['name'].'-'.$conf['sitename'];
$header['mobile_title'] = $forum['name'];
$header['mobile_link'] = url("forum-$fid");
$header['keywords'] = '';
$header['description'] = $forum['brief'];

$_SESSION['fid'] = $fid;

// hook forum_end.php

include _include(APP_PATH.'view/htm/forum.htm');

?>