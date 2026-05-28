<?php

// hook model_thread_start.php

// ------------> 最原生�?CURD，无关联其他数据�?

function thread__create($arr) {
	// hook model_thread__create_start.php
	$r = db_insert('thread', $arr);
	// hook model_thread__create_end.php
	return $r;
}

function thread__update($tid, $arr) {
	// hook model_thread__update_start.php
	$r = db_update('thread', array('tid'=>$tid), $arr);
	// hook model_thread__update_end.php
	return $r;
}

function thread__read($tid) {
	// hook model_thread__read_start.php
	$thread = db_find_one('thread', array('tid'=>$tid));
	// hook model_thread__read_end.php
	return $thread;
}

function thread__delete($tid) {
	// hook model_thread__delete_start.php
	$r = db_delete('thread', array('tid'=>$tid));
	// hook model_thread__delete_end.php
	return $r;
}

function thread__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_thread__find_start.php
	
	$arrlist = db_find('thread', $cond, $orderby, $page, $pagesize, 'tid', array('tid'));
	if(empty($arrlist)) return array();
	
	$tidarr = arrlist_values($arrlist, 'tid');
	$threadlist = db_find('thread', array('tid'=>$tidarr), $orderby, 1, $pagesize, 'tid');
	
	// hook model_thread__find_end.php
	return $threadlist;
}

function thread_create($arr, &$pid) {
	global $conf, $gid;
	$fid = $arr['fid'];
	$uid = $arr['uid'];
	$subject = $arr['subject'];
	$message = $arr['message'];
	$time = $arr['time'];
	$longip = $arr['longip'];
	$doctype = $arr['doctype'];
	$brief = thread_brief_supported() ? array_value($arr, 'brief', '') : '';
	$tags = thread_tags_supported() ? thread_tags_filter(array_value($arr, 'tags', '')) : '';
	$recommend = thread_recommend_supported() ? intval(array_value($arr, 'recommend', 0)) : 0;
	$digest = thread_digest_supported() ? intval(array_value($arr, 'digest', 0)) : 0;
	$mod_recommend = thread_mod_recommend_supported() ? intval(array_value($arr, 'mod_recommend', 0)) : 0;
	
	# 论坛帖子数据，一页显示，不分页�?
	$post = array(
		'tid'=>0,
		'isfirst'=>1,
		'uid'=>$uid,
		'create_date'=>$time,
		'userip'=>$longip,
		'message'=>$message,
		'doctype'=>$doctype,
	);
	
	// hook model_thread_create_start.php
	
	$pid = post__create($post, $gid);
	if(!$pid && $pid !== FALSE) {
		$lastpost = db_find_one('post', array('tid'=>0, 'isfirst'=>1, 'uid'=>$uid, 'create_date'=>$time, 'userip'=>$longip), array('pid'=>-1), array('pid'));
		$lastpost AND $pid = intval($lastpost['pid']);
	}
	if($pid === FALSE || !$pid) return FALSE;
	
	// 创建主题
	$thread = array (
		'fid'=>$fid,
		'subject'=>$subject,
		'uid'=>$uid,
		'create_date'=>$time,
		'last_date'=>$time,
		'firstpid'=>$pid,
		'lastpid'=>$pid,
		'userip'=>$longip,
	);
	if(thread_brief_supported()) $thread['brief'] = $brief;
	if(thread_tags_supported()) $thread['tags'] = $tags;
	if(thread_recommend_supported()) $thread['recommend'] = $recommend;
	if(thread_digest_supported()) $thread['digest'] = $digest;
	if(thread_mod_recommend_supported()) $thread['mod_recommend'] = $mod_recommend;
	
	// hook model_thread__create_before.php
	
	$tid = thread__create($thread);
	if($tid === FALSE) {
		post__delete($pid);
		return FALSE;
	}
	// 板块总数+1, 用户发帖+1
	
	// 更新统计数据
	$uid AND user__update($uid, array('threads+'=>1));
	forum__update($fid, array('threads+'=>1, 'todaythreads+'=>1));
	
	// 关联
	post__update($pid, array('tid'=>$tid), $tid);

	// 我参与的发帖
	$uid AND mythread_create($uid, $tid);
	
	// 关联附件
	attach_assoc_post($pid);
	
	// 全站发帖�?
	runtime_set('threads+', 1);
	runtime_set('todaythreads+', 1);
	
	// 更新板块信息�?
	forum_list_cache_delete();
	
	if($uid) {
		$add_credits = 10;
		user_update($uid, array('credits+'=>$add_credits));
		$_user = user_read($uid);
		function_exists('credit_log_create') AND credit_log_create($uid, 'credits', $add_credits, $_user['credits'], '发布主题奖励', 'thread_create', $tid, 0);
		user_update_group($uid);
	}

	// hook model_thread_create_end.php
	
	return $tid;
}

// 不要在大循环里调用此函数！比较耗费资源�?
function thread_update($tid, $arr) {
	global $conf;
	$thread = thread__read($tid);
	
	// hook model_thread_update_start.php
	
	if(isset($arr['subject']) && $arr['subject'] != $thread['subject']) {
		$thread['top'] > 0 AND thread_top_cache_delete();
	}
	
	// 更改 fid, 移动主题，相关资源也需要更�?
	if(isset($arr['fid']) && $arr['fid'] != $thread['fid']) {
		forum__update($arr['fid'], array('threads+'=>1));
		forum__update($thread['fid'], array('threads-'=>1));
		thread_top_update_by_tid($tid, $arr['fid']);
	}
	
	if(!$arr) return TRUE;
	
	$r = thread__update($tid, $arr);
	
	// hook model_thread_update_end.php
	return $r;
}

// views + 1
function thread_inc_views($tid, $n = 1) {
	// hook model_thread_inc_views_start.php
	global $conf;
	if(!$conf['update_views_on']) return TRUE;
	$sqladd = strpos($conf['db']['type'], 'mysql') === FALSE ? '' : ' LOW_PRIORITY';
	$r = db_exec("UPDATE$sqladd `bbs_thread` SET views=views+$n WHERE tid='$tid'");
	// hook model_thread_inc_views_end.php
	return $r;
}

function thread_read($tid) {
	// hook model_thread_read_start.php
	$thread = thread__read($tid);
	thread_format($thread);
	// hook model_thread_read_end.php
	return $thread;
}

// 从缓存中读取，避免重复从数据库取数据，主要用来前端显示，可能有延迟。重要业务逻辑不要调用此函数，数据可能不准确，因为并没有清理缓存，针对 request 生命周期有效�?
function thread_read_cache($tid) {
	// hook model_thread_read_cache_start.php
	static $cache = array(); // 用静态变量只能在当前 request 生命周期缓存，要跨进程，可以再加一层缓存： memcached/xcache/apc/
	if(isset($cache[$tid])) return $cache[$tid];
	$cache[$tid] = thread_read($tid);
	// hook model_thread_read_cache_end.php
	return $cache[$tid];
}

// 删除主题
function thread_delete($tid) {
	global $conf;
	$thread = thread__read($tid);
	if(empty($thread)) return TRUE;
	$fid = $thread['fid'];
	$uid = $thread['uid'];
	
	// hook model_thread_delete_start.php
	
	// 删除所有回帖，同时更新 posts 统计�?
	$n = post_delete_by_tid($tid);
	
	// 删除我的主题
	$uid AND mythread_delete($uid, $tid);
	
	// 清除相关缓存
	forum_list_cache_delete();
	
	$r = thread__delete($tid);
	if($r === FALSE) return FALSE;
	
	// 更新统计
	forum__update($fid, array('threads-'=>1));
	user__update($uid, array('threads-'=>1));
	
	// 全站统计
	runtime_set('threads-', 1);
	
	// hook model_thread_delete_end.php
	
	return $r;
}

function thread_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_thread_find_start.php
	$threadlist = thread__find($cond, $orderby, $page, $pagesize);
	if($threadlist) foreach ($threadlist as &$thread) thread_format($thread);
	// hook model_thread_find_end.php
	return $threadlist;
}

// $order: tid/lastpid
// 按照: 发帖时间/最后回复时�?倒序，不包含置顶�?
function thread__find_by_fid($fid, $page = 1, $pagesize = 20, $order = 'lastpid') {
	global $conf, $forumlist, $runtime;
	$forum = $fid ? $forumlist[$fid] : array();
	$threads = empty($forum) ? $runtime['threads'] : $forum['threads'];
	
	// hook model__thread_find_by_fid_start.php
	
	$cond = array();
	$fid AND $cond['fid'] = $fid;
	
	$desc = TRUE;
	$limitpage = 50000; // 如果需要防�?CC 攻击，可以调整为 5000
	if($page > 100) {
		$totalpage = ceil($threads / $pagesize);
		$halfpage = ceil($totalpage / 2);
		if($halfpage > $limitpage && $page < ($totalpage - $limitpage)) {
			$page = $limitpage;
		}
		if($page > $halfpage) {
			$page = max(1, $totalpage - $page + 1) ;
			$threadlist = thread_find($cond, array($order=>1), $page, $pagesize);
			$threadlist = array_reverse($threadlist, TRUE);
			$desc = FALSE;
		}
	}
	if($desc) {
		$orderby = array($order=>-1);
		$threadlist = thread_find($cond, $orderby, $page, $pagesize);
	}
	
	// hook model__thread_find_by_fid_end.php
	
	return $threadlist;
}

// $order: tid/lastpid
// 按照: 发帖时间/最后回复时�?倒序，包含置顶帖
function thread_find_by_fid($fid, $page = 1, $pagesize = 20, $order = 'lastpid') {
	global $conf, $forumlist, $runtime;

	// hook model_thread_find_by_fid_start.php

	$threadlist = thread__find_by_fid($fid, $page, $pagesize, $order);

	// hook model_thread_find_by_fid_middle.php
	
	// 查找置顶�?
	if($order == $conf['order_default'] && $page == 1) {
		$toplist3 = thread_top_find(0);
		$toplist1 = thread_top_find($fid);
		//$toplist = thread_top_find($fid);
		$threadlist = $toplist3 + $toplist1 + $threadlist;
	}
	
	// hook model_thread_find_by_fid_end.php
	return $threadlist;
}

// 从多个版块获取列表数�?
function thread_find_by_fids($fids, $page = 1, $pagesize = 20, $order = 'lastpid', $threads = FALSE) {
	
	// hook model_thread_find_by_fids_start.php
	
	$threadlist = thread_find(array('fid'=>$fids), array($order=>-1), $page, $pagesize);
	
	// hook model_thread_find_by_fids_end.php
	
	return $threadlist;
}

// 默认搜索标题
function thread_find_by_keyword($keyword) {
	// hook model_thread_find_by_keyword_start.php
	$threadlist = db_find('thread', array('subject'=>array('LIKE'=>$keyword)), array(), 1, 60);
	$threadlist = arrlist_multisort($threadlist, 'tid', FALSE); // �?PHP 排序，mysql 排序消耗太大�?
	if($threadlist) {
		foreach ($threadlist as &$thread) {
			thread_format($thread);
			$thread['subject'] = post_highlight_keyword($thread['subject'], $keyword);
		}
	}
	// hook model_thread_find_by_keyword_end.php
	return $threadlist;
}


function thread_recommend_supported() {
	static $supported = NULL;
	if($supported !== NULL) return $supported;
	$db = $_SERVER['db'];
	$table = $db->tablepre.'thread';
	$r = db_sql_find_one("SHOW COLUMNS FROM `$table` LIKE 'recommend'");
	$supported = empty($r) ? 0 : 1;
	return $supported;
}

function thread_digest_supported() {
	static $supported = NULL;
	if($supported !== NULL) return $supported;
	$db = $_SERVER['db'];
	$table = $db->tablepre.'thread';
	$r = db_sql_find_one("SHOW COLUMNS FROM `$table` LIKE 'digest'");
	$supported = empty($r) ? 0 : 1;
	return $supported;
}

function thread_mod_recommend_supported() {
	static $supported = NULL;
	if($supported !== NULL) return $supported;
	$db = $_SERVER['db'];
	$table = $db->tablepre.'thread';
	$r = db_sql_find_one("SHOW COLUMNS FROM `$table` LIKE 'mod_recommend'");
	$supported = empty($r) ? 0 : 1;
	return $supported;
}

function thread_feature_supported() {
	return thread_digest_supported() && thread_mod_recommend_supported();
}

function thread_brief_supported() {
	static $supported = NULL;
	if($supported !== NULL) return $supported;
	$db = $_SERVER['db'];
	$table = $db->tablepre.'thread';
	$r = db_sql_find_one("SHOW COLUMNS FROM `$table` LIKE 'brief'");
	$supported = empty($r) ? 0 : 1;
	return $supported;
}

function thread_tags_supported() {
	static $supported = NULL;
	if($supported !== NULL) return $supported;
	$db = $_SERVER['db'];
	$table = $db->tablepre.'thread';
	$r = db_sql_find_one("SHOW COLUMNS FROM `$table` LIKE 'tags'");
	$supported = empty($r) ? 0 : 1;
	return $supported;
}

function thread_tags_filter($tags) {
	$tags = str_replace(array('，', '、', ';', '；', '|'), ',', $tags);
	$arr = preg_split('/[\s,]+/u', $tags);
	$result = array();
	if($arr) foreach($arr as $tag) {
		$tag = trim(strip_tags($tag));
		$tag = preg_replace('/[#]+/u', '', $tag);
		if($tag === '') continue;
		$tag = htmlspecialchars(xn_substr($tag, 0, 16), ENT_QUOTES);
		if(in_array($tag, $result)) continue;
		$result[] = $tag;
		if(count($result) >= 5) break;
	}
	return implode(',', $result);
}

function thread_tags_to_array($tags) {
	$tags = trim($tags);
	if($tags === '') return array();
	$arr = explode(',', $tags);
	$result = array();
	foreach($arr as $tag) {
		$tag = trim($tag);
		if($tag === '') continue;
		$result[] = $tag;
	}
	return $result;
}

function thread_special_sort($threadlist, $order = 'lastpid') {
	if(empty($threadlist)) return array();
	$recommend_sort = $mod_recommend_sort = $digest_sort = $normal_sort = array();
	foreach($threadlist as $_thread) {
		if(thread_recommend_supported() && !empty($_thread['recommend'])) {
			$recommend_sort[] = $_thread;
		} elseif(thread_mod_recommend_supported() && !empty($_thread['mod_recommend'])) {
			$mod_recommend_sort[] = $_thread;
		} elseif(thread_digest_supported() && !empty($_thread['digest'])) {
			$digest_sort[] = $_thread;
		} else {
			$normal_sort[] = $_thread;
		}
	}
	$recommend_sort = arrlist_multisort($recommend_sort, $order, FALSE);
	$mod_recommend_sort = arrlist_multisort($mod_recommend_sort, $order, FALSE);
	$digest_sort = arrlist_multisort($digest_sort, $order, FALSE);
	$normal_sort = arrlist_multisort($normal_sort, $order, FALSE);
	return array_merge($recommend_sort, $mod_recommend_sort, $digest_sort, $normal_sort);
}

function thread_format(&$thread) {
	
	global $conf, $forumlist;
	if(empty($thread)) return;
	
	// hook model_thread_format_start.php
	
	$thread['create_date_fmt'] = humandate($thread['create_date']);
	$thread['last_date_fmt'] = humandate($thread['last_date']);
	
	$user = user_read_cache($thread['uid']);
	$thread['username'] = $user['username'];
	$thread['user_avatar_url'] = $user['avatar_url'];
	$thread['user'] = $user;
	
	$forum = isset($forumlist[$thread['fid']]) ? $forumlist[$thread['fid']] : array('name'=>'');
	$thread['forumname'] = $forum['name'];
	$thread['brief'] = array_value($thread, 'brief', '');
	$thread['recommend'] = intval(array_value($thread, 'recommend', 0));
	$thread['digest'] = intval(array_value($thread, 'digest', 0));
	$thread['mod_recommend'] = intval(array_value($thread, 'mod_recommend', 0));
	$thread['tags'] = array_value($thread, 'tags', '');
	$thread['taglist'] = thread_tags_to_array($thread['tags']);
	
	if($thread['last_date'] == $thread['create_date']) {
		//$thread['last_date'] = 0;
		$thread['last_date_fmt'] = '';
		$thread['lastuid'] = 0;
		$thread['lastusername'] = '';
	} else {
		$lastuser = $thread['lastuid'] ? user_read_cache($thread['lastuid']) : array();
		$thread['lastusername'] = $thread['lastuid'] ? $lastuser['username'] : lang('guest');
	}
	
	$thread['url'] = "thread-$thread[tid].htm";
	$thread['user_url'] = "user-$thread[uid]".($thread['uid'] ? '' : "-$thread[firstpid]").".htm";
	
	$thread['top_class'] = $thread['top'] ? 'top_'.$thread['top'] : '';

	$thread['pages'] = ceil($thread['posts'] / $conf['postlist_pagesize']);
		
	// hook model_thread_format_end.php
}

function thread_format_last_date(&$thread) {
	// hook model_thread_format_last_date_start.php
	if($thread['last_date'] != $thread['create_date']) {
		$thread['last_date_fmt'] = humandate($thread['last_date']);
	} else {
		$thread['create_date_fmt'] = humandate($thread['create_date']);
	}
	// hook model_thread_format_last_date_end.php
}

function thread_count($cond = array()) {
	// hook model_thread_count_start.php
	$n = db_count('thread', $cond);
	// hook model_thread_count_end.php
	return $n;
}

function thread_maxid() {
	// hook model_thread_maxid_start.php
	$n = db_maxid('thread', 'tid');
	// hook model_thread_maxid_end.php
	return $n;
}

function thread_safe_info($thread) {
	// hook model_thread_safe_info_start.php
	unset($thread['userip']);
	if(!empty($thread['user'])) {
		$thread['user'] = user_safe_info($thread['user']);
	}
	// hook model_thread_safe_info_end.php
	return $thread;
}

function thread_get_level($n, $levelarr) {
	// hook model_thread_get_level_start.php
	foreach($levelarr as $k=>$level) {
		if($n <= $level) return $k;
	}
	// hook model_thread_get_level_end.php
	return $k;
}


// �?$threadlist 权限过滤
function thread_list_access_filter(&$threadlist, $gid) {
	global $conf, $forumlist;
	if(empty($threadlist)) return;
	
	// hook model_thread_list_access_filter_start.php
	
	foreach($threadlist as $tid=>$thread) {
		if(empty($forumlist[$thread['fid']]['accesson'])) continue;
		if($thread['top'] > 0) continue;
		if(!forum_access_user($thread['fid'], $gid, 'allowread')) {
			unset($threadlist[$tid]);
		}
	}
	// hook model_thread_list_access_filter_end.php
}

function thread_find_by_tids($tids, $order = array('lastpid'=>-1)) {
	// hook model_thread_find_by_tids_start.php
	//$start = ($page - 1) * $pagesize;
	//$tids = array_slice($tids, $start, $pagesize);
	if(!$tids) return array();
	$threadlist = db_find('thread', array('tid'=>$tids), $order, 1, 1000, 'tid');
	if($threadlist) foreach($threadlist as &$thread) thread_format($thread);
	// hook model_thread_find_by_tids_end.php
	return $threadlist;
}

// 查找 lastpid
function thread_find_lastpid($tid) {
	$arr = db_find_one("post", array('tid'=>$tid), array('pid'=>-1), array('pid'));
	$lastpid = empty($arr) ? 0 : $arr['pid'];
	return $lastpid;
}

// 更新最后的 uid pid
function thread_update_last($tid) {
	$lastpid = thread_find_lastpid($tid);
	if(empty($lastpid)) return;
	
	$lastpost = post__read($lastpid);
	if(empty($lastpost)) return;
	
	$r = thread__update($tid, array('lastpid'=>$lastpid, 'lastuid'=>$lastpost['uid'], 'last_date'=>$lastpost['create_date']));

	return $r;
}

// hook model_thread_end.php

?>