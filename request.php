<?php
require("fns.php");

use PhpMqtt\Client\MqttClient;

if (isset($_GET['mutationid']) && isset($_GET['nodeid'])) {
    $mutationid = $_GET['mutationid'];
    $node_id    = $_GET['nodeid'];

    $sql = "SELECT mutations.name, mutations.code,devices.node_id,devices.id, mutations.mutationid, mutations_actions.actionid, mutations_actions.boot
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
    $stmt->execute([ 'deviceid'=>$id ]);

    //select only the one from the form
    $stmt = $db->prepare('UPDATE mutations_actions SET `boot` = 1 where actionid = :actionid');
    $stmt->execute([ 'actionid'=>$actionid ]);

    //force update
    $stmt = $db->prepare('UPDATE devices SET `update`=1 where id = :deviceid');
    $stmt->execute([ 'deviceid'=>$id ]);

    //log mutation as initial
    $stmt = $db->prepare('insert into mutations_log (deviceid, mutationid, date, type, status) values ( :deviceid, :mutationid, :date, :init, :status )');
    $stmt->execute([ 'deviceid'=>$id, 'mutationid'=>$result['mutationid'], 'date'=> date('Y-m-d H:i:s'), 'init'=>'init', 'status' => 'Pending']);

    if ( $stmt ) {
        echo '<p>Mutation action boot is changed</p>';

        $client->publish('/mutation/update', $result['node_id'] , MqttClient::QOS_AT_MOST_ONCE);

        echo "Sent instruction for update\n";

        $client->disconnect();
        unset($client);
    } else {
        echo '<p>Mutation action boot is not changed</p>';
    }
}
?>
