<?php
session_start();
include("functions.php");
check_session_variables();

// si pas autorisé à créer un user
if (isset($_GET["computers"]) || $_SESSION["admin_level"] == 0) {
    header("location: computers.php");
}

$errors = array();
$success = array();
if (isset($_POST["register_computer"])) {
    if (empty($_POST["name"]) || empty($_POST["mac"]) || empty($_POST["ip"])) {
        array_push($errors, "You have to fill all empty boxes");
    }

    if (strlen($_POST["name"]) != strlen(get_valid_str($_POST["name"]))) {
        array_push($errors, "The name must not contain any space");
    }

    if (strlen($_POST["name"]) > 10) {
        array_push($errors, "The name has to be less than 10 characters long");
    }

    if (!filter_var($_POST["ip"], FILTER_VALIDATE_IP)) {
        array_push($errors, $_POST["ip"] . " is not a valid IP address");
    }

    $_POST["mac"] = get_valid_str($_POST["mac"]);
    if (strlen($_POST["mac"]) != 17 || substr_count($_POST["mac"], ":", 0) != 5) {
        array_push($errors, $_POST["mac"] . " is not a valid mac address");
    }

    if (get_computer("name", $_POST["name"]) != null || get_computer("mac", $_POST["mac"]) != null || get_computer("ip", $_POST["ip"]) != null) {
        array_push($errors, "The computer already exists");
    }

    if (count($errors) == 0) {
        add_computer($_POST["name"], $_POST["mac"], $_POST["ip"]);
        array_push($success, "Computer added successfully");
    }
}
?>

<!DOCTYPE html>

<html>

<head>
    <title>Add Computer</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>Add Computer</h1>
        <a href="register_computer.php?computers=1"><button class="draw-border">Back</button></a>
        <form method="post">
            <input class="draw-border" required type="text" placeholder="Name" name="name"> <br>
            <input class="draw-border" required type="text" placeholder="MAC address (AA:BB:CC:00:11:22)" name="mac"> <br>
            <input class="draw-border" required type="text" placeholder="IP address" name="ip"> <br>
            <?php include("popup.php"); ?>
            <button class="draw-border green" name="register_computer">Add</button>
        </form>
    </div>
</body>

</html>