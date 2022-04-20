<?php
require("fns.php");
$chipid = $_GET['id'];

if (isset($_GET['list'])) {
    $sql = "SELECT * FROM esp WHERE chip_id='$chipid' and `update`=1";
    $sth = $db->prepare($sql);
    $sth->execute();
    $esp_id_fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $esp_id = $esp_id_fetch['id'];

	$sql = "SELECT * FROM data WHERE esp_id='$esp_id' ORDER BY `boot` DESC";
    $files = $db->query($sql);

    foreach ($files as $value) {
    	echo $value['filename']."\n";
    }

}

if (isset($_GET['update'])) {
	$sql = "SELECT * FROM esp WHERE chip_id='$chipid'";
    $sth = $db->prepare($sql);
    $sth->execute();
    $fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $result = $fetch['update'];

    if ($result == 1) {
        echo "UPDATE";
        $sql = "UPDATE `esp` SET `update`=0 WHERE chip_id=$chipid";
        $db->exec($sql);
    } else {echo "";}

    $sql = "UPDATE `esp` SET timestamp=now() WHERE chip_id=$chipid";
    $db->exec($sql);

    $sql1 = "SELECT * FROM mutations_log WHERE espid='$fetch[id]' order by emid desc limit 1";
    $sth1 = $db->prepare($sql1);
    $sth1->execute();
    $fetch1 = $sth1->fetch(PDO::FETCH_ASSOC);

    $sql = "UPDATE mutations_log SET status='Activated', mutation_date=now() WHERE emid='$fetch1[emid]'";
    $db->exec($sql);

    $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
    fwrite($myfile, $sql);


}

if (isset($_GET['alive'])) {
	$sql = "SELECT * FROM esp WHERE chip_id='$chipid'";
    $sth = $db->prepare($sql);
    $sth->execute();
    $fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $result = $fetch['status'];

    if ($result == 1) {
        echo "UPDATE";
        $sql = "UPDATE `esp` SET `status`='Available' WHERE chip_id=$chipid";
        $db->exec($sql);
    } else {echo "";}

    $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
    fwrite($myfile, $sql);
}

?>
