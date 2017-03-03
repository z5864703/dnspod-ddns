<?php

/**
 * Dnspod接口服务类
 */
class Dnspod
{

    const API_USER_DETAIL = 'https://dnsapi.cn/User.Detail';
    const API_DOMAIN_LIST = 'https://dnsapi.cn/Domain.List';
    const API_RECORD_LIST = 'https://dnsapi.cn/Record.List';
    const API_RECORD_CREATE = 'https://dnsapi.cn/Record.Create';
    const API_RECORD_DDNS = 'https://dnsapi.cn/Record.Ddns';

    private $config = array(
        'login_token' => '',
        'format' => 'json',
        'lang' => 'cn'
    );

    /**
     * 最后响应内容
     * @var string
     */
    public $last_result = '';

    /**
     * 错误内容
     * @var string
     */
    public $error = '';

    public function __construct($config = null)
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 获取域名列表
     * @return mixed
     */
    public function getDomainList()
    {
        return $this->post_data(self::API_DOMAIN_LIST, $this->config);
    }

    /**
     * 获取域名ID
     * @param string $domain_name 域名名称
     * @return bool
     */
    public function getDomainId($domain_name)
    {
        $domain_info = $this->getDomainList();

        if (is_array($domain_info) && array_key_exists('domains', $domain_info)) {
            foreach ($domain_info['domains'] as $val) {
                if ($val['name'] == $domain_name) {
                    return $val['id'];
                }
            }
        }
        return false;
    }

    /**
     * 获取域名记录列表
     * @param int $domain_id 域名ID
     * @return mixed
     */
    public function getRecordList($domain_id)
    {
        $post_data = array_merge($this->config, array(
            'domain_id' => intval($domain_id)
        ));

        return $this->post_data(self::API_RECORD_LIST, $post_data);
    }

    /**
     * 添加域名解析记录
     * @param int $domain_id 域名ID
     * @param string $sub_domain 主机记录
     * @param string $record_value 记录值
     * @param string $record_type 记录类型
     * @param string $record_line 记录线路
     * @param null $mx
     * @return bool
     */
    public function addRecord($domain_id, $sub_domain, $record_value, $record_type = 'A', $record_line = '默认', $mx = null)
    {
        $post_data = array_merge($this->config, array(
            'domain_id' => $domain_id,
            'sub_domain' => $sub_domain,
            'record_type' => $record_type,
            'record_line' => $record_line,
            'value' => $record_value,
            'mx' => $mx,
        ));

        $result = $this->post_data(self::API_RECORD_CREATE, $post_data);

        if (!empty($result) && $result['status']['code'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更改域名动态DNS记录
     * @param int $domain_id 域名ID
     * @param int $record_id 记录ID
     * @param string $sub_domain 主机记录
     * @param string $record_value 记录值
     * @return bool
     */
    public function changeDDNSRecord($domain_id, $record_id, $sub_domain, $record_value)
    {
        $post_data = array_merge($this->config, array(
            'domain_id' => $domain_id,
            'record_id' => $record_id,
            'sub_domain' => $sub_domain,
            'record_line' => '默认',
            'value' => $record_value,
        ));

        $result = $this->post_data(self::API_RECORD_DDNS, $post_data);

        if (!empty($result) && $result['status']['code'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证帐号
     * @return bool
     */
    public function verify_user()
    {
        $user_info = $this->post_data(self::API_USER_DETAIL, $this->config);

        if (!empty($user_info) && $user_info['status']['code'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 发起POST请求，获取响应资源
     * @param string $url 请求URL
     * @param array $post_data 发送post数据
     * @return array|bool
     */
    private function post_data($url, $post_data)
    {
        $curl = curl_init();                                    //初始化一个CURL资源
        curl_setopt($curl, CURLOPT_URL, $url);
        //curl_setopt($curl, CURLOPT_HEADER, 1);                //设置是否返回header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);  //设置POST数据

        //执行发送请求
        if (($data = curl_exec($curl)) === false) {
            $this->error = curl_error($curl);
            return false;
        }

        $this->last_result = $data;
        $data_array = json_decode($data, true);                //解析响应数据
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = json_last_error_msg();
            return false;
        }

        return $data_array;
    }
}
