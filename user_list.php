<?php
include("functions.php");
session_start();
recover_config();
check_session_variables();

if (isset($_GET["home"])) {
    header("location: home.php");
}

if (isset($_GET["register"])) {
    header("location: register.php");
}

$users = get_json_content("users.json");
?>

<!DOCTYPE html>

<html>

<head>
    <title>User List</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>User List</h1>
        <button class="draw-border green" id="register">Add User</button>
        <a href="user_list.php?home=1"><button class="draw-border">Back</button></a>
        <table class="center">
            <tr>
                <th>Username</th>
                <th>Admin Level</th>
                <th style="width:fit-content">Creation Date</th>
                <th></th>
            </tr>
            <?php
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user["username"] . "</td>";
                echo "<td>" . ($user["admin_level"] == 0 ? "Observer" : "Overseer") . "</td>";
                echo "<td>" . $user["creation_date"] . "</td>";
                echo "<td><button class='draw-border red' id='delete_" . $user["username"] . "'>Delete</button></td>";
                echo "</tr>";
            }

            ?>
        </table>
    </div>
</body>
<input type="hidden" level=<?php echo $_SESSION["admin_level"] ?>>
<textarea class="hidden" id="pubkey">-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlsxrKzRVjSWTf3nS/Jeu
qGYgOhF/hHNBGArdW0CLcpI1vG07CHLFKfEHqdHSAqpOBFHiDptYtx7VVFdwfZ8B
bertmhihvvMNpzhMsye4wEmMJ7hR6szryaTLelz8jmvMwJDJHPRlbdqOYDB/0juu
k0cFI/S3QIylycw/ErQBTnkAGadQZcQDGpLcvxHS7Gk1akt00Atagh4gLm9RgEtD
Vi0CQ9R9xlGe8oWH45hJUn4Klp/0iB0WQ7ZWchxOQSC0CndKSLM3zCnt5vxP3fOw
S703WuYF7nYhtbNk1Xi2fJ6mtdN2Feg3bgQu7lfDAq0VwzYxvM2+BKWhy4MyqYgH
cwIDAQAB
-----END PUBLIC KEY-----</textarea>
<script src="jquery-3.6.0.min.js"></script>
<script src="jsencrypt.min.js"></script>
<script>
    var admin_level = $("input[type='hidden']").attr("level");
    let encrypt = new JSEncrypt();
    encrypt.setPublicKey($("#pubkey").val());

    function remove_first_occurrence(str, searchstr) {
        var index = str.indexOf(searchstr);
        if (index === -1) {
            return str;
        }
        return str.slice(0, index) + str.slice(index + searchstr.length);
    }

    jQuery(document).ready(function() {
        $("button").on("click", function() {
            button = $(this);
            if (button.attr("id").indexOf("delete_") === -1) return;
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }

            if (confirm("Are you sure that you want to delete that user?")) {
                username = remove_first_occurrence(button.attr("id"), "delete_");
                $.ajax({
                    url: "jquery_handler",
                    type: "post",
                    data: {
                        name: encrypt.encrypt("remove_user"),
                        username: encrypt.encrypt(username)
                    },
                    success: function(data) {
                        if (data == "done") button.parent().parent().remove();
                        else alert("Error trying to delete that user")
                    }
                });
            }
        });

        $("button#register").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            window.location.href = "user_list.php?register=1";
        });
    });
</script>

</html>