<?php
require("fns.php");
$node_id = $_GET['id'];

if (isset($_GET['list'])) {
    $sql = "SELECT * FROM devices WHERE node_id='$node_id' and `update`=1";
    $sth = $db->prepare($sql);
    $sth->execute();
    $node_id_fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $node_id = $node_id_fetch['id'];

	$sql = "SELECT * FROM data WHERE node_id='$node_id' ORDER BY `boot` DESC";
    $files = $db->query($sql);

    foreach ($files as $value) {
    	echo $value['filename']."\n";
    }

}

if (isset($_GET['update'])) {
	$sql = "SELECT * FROM devices WHERE node_id='$node_id'";
    $sth = $db->prepare($sql);
    $sth->execute();
    $fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $result = $fetch['update'];

    if ($result == 1) {
        echo "UPDATE";
        $sql = "UPDATE `devices` SET `update`=0 WHERE node_id=$node_id";
        $db->exec($sql);
    } else {echo "";}

    $sql = "UPDATE `devices` SET timestamp=now() WHERE node_id=$node_id";
    $db->exec($sql);

    $sql1 = "SELECT * FROM mutations_log WHERE node_id='$fetch[id]' order by emid desc limit 1";
    $sth1 = $db->prepare($sql1);
    $sth1->execute();
    $fetch1 = $sth1->fetch(PDO::FETCH_ASSOC);

    $sql = "UPDATE mutations_log SET status='Activated', date=now() WHERE emid='$fetch1[emid]'";
    $db->exec($sql);

    $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
    fwrite($myfile, $sql);


}

if (isset($_GET['alive'])) {
	$sql = "SELECT * FROM devices WHERE node_id='$node_id'";
    $sth = $db->prepare($sql);
    $sth->execute();
    $fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $result = $fetch['status'];

    if ($result == 1) {
        echo "UPDATE";
        $sql = "UPDATE `devices` SET `status`='Available' WHERE node_id=$node_id";
        $db->exec($sql);
    } else {echo "";}

    $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
    fwrite($myfile, $sql);
}

?>
