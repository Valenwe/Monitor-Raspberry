<?php
include "functions.php";
session_start();
recover_config();
check_session_variables();

if (isset($_GET["home"])) {
    header("location: home.php");
}

if (isset($_GET["register_computer"])) {
    header("location: register_computer.php");
}

$computers = get_json_content("computers.json");

?>
<!DOCTYPE html>

<html>

<head>
    <title>Computers</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center" style="width: 75%">
        <h1>Computers</h1>
        <button class="draw-border green" id="register_computer">Add computer</button>
        <button class="draw-border" id="refresh">Refresh all</button>
        <a href="computers.php?home=1"><button class="btn draw-border">Back</button></a>

        <?php
        if (!empty($computers)) {
            echo "<table class='center'>
                    <tr>
                        <th>Name</th>
                        <th>IP address</th>
                        <th>Mac address</th>
                        <th>State</th>
                    </tr>";
            foreach ($computers as $computer) {
                echo "<tr>";
                echo "<td>" . $computer["name"] . "</td>";
                echo "<td id='ip'>" . $computer["ip"] . "</td>";
                echo "<td>" . $computer["mac"] . "</td>";
                echo "<td id='state'>?</td>";

                echo "<td><button class='draw-border green' id='wake_" . $computer["name"] . "'>Wake</button></td>";
                echo "<td><button class='draw-border red' id='shutdown_" . $computer["name"] . "'>Shutdown</button></td>";
                echo "<td><button class='draw-border' id='refresh_only'>Refresh</button></td>";
                echo "<td><button class='draw-border red' id='delete_" . $computer["name"] . "'>Delete</button></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else echo "<h2 class='error'>No computers registered for the moment</h2>";

        ?>

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

    function ping_unique_computer(state) {
        state.text("Pinging...");
        ip = state.parent().find("#ip").text();
        $.ajax({
            url: "jquery_handler",
            type: "post",
            async: false,
            data: {
                name: encrypt.encrypt("ping_computer"),
                ip: encrypt.encrypt(ip)
            },
            success: function(data) {
                if (data == "ON") state.text("ON");
                else if (data == "OFF") state.text("OFF");
            },
            error: function(data) {
                state.text("?");
            }
        });
    }

    function ping_computers() {
        $("td#state").each(function() {
            ping_unique_computer($(this));
        });
    }

    jQuery(document).ready(function() {
        $("button").on("click", function() {
            button = $(this);
            if (button.attr("id").indexOf("delete_") === -1 && button.attr("id").indexOf("wake_") === -1 &&
                button.attr("id").indexOf("shutdown_") === -1) return;
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }

            if (button.attr("id").indexOf("delete_") !== -1) {
                if (confirm("Are you sure that you want to delete that computer?")) {
                    name = remove_first_occurrence(button.attr("id"), "delete_");
                    $.ajax({
                        url: "jquery_handler",
                        type: "post",
                        data: {
                            name: encrypt.encrypt("remove_computer"),
                            computer_name: encrypt.encrypt(name)
                        },
                        success: function(data) {
                            if (data == "done") {
                                button.parent().parent().remove();
                                if ($("td").length == 0) {
                                    $("table").remove();
                                    $("div").append("<h2 class='error'>No computers registered for the moment</h2>")
                                }
                            } else alert("Error trying to delete that computer")
                        }
                    });
                }
            }

            if (button.attr("id").indexOf("wake_") !== -1) {
                if (confirm("Are you sure that you want to turn on that computer?")) {
                    name = remove_first_occurrence(button.attr("id"), "wake_");
                    $.ajax({
                        url: "jquery_handler",
                        type: "post",
                        data: {
                            name: encrypt.encrypt("wake_computer"),
                            computer_name: encrypt.encrypt(name)
                        },
                        success: function(data) {
                            if (data != "done") alert("Error trying to turn on that computer")
                        }
                    });
                }
            }

            if (button.attr("id").indexOf("shutdown_") !== -1) {
                inputs = "";
                while (inputs != null && inputs.length != 2) {
                    inputs = prompt("Enter <name>@<password> from your session\n(Separated by '@')");
                    if (inputs == null) return;
                    else inputs = inputs.split("@");
                }

                name = remove_first_occurrence(button.attr("id"), "shutdown_");

                $.ajax({
                    url: "jquery_handler",
                    type: "post",
                    data: {
                        name: encrypt.encrypt("shutdown_computer"),
                        computer_name: encrypt.encrypt(name),
                        session_name: encrypt.encrypt(inputs[0]),
                        session_password: encrypt.encrypt(inputs[1])
                    },
                    success: function(data) {
                        alert(data)
                        if (data != "done") alert("Error trying to shutdown that computer")
                    }
                });

            }

        });

        $("button#register_computer").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            window.location.href = "computers.php?register_computer=1";
        });

        $("button#refresh").on("click", function() {
            ping_computers();
        });

        $("button#refresh_only").on("click", function() {
            ping_unique_computer($(this).parent().parent().find("#state"));
        });
    });
</script>

</html>