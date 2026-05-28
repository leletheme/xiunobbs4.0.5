# beta4 -> beta5
DROP TABLE IF EXISTS bbs_queue;
CREATE TABLE bbs_queue (
  queueid int(11) unsigned NOT NULL default '0',		# 队列 id
  v int(11) NOT NULL default '0',			# 队列中存放的数据，只能为 int
  expiry int(11) unsigned NOT NULL default '0',		# 过期时间，默认 0，不过期
  UNIQUE KEY(queueid, v),
  KEY(expiry)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE bbs_post ADD COLUMN quotepid int(11) NOT NULL default '0';

CREATE TABLE IF NOT EXISTS bbs_credit_log (
  logid int(11) unsigned NOT NULL auto_increment,
  uid int(11) unsigned NOT NULL default '0',
  type char(16) NOT NULL default '',
  change_num int(11) NOT NULL default '0',
  after_num int(11) NOT NULL default '0',
  reason char(64) NOT NULL default '',
  action char(32) NOT NULL default '',
  related_id int(11) unsigned NOT NULL default '0',
  operator_uid int(11) unsigned NOT NULL default '0',
  create_date int(11) unsigned NOT NULL default '0',
  create_ip int(11) unsigned NOT NULL default '0',
  PRIMARY KEY (logid),
  KEY uid_logid (uid, logid),
  KEY action_related (action, related_id),
  KEY create_date (create_date)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS bbs_checkin (
  uid int(11) unsigned NOT NULL default '0',
  ymd int(8) unsigned NOT NULL default '0',
  year smallint(4) unsigned NOT NULL default '0',
  month tinyint(2) unsigned NOT NULL default '0',
  day tinyint(2) unsigned NOT NULL default '0',
  create_date int(11) unsigned NOT NULL default '0',
  reward_type char(16) NOT NULL default 'credits',
  reward_value int(11) NOT NULL default '0',
  streak int(11) unsigned NOT NULL default '1',
  PRIMARY KEY (uid, ymd),
  KEY ymd (ymd),
  KEY uid_month (uid, year, month)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;