<?php
include "functions.php";
session_start();
recover_config();

if (!at_least_one_user()) {
    header("location: register.php");
}

if(isset($_SESSION["username"])) {
    header("location: home.php");
}

$errors = array();
if (isset($_POST["login"])) {
    if (empty($_POST["username"]) || empty($_POST["password"])) {
        array_push($errors, "You have to enter a username or password.");
    } else {
        $_POST["username"] = get_valid_str($_POST["username"]);
        $user = get_user($_POST["username"]);
        if ($user == null) {
            array_push($errors, "Unknown username");
        } else {
            if (!password_verify($_POST["password"], $user["password"])) {
                array_push($errors, "Wrong password");
            }
        }

        // register the login attempt anyway
        if (count($errors) > 0) {
            register_connection("false_login.json", True);
        } else {
            register_connection("login.json");
            attribute_session_variables();
            header("location: home.php");
        }
    }
}

?>

<!DOCTYPE html>

<html>

<head>
    <title>Login</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>Connection</h1>
        <form method="post">
            <input class="draw-border" type="text" required placeholder="Username" name="username"> <br>
            <input class="draw-border" type="password" required placeholder="Password" name="password"> <br>
            <?php include("popup.php"); ?>
            <button class= "draw-border green" name="login">Login</button>
        </form>
    </div>

</body>

</html>