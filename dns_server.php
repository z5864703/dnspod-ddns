<?php
if (empty($_GET['token']) || empty($_GET['domain']) || empty($_GET['record_domain'])) {
    exit('parameter error');
}
$token = $_GET['token'];
$domain = $_GET['domain'];
$record_domain = strpos($_GET['record_domain'], ',') ? explode(',', $_GET['record_domain']) : [$_GET['record_domain']];

//引入服务类
require_once(dirname(__FILE__) . '/Dnspod.php');

$Dnspod = new Dnspod([
    'login_token' => $token
]);
//验证用户
if ($Dnspod->verify_user() == false) {
    exit('token error');
}
//获取域名ID
$domain_id = $Dnspod->getDomainId($domain);
if ($domain_id == false) {
    exit('domain id error');
}
//获取记录列表
$record_list = $Dnspod->getRecordList($domain_id);
if ($record_list == false) {
    save_log(json_encode([
        'action' => 'getRecordList',
        'domain_id' => $domain_id,
        'error_msg' => $Dnspod->error
    ]));
    exit('record list error');
}

$client_ip = get_client_ip();

foreach ($record_list['records'] as $val) {
    if (in_array($val['name'], $record_domain) && $val['type'] == 'A') {
        if (trim($val['value']) != $client_ip) {
            if (!$Dnspod->changeDDNSRecord($domain_id, $val['id'], $val['name'], $client_ip)) {
                save_log(json_encode([
                    'action' => 'changeDDNSRecord',
                    'record_domain' => $val['name'],
                    'error_msg' => $Dnspod->error
                ]));
                exit('save change ddns error');
            }
        }
    }
}

/**
 * 保存日志
 * @param string $message 日志消息
 */
function save_log($message)
{
    file_put_contents('dns_error.txt', date('Y-m-d H:i:s') . "：" . $message . PHP_EOL, FILE_APPEND);
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = null;
    if (null !== $ip) {
        return $ip[$type];
    }
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}
