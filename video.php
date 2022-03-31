<?php
include "functions.php";
session_start();
recover_config();
check_session_variables();

if (isset($_GET["webcam_logs"])) {
    header("location: webcam_logs.php");
}

if (!isset($_GET["id"])) {
    header("location: webcam_logs.php");
}

$images = glob(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $_SESSION["img_path"] . "/*");
$images = array_filter($images, function ($file) {
    return is_file($file) && strpos($file, "feed.jpg") === false && explode("_", basename($file))[0] == $_GET["id"];
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
    <title>Video display</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="style.css?<?php echo round(microtime(true) * 1000); ?>">
</head>

<body>
    <div class="box center">
        <h1>Video display</h1>
        <button class="draw-border yellow" id="pause">Pause</button>
        <button disabled class="blue" id="frame_counter">Frame 0/0</button>
        <a href="video.php?webcam_logs=1"><button class="draw-border">Back</button></a> <br>
        <img id="preview" src="imgs/2_20220128_202444_COLLIDE.jpg">
    </div>
</body>

<script src="jquery-3.6.0.min.js"></script>
<script>
    var frame_list = [];
    var frame_index = 0;
    var pause = false;

    function next_frame() {
        if (!pause) {
            $("#preview").attr("src", frame_list[frame_index]);
            let false_frame_nb = Math.floor((frame_index + 2) / 2);

            if (false_frame_nb > frame_list.length / 2) false_frame_nb = frame_list.length / 2;

            $("#frame_counter").text("Frame " + false_frame_nb + "/" + frame_list.length / 2);
            if (frame_index == frame_list.length)
                frame_index = 0;
            else frame_index++;
        }
    }

    <?php foreach ($images as $img) {
        echo "frame_list.push('" . $_SESSION["img_path"] . "/" . basename($img) . "');";
    } ?>

    setInterval(next_frame, 200);

    jQuery(document).ready(function() {
        $("#pause").on("click", function() {
            pause = !pause;
            if (pause) $(this).text("Resume");
            else $(this).text("Pause");
        })
    });
</script>

</html>