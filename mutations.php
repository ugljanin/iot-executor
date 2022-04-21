<?php
include("fns.php");
check_user();

if (isset($_GET['action']) && $_GET['action'] == 'list') {

	$title = "List of created mutations";
	include "header.php";
	if ( $_SESSION['role'] == 'engineer' ) {
?>
		<a href="mutations.php?action=create" class="btn btn-primary" role="button">Create mutation</a>
	<?php
	}
	?>
	<div class="container">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Description</th>
					<th>Code</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$sql = "select * from mutations";
				$sth = $db->prepare($sql);
				$sth->execute();
				$result = $sth->fetchAll(PDO::FETCH_ASSOC);

				foreach ($result as $mutations) {
					echo "<tr>";
					echo "<td>{$mutations['mutationid']}</td>";
					echo "<td><strong>{$mutations['name']}</strong></td>";
					echo "<td>{$mutations['description']}</td>";
					echo "<td><a href=\"mutations.php?action=viewcode&id={$mutations['mutationid']}\" class=\"btn btn-sm btn-success\">View code</a>";
					echo "<a href=\"mutations.php?action=create&id={$mutations['mutationid']}\" class=\"btn btn-sm btn-primary\">Edit</a>";
					if( $_SESSION['role'] == 'engineer' ) {
						echo "<a href=\"mutations.php?action=delete&id={$mutations['mutationid']}\" class=\"btn btn-sm btn-danger\">Delete</a>";
					}
					echo "</td>";
					echo "</tr>";
				}
				?>
			</tbody>
		</table>
	</div>
<?php
}
if (isset($_GET['action']) && $_GET['action'] == 'viewcode') {

	$title = "View code for mutation";
	include "header.php";

	$id   = $_GET['id'];
	$stmt = $db->prepare('select * from mutations where mutationid= :mutationid');

	$stmt->execute(['mutationid' => $id]);

	$result = $stmt->fetch(PDO::FETCH_ASSOC);

	echo '<pre>';
	echo $result['code'];
	echo '</pre>';
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && $_SESSION['role'] == 'engineer') {
	// Delete mutation
	$title = "Detele mutation ";
	include "header.php";

	if( isset($_GET['id'])) {
		$id   = $_GET['id'];
		$stmt = $db->prepare("delete from mutations where mutationid = :id");
		$stmt->execute(['id' => $id]);
	}

	if ($stmt) {
		echo 'Mutation is deleted';

		$_SESSION["messagetype"] = "success";
		$_SESSION["message"]     = "Mutation is deleted";

		redirect('mutations.php?action=list');
	} else {
		echo 'Mutation is not deleted';

		$_SESSION["messagetype"] = "danger";
		$_SESSION["message"]     = "Mutation is not deleted";

		redirect('mutations.php?action=list');
	}
}
if (isset($_GET['action']) && $_GET['action'] == 'create') {
	$title = "Create mutation";

	if(isset($_GET['id'])) {
		$id = $_GET['id'];

		$stmt = $db->prepare('select * from mutations where mutationid = :id');
		$stmt->execute(['id' => $id]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
	}

	include "header.php";
?>
	<div class="row-flud">
		<form action="mutations.php?action=save" method="post">
			<input type="hidden" name="mutationid" value="<?php echo isset($id) ? $id : ''; ?>">
			<h2>Content of Mutation</h2>
			<div class="row">

				<div class='col-sm-12'>
					<div class="form-group">
						<label for="title">Name:</label>
						<div class="rule-value-container">
							<input class="form-control" type="text" name="name" value="<?php echo isset($result['name']) ? $result['name'] : ''; ?>">
						</div>
					</div>
				</div>
				<div class='col-sm-12'>
					<div class="form-group">
						<label for="title">Category:</label>
						<select name="category" class="form-control">
							<option value="traffic">Traffic light</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div>
				</div>
				<div class='col-sm-12'>
					<div class="form-group">
						<label for="title">Description:</label>
						<textarea name="description" class="form-control" id="description"><?php echo isset($result['description']) ? $result['description'] : ''; ?></textarea>
					</div>
				</div>
				<div class='col-sm-12'>
					<div class="form-group">
						<label for="code">Code:</label>
						<textarea name="code" class="form-control" id="code"><?php echo isset($result['code']) ? $result['code'] : ''; ?></textarea>
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
if (isset($_GET['action']) && $_GET['action'] == 'save') {
	$name        = $_POST['name'];
	$description = $_POST['description'];
	$code        = $_POST['code'];
	$category       = $_POST['category'];
	$date        = date('Y-m-d H:s:i', time());
	$id          = $_POST['mutationid'];
	$title       = "Saving in database";

	include "header.php";

	if ($id != '' || !empty($id)) {
		$stmt = $db->prepare('update mutations
						set description= :description,
						name= :name,
						category= :category,
						code= :code
						where mutationid= :id');
		$data = ['name' => $name, 'description' => $description, 'code' => $code, 'category' => $category, 'id' => $id];
	} else {
		$stmt = $db->prepare("insert into mutations (name,description,code,category) values ( :name, :description, :code, :category)");
		$data = ['name' => $name, 'description' => $description, 'code' => $code, 'category' => $category];
	}

	$stmt->execute($data);

	if ($stmt) {
		echo '<p>Mutation added in the database</p>';

		$_SESSION["messagetype"] = "success";
		$_SESSION["message"]     = "Mutation is created";

		redirect('mutations.php?action=list');
	} else {
		echo '<p>Mutation is not added in the database</p>';

		$_SESSION["messagetype"] = "danger";
		$_SESSION["message"]     = "Mutation is not created";

		redirect('mutations.php?action=list');
	}
}

include "footer.php"; ?>
