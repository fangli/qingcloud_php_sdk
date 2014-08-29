<?php
require_once 'qingcloud_sdk.class.php';

# 初始化一个青云的基础设施SDK类
# 第一个参数是access_key； 第二个参数是access_secret；第三个参数是可选的，默认zone。
$qc1 = new Qingcloud('QYACCESSKEYIDEXAMPLE', 'SECRETACCESSKEY', 'pek1');

# 接下来，直接调用文档里面的各个Action名称就行了，注意大小写
# 可传入0个、1个或2个参数
# 如果传入0个参数，则必须在前一步骤new class时，就指定好第三个参数，默认zone名称
# 如果传入1个参数，则参数为array()，包含了所有的查询参数。
# 如果传入2个参数，则第1个参数为包含了查询参数的array()，第2个参数为zone名称

# $req = $qc1->[action]会返回一个 QingcloudResponse Object
# 通过 $req->result 获取此次请求成功与否，成功为true，失败为false
# 当失败时，通过$req->status_code 获取失败的状态码，通过$req->error_message获取失败的消息提示
# 当成功时，通过$req->body获取返回的数据array。可以通过$req->body['instance_id']等数组获取具体数据。

$response = $qc1->DescribeInstances();
if ($response->result == true) {
    print_r($response->body);
} else {
    echo $response->status_code . "\n";
    echo $response->error_message;
}


$response = $qc1->DescribeEips(array('eips.1' => 'eip-xxxxxxxx', 'eips.2' => 'eip-yyyyyyyy'));
if ($response->result == true) {
    print_r($response->body);
} else {
    echo $response->status_code . "\n";
    echo $response->error_message;
}


$response = $qc1->StartInstances(array('instances.1' => 'i-xxxxxxxx'));
if ($response->result == true) {
    print_r($response->body);
} else {
    echo $response->status_code . "\n";
    echo $response->error_message;
}

# 注意下面的防火墙位于pek2区，所以第二个参数需要明确指定
$response = $qc1->DescribeSecurityGroups(array('security_groups.1' => 'sg-xxxxxxxx'), 'pek2');
if ($response->result == true) {
    print_r($response->body);
} else {
    echo $response->status_code . "\n";
    echo $response->error_message;
}
