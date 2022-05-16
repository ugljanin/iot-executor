<?php
require("fns.php");
check_user();

$title = "Devices";
include "header.php";

use PhpMqtt\Client\MqttClient;

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
                    <th>node_id</th>
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

                $sql = "SELECT * FROM devices ORDER BY status asc";
                $data = $db->query($sql);

                foreach ($data as $devices) {
                    $upd = $devices['update'] == 1 ? "Yes" : "No";
                    echo "<tr>";
                    echo "<td>" . $devices['name'] . "</td>";
                    echo "<td>" . $devices['node_id'] . "</td>";
                    echo "<td>" . $devices['description'] . "</td>";
                    echo "<td>" . $upd . "</td>";
                    echo "<td>" . $devices['timestamp'] . "</td>";
                    echo "<td>" . $devices['status'] . "</td>";
                    echo "<td>";
                    echo "<a href='?action=add&id=$devices[id]' class='btn-sm btn-info' role='button'>Edit</a>&nbsp;";
                    echo "<a href='devices.php?action=update&id=$devices[id]' class='btn-sm " . ($devices['update'] == 1 ? 'btn-dark' : 'btn-warning') ."' role='button'>";
                    if( $devices['update'] == 1 )
                        echo "Cancel update";
                    else
                        echo "Force update";
                    echo "</a>&nbsp;";
                    echo "<a href='devices.php?action=mutations&id=$devices[id]' class='btn-sm btn-success' role='button'>Assigned reconfigurations</a>";
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='devices.php?action=delete&id=$devices[id]' class='btn-sm btn-danger' role='button'>Delete</a>";
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
        <h4>Select mutation script from the list below, in order to be booted on the selected device.
            <?php
            if ($_SESSION['role'] == 'engineer') {
                echo "<a href='devices.php?action=addmutation&id=$id' class='btn btn-primary pull-right' role='button'>Add additional mutation to list</a>";
            }
            ?>
        </h4>
        <form action="devices.php?action=saveboot" method="post">
            <input type="hidden" name="node_id" value="<?php echo $id; ?>">
            <?php
            $stmt = $db->prepare('SELECT mutations.name, mutations.mutationid, mutations_actions.actionid, mutations_actions.boot
            FROM mutations,mutations_actions
            where mutations.mutationid=mutations_actions.mutationid
            and mutations_actions.node_id = :node_id
            ORDER BY actionid DESC');

            $stmt->execute(['node_id' => $id]);

        if( count( $result ) == 0 ) {
            echo '<h2>No mutation code is assigned to the device, please assign it first</h2>';
            exit();
        }
         ?>
            <h4>Select reconfiguration script from the list below, in order to be booted on the selected device, or add additional one</h4>

            foreach ($result as $devices) {

                if ($devices['boot'] == 1) {
                    $checked = "checked";
                } else {
                    $checked = "";
                }
            ?>
                <div class="radio">
                    <label><input type="radio" name="actionid" <?php echo $checked; ?> value="<?php echo $devices['actionid']; ?>"><?php echo $devices['name']; ?></label>
                </div>
            <?php
            }
            echo "<input type=\"submit\" class='btn btn-success pull-right' role='button' value='Save'>";
            ?>
        </form>
    </div>
    <?php
    }
} else if ($_GET['action'] == 'addmutation' && $_SESSION['role'] == 'engineer' ) {
    if (isset($_GET['id'])) {

        $node_id = $_GET['id'];

        $stmt = $db->prepare("SELECT * FROM devices WHERE id= :id");
        $stmt->execute(['id' => $node_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $r_name   = $result['name'];
        $r_desc   = $result['description'];
        $r_node_id = $result['node_id'];
    }
?>
    <div class='container'>
        <form class='form-horizontal' action='devices.php?action=savemutation' method='POST'>
            <input type='hidden' value='<?php echo $node_id; ?>' name='node_id' />
            <div class="form-group">
                <label for="text">Select reconfiguration:</label>
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
} else if ($_GET['action'] == 'add') {
    if (isset($_GET['id'])) {

        $id = $_GET['id'];

        $stmt = $db->prepare( 'SELECT * FROM devices WHERE id = :id' );
        $stmt->execute( [ 'id' => $id ] );

        $result = $stmt->fetch( PDO::FETCH_ASSOC );

        $r_name    = $result['name'];
        $r_desc    = $result['description'];
        $r_node_id = $result['node_id'];
    }
    ?>
    <div class='container'>
        <form class='form-horizontal' action='devices.php?action=save' method='POST'>
            <?php
            if ( isset( $id ) )
                echo "<input type='hidden' value=$id name='id' />";
            ?>
            <div class='form-group'>
                <label class='col-md-2 control-label' for='Node name'>Node name</label>
                <div class='col-md-10'>
                    <input id='name' name='name' type='text' placeholder='Enter a node name' class='form-control input-md' value='<?php echo isset($r_name) ? $r_name : ''; ?>'>

                </div>
            </div>
            <div class='form-group'>
                <label class='col-md-2 control-label' for='description'>Node description</label>
                <div class='col-md-10'>
                    <textarea class='form-control' id='desc' name='desc' placeholder='Enter a description'><?php echo isset( $r_desc ) ? $r_desc : ''; ?></textarea>
                </div>
            </div>
            <div class='form-group'>
                <label class='col-md-2 control-label' for='node_id'>node_id</label>
                <div class='col-md-10'>
                    <input id='node_id' name='node_id' type='text' placeholder='Place the node_id of the devices node' class='form-control input-md' value='<?php echo isset( $r_node_id ) ? $r_node_id : ''; ?>' <?php echo isset( $_GET['id'] ) ? 'readonly' : ''; ?>>
                </div>
            </div>
    <?php
    if ( isset( $_GET['id'] ) ) {

        $stmt = $db->prepare( 'SELECT * FROM data WHERE deviceid = :deviceid' );
        $stmt->execute( [ 'deviceid' => $id ] );
        $result = $stmt->fetch( PDO::FETCH_ASSOC );

        $r_folder   = $result['folder'];
        $r_filename = $result['filename'];

        $fn   = "uploads/" . $r_folder . "/" . $r_filename;
        $file = fopen($fn, "a+");
        $size = filesize($fn);
        $text = fread($file, $size);
        fclose($file);
        ?>
        <!-- Textarea -->
        <div class='form-group'>
            <label class='col-md-2 control-label' for='node_id'>Code assigned to the device</label>
            <div class='col-md-10'>
            <textarea class='form-control' id='fileEditor' readonly name='fileEditor_data' style='min-width: 100%' rows='10'><?php echo $text;?></textarea>
            </div>
        </div>

        <button id='submit' name='submit' class='btn btn-danger pull-right'>Submit</button>
        </form>
    </div>
    <?php
    }
} else if ($_GET['action'] == 'save') {

    $node_id   = $_POST['node_id'];
    $node_desc = $_POST['desc'];
    $node_name = $_POST['name'];

    if (isset($_POST['id'])) {
        $id = $_POST['id'];

        $stmt = $db->prepare('UPDATE devices SET `name` = :node_name, `description` = :node_desc, `node_id` = :node_id WHERE id = :deviceid');
        $stmt->execute(['node_name' => $node_name, 'node_desc'=> $node_desc, 'node_id'=>$node_id, 'deviceid'=>$id ]);

    } else {
        $stmt = $db->prepare('INSERT INTO devices (name, description, node_id) VALUES ( :node_name, :node_desc, :node_id)');
        $stmt->execute(['node_name' => $node_name, 'node_desc'=> $node_desc, 'node_id'=>$node_id ]);
    }

    $sql = "SELECT * FROM devices WHERE node_id = :node_id";
    $sth = $db->prepare($sql);
    $sth->execute([ 'node_id'=>$node_id ]);

    $node_id_fetch = $sth->fetch(PDO::FETCH_ASSOC);
    $node_id = $node_id_fetch['id'];

    if ( !file_exists($path . $node_id) ) {
        mkdir( $path . $node_id, 0755, true );
        $myfile = fopen($path . $node_id . "/boot.lua", "w") or die("Unable to open file!");
        $txt = "print(\"IoT Executor\")";
        fwrite($myfile, $txt);
        fclose($myfile);
    }

    $sql = "INSERT INTO data (folder,filename,node_id) VALUES ('$node_id','boot.lua','$node_id')";
    $db->exec($sql);

    if ( $sth ) {
        echo '<p>Device added in database</p>';

        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Device is created";
        redirect('devices.php?action=list');
    } else {
        echo '<p>Device not added in database</p>';

        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Device is not created";
        redirect('devices.php?action=list');
    }
} else if ($_GET['action'] == 'saveboot') {
    $actionid = $_POST['actionid'];
    $node_id = $_POST['node_id'];

    $sql = "SELECT mutations.name, mutations.code,devices.node_id, mutations.mutationid, mutations_actions.actionid,mutations_actions.node_id, mutations_actions.boot
    FROM mutations,mutations_actions,devices
    where mutations.mutationid=mutations_actions.mutationid
    and mutations_actions.node_id=devices.id
    and mutations_actions.actionid='" . $actionid . "'
    ORDER BY actionid DESC";
    $sth = $db->prepare($sql);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);

    $fn = "uploads/" . $result['node_id'] . "/boot.lua";
    $file = fopen($fn, "w");
    fwrite($file, $result['code']);
    fclose($file);

    //unselect all
    $sql = "UPDATE mutations_actions SET boot=0 where node_id=$node_id";
    $db->exec($sql);

    //select only the one from the form
    $stmt = $db->prepare('UPDATE mutations_actions SET `boot` = 1 where actionid = :actionid');
    $stmt->execute([ 'actionid'=>$actionid ]);

    //force update
    $sql = "UPDATE devices set `update`=1 where id='" . $node_id . "'";
    $db->exec($sql);

    //log mutation as initial
    $sql = "insert into mutations_log (node_id, mutationid, mutation_date, type, status) values ('" . $node_id . "', '" . $result['mutationid'] . "','" . date('Y-m-d H:i:s') . "','init','Pending')";
    $db->exec($sql);

    if ($stmt) {
        echo '<p>Mutation action boot is changed</p>';

        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Mutation action boot is changed";

        $client->publish('/mutation/update', $result['node_id'] , MqttClient::QOS_AT_MOST_ONCE);

        echo "Sent instruction for update \n";

        redirect('devices.php?action=mutations&id=' . $node_id);
    } else {
        echo '<p>Mutation action boot is not changed</p>';

        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Mutation action boot is not changed";
        redirect('devices.php?action=mutations&id=' . $node_id);
    }
} else if ($_GET['action'] == 'savemutation') {
    $mutationid = $_POST['mutationid'];
    $node_id    = $_POST['node_id'];

    $stmt = $db->prepare('INSERT INTO mutations_actions (mutationid,node_id) VALUES ( :mutationid, :node_id)');
    $stmt->execute([ 'mutationid'=> $mutationid, 'node_id'=>$node_id ]);

    if ($stmt) {
        echo '<p>Mutation action added in database</p>';
        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Mutation action added in database";
        redirect('devices.php?action=mutations&id='.$id);
    } else {
        echo '<p>Mutation action not added in database</p>';
        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Mutation action not added in database";
        redirect('devices.php?action=list');
    }
} else if ($_GET['action'] == 'update') {
    if (isset($_GET['id'])) {
        $node_id = $_GET['id'];

        $stmt = $db->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->execute(['id' => $node_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['update'] == 1) {
            $val = 0;
            $message = "off";
        } else {
            $val = 1;
            $message = "on";

            $client->publish('/mutation/update', $result['node_id'] , MqttClient::QOS_AT_MOST_ONCE);

            echo "Sent instruction for update\n";
        }

        $sql = "UPDATE devices SET `update`='$val' WHERE id=$node_id";
        $db->exec($sql);

        if ( $stmt ) {
            echo "<p>Device update is {$message}</p>";

            $_SESSION["messagetype"] = "success";
            $_SESSION["message"] = "Device update is $message";
            redirect('devices.php?action=list');
        } else {
            echo '<p>Device update problem</p>';

            $_SESSION["messagetype"] = "danger";
            $_SESSION["message"] = "Device update problem";
            redirect('devices.php?action=list');
        }
    }
} else if ($_GET['action'] == 'delete') {
    if (isset($_GET['id'])) {
        $node_id = $_GET['id'];

        $stmt = $db->prepare("SELECT * FROM devices WHERE id= :id");
        $stmt->execute(['id' => $node_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $node_id = $result['node_id'];

        $stmt = $db->prepare("DELETE FROM devices WHERE id = :id");
        $stmt->execute(['id' => $node_id]);

        if ($stmt->execute()) {
            unlink($path . $node_id . "/boot.lua");
            rmdir($path . $node_id);
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
}
include "footer.php";
?>
