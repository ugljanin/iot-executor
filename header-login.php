<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>Login to IoT Executor</title>

    <!-- Bootstrap -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/assets/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="/assets/css/nprogress.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="/assets/css/animate.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="/assets/css/custom.min.css" rel="stylesheet">


    </head>
     <body class="login">


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
