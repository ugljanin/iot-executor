<?php
require("fns.php");

use PhpMqtt\Client\MqttClient;

if (isset($_GET['mutationid']) && isset($_GET['nodeid'])) {
    $mutationid = $_GET['mutationid'];
    $chip_id = $_GET['nodeid'];

    $sql = "SELECT mutations.name, mutations.code,esp.chip_id,esp.id, mutations.mutationid, mutations_actions.actionid,
    mutations_actions.esp_id, mutations_actions.boot
        FROM mutations,mutations_actions,esp
        where mutations.mutationid=mutations_actions.mutationid
        and mutations_actions.esp_id=esp.id
        and esp.chip_id='".$chip_id."'
        and mutations_actions.mutationid='" . $mutationid . "'";

    $sth = $db->prepare($sql);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);

    $esp_id=$result['id'];
    $actionid=$result['actionid'];

    $fn = "uploads/" . $result['chip_id'] . "/boot.lua";
    $file = fopen($fn, "w");
    fwrite($file, $result['code']);
    fclose($file);

    //unselect all
    $sql = "UPDATE mutations_actions SET boot=0 where esp_id=$esp_id";
    $db->exec($sql);

    //select only the one from the form
    $sql = "UPDATE mutations_actions SET `boot` = '1' WHERE actionid = $actionid and  esp_id=$esp_id";
    $db->exec($sql);

    //force update
    $sql = "UPDATE esp set `update`=1 where id='" . $esp_id . "'";
    $db->exec($sql);

    //log mutation as initial
    $sql = "insert into mutations_log (espid, mutationid, mutation_date, type, status) values ('" . $esp_id . "', '" . $result['mutationid'] . "','" . date('Y-m-d H:i:s') . "','init','Pending')";
    $db->exec($sql);

    if ($sth->execute()) {
        echo '<p>Mutation action boot is changed</p>';

        $client->publish('/mutation/update', $result['chip_id'] , MqttClient::QOS_AT_MOST_ONCE);

        echo "Sent instruction for update\n";

        $client->disconnect();
        unset($client);
    } else {
        echo '<p>Mutation action boot is not changed</p>';
    }
}
