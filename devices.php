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
                echo "<a href='devices.php?action=add&newdevice=1' class='btn btn-primary pull-right' role='button'>Add new device</a>";
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
                    echo "<a href='devices.php?action=update&id=$devices[id]' class='btn-sm " . ($devices['update'] == 1 ? 'btn-dark' : 'btn-warning') ."' role='button'>";
                    if( $devices['update'] == 1 )
                        echo "Cancel update";
                    else
                        echo "Force update";
                    echo "</a>&nbsp;";
                    echo "<a href='devices.php?action=mutations&id=$devices[id]' class='btn-sm btn-success' role='button'>Codes</a>&nbsp;";
                    echo "<a href='devices.php?action=capabilities&id=$devices[id]' class='btn-sm btn-success' role='button'>Capabilities</a>";
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='?action=add&id=$devices[id]' class='btn-sm btn-info' role='button'>Edit</a>&nbsp;";
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

    if( isset( $_GET['id'] ) ) {
        $id = $_GET['id'];

        ?>
        <div class="container">
            <?php
            if ($_SESSION['role'] == 'engineer') {
                echo "<a href='devices.php?action=addmutation&id=$id' class='btn btn-primary pull-right' role='button'>Add additional reconfiguration code to the list</a>";
            }
            ?>
        <?php

        $stmt = $db->prepare('SELECT mutations.name, mutations.mutationid, mutations_actions.actionid, mutations_actions.boot
        FROM mutations,mutations_actions
        where mutations.mutationid=mutations_actions.mutationid
        and mutations_actions.deviceid = :deviceid
        ORDER BY actionid DESC');
        $stmt->execute(['deviceid' => $id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if( count( $result ) == 0 ) {
            echo '<h2>No reconfiguration code is assigned to the device, please assign it first</h2>';
            exit();
        }
         ?>
            <h4>Select reconfiguration script from the list below, in order to be booted on the selected device, or add additional one</h4>

            <form action="devices.php?action=saveboot" method="post">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php
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
    </div>
    <?php
    }
} else if ( $_GET['action'] == 'capabilities' ) {

    if( isset( $_GET['id'] ) ) {
        $id = $_GET['id'];

        ?>
        <div class="container">
            <?php
            if ($_SESSION['role'] == 'engineer') {
                echo "<a href='devices.php?action=addcapability&id=$id' class='btn btn-primary pull-right' role='button'>Add capability to list</a>";
            }
            ?>
        <?php

        $stmt = $db->prepare('SELECT capabilities.name, capabilities.capabilityid, device_capabilities.deviceid, device_capabilities.id
        FROM devices, capabilities, device_capabilities
        where device_capabilities.capabilityid = capabilities.capabilityid
        and device_capabilities.deviceid = devices.id
        and device_capabilities.deviceid = :deviceid
        ORDER BY capabilities.name DESC');
        $stmt->execute(['deviceid' => $id]);
        $assigned_capabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if( count( $assigned_capabilities ) == 0 ) {
            echo '<h2>No capabilities are added, please add them first</h2>';
            exit();
        }
         ?>
            <h4>List of assigned capabilities to this device.</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Capability ID</th>
                        <th>Name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($assigned_capabilities as $capabilities) {
                ?>
                    <tr>
                        <td width="100"><?php echo $capabilities['capabilityid']; ?></td>
                        <td><?php echo $capabilities['name']; ?></td>
                        <td><a href='devices.php?action=unassigncapability&id=<?php echo $capabilities['id'];?>' class='btn-sm btn-danger' role='button'>Delete</a></td>
                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    }
} else if ($_GET['action'] == 'addmutation' && $_SESSION['role'] == 'engineer' ) {
    if (isset($_GET['id'])) {

        $id = $_GET['id'];

        $stmt = $db->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $r_name    = $result['name'];
        $r_desc    = $result['description'];
        $r_node_id = $result['node_id'];
    }
?>
    <div class='container'>
        <form class='form-horizontal' action='devices.php?action=assignmutation' method='POST'>
            <input type='hidden' value='<?php echo $id; ?>' name='id' />
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
} else if ($_GET['action'] == 'addcapability' && $_SESSION['role'] == 'engineer' ) {
    if (isset($_GET['id'])) {

        $id = $_GET['id'];

        $stmt = $db->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $r_name    = $result['name'];
        $r_desc    = $result['description'];
        $r_node_id = $result['node_id'];
    }
?>
    <div class='container'>
        <form class='form-horizontal' action='devices.php?action=assigncapability' method='POST'>
            <input type='hidden' value='<?php echo $id; ?>' name='id' />
            <div class="form-group">
                <label for="text">Select capability to be added for selected device:</label>
                <select name="capabilityid" class="form-control">
                    <?php
                    $sqlc = "select * from capabilities order by name asc";
                    $sth = $db->prepare($sqlc);
                    $sth->execute();
                    while ($capability = $sth->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $capability['capabilityid'] . '">' . $capability['name'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <button id='submit' name='submit' class='btn btn-primary pull-right'>Add capability</button>
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

        if( $result ) {
            $r_folder   = $result['folder'];
            $r_filename = $result['filename'];
            if( file_exists("uploads/" . $r_folder . "/" . $r_filename) ) {
                $fn   = "uploads/" . $r_folder . "/" . $r_filename;
                $file = fopen($fn, "a+");
                $size = filesize($fn);
                $text = fread($file, $size);
                fclose($file);
            }
            ?>
            <!-- Textarea -->
            <div class='form-group'>
                <label class='col-md-2 control-label' for='node_id'>Code assigned to the device</label>
                <div class='col-md-10'>
                <textarea class='form-control' id='fileEditor' readonly name='fileEditor_data' style='min-width: 100%' rows='10'><?php echo $text;?></textarea>
                </div>
            </div>
            <?php
        }

    }
    ?>
    <button id='submit' name='submit' class='btn btn-danger pull-right'>Submit</button>
        </form>
    </div>
    <?php
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

    $result = $sth->fetch(PDO::FETCH_ASSOC);
    $deviceid=$result['id'];

    if ( !file_exists($path . $node_id) ) {
        mkdir( $path . $node_id, 0755, true );
        $myfile = fopen($path . $node_id . "/boot.lua", "w") or die("Unable to open file!");
        $txt = "print(\"IoT Executor\")";
        fwrite($myfile, $txt);
        fclose($myfile);
    }

    $sth = $db->prepare('INSERT INTO data ( folder, filename, deviceid ) VALUES ( :node_id, :filename, :deviceid )');
    $sth->execute([ 'node_id'=>$node_id, 'filename'=>'boot.lua', 'deviceid'=>$deviceid ]);

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
    $id       = $_POST['id'];

    $stmt = $db->prepare('SELECT mutations.name, mutations.code,devices.node_id, mutations.mutationid, mutations_actions.actionid,
    mutations_actions.boot
    FROM mutations,mutations_actions,devices
    where mutations.mutationid=mutations_actions.mutationid
    and mutations_actions.deviceid = devices.id
    and mutations_actions.actionid = :actionid
    ORDER BY actionid DESC');
    $stmt->execute([ 'actionid' => $actionid ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

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

    if ($stmt) {
        echo '<p>Reconfiguration action boot is changed</p>';

        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Reconfiguration action boot is changed";

        $client->publish('/mutation/update', $result['node_id'] , MqttClient::QOS_AT_MOST_ONCE);

        echo "Sent instruction for update \n";

        redirect('devices.php?action=list');
    } else {
        echo '<p>Reconfiguration action boot is not changed</p>';

        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Reconfiguration action boot is not changed";
        redirect('devices.php?action=list');
    }
} else if ($_GET['action'] == 'assignmutation') {
    $mutationid = $_POST['mutationid'];
    $id         = $_POST['id'];

    $stmt = $db->prepare('INSERT INTO mutations_actions (mutationid,deviceid) VALUES ( :mutationid, :deviceid)');
    $stmt->execute([ 'mutationid'=> $mutationid, 'deviceid'=>$id ]);

    if ($stmt) {
        echo '<p>Reconfiguration action added in database</p>';
        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Reconfiguration action added in database";
        redirect('devices.php?action=mutations&id='.$id);
    } else {
        echo '<p>Reconfiguration action not added in database</p>';
        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Reconfiguration action not added in database";
        redirect('devices.php?action=list');
    }
} else if ($_GET['action'] == 'assigncapability') {
    $capabilityid = $_POST['capabilityid'];
    $id           = $_POST['id'];

    $stmt = $db->prepare('INSERT INTO device_capabilities (capabilityid,deviceid) VALUES ( :capabilityid, :deviceid )');
    $stmt->execute([ 'capabilityid'=> $capabilityid, 'deviceid'=>$id ]);

    if ($stmt) {
        echo '<p>Capability assigned to the device</p>';
        $_SESSION["messagetype"] = "success";
        $_SESSION["message"] = "Capability assigned to the device";
        redirect('devices.php?action=capabilities&id='.$id);
    } else {
        echo '<p>Capability is not assigned to the device</p>';
        $_SESSION["messagetype"] = "danger";
        $_SESSION["message"] = "Capability is not assigned to the device";
        redirect('devices.php?action=list');
    }
} else if ($_GET['action'] == 'update') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        $stmt = $db->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->execute(['id' => $id]);

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

        $stmt = $db->prepare('UPDATE devices SET `update` = :val WHERE id = :deviceid');
        $stmt->execute(['val' => $val, 'deviceid'=>$id ]);

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
        $id = $_GET['id'];

        $stmt = $db->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $node_id = $result['node_id'];

        $stmt = $db->prepare("DELETE FROM devices WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $stmt = $db->prepare("DELETE FROM mutations_actions WHERE deviceid = :id");
        $stmt->execute(['id' => $id]);

        $stmt = $db->prepare("DELETE FROM data WHERE deviceid = :id");
        $stmt->execute(['id' => $id]);

        if ( $stmt ) {
            if(file_exists($path . $node_id . "/boot.lua")) {
                unlink($path . $node_id . "/boot.lua");
                rmdir($path . $node_id);
            }

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
} else if ($_GET['action'] == 'unassigncapability') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        $stmt = $db->prepare( "select * FROM device_capabilities WHERE id = :id " );
        $stmt->execute( [ 'id' => $id ] );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $deviceid    = $result['deviceid'];

        $stmt = $db->prepare( "delete FROM device_capabilities WHERE id = :id " );
        $stmt->execute( [ 'id' => $id ] );

        if ( $stmt ) {
            echo '<p>Capability unasssigned from the device</p>';
            $_SESSION["messagetype"] = "success";
            $_SESSION["message"] = "Capability unasssigned from the device";
            redirect('devices.php?action=capabilities&id='.$deviceid);
        } else {
            echo '<p>Capability is not unasssigned from the device</p>';
            $_SESSION["messagetype"] = "danger";
            $_SESSION["message"] = "Capability is not unasssigned from the device";
            redirect('devices.php?action=capabilities&id='.$deviceid);
        }
    }
}
include "footer.php";
?>
