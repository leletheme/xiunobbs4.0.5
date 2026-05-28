<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = realpath(dirname(__FILE__).'/../');
$fix = isset($_GET['fix']) || (PHP_SAPI == 'cli' && in_array('--fix', $_SERVER['argv']));
$is_cli = PHP_SAPI == 'cli';
$results = array();

function ic_add(&$results, $group, $name, $status, $message, $extra = '') {
	$results[] = array(
		'group'=>$group,
		'name'=>$name,
		'status'=>$status,
		'message'=>$message,
		'extra'=>$extra,
	);
}

function ic_status_text($status) {
	if($status == 'ok') return '通过';
	if($status == 'warn') return '警告';
	if($status == 'fail') return '失败';
	return '信息';
}

function ic_read_conf($file) {
	if(!is_file($file)) return array();
	$conf = @include $file;
	return is_array($conf) ? $conf : array();
}

function ic_safe($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function ic_tail($file, $lines = 20) {
	if(!is_file($file)) return '';
	$data = @file($file);
	if(!$data) return '';
	$data = array_slice($data, -$lines);
	return implode('', $data);
}

function ic_lint_file($file) {
	if(!function_exists('shell_exec')) return array('warn', '当前环境禁用了 shell_exec，无法执行 php -l');
	$php = PHP_BINARY ? PHP_BINARY : 'php';
	$cmd = escapeshellarg($php).' -l '.escapeshellarg($file).' 2>&1';
	$out = shell_exec($cmd);
	if($out === NULL) return array('warn', '无法执行 php -l');
	if(stripos($out, 'No syntax errors detected') !== FALSE) return array('ok', trim($out));
	return array('fail', trim($out));
}

function ic_check_dir(&$results, $root, $path, $must_write, $fix) {
	$full = $root.'/'.$path;
	if(!is_dir($full)) {
		if($fix) @mkdir($full, 0777, TRUE);
	}
	if(!is_dir($full)) {
		ic_add($results, '目录', $path, 'fail', '目录不存在', $fix ? '已尝试创建但失败，请检查父目录权限。' : '可加参数 ?fix=1 自动创建。');
		return;
	}
	if($must_write && !is_writable($full)) {
		ic_add($results, '目录', $path, 'fail', '目录不可写', '安装程序需要该目录可写。');
		return;
	}
	ic_add($results, '目录', $path, 'ok', $must_write ? '目录存在且可写' : '目录存在');
}

if(!$root) {
	exit('无法定位项目根目录');
}

$conf_file = $root.'/conf/conf.php';
$conf_default_file = $root.'/conf/conf.default.php';
$conf = ic_read_conf($conf_file);
$conf_default = ic_read_conf($conf_default_file);

ic_add($results, '基础', '项目根目录', 'ok', $root);
ic_add($results, '基础', 'PHP 版本', version_compare(PHP_VERSION, '7.0.0', '>=') ? 'ok' : 'fail', PHP_VERSION, '建议 PHP 7.4 或 PHP 8.0+。');
ic_add($results, '基础', '操作系统', 'ok', PHP_OS);
ic_add($results, '基础', '当前运行模式', 'ok', $is_cli ? 'CLI' : 'Web');

foreach(array('pdo_mysql', 'mysqli', 'mbstring', 'json', 'curl', 'fileinfo', 'gd', 'openssl') as $ext) {
	$status = extension_loaded($ext) ? 'ok' : ($ext == 'pdo_mysql' || $ext == 'json' ? 'fail' : 'warn');
	ic_add($results, '扩展', $ext, $status, extension_loaded($ext) ? '已启用' : '未启用');
}

foreach(array('glob', 'gzinflate', 'mb_substr', 'file_get_contents', 'file_put_contents', 'json_decode') as $fn) {
	ic_add($results, '函数', $fn.'()', function_exists($fn) ? 'ok' : 'fail', function_exists($fn) ? '可用' : '不可用');
}

ic_check_dir($results, $root, 'conf', TRUE, $fix);
ic_check_dir($results, $root, 'log', TRUE, $fix);
ic_check_dir($results, $root, 'tmp', TRUE, $fix);
ic_check_dir($results, $root, 'upload', TRUE, $fix);
ic_check_dir($results, $root, 'install', FALSE, $fix);
ic_check_dir($results, $root, 'xiunophp', FALSE, $fix);
ic_check_dir($results, $root, 'model', FALSE, $fix);
ic_check_dir($results, $root, 'route', FALSE, $fix);
ic_check_dir($results, $root, 'view', FALSE, $fix);

if(is_dir($root.'/plugin')) {
	ic_add($results, '目录', 'plugin', is_writable($root.'/plugin') ? 'ok' : 'warn', is_writable($root.'/plugin') ? '插件目录存在且可写' : '插件目录存在但不可写', '没有插件目录不应阻止安装。');
} else {
	ic_add($results, '目录', 'plugin', 'ok', '插件目录不存在', '当前安装流程不应强制要求 plugin 目录。');
}

foreach(array('index.php', 'install/index.php', 'install/install.func.php', 'install/install.sql', 'conf/conf.default.php', 'xiunophp/xiunophp.php', 'model/plugin.func.php', 'model/thread.func.php') as $file) {
	ic_add($results, '文件', $file, is_file($root.'/'.$file) ? 'ok' : 'fail', is_file($root.'/'.$file) ? '文件存在' : '文件缺失');
}

if(is_file($root.'/index.html')) {
	ic_add($results, '入口', 'index.html', 'warn', '检测到默认 index.html', '如果服务器首页优先级高于 index.php，可能会显示默认建站页，建议删除或调整默认首页顺序。');
} else {
	ic_add($results, '入口', 'index.html', 'ok', '未检测到 index.html 干扰');
}

if(!is_file($conf_file)) {
	ic_add($results, '配置', 'conf/conf.php', 'warn', '配置文件不存在', '未安装状态应进入 /install/。');
} elseif(empty($conf)) {
	ic_add($results, '配置', 'conf/conf.php', 'fail', '配置文件存在但无法读取为数组');
} else {
	$installed = !empty($conf['installed']) ? 1 : 0;
	ic_add($results, '配置', 'installed', $installed ? 'ok' : 'warn', $installed ? '已安装' : '未安装', '未安装时前台应跳转 /install/。');
	ic_add($results, '配置', 'db.type', empty($conf['db']['type']) ? 'fail' : 'ok', empty($conf['db']['type']) ? '缺失' : $conf['db']['type']);
	ic_add($results, '配置', 'tmp_path', empty($conf['tmp_path']) ? 'fail' : 'ok', empty($conf['tmp_path']) ? '缺失' : $conf['tmp_path']);
	ic_add($results, '配置', 'upload_path', empty($conf['upload_path']) ? 'fail' : 'ok', empty($conf['upload_path']) ? '缺失' : $conf['upload_path']);
}

if(!is_file($conf_default_file)) {
	ic_add($results, '配置', 'conf.default.php', 'fail', '默认配置文件缺失');
} elseif(empty($conf_default)) {
	ic_add($results, '配置', 'conf.default.php', 'fail', '默认配置文件无法读取为数组');
} else {
	ic_add($results, '配置', 'conf.default.php', 'ok', '默认配置可读取');
}

if(!empty($conf['db']['type'])) {
	$type = $conf['db']['type'];
	$dbconf = isset($conf['db'][$type]['master']) ? $conf['db'][$type]['master'] : array();
	if($type == 'pdo_mysql' && extension_loaded('pdo_mysql') && $dbconf) {
		$host = isset($dbconf['host']) ? $dbconf['host'] : '127.0.0.1';
		$name = isset($dbconf['name']) ? $dbconf['name'] : '';
		$user = isset($dbconf['user']) ? $dbconf['user'] : '';
		$password = isset($dbconf['password']) ? $dbconf['password'] : '';
		$charset = isset($dbconf['charset']) ? $dbconf['charset'] : 'utf8';
		try {
			$pdo = new PDO("mysql:host=$host;dbname=$name;charset=$charset", $user, $password, array(PDO::ATTR_TIMEOUT=>5));
			ic_add($results, '数据库', 'PDO 连接', 'ok', '连接成功', '数据库名：'.$name);
			$prefix = isset($dbconf['tablepre']) ? $dbconf['tablepre'] : 'bbs_';
			foreach(array('user', 'group', 'forum', 'thread', 'post', 'kv') as $table) {
				$stmt = $pdo->query("SHOW TABLES LIKE ".$pdo->quote($prefix.$table));
				ic_add($results, '数据库表', $prefix.$table, $stmt && $stmt->fetch() ? 'ok' : 'warn', $stmt ? '检测完成' : '检测失败');
			}
		} catch(Exception $e) {
			ic_add($results, '数据库', 'PDO 连接', 'fail', $e->getMessage());
		}
	} else {
		ic_add($results, '数据库', '连接检测', 'warn', '当前工具仅自动检测 pdo_mysql 配置');
	}
}

foreach(array('index.php', 'install/index.php', 'install/install.func.php', 'model/thread.func.php', 'model/post.func.php', 'route/index.php', 'route/thread.php', 'route/post.php', 'admin/route/setting.php') as $file) {
	$full = $root.'/'.$file;
	if(is_file($full)) {
		$r = ic_lint_file($full);
		ic_add($results, '语法', $file, $r[0], $r[1]);
	}
}

foreach(array('log/'.date('Ym').'/php_error.php', 'log/'.date('Ym').'/debug_error.php', 'log/'.date('Ym').'/db_error.php', 'log/'.date('Ym').'/db_exec.php') as $file) {
	$full = $root.'/'.$file;
	if(is_file($full)) {
		$tail = trim(ic_tail($full, 12));
		ic_add($results, '日志', $file, $tail === '' ? 'ok' : 'warn', $tail === '' ? '日志为空' : '最近日志如下', $tail);
	}
}

$fail = 0;
$warn = 0;
foreach($results as $r) {
	if($r['status'] == 'fail') $fail++;
	if($r['status'] == 'warn') $warn++;
}

if($is_cli) {
	echo "Xiuno 安装程序排查报告\n";
	echo "根目录：$root\n";
	echo "失败：$fail，警告：$warn\n\n";
	foreach($results as $r) {
		echo '['.ic_status_text($r['status']).'] '.$r['group'].' - '.$r['name'].'：'.$r['message'];
		if($r['extra'] !== '') echo "\n  ".$r['extra'];
		echo "\n";
	}
	exit;
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Xiuno 安装程序排查工具</title>
<style>
body{font-family:Arial,"Microsoft YaHei",sans-serif;background:#f6f7fb;color:#1f2937;margin:0;padding:24px}.wrap{max-width:1180px;margin:0 auto}.head{background:#111827;color:#fff;border-radius:12px;padding:22px 26px;margin-bottom:18px}.head h1{margin:0 0 8px;font-size:24px}.head p{margin:0;color:#cbd5e1}.summary{display:flex;gap:12px;margin-bottom:18px}.box{background:#fff;border-radius:12px;padding:16px 18px;box-shadow:0 1px 3px rgba(15,23,42,.08);flex:1}.box b{font-size:28px;display:block}.ok b{color:#16a34a}.warn b{color:#d97706}.fail b{color:#dc2626}table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.08)}th,td{padding:12px 14px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}th{background:#f3f4f6;font-weight:700}.tag{display:inline-block;border-radius:999px;padding:3px 9px;font-size:12px;color:#fff}.tag-ok{background:#16a34a}.tag-warn{background:#d97706}.tag-fail{background:#dc2626}.tag-info{background:#2563eb}pre{white-space:pre-wrap;word-break:break-all;background:#0f172a;color:#e5e7eb;padding:10px;border-radius:8px;max-height:260px;overflow:auto}.actions{margin:16px 0}.actions a{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px}.muted{color:#64748b;font-size:13px}
</style>
</head>
<body>
<div class="wrap">
	<div class="head">
		<h1>Xiuno 安装程序排查工具</h1>
		<p>根目录：<?php echo ic_safe($root); ?></p>
	</div>
	<div class="summary">
		<div class="box ok"><span>检查项</span><b><?php echo count($results); ?></b></div>
		<div class="box fail"><span>失败</span><b><?php echo $fail; ?></b></div>
		<div class="box warn"><span>警告</span><b><?php echo $warn; ?></b></div>
	</div>
	<div class="actions">
		<a href="?fix=1">自动创建缺失必要目录</a>
		<span class="muted"> 仅创建 conf、log、tmp、upload 等必要目录，不会删除文件。</span>
	</div>
	<table>
		<tr><th width="110">分组</th><th width="220">检查项</th><th width="80">状态</th><th>结果</th><th>附加信息</th></tr>
		<?php foreach($results as $r) { ?>
		<tr>
			<td><?php echo ic_safe($r['group']); ?></td>
			<td><?php echo ic_safe($r['name']); ?></td>
			<td><span class="tag tag-<?php echo ic_safe($r['status']); ?>"><?php echo ic_status_text($r['status']); ?></span></td>
			<td><?php echo ic_safe($r['message']); ?></td>
			<td><?php echo strpos($r['extra'], "\n") !== FALSE || strlen($r['extra']) > 160 ? '<pre>'.ic_safe($r['extra']).'</pre>' : ic_safe($r['extra']); ?></td>
		</tr>
		<?php } ?>
	</table>
</div>
</body>
</html>
