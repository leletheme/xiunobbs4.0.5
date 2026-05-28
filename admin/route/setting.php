<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

include _include(APP_PATH.'model/smtp.func.php');
$smtplist = smtp_init(APP_PATH.'conf/smtp.conf.php');
// hook admin_setting_start.php

if($action == 'base') {
	
	// hook admin_setting_base_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_base_get_start.php
		
		$input = array();
		$input['sitename'] = form_text('sitename', $conf['sitename']);
		$input['sitebrief'] = form_textarea('sitebrief', $conf['sitebrief'], '100%', 100);
		$input['runlevel'] = form_radio('runlevel', array(0=>lang('runlevel_0'), 1=>lang('runlevel_1'), 2=>lang('runlevel_2'), 3=>lang('runlevel_3'), 4=>lang('runlevel_4'), 5=>lang('runlevel_5')), $conf['runlevel']);
		$input['user_create_on'] = form_radio_yes_no('user_create_on', $conf['user_create_on']);
		$input['user_create_email_on'] = form_radio_yes_no('user_create_email_on', $conf['user_create_email_on']);
		$input['user_resetpw_on'] = form_radio_yes_no('user_resetpw_on', $conf['user_resetpw_on']);
		$input['lang'] = form_select('lang', array('zh-cn'=>lang('lang_zh_cn'), 'zh-tw'=>lang('lang_zh_tw'), 'en-us'=>lang('lang_en_us'), 'ru-ru'=>lang('lang_ru_ru'), 'th-th'=>lang('lang_th_th')), $conf['lang']);
		
		$header['title'] = lang('admin_site_setting');
		$header['mobile_title'] =lang('admin_site_setting');
		
		// hook admin_setting_base_get_end.php
		
		include _include(ADMIN_PATH.'view/htm/setting_base.htm');
		
	} else {
		
		$sitebrief = param('sitebrief', '', FALSE);
		$sitename = param('sitename', '', FALSE);
		$runlevel = param('runlevel', 0);
		$user_create_on = param('user_create_on', 0);
		$user_create_email_on = param('user_create_email_on', 0);
		$user_resetpw_on = param('user_resetpw_on', 0);
		
		$_lang = param('lang');
		
		// hook admin_setting_base_post_start.php
		
		$replace = array();
		$replace['sitename'] = $sitename;
		$replace['sitebrief'] = $sitebrief;
		$replace['runlevel'] = $runlevel;
		$replace['user_create_on'] = $user_create_on;
		$replace['user_create_email_on'] = $user_create_email_on;
		$replace['user_resetpw_on'] = $user_resetpw_on;
		$replace['lang'] = $_lang;
		
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
	
		// hook admin_setting_base_post_end.php
		
		message(0, lang('modify_successfully'));
	}

} elseif($action == 'smtp') {

	// hook admin_setting_smtp_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_smtp_get_start.php
		
		$header['title'] = lang('admin_setting_smtp');
		$header['mobile_title'] = lang('admin_setting_smtp');
	
		$smtplist = smtp_find();
		$maxid = smtp_maxid();
		
		// hook admin_setting_smtp_get_end.php
		
		include _include(ADMIN_PATH."view/htm/setting_smtp.htm");
	
	} else {
		
		// hook admin_setting_smtp_post_start.php
		
		$email = param('email', array(''));
		$host = param('host', array(''));
		$port = param('port', array(0));
		$user = param('user', array(''));
		$pass = param('pass', array(''));
		
		$smtplist = array();
		foreach ($email as $k=>$v) {
			$smtplist[$k] = array(
				'email'=>$email[$k],
				'host'=>$host[$k],
				'port'=>$port[$k],
				'user'=>$user[$k],
				'pass'=>$pass[$k],
			);
		}
		$r = file_put_contents_try(APP_PATH.'conf/smtp.conf.php', "<?php\r\nreturn ".var_export($smtplist,true).";\r\n?>");
		!$r AND message(-1, lang('conf/smtp.conf.php', array('file'=>'conf/smtp.conf.php')));
		
		// hook admin_setting_smtp_post_end.php
		
		message(0, lang('save_successfully'));
	}
} elseif($action == 'checkin') {

	if($method == 'GET') {
		$setting = checkin_setting();
		$input = array();
		$input['enable'] = form_radio_yes_no('enable', $setting['enable']);
		$input['continue_max'] = form_text('continue_max', $setting['continue_max']);
		$input['calendar_days'] = form_text('calendar_days', $setting['calendar_days']);
		$reward_types = checkin_reward_types();
		$header['title'] = '签到设置';
		$header['mobile_title'] = '签到设置';
		include _include(ADMIN_PATH.'view/htm/setting_checkin.htm');
	} else {
		$setting = array(
			'enable'=>param('enable', 0),
			'reward_types'=>param('reward_types', array()),
			'reward_values'=>param('reward_values', array()),
			'continue_rewards'=>param('continue_rewards', array()),
			'continue_max'=>param('continue_max', 1),
			'calendar_days'=>param('calendar_days', 35),
		);
		$r = checkin_setting_set($setting);
		$r === FALSE AND message(-1, lang('save_failed'));
		message(0, lang('save_successfully'));
	}
} elseif($action == 'quickbar') {

	if($method == 'GET') {
		$setting = setting_get('quickbar');
		!is_array($setting) AND $setting = array();
		$quickbar_enable = intval(array_value($setting, 'enable', 1));
		$quickbar_wechat_qrcode = array_value($setting, 'wechat_qrcode', '');
		$quickbar_wechat_qrcode_preview = $quickbar_wechat_qrcode;
		if($quickbar_wechat_qrcode_preview && !preg_match('#^(https?:)?//#i', $quickbar_wechat_qrcode_preview) && strpos($quickbar_wechat_qrcode_preview, '../') !== 0 && strpos($quickbar_wechat_qrcode_preview, '/') !== 0) {
			$quickbar_wechat_qrcode_preview = '../'.$quickbar_wechat_qrcode_preview;
		}
		$quickbar_wechat_text = array_value($setting, 'wechat_text', '扫一扫添加站长微信');
		$input = array();
		$input['enable'] = form_radio_yes_no('enable', $quickbar_enable);
		$input['wechat_qrcode'] = form_text('wechat_qrcode', $quickbar_wechat_qrcode);
		$input['wechat_text'] = form_text('wechat_text', $quickbar_wechat_text);
		$header['title'] = '快捷按钮设置';
		$header['mobile_title'] = '快捷按钮设置';
		include _include(ADMIN_PATH.'view/htm/setting_quickbar.htm');
	} else {
		$setting = array(
			'enable'=>param('enable', 0),
			'wechat_qrcode'=>param('wechat_qrcode', '', FALSE),
			'wechat_text'=>param('wechat_text', '', FALSE),
		);
		$r = setting_set('quickbar', $setting);
		$r === FALSE AND message(-1, lang('save_failed'));
		message(0, lang('save_successfully'));
	}
} elseif($action == 'copyright') {

	if($method == 'GET') {
		$setting = setting_get('copyright');
		!is_array($setting) AND $setting = array();
		$copyright_enable = intval(array_value($setting, 'enable', 1));
		$copyright_title = array_value($setting, 'title', '版权声明');
		$copyright_content = array_value($setting, 'content', '它以克制的设计语言回应内容本身，让界面退后一步，让讨论与表达重新成为中心。简洁的结构、舒展的留白与温和的节奏，使每一次浏览都如翻阅一册安静的笔记，在秩序之中保留人与人之间真实的温度。');
		$input = array();
		$input['enable'] = form_radio_yes_no('enable', $copyright_enable);
		$input['title'] = form_text('title', $copyright_title);
		$input['content'] = form_textarea('content', $copyright_content, '100%', 120);
		$header['title'] = '版权声明设置';
		$header['mobile_title'] = '版权声明设置';
		include _include(ADMIN_PATH.'view/htm/setting_copyright.htm');
	} else {
		$setting = array(
			'enable'=>param('enable', 0),
			'title'=>param('title', '', FALSE),
			'content'=>param('content', '', FALSE),
		);
		$r = setting_set('copyright', $setting);
		$r === FALSE AND message(-1, lang('save_failed'));
		message(0, lang('save_successfully'));
	}
} elseif($action == 'readmore') {

	if($method == 'GET') {
		$setting = setting_get('readmore');
		!is_array($setting) AND $setting = array();
		$readmore_enable = intval(array_value($setting, 'enable', 1));
		$readmore_height = intval(array_value($setting, 'height', 520));
		$readmore_height < 240 AND $readmore_height = 240;
		$readmore_length = intval(array_value($setting, 'length', 800));
		$readmore_length < 100 AND $readmore_length = 100;
		$readmore_button_text = array_value($setting, 'button_text', '展开阅读全文');
		$input = array();
		$input['enable'] = form_radio_yes_no('enable', $readmore_enable);
		$input['height'] = form_text('height', $readmore_height);
		$input['length'] = form_text('length', $readmore_length);
		$input['button_text'] = form_text('button_text', $readmore_button_text);
		$header['title'] = '阅读全文设置';
		$header['mobile_title'] = '阅读全文设置';
		include _include(ADMIN_PATH.'view/htm/setting_readmore.htm');
	} else {
		$height = param('height', 520);
		$height = max(240, min(2000, intval($height)));
		$length = param('length', 800);
		$length = max(100, min(100000, intval($length)));
		$setting = array(
			'enable'=>param('enable', 0),
			'height'=>$height,
			'length'=>$length,
			'button_text'=>param('button_text', '', FALSE),
		);
		$setting['button_text'] = trim($setting['button_text']) === '' ? '展开阅读全文' : $setting['button_text'];
		$r = setting_set('readmore', $setting);
		$r === FALSE AND message(-1, lang('save_failed'));
		message(0, lang('save_successfully'));
	}
} elseif($action == 'quickbar_upload') {

	if($method != 'POST') message(-1, lang('method_error'));
	$name = param('name', '', FALSE);
	$data_param = param('data', '', FALSE);
	if(empty($data_param) && isset($_POST['data'])) $data_param = $_POST['data'];
	if(empty($data_param) && isset($_POST['upfile'])) $data_param = $_POST['upfile'];
	if(empty($name) && isset($_POST['name'])) $name = $_POST['name'];
	if(empty($name)) $name = 'wechat_qrcode.png';
	if(empty($data_param)) message(-1, lang('data_is_empty'));
	$data_param = str_replace(' ', '+', $data_param);
	$data = strpos($data_param, ',') === FALSE ? base64_decode($data_param) : base64_decode(substr($data_param, strpos($data_param, ',') + 1));
	empty($data) AND message(-1, lang('data_is_empty'));
	$size = strlen($data);
	$size > 2048000 AND message(-1, lang('filesize_too_large', array('maxsize'=>'2M', 'size'=>$size)));
	$ext = strtolower(file_ext($name, 7));
	!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp')) AND message(-1, '仅支持 jpg、png、gif、bmp、webp 图片');
	$filename = 'wechat_qrcode.png';
	$path = $conf['upload_path'].'quickbar/';
	$url = $conf['upload_url'].'quickbar/'.$filename;
	!is_dir($path) AND (mkdir($path, 0777, TRUE) OR message(-2, lang('directory_create_failed')));
	file_put_contents($path.$filename, $data) OR message(-1, lang('write_to_file_failed'));
	$setting = setting_get('quickbar');
	!is_array($setting) AND $setting = array();
	$setting['wechat_qrcode'] = $url;
	setting_set('quickbar', $setting);
	message(0, array('url'=>$url));
} elseif($action == 'checkin_log') {

	$page = param('page', param(2, 1));
	$pagesize = 20;
	$uid_filter = param('uid', 0);
	$ymd = param('ymd');
	$month = param('month');
	$date_start = param('date_start');
	$date_end = param('date_end');
	$cond = array();
	$uid_filter AND $cond['uid'] = $uid_filter;
	$ymd AND $cond['ymd'] = intval(str_replace('-', '', $ymd));
	if($month) {
		$_month_time = strtotime($month.'-01');
		$cond['year'] = intval(date('Y', $_month_time));
		$cond['month'] = intval(date('n', $_month_time));
	}
	$date_start AND $cond['ymd'] = array('>='=>intval(str_replace('-', '', $date_start)));
	$date_end AND $cond['ymd'] = array('<='=>intval(str_replace('-', '', $date_end)));
	if($date_start && $date_end) $cond['ymd'] = array('>='=>intval(str_replace('-', '', $date_start)), '<='=>intval(str_replace('-', '', $date_end)));
	$total = checkin_count($cond);
	$checkin_list = checkin_find($cond, $page, $pagesize);
	$userlist = array();
	if($checkin_list) {
		foreach($checkin_list as $_record) {
			$_user = user_read_cache($_record['uid']);
			$_user AND $userlist[$_record['uid']] = $_user;
		}
	}
	$pagination_url = url('setting-checkin_log').'?uid='.$uid_filter.'&ymd='.urlencode($ymd).'&month='.urlencode($month).'&date_start='.urlencode($date_start).'&date_end='.urlencode($date_end).'&page={page}';
	$pagination = pagination($pagination_url, $total, $page, $pagesize);
	$header['title'] = '签到记录';
	$header['mobile_title'] = '签到记录';
	include _include(ADMIN_PATH.'view/htm/setting_checkin_log.htm');

}

// hook admin_setting_end.php

?>