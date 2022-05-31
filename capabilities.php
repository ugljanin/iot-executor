<?php
include("fns.php");
check_user();

if ( $_GET['action'] == 'list' ) {
    $title="List of created capabilities";
    include "header.php";
    if($_SESSION['role']=='engineer')
    {
        ?>
        <a href="mutations.php?action=create" class="btn btn-primary" role="button">Create capability</a>
        <?php
    }
        ?>
    <div class="container">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $db->prepare('select * from capabilities');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ( $result as $capabilities ) {
            echo '<tr>';
            echo '<td><strong>'.$capabilities['name'].'</strong></td>';
            echo '<td>'.$capabilities['description'].'</td>';
            echo '<td>';
            echo '<a href="capabilities.php?action=create&id='.$capabilities['capabilityid'].'" class="btn btn-sm btn-primary">Edit</a>';
            echo '<a href="capabilities.php?action=delete&id='.$capabilities['capabilityid'].'" class="btn btn-sm btn-danger" role="button">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
            ?>
        </tbody>
    </table>
    </div>
	<?php
}
else if ( $_GET['action']=='delete' && $_SESSION['role']=='engineer' ) {
	$title="Detele capability ";
	include "header.php";

    $id = $_GET['id'];
	echo 'Capability is deleted';

    $stmt = $db->prepare('delete from capabilities where capabilityid= :capabilityid');
	$stmt->execute(['capabilityid' => $id]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare('delete from device_capabilities where capabilityid= :capabilityid');
	$stmt->execute(['capabilityid' => $id]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);

	if ( $result ) {
		$_SESSION["messagetype"] = "success";
		$_SESSION["message"] = "Capability is deleted";
		redirect('capabilities.php?action=list');
	} else {
		echo 'Capability is not deleted';
		$_SESSION["messagetype"] = "danger";
		$_SESSION["message"] = "Capability is not deleted";
		redirect('capabilities.php?action=list');
	}
}
else if ( $_GET['action'] == 'create' ) {
	$title="Create Capability";
    $id = $_GET['id'];
    $stmt = $db->prepare('select * from capabilities where capabilityid = :capabilityid');
	$stmt->execute(['capabilityid' => $id]);
	$capability = $stmt->fetch(PDO::FETCH_ASSOC);
	include "header.php";
	?>
	<div class="row-flud">
		<form action="capabilities.php?action=save" method="post">
		<input type="hidden" name="capabilityid" value="<?php echo $id;?>">
		<h2>Content of Capability</h2>
			<div class="row">
				<div class='col-sm-12'>
					<div class="form-group">
						<label for="title">Name:</label>
                        <div class="rule-value-container">
                            <input class="form-control" type="text" name="name" value="<?php echo $capability['name'];?>">
                        </div>
					</div>
				</div>
				<div class='col-sm-12'>
					<div class="form-group">
						<label for="title">Description:</label>
						<textarea name="description" class="form-control" id="description"><?php echo $capability['description'];?></textarea>
					</div>
				</div>
				<div class='col-sm-6'>
					<button class="btn btn-success btn-lg">Save</button>
				</div>
			</div>
		</form>
		</div>
<?php
}
else if ( $_GET['action'] == 'save' ) {
	$title="Saving in database";
	include "header.php";

	$name = $_POST['name'];
	$description = $_POST['description'];
    $date=date('Y-m-d H:s:i', time());
    $capabilityid = $_POST['capabilityid'];

	if ( $capabilityid != ''|| !empty( $capabilityid ) ) {
        $stmt = $db->prepare('UPDATE capabilities SET description = :description, name = :name where capabilityid = :capabilityid');
        $stmt->execute([ 'description'=>$description, 'name'=>$name, 'capabilityid'=>$capabilityid ]);
	} else {
		$stmt = $db->prepare("insert into capabilities (name,description) values ( :name, :description )");
        $stmt->execute([ 'description'=>$description, 'name'=>$name ]);
	}

	if($stmt) {
		echo '<p>Capability added in database</p>';

		$_SESSION["messagetype"] = "success";
		$_SESSION["message"] = "Capability is created";
		// redirect('capabilities.php?action=list');
	}
}
include "footer.php";
?>
