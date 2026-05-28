<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// hook thread_start.php

// 发表主题帖 | create new thread
if($action == 'create') {
	
	// hook thread_create_get_post.php
		
	user_login_check();

	if($method == 'GET') {
		
		// hook thread_create_get_start.php
		
		$fid = param(2, 0);
		$forum = $fid ? forum_read($fid) : array();
		
		$forumlist_allowthread = forum_list_access_filter($forumlist, $gid, 'allowthread');
		$forumarr = xn_json_encode(arrlist_key_values($forumlist_allowthread, 'fid', 'name'));
		if(empty($forumlist_allowthread)) {
			message(-1, lang('user_group_insufficient_privilege'));
		}
		
		$header['title'] = lang('create_thread');
		$header['mobile_title'] = $fid ? $forum['name'] : '';
		$header['mobile_linke'] = url("forum-$fid");
		
		// hook thread_create_get_end.php
		
		include _include(APP_PATH.'view/htm/post.htm');
		
	} else {
		
		// hook thread_create_thread_start.php
		
		$fid = param('fid', 0);
		$forum = forum_read($fid);
		empty($forum) AND message('fid', lang('forum_not_exists'));
		
		$r = forum_access_user($fid, $gid, 'allowthread');
		!$r AND message(-1, lang('user_group_insufficient_privilege'));
		
		$subject = htmlspecialchars(param('subject', '', FALSE));
		empty($subject) AND message('subject', lang('please_input_subject'));
		xn_strlen($subject) > 128 AND message('subject', lang('subject_length_over_limit', array('maxlength'=>128)));
		$brief = thread_brief_supported() ? htmlspecialchars(param('brief', '', FALSE)) : '';
		xn_strlen($brief) > 240 AND message('brief', '摘要长度不能超过 240 个字符');
		$tags = thread_tags_supported() ? thread_tags_filter(param('tags', '', FALSE)) : '';
		$recommend = thread_recommend_supported() && forum_access_mod($fid, $gid, 'allowtop') ? param('recommend', 0) : 0;
		$digest = thread_digest_supported() && forum_access_mod($fid, $gid, 'allowtop') ? param('digest', 0) : 0;
		$mod_recommend = thread_mod_recommend_supported() && forum_access_mod($fid, $gid, 'allowtop') ? param('mod_recommend', 0) : 0;
		
		$message = param('message', '', FALSE);
		empty($message) AND message('message', lang('please_input_message'));
		$doctype = param('doctype', 0);
		$doctype > 10 AND message(-1, lang('doc_type_not_supported'));
		xn_strlen($message) > 2028000 AND message('message', lang('message_too_long'));
		
		$thread = array (
			'fid'=>$fid,
			'uid'=>$uid,
			'sid'=>$sid,
			'subject'=>$subject,
			'brief'=>$brief,
			'tags'=>$tags,
			'recommend'=>$recommend,
			'digest'=>$digest,
			'mod_recommend'=>$mod_recommend,
			'message'=>$message,
			'time'=>$time,
			'longip'=>$longip,
			'doctype'=>$doctype,
		);
		
		// hook thread_create_thread_before.php
		
		$tid = thread_create($thread, $pid);
		$pid === FALSE AND message(-1, $errstr ? $errstr : lang('create_post_failed'));
		$tid === FALSE AND message(-1, $errstr ? $errstr : lang('create_thread_failed'));
		
		// hook thread_create_thread_end.php
		message(0, lang('create_thread_sucessfully'));
	}
	
// 帖子详情 | post detail
} else {
	
	// thread-{tid}-{page}-{keyword}.htm
	$tid = param(1, 0);
	$page = param(2, 1);
	$keyword = param(3);
	$pagesize = $conf['postlist_pagesize'];
	//$pagesize = 10;
	//$page == 1 AND $pagesize++;
	
	// hook thread_info_start.php
	
	$thread = thread_read($tid);
	empty($thread) AND message(-1, lang('thread_not_exists'));
	
	$fid = $thread['fid'];
	$forum = forum_read($fid);
	empty($forum) AND message(3, lang('forum_not_exists'));
	
	$postlist = post_find_by_tid($tid, $page, $pagesize);
	empty($postlist) AND message(4, lang('post_not_exists'));
	
	if($page == 1) {
		empty($postlist[$thread['firstpid']]) AND message(-1, lang('data_malformation'));
		$first = $postlist[$thread['firstpid']];
		unset($postlist[$thread['firstpid']]);
		$attachlist = $imagelist = $filelist = array();
		
		// 如果是大站，可以用单独的点击服务，减少 db 压力
		// if request is huge, separate it from mysql server
		thread_inc_views($tid);
	} else {
		$first = post_read($thread['firstpid']);
	}
	
	$keywordurl = '';
	if($keyword) {
		$thread['subject'] = post_highlight_keyword($thread['subject'], $keyword);
		//$first['message'] = post_highlight_keyword($first['subject']);
		$keywordurl = "-$keyword";
	}
	$allowpost = forum_access_user($fid, $gid, 'allowpost') ? 1 : 0;
	$allowupdate = forum_access_mod($fid, $gid, 'allowupdate') ? 1 : 0;
	$allowdelete = forum_access_mod($fid, $gid, 'allowdelete') ? 1 : 0;
	
	forum_access_user($fid, $gid, 'allowread') OR message(-1, lang('user_group_insufficient_privilege'));
	
	$pagination = pagination(url("thread-$tid-{page}$keywordurl"), $thread['posts'] + 1, $page, $pagesize);
	
	$header['title'] = $thread['subject'].'-'.$forum['name'].'-'.$conf['sitename']; 
	//$header['mobile_title'] = lang('thread_detail');
	$header['mobile_title'] = $forum['name'];;
	$header['mobile_link'] = url("forum-$fid");
	$header['keywords'] = ''; 
	$header['description'] = $thread['subject'];
	$_SESSION['fid'] = $fid;
	
	$_uid = intval($thread['uid']);
	$_user = $thread['user'];
	$profile_threadlist = thread_find(array('uid'=>$_uid), array('create_date'=>-1, 'tid'=>-1), 1, 10);
	thread_list_access_filter($profile_threadlist, $gid);
	$profile_hot_threadlist = thread_find(array('uid'=>$_uid), array('views'=>-1, 'posts'=>-1, 'tid'=>-1), 1, 10);
	thread_list_access_filter($profile_hot_threadlist, $gid);
	$quickbar_setting = setting_get('quickbar');
	!is_array($quickbar_setting) AND $quickbar_setting = array();
	$quickbar_enable = intval(array_value($quickbar_setting, 'enable', 1));
	$quickbar_wechat_qrcode = array_value($quickbar_setting, 'wechat_qrcode', '');
	if($quickbar_wechat_qrcode && !preg_match('#^(https?:)?//#i', $quickbar_wechat_qrcode) && strpos($quickbar_wechat_qrcode, '/') !== 0) {
		$quickbar_wechat_qrcode = './'.$quickbar_wechat_qrcode;
	}
	$quickbar_wechat_text = array_value($quickbar_setting, 'wechat_text', '扫一扫添加站长微信');
	$copyright_setting = setting_get('copyright');
	!is_array($copyright_setting) AND $copyright_setting = array();
	$copyright_enable = intval(array_value($copyright_setting, 'enable', 1));
	$copyright_title = array_value($copyright_setting, 'title', '版权声明');
	$copyright_content = array_value($copyright_setting, 'content', '它以克制的设计语言回应内容本身，让界面退后一步，让讨论与表达重新成为中心。简洁的结构、舒展的留白与温和的节奏，使每一次浏览都如翻阅一册安静的笔记，在秩序之中保留人与人之间真实的温度。');
	$readmore_setting = setting_get('readmore');
	!is_array($readmore_setting) AND $readmore_setting = array();
	$readmore_enable = intval(array_value($readmore_setting, 'enable', 1));
	$readmore_height = intval(array_value($readmore_setting, 'height', 520));
	$readmore_height < 240 AND $readmore_height = 240;
	$readmore_length = intval(array_value($readmore_setting, 'length', 800));
	$readmore_length < 100 AND $readmore_length = 100;
	$readmore_button_text = array_value($readmore_setting, 'button_text', '展开阅读全文');
	$readmore_text = strip_tags($first['message_fmt'] ? $first['message_fmt'] : xn_txt_to_html($first['message']));
	$readmore_text = html_entity_decode($readmore_text, ENT_QUOTES, 'UTF-8');
	$readmore_need = $readmore_enable && $page == 1 && xn_strlen(trim($readmore_text)) >= $readmore_length;
	$prev_thread = thread_find(array('fid'=>$fid, 'tid'=>array('<'=>$tid)), array('tid'=>-1), 1, 1);
	$prev_thread = $prev_thread ? array_pop($prev_thread) : array();
	$next_thread = thread_find(array('fid'=>$fid, 'tid'=>array('>'=>$tid)), array('tid'=>1), 1, 1);
	$next_thread = $next_thread ? array_pop($next_thread) : array();
	
	
	
	// hook thread_info_end.php
	
	include _include(APP_PATH.'view/htm/thread.htm');
}

// hook thread_end.php

?>