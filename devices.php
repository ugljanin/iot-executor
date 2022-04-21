<?php
require("fns.php");
check_user();

$title = "Devices";
include "header.php";

use PhpMqtt\Client\MqttClient;

// $valid_formats = array("lua", "txt", "mono", "html", "htm");
// $max_file_size = 1024 * 100; //100 kb
$path = "uploads/"; // Upload directory
$count = 0;

if ($_GET['action'] == 'list') {
?>
    <div class="container">
        <h4>List of managed devices
            <?php
            if ($_SESSION['role'] == 'engineer') {
                echo "<a href='devices.php?action=add&newdevice=1' class='btn btn-danger pull-right' role='button'>Add new device</a>";
            }
            ?>
        </h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ChipID</th>
                    <th>Description</th>
                    <th>Force update</th>
                    <th>Latest change</th>
                    <th>Availability</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php

                $sql = "SELECT * FROM esp ORDER BY status asc";
                $data = $db->query($sql);

                foreach ($data as $esp) {
                    $upd = $esp['update'] == 1 ? "Yes" : "No";
                    echo "<tr>";
                    echo "<td>" . $esp['name'] . "</td>";
                    echo "<td>" . $esp['chip_id'] . "</td>";
                    echo "<td>" . $esp['description'] . "</td>";
                    echo "<td>" . $upd . "</td>";
                    echo "<td>" . $esp['timestamp'] . "</td>";
                    echo "<td>" . $esp['status'] . "</td>";
                    echo "<td>";
                    echo "<a href='?action=add&id=$esp[id]' class='btn-sm btn-info' role='button'>Edit</a>&nbsp;";
                    echo "<a href='devices.php?action=update&id=$esp[id]' class='btn-sm " . ($esp['update'] == 1 ? 'btn-dark' : 'btn-warning') ."' role='button'>";
                    if( $esp['update'] == 1 )
                        echo "Cancel update";
                    else
                        echo "Force update";
                    echo "</a>&nbsp;";
                    echo "<a href='devices.php?action=mutations&id=$esp[id]' class='btn-sm btn-success' role='button'>Mutations list</a>";
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='devices.php?action=delete&id=$esp[id]' class='btn-sm btn-danger' role='button'>Delete</a>";
                    echo "</td>";
                    echo "<tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
<?php
}
if ( $_GET['action'] == 'mutations' ) {
    $id = $_GET['id'];
?>
    <div class="container">
        <h4>Select mutation script from the list below, to be booted on the selected device.
            <?php
            if ($_SESSION['role'] == 'engineer') {
                echo "<a href='devices.php?action=addmutation&id=$id' class='btn btn-primary pull-right' role='button'>Add additional mutation to list</a>";
            }
            ?>
        </h4>
        <form action="devices.php?action=saveboot" method="post">
            <input type="hidden" name="esp_id" value="<?php echo $id; ?>">
            <?php
            $stmt = $db->prepare('SELECT mutations.name, mutations.mutationid, mutations_actions.actionid, mutations_actions.boot
            FROM mutations,mutations_actions
            where mutations.mutationid=mutations_actions.mutationid
            and mutations_actions.esp_id = :espid
            ORDER BY actionid DESC');

            $stmt->execute(['espid' => $id]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $esp) {

                if ($esp['boot'] == 1) {
                    $checked = "checked";
                } else {
                    $checked = "";
                }
            ?>
                <div class="radio">
                    <label><input type="radio" name="actionid" <?php echo $checked; ?> value="<?php echo $esp['actionid']; ?>"><?php echo $esp['name']; ?></label>
                </div>
            <?php
            }
            echo "<input type=\"submit\" class='btn btn-success pull-right' role='button' value='Save'>";
            ?>
        </form>
    </div>
<?php
} else if ($_GET['action'] == 'addmutation' && $_SESSION['role'] == 'engineer' ) {
    if (isset($_GET['id'])) {

        $nodeId = $_GET['id'];

        $sql  = "SELECT * FROM esp WHERE id= :id";
        $stmt = $db->prepare($sql);

        $stmt->execute(['id' => $nodeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $r_name   = $result['name'];
        $r_desc   = $result['description'];
        $r_chipid = $result['chip_id'];
    }
?>
    <div class='container'>
        <form class='form-horizontal' action='devices.php?action=savemutation' method='POST'>
            <input type='hidden' value='<?php echo $nodeId; ?>' name='esp_id' />
            <?php
            if (isset($_GET['actionid']))
                echo "<input type='hidden' value=$_GET[actionid] name='edit' />";
            ?>

            <div class="form-group">
                <label for="text">Select mutation:</label>
                <select name="mutationid" class="form-control">
                    <?php

                    $sqlc = "select * from mutations order by name asc";
                    $sth = $db->prepare($sqlc);
                    $sth->execute();

                    while ($mutation = $sth->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $mutation['mutationid'] . '">' . $mutation['name'] . '</option>';
                    }
                    ?>
                </select>
            </div>

            <button id='submit' name='submit' class='btn btn-danger pull-right'>Submit</button>


        </form>
    </div>

    <?php
    if (isset($_GET['editor'])) {

        $fileId = $_GET['editor'];

        $sql = "SELECT * FROM data WHERE id=$fileId";
        $sth = $db->prepare($sql);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);

        $r_folder   = $result['folder'];
        $r_filename = $result['filename'];
        $r_espid    = $result['esp_id'];

        $fn = "uploads/" . $r_folder . "/" . $r_filename;
        $file = fopen($fn, "a+");
        $size = filesize($fn);
        $text = fread($file, $size);
        fclose($file);


        echo " <div class='container'>
      <br /> <br />
        <form class='form-horizontal' action='?files=$r_espid' method='POST' enctype='multipart/form-data'>
        <h4>Online file editor - $r_filename
        <button id='submit' name='submit' class='btn btn-danger pull-right'>Modify</button></h4><hr>

                <!-- Textarea -->
                <div class='form-group'>
                  <div class='col-md-12'>
                    <textarea class='form-control' id='fileEditor' name='fileEditor_data' style='min-width: 100%' rows='10'>$text</textarea>
                  </div>
                </div>";

        echo "<input type='hidden' value=$fileId name='fileEditor_id' />";
        echo "

            </form>
    </div>";
    }
} else if ($_GET['action'] == 'add') {
    if (isset($_GET['id'])) {

        $nodeIdUpd = $_GET['id'];

        $sql = "SELECT * FROM esp WHERE id=$nodeIdUpd";
        $sth = $db->prepare($sql);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);

        $r_name   = $result['name'];
        $r_desc   = $result['description'];
        $r_chipid = $result['chip_id'];
    }
    ?>
    <div class='container'>
        <form class='form-horizontal' action='devices.php?action=save' method='POST'>
            <?php
            if (isset($nodeIdUpd))
                echo "<input type='hidden' value=$nodeIdUpd name='id' />";
            ?>
            <div class='form-group'>
                <label class='col-md-4 control-label' for='Node name'>Node name</label>
                <div class='col-md-4'>
                    <input id='Nname' name='Nname' type='text' placeholder='Enter a node name' class='form-control input-md' value='<?php echo isset($r_name) ? $r_name : ''; ?>'>

                </div>
            </div>
            <div class='form-group'>
                <label class='col-md-4 control-label' for='description'>Node description</label>
                <div class='col-md-4'>
                    <textarea class='form-control' id='Ndesc' name='Ndesc' placeholder='Enter a description'><?php echo isset($r_desc) ? $r_desc : ''; ?></textarea>
                </div>
            </div>
            <div class='form-group'>
                <label class='col-md-4 control-label' for='chipid'>ChipID</label>
                <div class='col-md-4'>
                    <input id='chipid' name='chipid' type='text' placeholder='Place the ChipID of the esp node' class='form-control input-md' value='<?php echo isset($r_chipid) ? $r_chipid : ''; ?>' <?php if (isset($_GET['id'])) echo 'readonly'; ?>>

                </div>
            </div>
            <button id='submit' name='submit' class='btn btn-danger pull-right'>Submit</button>
        </form>
    </div>

    <?php
    if (isset($_GET['editor'])) {

        $fileId = $_GET['editor'];

        $sql = "SELECT * FROM data WHERE id=$fileId";
        $sth = $db->prepare($sql);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);

        $r_folder   = $result['folder'];
        $r_filename = $result['filename'];
        $r_espid    = $result['esp_id'];

        $fn   = "uploads/" . $r_folder . "/" . $r_filename;
        $file = fopen($fn, "a+");
        $size = filesize($fn);
        $text = fread($file, $size);

        fclose($file);


        echo " <div class='container'>
      <br /> <br />
        <form class='form-horizontal' action='?files=$r_espid' method='POST' enctype='multipart/form-data'>
        <h4>Online file editor - $r_filename
        <button id='submit' name='submit' class='btn btn-danger pull-right'>Modify</button></h4><hr>

                <!-- Textarea -->
                <div class='form-group'>
                  <div class='col-md-12'>
                    <textarea class='form-control' id='fileEditor' name='fileEditor_data' style='min-width: 100%' rows='10'>$text</textarea>
                  </div>
                </div>";

        echo "<input type='hidden' value=$fileId name='fileEditor_id' />";
        echo "

            </form>
    </div>";
    }
} else if ($_GET['action'] == 'save') {
    $chipid   = $_POST['chipid'];
    $nodeDesc = $_POST['Ndesc'];
    $nodeName = $_POST['Nname'];

    if (isset($_POST['id'])) {
        $nodeId = $_POST['id'];

        $stmt = $db->prepare('UPDATE esp SET `name` = :nodeName, `description` = :nodeDesc, `chip_id` = :chipid WHERE id = :nodeId');
        $stmt->execute(['nodeName' => $nodeName, 'nodeDesc'=> $nodeDesc, 'chipid'=>$chipid, 'nodeId'=>$nodeId ]);

    } else {
        $stmt = $db->prepare('INSERT INTO esp (name, description, chip_id) VALUES ( :nodeName, :nodeDesc, :chipid)');
        $stmt->execute(['nodeName' => $nodeName, 'nodeDesc'=> $nodeDesc, 'chipid'=>$chipid ]);
    }

    $sql = "SELECT * FROM esp WHERE chip_id = :chipid";
    $sth = $db->prepare($sql);
    $sth->execute([ 'chipid'=>$chipid ]);

    $esp_id_fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $esp_id = $esp_id_fetch['id'];

    if (!file_exists($path . $chipid)) {
        mkdir($path . $chipid, 0755, true);
        $myfile = fopen($path . $chipid . "/boot.lua", "w") or die("Unable to open file!");
        $txt = "print(\"Hello world\")";
        fwrite($myfile, $txt);
        fclose($myfile);
    }

    $sql = "INSERT INTO data (folder,filename,esp_id) VALUES ('$chipid','boot.lua','$esp_id')";
    $db->exec($sql);

    if ($sth->execute()) {
        echo '<p>Device added in database</p>';

        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Device is created";
        redirect('devices.php?action=list');
    } else {
        echo '<p>Device not added in database</p>';

        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Device is not created";
        // redirect('devices.php?action=list');
    }
} else if ($_GET['action'] == 'saveboot') {
    $actionid = $_POST['actionid'];
    $esp_id = $_POST['esp_id'];

    $sql = "SELECT mutations.name, mutations.code,esp.chip_id, mutations.mutationid, mutations_actions.actionid,mutations_actions.esp_id, mutations_actions.boot
    FROM mutations,mutations_actions,esp
    where mutations.mutationid=mutations_actions.mutationid
    and mutations_actions.esp_id=esp.id
    and mutations_actions.actionid='" . $actionid . "'
    ORDER BY actionid DESC";
    $sth = $db->prepare($sql);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);

    $fn = "uploads/" . $result['chip_id'] . "/boot.lua";
    $file = fopen($fn, "w");
    fwrite($file, $result['code']);
    fclose($file);

    //unselect all
    $sql = "UPDATE mutations_actions SET boot=0 where esp_id=$esp_id";
    $db->exec($sql);

    //select only the one from the form
    $sql = "UPDATE mutations_actions SET `boot` = '1' WHERE actionid = $actionid";
    $db->exec($sql);

    //force update
    $sql = "UPDATE esp set `update`=1 where id='" . $esp_id . "'";
    $db->exec($sql);

    //log mutation as initial
    $sql = "insert into mutations_log (espid, mutationid, mutation_date, type, status) values ('" . $esp_id . "', '" . $result['mutationid'] . "','" . date('Y-m-d H:i:s') . "','init','Pending')";
    $db->exec($sql);

    if ($sth->execute()) {
        echo '<p>Mutation action boot is changed</p>';

        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Mutation action boot is changed";

        $client->publish('/mutation/update', $result['chip_id'] , MqttClient::QOS_AT_MOST_ONCE);

        echo "Sent instruction for update \n";

        redirect('devices.php?action=mutations&id=' . $esp_id);
    } else {
        echo '<p>Mutation action boot is not changed</p>';

        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Mutation action boot is not changed";
        redirect('devices.php?action=mutations&id=' . $esp_id);
    }
} else if ($_GET['action'] == 'savemutation') {
    $mutationid = $_POST['mutationid'];
    $actionid   = $_POST['actionid'];
    $esp_id     = $_POST['esp_id'];

    if (isset($_POST['edit'])) {
        $sql = "UPDATE mutations_actions SET `mutationid` = '$mutationid', `esp_id` = '$esp_id' WHERE actionid = $actionid";
    } else {
        $sql = "INSERT INTO mutations_actions (mutationid,esp_id) VALUES ('$mutationid','$esp_id')";
    }
    if ($db->exec($sql)) {
        echo '<p>Mutation action added in database</p>';

        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Mutation action added in database";
        redirect('devices.php?action=list');
    } else {
        echo '<p>Mutation action not added in database</p>';

        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Mutation action not added in database";
        redirect('devices.php?action=list');
    }
} else if ($_GET['action'] == 'update') {
    if (isset($_GET['id'])) {
        $nodeIdUpd = $_GET['id'];
        $sql = "SELECT * FROM esp WHERE id=$nodeIdUpd";
        $sth = $db->prepare($sql);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);

        if ($result['update'] == 1) {
            $val = 0;
            $message = "off";
        } else {
            $val = 1;
            $message = "on";

            $client->publish('/mutation/update', $result['chip_id'] , MqttClient::QOS_AT_MOST_ONCE);

            echo "Sent instruction for update\n";

        }

        $sql = "UPDATE esp SET `update`='$val' WHERE id=$nodeIdUpd";
        $db->exec($sql);

        if ($sth->execute()) {
            echo "<p>Device update is {$message}</p>";

            $_SESSION["messagetype"] = "success";
            $_SESSION["message"] = "Device update is $message";
            redirect('devices.php?action=list');
        } else {
            echo '<p>Device update problem</p>';

            $_SESSION["messagetype"] = "danger";
            $_SESSION["message"] = "Device update problem";
            // redirect('devices.php?action=list');
        }
    }
} else if ($_GET['action'] == 'delete') {
    if (isset($_GET['id'])) {
        $nodeId = $_GET['id'];

        $sql = "SELECT * FROM esp WHERE id=$nodeId";
        $sth = $db->prepare($sql);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $chip_id = $result['chip_id'];

        $sql = "DELETE FROM esp WHERE id=$nodeId";
        $sth = $db->prepare($sql);
        $sth->execute();

        if ($sth->execute()) {
            unlink($path . $chip_id . "/boot.lua");
            rmdir($path . $chip_id);
            echo '<p>Device is deleted</p>';

            $_SESSION["messagetype"] = "success";
            $_SESSION["message"] = "Device is deleted";
            redirect('devices.php?action=list');
        } else {
            echo '<p>Device is not deleted</p>';

            $_SESSION["messagetype"] = "danger";
            $_SESSION["message"] = "Device is not deleted";
            redirect('devices.php?action=list');
        }
    }
} else if ($_GET['action'] == 'files') {
    ?>
    <div class="container">
        <?php
        if (isset($_GET['files'])) {

            $fileId = $_GET['files'];

            if (isset($_GET['boot'])) {
                $file_id = $_GET['boot'];

                $sql = "SELECT * FROM data WHERE esp_id=$fileId";
                $files = $db->query($sql);
                foreach ($files as $value) {
                    if ($file_id == $value['id']) {
                        $sql = "UPDATE data SET `boot`=1 WHERE id=`".$value['id']."`";
                        $db->exec($sql);
                    } else {
                        $sql = "UPDATE data SET `boot`=0 WHERE id=`".$value['id']."`";
                        $db->exec($sql);
                    }
                }
            }

            if (isset($_GET['delfile'])) {
                $file_del = $_GET['delfile'];

                $sql = "DELETE FROM data WHERE id=$file_del";
                $db->exec($sql);
            }
        ?>
            <br /><br />
            <h4>List of files to be downloaded by esp
                <form id='form' role='form' action='' method='POST' class='navbar-form navbar-right' enctype='multipart/form-data'>

                    <div class='input-group'>
                        <span class='input-group-btn'>
                            <span class='btn btn-primary btn-file'>
                                Browse&hellip;
                                <input id='file' type='file' name='files[]' multiple />
                            </span>
                        </span>
                        <input type='text' class='form-control' readonly>
                    </div>

                    <div class='form-group'>
                        <input type='hidden' value=$fileId name='esp_id' />
                        <button type='submit' class='btn btn-success'>Upload</button>
                    </div>

                </form>

            </h4>
            <hr>
            <table class='table table-hover'>
                <thead>
                    <tr>
                        <th>File ID</th>
                        <th>Filename</th>
                        <th>Folder</th>
                        <th>Boot flag</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    $sql = "SELECT * FROM data WHERE esp_id='$fileId'";
                    $files = $db->query($sql);
                    //$file = $files->fetch(PDO::FETCH_ASSOC);

                    foreach ($files as $value) {
                        echo "<tr>";
                        echo "<th scope='row'>".$value['id']."</th>";
                        echo "<td>".$value['filename']."</td>";
                        echo "<td>".$value['folder']."</td>";
                        echo "<td>".$value['boot']."</td>";
                        echo "<td><a href='?files=".$value['esp_id']."&delfile=".$value['id']."' class='btn-sm btn-danger' role='button'>Delete</a>&nbsp;<a href='?files=$value[esp_id]&boot=$value[id]' class='btn-sm btn-success' role='button'>Boot Flag</a>&nbsp;<a href='?editor=$value[id]' class='btn-sm btn-primary' role='button'>Edit</a></td>";
                        echo "<tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php
        }
        ?>
    </div>
<?php
}


include "footer.php";
?>
