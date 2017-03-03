<?php
#!/usr/bin/php -q
if ($argc > 2 || $argc < 1) {
    die('parameter error');
}
require_once(dirname(__FILE__) . '/Dnspod.php');

$domain_name = 'igolink.net';           //主域名
$sub_domain = ['@', 'gefire', 'open'];  //待更新记录列表
$token = '';                            //dnspod api使用token


$dnspod = new Dnspod([
    'login_token' => $token
]);

$domain_id = $dnspod->getDomainId($domain_name);
if ($domain_id == false) {
    save_log('获取域名ID错误');
    exit('domain id error');
}

if ($argc == 1 || strtolower($argv[1]) == 'update_ddns') {
    $record_list = $dnspod->getRecordList($domain_id);
    if ($record_list == false) {
        save_log('获取域名记录列表错误：' . $dnspod->error);
        exit('record list error');
    }

    $this_ip = trim(get_this_ip());

    foreach ($record_list['records'] as $val) {
        if (in_array($val['name'], $sub_domain) && $val['type'] == 'A') {
            if ($val['value'] != $this_ip) {
                if (!$dnspod->changeDDNSRecord($domain_id, $val['id'], $val['name'], $this_ip)) {
                    save_log('更新域名记录错误：' . $dnspod->error);
                }
            }
        }
    }
    exit();
}
switch (strtolower($argv[1])) {
    //添加记录
    case 'add_record':
        fwrite(STDOUT, 'input record_type(A,CNAME):');  //输入记录类型
        $add_record_type = trim(strtoupper(fgets(STDIN)));
        if (!in_array($add_record_type, ['A', 'CNAME'])) {
            exit('record_type error');
        }

        fwrite(STDOUT, 'input sub_domain: ');   //输入记录值
        $add_sub_domain = trim(fgets(STDIN));
        if (empty($add_sub_domain)) {
            exit('sub_domain is null');
        }

        $this_ip = trim(get_this_ip());

        if ($dnspod->addRecord($domain_id, $add_sub_domain, $this_ip, $add_record_type)) {

            save_log('添加记录成功，记录类型：' . $add_record_type . ', 记录值：' . $add_sub_domain);
            exit('add record ok');

        } else {
            save_log(json_encode($dnspod->error));
            save_log('添加记录失败');
            exit('add record error');
        }
        break;
}
/**
 * 获取当前IP
 * @return string
 */
function get_this_ip()
{
    return file_get_contents('http://members.3322.org/dyndns/getip');
}

/**
 * 保存日志
 * @param string $message 日志消息
 */
function save_log($message)
{
    file_put_contents('dns_error.txt', date('Y-m-d H:i:s') . "：" . $message . PHP_EOL, FILE_APPEND);
}
