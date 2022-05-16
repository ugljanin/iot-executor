<?php
require("fns.php");

use PhpMqtt\Client\MqttClient;

<<<<<<< HEAD
if (isset($_POST['mutationid']) && isset($_POST['nodeid'])) {
    $mutationid = $_POST['mutationid'];
    $node_id    = $_POST['nodeid'];

    $sql = "SELECT mutations.name, mutations.code,devices.node_id,devices.id, mutations.mutationid, mutations_actions.actionid, mutations_actions.boot
        FROM mutations,mutations_actions,devices
        where mutations.mutationid=mutations_actions.mutationid
        and mutations_actions.deviceid=devices.id
=======
if (isset($_GET['mutationid']) && isset($_GET['nodeid'])) {
    $mutationid = $_GET['mutationid'];
    $node_id = $_GET['nodeid'];

    $sql = "SELECT mutations.name, mutations.code,devices.node_id,devices.id, mutations.mutationid, mutations_actions.actionid,
    mutations_actions.node_id, mutations_actions.boot
        FROM mutations,mutations_actions,devices
        where mutations.mutationid=mutations_actions.mutationid
        and mutations_actions.node_id=devices.id
>>>>>>> 7cce95b (Code refactoring)
        and devices.node_id='".$node_id."'
        and mutations_actions.mutationid='" . $mutationid . "'";

    $sth = $db->prepare($sql);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);

<<<<<<< HEAD
    $deviceid = $result['id'];
    $actionid = $result['actionid'];
=======
    $node_id=$result['id'];
    $actionid=$result['actionid'];
>>>>>>> 7cce95b (Code refactoring)

    $fn = "uploads/" . $result['node_id'] . "/boot.lua";
    $file = fopen($fn, "w");
    fwrite($file, $result['code']);
    fclose($file);

    //unselect all
<<<<<<< HEAD
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
=======
    $sql = "UPDATE mutations_actions SET boot=0 where node_id=$node_id";
    $db->exec($sql);

    //select only the one from the form
    $sql = "UPDATE mutations_actions SET `boot` = '1' WHERE actionid = $actionid and  node_id=$node_id";
    $db->exec($sql);

    //force update
    $sql = "UPDATE devices set `update`=1 where id='" . $node_id . "'";
    $db->exec($sql);

    //log mutation as initial
    $sql = "insert into mutations_log (node_id, mutationid, mutation_date, type, status) values ('" . $node_id . "', '" . $result['mutationid'] . "','" . date('Y-m-d H:i:s') . "','init','Pending')";
    $db->exec($sql);
>>>>>>> 7cce95b (Code refactoring)

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
