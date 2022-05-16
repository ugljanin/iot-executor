<?php
require("fns.php");
header("Content-Type:application/json");
$data = json_decode(file_get_contents('php://input'), true);

use PhpMqtt\Client\MqttClient;

if (isset($data['mutationid']) && isset($data['nodeid'])) {
    $mutationid = $data['mutationid'];
    $node_id    = $data['nodeid'];

    $sql = "SELECT mutations.name, mutations.code,devices.node_id,devices.id, mutations.mutationid, mutations_actions.actionid,
        mutations_actions.boot
        FROM mutations,mutations_actions,devices
        where mutations.mutationid=mutations_actions.mutationid
        and mutations_actions.deviceid=devices.id
        and devices.node_id='".$node_id."'
        and mutations_actions.mutationid='" . $mutationid . "'";

    $sth = $db->prepare($sql);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);

    $deviceid = $result['id'];
    $actionid = $result['actionid'];

    $fn = "uploads/" . $result['node_id'] . "/boot.lua";
    $file = fopen($fn, "w");
    fwrite($file, $result['code']);
    fclose($file);

    //unselect all
    $stmt = $db->prepare('UPDATE mutations_actions SET boot=0 where deviceid = :deviceid');
    $stmt->execute([ 'deviceid'=>$deviceid ]);

    //select only the one from the form
    $stmt = $db->prepare('UPDATE mutations_actions SET `boot` = 1 where actionid = :actionid');
    $stmt->execute([ 'actionid'=>$actionid ]);

    //force update
    $stmt = $db->prepare('UPDATE devices SET `update`=1 where id = :deviceid');
    $stmt->execute([ 'deviceid'=>$deviceid ]);

    //log mutation as initial
    $stmt = $db->prepare('insert into mutations_log (deviceid, mutationid, date, type, status) values ( :deviceid, :mutationid, :date, :init, :status )');
    $stmt->execute([ 'deviceid'=>$deviceid, 'mutationid'=>$result['mutationid'], 'date'=> date('Y-m-d H:i:s'), 'init'=>'init', 'status' => 'Pending']);

    if ( $stmt ) {
        $client->publish('/mutation/update', $result['node_id'] , MqttClient::QOS_AT_MOST_ONCE);
        response(200, "Sent instruction for update on node ".$result['node_id']);
        $client->disconnect();
        unset($client);
    } else {
        response(400, "Mutation action boot is not changed");
    }
}


function response($status, $status_message)
{
    header("HTTP/1.1 " . $status);

    $response['status'] = $status;
    $response['status_message'] = $status_message;

    $json_response = json_encode($response);
    echo $json_response;
}
?>
