<?php
require_once("fns.php");

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}

$date    = date('Y-m-d H:i:s', time());
$time    = time();
$expired = $time - 1800;

$_SESSION['expire'] = $time + 28800;

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != "") {
	// if logged in send to dashboard page
	if ($_SESSION['role'] == 'USER')
		redirect("dashboard.php");
	else
		redirect("dashboard.php?action=list");
}

$title = "Login";

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "login" && !empty($_POST['user']) && !empty($_POST['passwd'])) {

	$password = trim($_POST['passwd']);

	if (preg_match("/^[a-z]+$/", strtolower($_POST['user'])) == 1) {
		$username = trim($_POST['user']);
	} else {
		$_SESSION['messagetype'] = "danger";
		$_SESSION['message'] = "Please fill the form";

		redirect("index.php");
		exit();
	}

	if ($username == "" || $password == "") {

		$_SESSION['messagetype'] = "danger";
		$_SESSION['message'] = "Please fill the form";

	} else {
		$stmt = $db->prepare('select * from log where sesid= :sesid and ip= :ip and status=0 and time > :expired');
		$stmt->execute(['sesid' => session_id(), 'ip' => $ip, 'expired' => $expired]);
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$num = count($result);

		$sql = "select * from employees where username='$username'";

		$stmt = $db->prepare('select * from employees where username= :username');
		$stmt->execute([ 'username' => $username ]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		// If such user does not exists.
		if ( !$user ) {

			$stmt = $db->prepare('insert into log (date,user,ip,time,status,sesid,description) values (:date, :user, :ip, :time, :status, :sessid, :description )');
			$stmt->execute([ 'date' => $date, 'user'=>$username, 'ip' => $ip, 'time'=>$time, 'sessid' => session_id(),'status'=> 0, 'description' => "Problem, the user {$username} does not exists" ]);

			$_SESSION['messagetype'] = "danger";
			$_SESSION['message'] = "Username is not correct";

			redirect("index.php");

		} else {
			if ($user['status'] == 'Blocked') {
				include "header-login.php";
				$_SESSION['messagetype'] = "danger";
				$_SESSION['message'] = "You are blocked";
				redirect("index.php");
				exit();
			}

			$stmt = $db->prepare('select * from employees where username= :username and passwd= :password and status= :status');
			$stmt->execute([ 'username' => $username, 'password' => $password, 'status' => 'Active' ]);
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			// If the password is OK.
			if ($user) {
				session_regenerate_id();

				$stmt = $db->prepare('insert into log (date,user,ip,time,status,sesid,description) values (:date, :user, :ip, :time , :status, :sessid, :description )');
				$stmt->execute([ 'date' => $date, 'user'=>$username, 'ip' => $ip, 'time'=>$time, 'sessid' => session_id(), 'status'=> 1, 'description' => "Login successful" ]);

				$_SESSION['messagetype'] = "success";
				$_SESSION['message']     = "You are logged in!";

				include "header-login.php";

				echo "<h1>You are logged in!</h1>";

				// Create new session.
				$_SESSION['user_id'] = $user['employeeid'];
				$_SESSION['role']    = $user['role'];
				$_SESSION['user']    = $user['username'];

				redirect("dashboard.php");

			} else {
				$_SESSION['messagetype'] = "danger";
				$_SESSION['message']     = "Username or password is not correct";

				$stmt = $db->prepare('insert into log (date,user,ip,time,status,sesid,description) values (:date, :user, :ip, :time, :status, :sessid, :description )');
				$stmt->execute([ 'date' => $date, 'user'=>$username, 'ip' => $ip, 'time'=>$time, 'sessid' => session_id(),'status'=> 0, 'description' => "Problem, the password does not match the user" ]);

				redirect("index.php");
			}
		}
	}
	// redirect("index.php");
}
include "header-login.php";
?>
<div class="login_wrapper">
	<div class="animate form login_form">
		<section class="login_content">
			<form class="form-horizontal" method="post" action="">
				<input type="hidden" name="action" value="login">
				<h1>Login Form</h1>
				<div>
					<input type="text" class="form-control" placeholder="Username" name="user" required="" />
				</div>
				<div>
					<input type="password" class="form-control" placeholder="Password" name="passwd" required="" />
				</div>
				<div>
					<input type="submit" value="Log in" class="btn btn-default submit">
				</div>

				<div class="clearfix"></div>

				<div class="separator">

					<div>
						<h1><i class="fa fa-cog"></i> IoT Executor</h1>
						<p>©2022</p>
					</div>
				</div>
			</form>
		</section>
	</div>

	<?php include 'footer-login.php'; ?>
