## TeamOne Redirect

霆万平头哥开源

![TeamOne](https://font.thwpmanage.com/img/teamone.jpg) 

TeamOne Redirect 是【霆万平头哥】基于WordPress 框架开发的插件。

### 介绍

***

TeamOne Redirect 的重定向管理器可在WordPress 框架中将页面URL重定向和路由重写目标url的能力，切换更换主题不会丢失代码片段，拥有即开即用，随时可关的特点。

针对了在 WordPress 框架重写目标URL机制中与中间服务器的请求优化，集成增加了RDS缓存链接层，大大加快了网页访问的速度。

### 功能模块

- 基本功能使用
- 数据批量处理工具
- RDS 缓存链路设置
- 路由重写机制
- 正则规则匹配机制

### 用处

- WordPress 框架中便捷化重定向主题页面URL地址
- WordPress 框架中路由重写功能，可指定源地址，获取目标地址页面数据
- 即开即用，随时即关，切换主题不会失去相关功能
- 实现了正则规则功能，可批量配置
- 批量导入导出，数据迁移更加便捷
- 列表页支持批量激活、删除等操作
- 列表页支持状态筛选搜索

### 插件相关选项

### 是否启用正则匹配

- Enable Regular Expressions (advanced)

### 规则选项

- 301 Moved Permanently
- Url Rewriting

### 激活状态

- Active
- Inactive

### 插件配置界面

- 前台路由匹配目标跳转域名
- RDS 服务器的IP或主机名 Host
- RDS 端口 Port
- RDS 密码 设置
- RDS 缓存KEY配置

### 注意事项：

- 如需要使用路由重写规则URL，需关闭正则匹配功能（Enable Regular Expressions (advanced)）。
- 本插件支持源链接URL（Redirect From）或者目的链接URL（Redirect To）为绝对路径，
  
  例：
  ```
  https://passvers.com/
  ```
  
  也可为相对路径，例：
  ```
  /news/
  ```
  
  但如为相对路径条件下，需在Setting配置页配置域名链接，例：
  ```
  https://test-th-team-one-redirect-manager.thwpmanage.com
  ```

- 该插件会截取浏览的网页除去域名部分的网址，匹配Redirect From去除域名与协议类型的规则进行跳转。
- 如果开启正则匹配规则情况下，需注意正则匹配只需匹配除去域名协议部分的网址即可，无需带协议匹配正则匹配。
