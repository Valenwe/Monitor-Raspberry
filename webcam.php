<?php
include "functions.php";
session_start();
recover_config();
check_session_variables();

if (isset($_GET["home"])) {
    header("location: home.php");
}

if (isset($_GET["webcam_logs"])) {
    header("location: webcam_logs.php");
}

if (isset($_GET["webcam_settings"])) {
    header("location: webcam_settings.php");
}

?>

<!DOCTYPE html>

<html>

<head>
    <title>Webcam Feed</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>Webcam feed</h1>
        <button class="draw-border <?php echo ($_SESSION["webcam_capture"] == 0 ? "red" : "green") ?>" id="webcam_capture">Capture <?php echo ($_SESSION["webcam_capture"] == 0 ? "OFF" : "ON") ?></button>
        <button class="draw-border <?php echo ($_SESSION["webcam_motion"] == 0 ? "red" : "green") ?>" id="webcam_motion">Motion Capture <?php echo ($_SESSION["webcam_motion"] == 0 ? "OFF" : "ON") ?></button>
        <button class="draw-border <?php echo ($_SESSION["webcam_send_email"] == 0 ? "red" : "green") ?>" id="webcam_send_email">Alarm <?php echo ($_SESSION["webcam_send_email"] == 0 ? "OFF" : "ON") ?></button>
        <?php if ($_SESSION["admin_level"] == 1) : ?>
            <a href="webcam.php?webcam_settings=1"><button class="draw-border yellow">Settings</button></a>
        <?php endif ?>
        <a href="webcam.php?webcam_logs=1"><button class="draw-border yellow">Detections</button></a>
        <!-- <button class="draw-border yellow" id="toggle_script">Toggle script</button> -->
        <a href="webcam.php?home=1"><button class="draw-border">Back</button></a>
        <img id="feed" class="hidden" src="imgs/feed.jpg">
        <h2 class='error'>Webcam offline</h2>
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
    var webcam_online = false;
    var admin_level = $("input[type='hidden']").attr("level");
    let encrypt = new JSEncrypt();
    encrypt.setPublicKey($("#pubkey").val());

    function webcam_set_offline() {
        $("#feed").addClass("hidden");
        if ($("h2.error").length == 0) {
            $("div").append("<h2 class='error'>Webcam offline</h2>");
        }
    }

    function webcam_set_online() {
        $("#feed").removeClass("hidden");
        $("h2.error").remove();
    }

    function refresh_feed() {
        $.ajax({
            url: "jquery_handler",
            type: "post",
            data: {
                name: encrypt.encrypt("ping_webcam")
            },
            success: function(data) {
                if (data == "ON") {
                    webcam_online = true;
                } else if (data == "OFF") {
                    webcam_online = false;
                }
            }
        });

        if (!webcam_online) webcam_set_offline();
        else {
            $("#feed").attr("src", "imgs/feed.jpg?" + new Date().getTime());
        }

    }

    jQuery(document).ready(function() {
        $("#feed").on("load", function() {
            webcam_set_online();
        });

        $("#feed").on("error", function() {
            webcam_set_offline();
        });

        $("#webcam_motion").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            button = $(this);
            $.ajax({
                url: "jquery_handler",
                type: "post",
                data: {
                    name: encrypt.encrypt("toggle_motion")
                },
                success: function(data) {
                    button.text("Motion Capture " + data);
                    if (data == "ON") {
                        button.removeClass("red");
                        button.addClass("green");
                    } else if (data == "OFF") {
                        button.removeClass("green");
                        button.addClass("red");
                    }
                }
            });
        });

        $("#webcam_capture").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            button = $(this);
            $.ajax({
                url: "jquery_handler",
                type: "post",
                data: {
                    name: encrypt.encrypt("toggle_capture")
                },
                success: function(data) {
                    button.text("Capture " + data);
                    if (data == "ON") {
                        button.removeClass("red");
                        button.addClass("green");
                    } else if (data == "OFF") {
                        button.removeClass("green");
                        button.addClass("red");
                    }
                }
            });
        });

        $("#webcam_send_email").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            button = $(this);
            $.ajax({
                url: "jquery_handler",
                type: "post",
                data: {
                    name: encrypt.encrypt("toggle_send_email")
                },
                success: function(data) {
                    button.text("Alarm " + data);
                    if (data == "ON") {
                        button.removeClass("red");
                        button.addClass("green");
                    } else if (data == "OFF") {
                        button.removeClass("green");
                        button.addClass("red");
                    }
                }
            });
        });

        /*
        $("#toggle_script").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            $.ajax({
                url: "jquery_handler",
                type: "post",
                data: {
                    name: encrypt.encrypt("ping_webcam"),
                    name_2: encrypt.encrypt("toggle_script")
                },
                success: function(data) {
                    alert(data);
                }
            });
        });*/
    });

    setInterval(refresh_feed, 1000);
</script>

</html>