<?php
include "functions.php";
session_start();
recover_config();
check_session_variables();

if (isset($_GET["webcam"])) {
    header("location: webcam.php");
}

//$images = scandir(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $_SESSION["img_path"]);
//array_pop($images);

$images = glob(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $_SESSION["img_path"] . "/*");
$images = array_filter($images, function ($file) {
    return is_file($file) && strpos($file, "feed.jpg") === false;
});

usort($images, function ($a, $b) {
    $file1 = explode("_", $a)[1] . "_" . explode("_", $a)[2];
    $str_date1 = substr($file1, 4, 2) . "/" . substr($file1, 6, 2) . "/" . substr($file1, 0, 4) . " " .
        substr($file1, 9, 2) . ":" . substr($file1, 11, 2) . ":" . substr($file1, 13, 2);

    $file2 = explode("_", $b)[1] . "_" . explode("_", $b)[2];
    $str_date2 = substr($file2, 4, 2) . "/" . substr($file2, 6, 2) . "/" . substr($file2, 0, 4) . " " .
        substr($file2, 9, 2) . ":" . substr($file2, 11, 2) . ":" . substr($file2, 13, 2);

    $date1 = strtotime($str_date1);
    $date2 = strtotime($str_date2);

    if ($date1 > $date2) return 1;
    if ($date1 < $date2) return -1;
    return 0;
});
?>

<!DOCTYPE html>

<html>

<head>
    <title>Webcam Logs</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center" style="width:80%">
        <h1>Webcam logs</h1>
        <a href="webcam.php?webcam=1"><button class="draw-border">Back</button></a>
        <button class="draw-border red" id="purge_logs">Delete all</button>
        <?php
        echo_images($images);
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

    jQuery(document).ready(function() {
        $("#purge_logs").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            if (confirm("Are you sure to delete all detection images?")) {
                $.ajax({
                    url: "jquery_handler",
                    type: "post",
                    data: {
                        name: encrypt.encrypt("purge_webcam_logs")
                    },
                    success: function(data) {
                        if (data == "done") {
                            if ($("details").length > 0) {
                                $("details").remove();
                                $("div").append("<h2 class='error'>No detections for the moment</h2>");
                            }
                        } else alert("Error trying to delete all")
                    }
                });
            }
        });

        $("details").on("click", function() {
            $("html, body").animate({
                scrollTop: $(this).offset().top
            }, 500);
        });

        $("button#view_log").on("click", function() {
            let id = $(this).parent().parent().attr("id");
            window.location.href = "video.php?id=" + id;
        });

        $("button#delete_log").on("click", function() {
            if (admin_level == 0) {
                alert("You do not have the authorization!");
                return;
            }
            if (confirm("Are you sure to delete this log?")) {
                let button = $(this);
                let id = $(this).parent().parent().attr("id");
                $.ajax({
                    url: "jquery_handler",
                    type: "post",
                    data: {
                        name: encrypt.encrypt("delete_log"),
                        id: encrypt.encrypt(id)
                    },
                    success: function(data) {
                        if (data == "done") {
                            button.parent().parent().remove();
                            if ($("details").length == 0) {
                                $("div").append("<h2 class='error'>No detections for the moment</h2>");
                            }
                        } else alert("Error trying to delete the log")
                    }
                });
            }
        });
    });
</script>

</html>