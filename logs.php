<?php
session_start();
include "functions.php";
check_session_variables();

$false_logs = array();
$logs = array();
if (file_exists("files/false_login.json")) {
    $false_logs = get_json_content("false_login.json");
}

if (file_exists("files/login.json")) {
    $logs = get_json_content("login.json");
}

if (isset($_GET["home"])) {
    header("location: home.php");
}

?>
<!DOCTYPE html>

<html>

<head>
    <title>Logs</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <button class="draw-border red" id="purge_logs">Delete all</button>
        <a href="webcam.php?home=1"><button class="draw-border">Back</button></a>
        <div id="wrong_logs">
            <h2>Wrong login attempt</h2>
            <?php if (!empty($false_logs)) : ?>
                <table class="center">
                    <tr>
                        <th>Date</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>IP Address</th>
                        <th>Country</th>
                        <th>City</th>
                    </tr>
                    <?php
                    foreach ($false_logs as $log) {
                        echo "<tr>";
                        echo "<td>" . $log["date"] . "</td>";
                        echo "<td>" . $log["username"] . "</td>";
                        echo "<td>" . $log["password"] . "</td>";
                        echo "<td>" . $log["ip"] . "</td>";

                        if (!isset($log["country"])) echo "<td>-</td>";
                        else echo "<td>" . $log["country"] . "</td>";

                        if (!isset($log["city"])) echo "<td>-</td>";
                        else echo "<td>" . $log["city"] . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
            <?php else : ?>
                <h3 class='error'>No logs for the moment</h3>
            <?php endif ?>
        </div>
        <div id="good_logs">
            <h2>Correct login attempt</h2>
            <?php if (!empty($logs)) : ?>
                <table class="center">
                    <tr>
                        <th style="width: fit-content">Date</th>
                        <th>Username</th>
                        <th>IP Address</th>
                        <th>Country</th>
                        <th>City</th>
                    </tr>
                    <?php
                    foreach ($logs as $log) {
                        echo "<tr>";
                        echo "<td>" . $log["date"] . "</td>";
                        echo "<td>" . $log["username"] . "</td>";
                        echo "<td>" . $log["ip"] . "</td>";

                        if (!isset($log["country"])) echo "<td>-</td>";
                        else echo "<td>" . $log["country"] . "</td>";

                        if (!isset($log["city"])) echo "<td>-</td>";
                        else echo "<td>" . $log["city"] . "</td>";

                        echo "</tr>";
                    }
                    ?>
                </table>
            <?php else : ?>
                <h3 class='error'>No logs for the moment</h3>
            <?php endif ?>
        </div>
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

    jQuery(document).ready(function() {
        $("#purge_logs").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            if (confirm("Are you sure to delete all connection logs?")) {
                $.ajax({
                    url: "jquery_handler",
                    type: "post",
                    data: {
                        name: encrypt.encrypt("purge_login_logs")
                    },
                    success: function(data) {
                        if (data == "done") {
                            $("table").remove();
                            if ($("div#wrong_logs").find("h3").length == 0)
                                $("div#wrong_logs").append("<h3 class='error'>No logs for the moment</h3>");
                            if ($("div#good_logs").find("h3").length == 0)
                                $("div#good_logs").append("<h3 class='error'>No logs for the moment</h3>");
                        } else alert("Error trying to delete all")
                    }
                });
            }
        })
    });
</script>

</html>