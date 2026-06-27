### 【站长交流群】
785017513

### 【作者QQ】
345179552

### 【作者微信】
Xiaole_920

# Xiuno BBS 定制版论坛项目

这是一个基于 **Xiuno BBS 4.0.4** 二次开发的 PHP 轻论坛项目。项目保留 Xiuno 原有的轻量、高性能、Hook 插件机制和 Bootstrap 响应式布局，同时针对实际站点运营需求增加了多项后台可控功能、前台 UI 优化、帖子详情页增强、安装程序兼容修复以及 PHP 8 运行环境适配。

本仓库适合用于：

- 轻量社区论坛
- 资源分享站
- 插件/主题资源站
- 个人技术交流社区
- 基于 Xiuno 的二次开发项目

---

## 项目基础信息

| 项目 | 说明 |
| --- | --- |
| 基础程序 | Xiuno BBS 4.0.4 |
| 后端语言 | PHP |
| 数据库 | MySQL / PDO MySQL |
| 前端框架 | Bootstrap 4、jQuery 3 |
| 模板机制 | PHP 模板 + Xiuno Hook 编译缓存 |
| 插件机制 | Hook + overwrite |
| 推荐 PHP | PHP 7.4 / PHP 8.0+ |
| 字符集 | UTF-8 |

---

## 当前定制功能概览

本项目不是原版 Xiuno BBS，而是在原程序基础上做了较多真实业务改造。

### 前台功能

- 首页、版块页、详情页宽度统一优化
- 帖子详情页双栏布局优化
- 帖子详情页作者资料卡优化
- 侧栏“更多作者好帖”“热门帖子”排序标签样式优化
- 帖子详情页新增上一篇 / 下一篇导航
- 帖子详情页支持后台可控版权声明
- 帖子详情页支持长文折叠与“展开阅读全文”
- 帖子详情页右侧快捷按钮
  - 快速发帖
  - 站长微信二维码
  - 返回顶部
- 快捷按钮微信二维码支持后台上传

### 后台功能

后台设置菜单中已扩展：

- 基本设置
- SMTP 设置
- 签到设置
- 快捷按钮
- 版权声明
- 阅读全文
- 签到记录

其中新增或增强的功能包括：

- 快捷按钮设置
- 微信二维码上传与预览
- 版权声明设置
- 阅读全文设置
  - 是否启用
  - 折叠高度
  - 触发字数
  - 按钮文字
- 签到记录查看
- 推荐内容管理入口

### 安装程序修复

安装程序经过针对当前环境的修复和增强：

- PHP 8 兼容性调整
- 安装保护逻辑优化
- PDO MySQL 检测与连接逻辑优化
- 数据库创建和重连流程修复
- 配置写入和环境检测增强
- `bbs_checkin` 表结构与备份库兼容调整

### PHP 8 兼容性

项目针对 PHP 8 环境处理过以下问题：

- 旧版字符串花括号偏移写法兼容检查
- 安装程序 PHP 7+ / PHP 8 环境适配
- 插件 Hook 中未定义数组键风险处理
- 回帖、发帖流程中异常返回风险排查

---

## 目录结构说明

```text
admin/                  后台管理路由、模板和菜单配置
conf/                   配置文件目录
install/                安装程序和数据库结构文件
lang/                   多语言包
log/                    运行日志目录
model/                  核心业务模型函数
route/                  前台路由控制器
tool/                   维护、迁移和排查工具
view/                   前台模板、CSS、JS、图片资源
xiunophp/               XiunoPHP 核心框架函数库
index.php               项目入口文件
model.inc.php           模型加载入口
index.inc.php           路由加载入口
README.md               项目说明文档
INSTALL.txt             原安装说明
LICENSE.txt             授权协议
```

---

## 重要目录说明

### `route/`

前台路由目录。

常用文件：

| 文件 | 说明 |
| --- | --- |
| `index.php` | 首页 |
| `forum.php` | 版块页 |
| `thread.php` | 帖子详情页 |
| `post.php` | 发帖、回帖、编辑、删除 |
| `user.php` | 用户相关 |
| `checkin.php` | 签到相关 |

### `admin/route/`

后台路由目录。

本项目中 `admin/route/setting.php` 已扩展：

- `setting-quickbar`
- `setting-quickbar_upload`
- `setting-copyright`
- `setting-readmore`
- `setting-checkin`
- `setting-checkin_log`

### `view/htm/`

前台模板目录。

重点文件：

| 文件 | 说明 |
| --- | --- |
| `index.htm` | 首页模板 |
| `forum.htm` | 版块页模板 |
| `thread.htm` | 帖子详情页模板 |
| `post_list.inc.htm` | 回复列表片段 |
| `thread_list.inc.htm` | 主题列表片段 |

### `view/css/bootstrap-bbs.css`

主要自定义前台样式文件。

当前项目大量 UI 优化都集中在这里，包括：

- 版心宽度
- 详情页布局
- 侧栏卡片
- 排序标签
- 阅读全文按钮
- 快捷按钮
- 微信二维码弹层
- 深色模式适配部分样式

### `admin/view/htm/`

后台模板目录。

本项目新增或重点维护：

| 文件 | 说明 |
| --- | --- |
| `setting_quickbar.htm` | 快捷按钮设置页 |
| `setting_copyright.htm` | 版权声明设置页 |
| `setting_readmore.htm` | 阅读全文设置页 |
| `setting_checkin.htm` | 签到设置页 |
| `setting_checkin_log.htm` | 签到记录页 |

---

## 环境要求

### 推荐环境

```text
Nginx / Apache
PHP 7.4 或 PHP 8.0+
MySQL 5.6+ / MariaDB 10+
PDO MySQL 扩展
```

### PHP 扩展建议

建议启用：

```text
pdo_mysql
mysqli
curl
mbstring
json
fileinfo
openssl
gd
```

其中：

- `pdo_mysql`：推荐数据库连接方式
- `curl`：远程请求、第三方接口或扩展功能可能需要
- `mbstring`：中文长度、字符串处理需要
- `fileinfo`：上传类型判断更安全
- `gd`：图片处理相关功能建议启用

---

## 安装说明

### 1. 上传代码

将项目代码上传到网站根目录，例如：

```text
/www/wwwroot/example.com/
```

确保以下目录可写：

```text
conf/
tmp/
log/
upload/
```

### 2. 访问安装程序

浏览器访问：

```text
https://你的域名/install/
```

根据页面提示完成：

1. 选择语言
2. 阅读协议
3. 环境检测
4. 数据库配置
5. 管理员账号创建
6. 完成安装

### 3. 安装后安全处理

安装完成后建议：

```text
删除或限制 install/ 目录访问
确认 conf/conf.php 权限安全
确认后台账号密码安全
```

### 4. 伪静态

项目支持 Xiuno 默认 URL 规则。

`.htaccess` 已存在，Apache 可直接使用。

Nginx 需要根据服务器环境配置 rewrite，或者保持 `url_rewrite_on = 0` 使用默认兼容模式。

---

## 后台入口

默认后台入口：

```text
https://你的域名/admin/
```

后台常用位置：

```text
设置 -> 基本设置
设置 -> 快捷按钮
设置 -> 版权声明
设置 -> 阅读全文
设置 -> 签到设置
设置 -> 签到记录
论坛
主题
用户
其他 -> 清理缓存
插件
```

---

## 快捷按钮功能

后台入口：

```text
设置 -> 快捷按钮
```

功能说明：

- 控制帖子详情页右侧快捷按钮是否显示
- 设置站长微信二维码图片
- 设置二维码说明文字
- 支持后台上传二维码图片
- 前台显示：
  - 快速发帖
  - 站长微信
  - 返回顶部

二维码上传保存位置：

```text
upload/quickbar/wechat_qrcode.png
```

保存到配置中的路径通常为：

```text
upload/quickbar/wechat_qrcode.png
```

后台预览会自动转换为：

```text
../upload/quickbar/wechat_qrcode.png
```

前台展示会按前台路径规则输出。

---

## 阅读全文功能

后台入口：

```text
设置 -> 阅读全文
```

功能说明：

当帖子详情页首帖正文过长时，自动折叠正文，并显示“展开阅读全文”按钮。

可配置项：

| 配置 | 说明 |
| --- | --- |
| 启用阅读全文 | 是否开启长文折叠 |
| 折叠高度 | 折叠后显示高度，单位 px |
| 触发字数 | 正文纯文字达到该字数才折叠 |
| 按钮文字 | 展开按钮显示文字 |

建议配置：

```text
启用：是
折叠高度：520
触发字数：800
按钮文字：展开阅读全文
```

---

## 版权声明功能

后台入口：

```text
设置 -> 版权声明
```

功能说明：

开启后，在帖子详情页正文下方显示版权声明区域。

可配置：

```text
是否启用
声明标题
声明内容
```

适合用于：

- 原创内容声明
- 转载说明
- 社区规则提醒
- 版权保护提示

---

## 帖子详情页增强

本项目对帖子详情页做了多处增强：

- 整体宽度与首页、版块页统一
- 双栏布局优化
- 作者资料卡样式优化
- 作者更多好帖
- 热门帖子
- 排序标签 UI 优化
- 正文下方上一篇 / 下一篇导航
- 正文版权声明
- 正文过长折叠
- 快捷按钮悬浮区

上一篇 / 下一篇规则：

```text
上一篇：同版块 tid 小于当前 tid 的最近主题
下一篇：同版块 tid 大于当前 tid 的最近主题
```

---

## 日志和缓存

### 日志目录

```text
log/
```

常见日志包括：

```text
php_error.php
debug_error.php
db_exec.php
```

### 缓存目录

```text
tmp/
```

Xiuno 会将路由、模板、模型和插件 Hook 编译到 `tmp/`。

如果你修改了：

```text
route/
model/
view/htm/
admin/view/htm/
plugin/
```

但线上没有变化，通常需要清理：

```text
tmp/*
```

如果服务器启用了 OPcache，还需要重启 PHP 或清理 OPcache。

---

## 上传和敏感文件安全

如果要上传到 GitHub，请不要上传真实运行配置和敏感数据。

建议忽略：

```text
conf/conf.php
conf/smtp.conf.php
tmp/*
log/*
upload/*
*.sql
```

原因：

- `conf/conf.php` 可能包含数据库账号密码和站点密钥
- `smtp.conf.php` 可能包含邮箱密码
- `log/` 可能包含报错路径、IP、SQL 信息
- `upload/` 可能包含用户上传文件
- `.sql` 可能包含数据库备份和用户数据

可以保留：

```text
conf/conf.default.php
log/.gitkeep
```

---

## 常用维护命令

### PHP 语法检查

```bash
php -l route/thread.php
php -l route/post.php
php -l admin/route/setting.php
php -l admin/view/htm/setting_quickbar.htm
php -l admin/view/htm/setting_readmore.htm
php -l admin/view/htm/setting_copyright.htm
```

### 清理缓存

Linux：

```bash
rm -rf tmp/*
```

Windows PowerShell：

```powershell
Remove-Item -Recurse -Force .\tmp\*
```

---

## 已知注意事项

### 1. 修改源码后前台无变化

优先清理：

```text
tmp/*
```

如果仍无变化，检查 OPcache。

### 2. 后台上传图片成功但前台不显示

重点检查：

```text
保存路径是否为 upload/xxx
后台预览是否需要 ../ 前缀
前台是否使用正确相对路径
upload 目录是否可访问
```

---

## 推荐 Git 忽略规则

建议创建 `.gitignore`：

```gitignore
/conf/conf.php
/conf/smtp.conf.php
/tmp/*
!/tmp/.gitkeep
/log/*
!/log/.gitkeep
/upload/*
*.sql
*.zip
*.tar
*.gz
.DS_Store
Thumbs.db
```

---

## 授权说明

Xiuno BBS 4.0 原程序采用 MIT 协议发布。

本项目是在 Xiuno BBS 4.0.4 基础上的二次开发版本。使用、修改、商用时请遵守原项目授权协议，并保留原有版权信息。

---

## 项目维护建议

1. 线上修改后及时同步到版本库。
2. 不要提交真实数据库配置、SMTP 密码和数据库备份。
3. 每次改动 PHP 文件后至少运行 `php -l` 检查语法。
4. 每次改动模板、路由、模型后清理 `tmp/` 缓存验证。
5. 升级 PHP 版本前先在测试环境检查兼容性。


