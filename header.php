<?php
$filename=basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
	<head>

		<!-- start: Meta -->
		<meta charset="utf-8">
		<title><?php echo $title;?></title>


   <!-- Bootstrap -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/assets/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="/assets/css/nprogress.css" rel="stylesheet">

    <!-- Custom styling plus plugins -->
    <link href="/assets/css/custom.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="/js/jquery.min.js"></script>
</head>

<body>

    <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">
          <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">
              <a href="index.php" class="site_title"><span>IoT Executor</span></a>
            </div>

            <div class="clearfix"></div>

            <!-- menu profile quick info -->
            <div class="profile clearfix">
              <div class="profile_pic">
                <img src="img/manager.png" alt="..." class="img-circle profile_img">
              </div>
              <div class="profile_info">
                <span>Welcome,</span>
                <h2><?php echo $_SESSION['user'];?></h2>
              </div>
            </div>
            <!-- /menu profile quick info -->
            <br />
            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <h3>General</h3>
                <ul class="nav side-menu">
                  <li <?php if($filename=='devices.php' ) echo ' class="active"';?>>
                      <a href="devices.php?action=list"><i class="fa fa-fw fa-cog"></i> Devices</a>
                  </li>
                  <li <?php if($filename=='mutations.php' ) echo ' class="active"';?>>
                      <a href="mutations.php?action=list"><i class="fa fa-exchange"></i> Mutations</a>
                  </li>
                </ul>
              </div>
            </div>
            <!-- /sidebar menu -->
          </div>
        </div>

        <!-- top navigation -->
        <div class="top_nav">
          <div class="nav_menu">
            <nav>
              <div class="nav toggle">
                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
              </div>
              <?php
              if( isset($_SESSION['user']) ) {
                ?>
                <ul class="nav navbar-nav navbar-right">
                  <li class="">
                    <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                      <img src="assets/img/manager.png" alt=""><?php echo $_SESSION['user'];?>
                      <span class=" fa fa-angle-down"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-usermenu pull-right">

                      <li><a href="logout.php"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                    </ul>
                  </li>
                </ul>
                <?php
              }
              ?>
            </nav>
          </div>
        </div>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3><?php global $title; echo $title;?></h3>
              </div>
            </div>

            <div class="clearfix"></div>

            <div class="row">
              <div class="col-md-12">
                <div class="x_panel">
                  <div class="x_title">
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

                  <?php
                  if (isset($ERROR_MSG) && isset($ERROR_TYPE) && $ERROR_MSG <> "") {
                      ?>
                      <div class="fadeInUp animated nicescroll alert alert-dismissable alert-<?php echo $ERROR_TYPE ?>">
                          <button data-dismiss="alert" class="close" type="button">x</button>
                          <p><?php echo $ERROR_MSG; ?></p>
                      </div>
                      <?php
                  }
                  ?>








