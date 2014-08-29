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

    public function __construct($access_key, $secret, $default_zone='', $timeout=10) {
        $this->access_key = $access_key;
        $this->secret = $secret;
        $this->timeout = $timeout;
        $this->default_zone = $default_zone;
    }

    private function request($url){
     
        if (!function_exists('curl_init')){
            die('Sorry php library cURL is not installed!');
        }
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Qingcloud/php_library1.0');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    function __call($action, $args) {

        if (!in_array($action, self::$API_ACTIONS)) {
            throw new Exception("The API action $action is not exist");
        }

        if (count($args) === 0) {
            return $this->_action($action);
        } else if (count($args) === 1) {
            return $this->_action($action, $args[0]);
        } else if (count($args) === 2) {
            return $this->_action($action, $args[0], $args[1]);
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
        return "$raw_param&signature=$urlsigb64";
    }

    private function get_sign_url($params, $action, $zone, $url='iaas') {
        $url = '/' . trim($url, '/') . '/';

        $params['action'] = $action;
        if ($zone === '') {
            if ($this->default_zone != '') {
                $params['zone'] = $this->default_zone;
            }
        } else {
            $params['zone'] = $zone;
        }

        $raw_param = $this->_sign('GET', $url, $params);
        return self::$API_ENDPOINT . "$url?$raw_param";
    }

    private function _action($action, $params=array(), $zone='') {
        $response = $this->request(
            $this->get_sign_url($params, $action, $zone)
        );
        return new QingcloudResponse(json_decode($response, true));
    }

}
