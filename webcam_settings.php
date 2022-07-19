<?php
include "functions.php";
session_start();
recover_config();
check_session_variables();

if (isset($_GET["webcam"]) || $_SESSION["admin_level"] != 1) {
    header("location: webcam.php");
}

?>

<!DOCTYPE html>

<html>

<head>
    <title>Webcam Settings</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>Webcam settings</h1>
        <a href="webcam_settings.php?webcam=1"><button class="draw-border">Back</button></a>


        <?php
        $not_to_show = array("username", "admin_level", "config_loaded", "json_path", "img_path",
    "webcam_capture", "webcam_motion", "webcam_send_email");
        foreach ($_SESSION as $key => $value) {
            if (in_array($key, $not_to_show)) continue;
            echo "<div>\n";
            echo "<h3>$key</h3>";
            echo "<input class='draw-border' type='text' value='$value'>";
            echo "<button class='draw-border green' id='save'>Save</button>";
            echo "</div>";
        }
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
    var webcam_online = false;
    var admin_level = $("input[type='hidden']").attr("level");
    let encrypt = new JSEncrypt();
    encrypt.setPublicKey($("#pubkey").val());

    jQuery(document).ready(function() {
        $("#save").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            button = $(this);
            key = button.parent().find("h3").text()
            value = button.parent().find("input").val()

            button.text("Saving...");
            $.ajax({
                url: "jquery_handler",
                type: "post",
                data: {
                    name: encrypt.encrypt("change_settings"),
                    key: encrypt.encrypt(key),
                    value: encrypt.encrypt(value)
                },
                success: function(data) {
                    if (data.length > 0)
                        alert(data)
                    else {
                        button.text("Saved ;)")
                        setTimeout(function() {
                            button.text("Save");
                        }, 2500);
                    }
                }
            });
        });
    });
</script>

</html>