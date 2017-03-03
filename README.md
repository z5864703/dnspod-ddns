# dnspod-ddns
基于dnspod api 开发的动态DNS更新脚本

PHP 5.3+ (需开启CURL扩展)

##dns_cmd.php
客户端命令行版本


##dns_server.php
服务端版本

部署到服务器端，客户端只需自定义策略请求服务接口即可。

示例：curl http://example.com/dns_server.php?token=xxx&domain=test.com&record_domain=www,cdn,cloud