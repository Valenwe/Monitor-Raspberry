<?php
include("functions.php");
session_start();
recover_config();
check_session_variables();

if (isset($_GET["logout"])) {
    session_destroy();
    header("location: login.php");
} else if (isset($_GET["webcam"])) {
    header("location: webcam.php");
} else if (isset($_GET["logs"])) {
    header("location: logs.php");
} else if (isset($_GET["user_list"])) {
    header("location: user_list.php");
} else if (isset($_GET["computers"])) {
    header("location: computers.php");
}

?>
<!DOCTYPE html>

<html>

<head>
    <title>Home</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>Home</h1>
        <p>Connected as
            <span style='color:yellow'><?php echo $_SESSION["username"] ?></span>
        </p>
        <p>Admin level
            <span style='color:yellow'><?php echo ($_SESSION["admin_level"] == 0 ? "Observer" : "Overseer") ?></span>
        </p>
        <a href="home.php?webcam=1"><button class="draw-border violet">Webcam</button></a>
        <a href="home.php?logs=1"><button class="draw-border orange">Logs</button></a>
        <a href="home.php?user_list=1"><button class="draw-border yellow">User List</button></a>
        <a href="home.php?computers=1"><button class="draw-border blue">Computers</button></a>
        <a href="home.php?logout=1"><button class="draw-border red">Log out</button></a>
    </div>
</body>

</html>