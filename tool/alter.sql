ALTER TABLE bbs_thread ADD COLUMN brief char(240) NOT NULL default '' after subject;
ALTER TABLE bbs_thread ADD COLUMN recommend tinyint(1) unsigned NOT NULL default '0' after brief;
ALTER TABLE bbs_thread ADD COLUMN tags char(120) NOT NULL default '' after recommend;

ALTER TABLE bbs_thread CHANGE lastpost last_date int(11) unsigned NOT NULL default '0';

DROP TABLE IF EXISTS bbs_thread_lastpid;
CREATE TABLE bbs_thread_lastpid (
  tid int(11) unsigned NOT NULL default '0',		# tid
  lastpid int(11) unsigned NOT NULL default '0',	# lastpid
  PRIMARY KEY (tid),					#
  UNIQUE KEY (lastpid)					#
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE bbs_thread ADD COLUMN userip int(11) unsigned NOT NULL default '0' after sid;

ALTER TABLE bbs_group ADD COLUMN allowcustomurl int(11) unsigned NOT NULL default '0';
UPDATE bbs_group SET allowcustomurl=1 WHERE gid=1 OR gid=2 OR gid=3;

ALTER TABLE bbs_post CHANGE attachs files smallint(3) unsigned NOT NULL default '0';
ALTER TABLE bbs_post ADD COLUMN images smallint(3) unsigned NOT NULL default '0' after sid;
ALTER TABLE bbs_thread ADD COLUMN images smallint(3) unsigned NOT NULL default '0' after agrees;
ALTER TABLE bbs_thread ADD COLUMN files smallint(3) unsigned NOT NULL default '0' after agrees;
ALTER TABLE bbs_attach ADD COLUMN isimage tinyint(11) NOT NULL default '0' after rmbs;

ALTER TABLE bbs_online ADD COLUMN data char(255) NOT NULL default '' after useragent;

ALTER TABLE bbs_thread DROP COLUMN seo_url;
ALTER TABLE bbs_thread ADD COLUMN url_on tinyint(1) unsigned NOT NULL default '0';	# 是否开启 SEO URL

DROP TABLE IF EXISTS bbs_thread_url;
CREATE TABLE bbs_thread_url (
  tid int(11) unsigned NOT NULL auto_increment,		# 主题id
  url char(128) NOT NULL default '',		# SEO URL
  PRIMARY KEY(tid),
  KEY (url)
);

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
  checkinid int(11) unsigned NOT NULL auto_increment,
  uid int(11) unsigned NOT NULL default '0',
  ymd int(11) unsigned NOT NULL default '0',
  year smallint(6) unsigned NOT NULL default '0',
  month tinyint(3) unsigned NOT NULL default '0',
  day tinyint(3) unsigned NOT NULL default '0',
  create_date int(11) unsigned NOT NULL default '0',
  reward_type char(32) NOT NULL default '',
  reward_value int(11) NOT NULL default '0',
  streak int(11) unsigned NOT NULL default '0',
  PRIMARY KEY (checkinid),
  UNIQUE KEY uid_ymd (uid, ymd),
  KEY uid_checkinid (uid, checkinid),
  KEY year_month (year, month),
  KEY ymd (ymd)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;