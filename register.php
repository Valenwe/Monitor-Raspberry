<?php
include("functions.php");
recover_config();

$first_user = at_least_one_user();
if ($first_user) {
    session_start();
    check_session_variables();

    // si pas autorisé à créer un user
    if (isset($_GET["user_list"]) || $_SESSION["admin_level"] == 0) {
        header("location: user_list.php");
    }
}

$errors = array();
$success = array();
if (isset($_POST["register"])) {
    if (empty($_POST["username"]) || empty($_POST["password"]) || empty($_POST["confirm_password"]) || empty($_POST["admin_level"])) {
        array_push($errors, "You have to fill all empty boxes");
    } else {
        if (get_user($_POST["username"]) != null) {
            array_push($errors, "The username already exists");
        }

        if (strlen($_POST["username"]) != strlen(get_valid_str($_POST["username"]))) {
            array_push($errors, "The username must not contain any space");
        }

        if (strlen($_POST["username"]) > 10) {
            array_push($errors, "The username has to be less than 10 characters long");
        }

        if ($_POST["password"] != $_POST["confirm_password"]) {
            array_push($errors, "The two passwords are not the same");
        }

        if (count($errors) == 0) {
            add_user($_POST["username"], $_POST["password"], $_POST["admin_level"], $first_user);
            array_push($success, "User successfully created");
        }
    }
}

?>

<!DOCTYPE html>

<html>

<head>
    <title>Register</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>Register</h1>

        <?php if ($first_user) : ?>
            <a href="register.php?user_list=1"><button class="btn draw-border">Back</button></a>
        <?php endif ?>

        <form method="post">
            <input class="draw-border" required type="text" placeholder="Username" name="username"> <br>
            <input class="draw-border" required type="password" placeholder="Password" name="password"> <br>
            <input class="draw-border" required type="password" placeholder="Confirm password" name="confirm_password"> <br>
            <label>Admin level</label>
            <select required name="admin_level">
                <?php if ($first_user) : ?>
                    <option value="level_observer">Observer</option>
                <?php endif ?>

                <option value="level_overseer">Overseer</option>
            </select> <br>
            <?php include("popup.php"); ?>
            <button class="draw-border green" name="register">Create</button>
        </form>
    </div>
</body>

</html>