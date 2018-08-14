<?php

class QingcloudResponse {
    public function __construct($resp) {
        if (!$resp) {
            $this->action = '';
            $this->result = false;
            $this->status_code = '-1';
            $this->error_message = 'Request failed, got invalid response';
            $this->body = null;
            return;
        }

        if ($resp['ret_code'] === 0) {
            $this->action = $resp['action'];
            $this->result = true;
            $this->status_code = 0;
            $this->error_message = '';
            unset($resp['action']);
            unset($resp['ret_code']);
            $this->body = $resp;
        } else {
            $this->action = '';
            $this->result = false;
            $this->status_code = $resp['ret_code'];
            $this->error_message = $resp['message'];
            $this->body = null;
        }
    }
}

class Qingcloud {

    static $API_ENDPOINT ='https://api.qingcloud.com';

    static $API_ACTIONS = array(
        'DescribeInstances',
        'RunInstances',
        'TerminateInstances',
        'StartInstances',
        'StopInstances',
        'RestartInstances',
        'ResetInstances',
        'ResizeInstances',
        'ModifyInstanceAttributes',
        'DescribeVolumes',
        'CreateVolumes',
        'DeleteVolumes',
        'AttachVolumes',
        'DetachVolumes',
        'ResizeVolumes',
        'ModifyVolumeAttributes',
        'DescribeVxnets',
        'CreateVxnets',
        'DeleteVxnets',
        'JoinVxnet',
        'LeaveVxnet',
        'ModifyVxnetAttributes',
        'DescribeVxnetInstances',
        'DescribeRouters',
        'CreateRouters',
        'DeleteRouters',
        'UpdateRouters',
        'PowerOffRouters',
        'PowerOnRouters',
        'JoinRouter',
        'LeaveRouter',
        'ModifyRouterAttributes',
        'DescribeRouterStatics',
        'AddRouterStatics',
        'ModifyRouterStaticAttributes',
        'DeleteRouterStatics',
        'DescribeRouterVxnets',
        'DescribeEips',
        'AllocateEips',
        'ReleaseEips',
        'AssociateEip',
        'DissociateEips',
        'ChangeEipsBandwidth',
        'ModifyEipAttributes',
        'DescribeSecurityGroups',
        'CreateSecurityGroup',
        'DeleteSecurityGroups',
        'ApplySecurityGroup',
        'ModifySecurityGroupAttributes',
        'DescribeSecurityGroupRules',
        'AddSecurityGroupRules',
        'DeleteSecurityGroupRules',
        'ModifySecurityGroupRuleAttributes',
        'DescribeKeyPairs',
        'CreateKeyPair',
        'DeleteKeyPairs',
        'AttachKeyPairs',
        'DetachKeyPairs',
        'ModifyKeyPairAttributes',
        'DescribeImages',
        'CaptureInstance',
        'DeleteImages',
        'ModifyImageAttributes',
        'CreateLoadBalancer',
        'DescribeLoadBalancers',
        'DeleteLoadBalancers',
        'ModifyLoadBalancerAttributes',
        'StartLoadBalancers',
        'StopLoadBalancers',
        'UpdateLoadBalancers',
        'AssociateEipsToLoadBalancer',
        'DissociateEipsFromLoadBalancer',
        'AddLoadBalancerListeners',
        'DescribeLoadBalancerListeners',
        'DeleteLoadBalancerListeners',
        'ModifyLoadBalancerListenerAttributes',
        'AddLoadBalancerBackends',
        'DescribeLoadBalancerBackends',
        'DeleteLoadBalancerBackends',
        'ModifyLoadBalancerBackendAttributes',
        'GetMonitor',
        'GetLoadBalancerMonitor',
        'DescribeSnapshots',
        'CreateSnapshots',
        'DeleteSnapshots',
        'ApplySnapshots',
        'ModifySnapshotAttributes',
        'CaptureInstanceFromSnapshot',
        'CreateVolumeFromSnapshot',
        'UploadUserDataAttachment',
    );

    static $POST_ACTIONS = array(
        'UploadUserDataAttachment',
    );

    public function __construct($access_key, $secret, $default_zone='', $timeout=10) {
        $this->access_key = $access_key;
        $this->secret = $secret;
        $this->timeout = $timeout;
        $this->default_zone = $default_zone;
    }

    private function request($url, $method='GET', $body='', $headers=array()){

        if (!function_exists('curl_init')){
            die('Sorry php library cURL is not installed!');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Qingcloud/php_library1.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($method == 'GET') {
            curl_setopt($ch, CURLOPT_HEADER, 0);
        } else if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    function __call($action, $args) {

        if (!in_array($action, self::$API_ACTIONS)) {
            throw new Exception("The API action $action is not exist");
        }

        $method = in_array($action, self::$POST_ACTIONS) ? 'POST' : 'GET';

        if (count($args) === 0) {
            return $this->_action($action, $method);
        } else if (count($args) === 1) {
            return $this->_action($action, $method, $args[0]);
        } else if (count($args) === 2) {
            return $this->_action($action, $method, $args[0], $args[1]);
        }
    }

    private function _sign($method='GET', $url='/', $params=array()) {
        $params['access_key_id'] = $this->access_key;
        $params['signature_method'] = 'HmacSHA256';
        $params['signature_version'] = 1;
        $params['version'] = 1;
        $params['time_stamp'] = str_replace('+00:00', 'Z', gmdate('c'));

        ksort($params);
        $raw_param = http_build_query($params);
        $raw_sign = "$method\n$url\n$raw_param";
        $sig = hash_hmac('sha256', $raw_sign, $this->secret, $raw_output=true);
        $sigb64 = base64_encode($sig);
        $urlsigb64 = urlencode($sigb64);
        return array($raw_param, $urlsigb64);
    }

    private function authorize($method, $params, $action, $zone, $url='iaas') {
        $url = '/' . trim($url, '/') . '/';

        $params['action'] = $action;
        if ($zone === '') {
            if ($this->default_zone != '') {
                $params['zone'] = $this->default_zone;
            }
        } else {
            $params['zone'] = $zone;
        }

        list($raw_param, $sign) = $this->_sign($method, $url, $params);

        if ($method == 'GET') {
            return self::$API_ENDPOINT . "$url?$raw_param&signature=$sign";
        } else if ($method == 'POST') {
            $body = "$raw_param&signature=$sign";
            $headers = array(
              'Content-Length' => strlen($body),
              'Content-Type' => 'application/x-www-form-urlencoded',
              'Accept' => 'text/plain',
              'Connection' => 'Keep-Alive'
            );
            return array(self::$API_ENDPOINT . "$url", $body, $headers);
        }
    }

    private function _action($action, $method='GET', $params=array(), $zone='') {
        if ($method == 'GET') {
            $response = $this->request(
              $this->authorize($method, $params, $action, $zone)
            );
        } else if ($method == 'POST') {
            list($url, $body, $headers) = $this->authorize(
              $method, $params, $action, $zone
            );
            $response = $this->request($url, $method, $body, $headers);
        }
        return new QingcloudResponse(json_decode($response, true));
    }

}
