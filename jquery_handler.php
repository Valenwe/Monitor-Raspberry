<?php
include "functions.php";
session_start();
recover_config();
check_session_variables();

try {
    decrypt_post();
} catch (Exception $e) {
    return;
}

if ($_SESSION["admin_level"] == 0 && (isset($_POST["name"]) && $_POST["name"] != "ping_computer")) return;

if (isset($_POST["name"])) {
    switch ($_POST["name"]) {
        case "toggle_motion":
            $_SESSION["webcam_motion"] = !$_SESSION["webcam_motion"];
            save_config();
            echo ($_SESSION["webcam_motion"] == 0 ? "OFF" : "ON");
            break;

        case "toggle_capture":
            $_SESSION["webcam_capture"] = !$_SESSION["webcam_capture"];
            save_config();
            echo ($_SESSION["webcam_capture"] == 0 ? "OFF" : "ON");
            break;

        case "ping_webcam":

            if (strtolower(PHP_OS) == 'winnt') {
                $command = 'tasklist /fi "imagename eq python.exe"';
            } else {
                $command = "pgrep -af webcam.py | grep -v grep";
            }

            exec($command, $output);
            if (gettype($output) == "array") {
                $output = implode("", $output);
            }

            $is_alive = substr_count($output, "webcam.py") > 0 || substr_count($output, "python.exe") > 0;

            // si on veut toggle le script python en plus (work only with Linux)
            // echo password | sudo -S
            if (isset($_POST["name_2"])) {
                if ($_POST["name_2"] == "toggle_script") {
                    if ($is_alive) {
                        $command = "cd /var/www/html && sh kill_python.sh 2>&1";
                        echo "Process killed";
                    } else {
                        $command = "cd /var/www/html/ && sh launch_python.sh 2>&1";
                        echo "Process started";
                    }
                }

                echo shell_exec($command);
            } else
                echo ($is_alive ? "ON" : "OFF");

            break;

        case "toggle_send_email":
            $_SESSION["webcam_send_email"] = !$_SESSION["webcam_send_email"];
            save_config();
            echo ($_SESSION["webcam_send_email"] == 0 ? "OFF" : "ON");
            break;

        case "delete_log":
            $images = glob(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $_SESSION["img_path"] . "/*");
            $images = array_filter($images, function ($file) {
                return is_file($file) && strpos($file, "feed.jpg") === false && explode("_", basename($file))[0] == $_POST["id"];
            });

            foreach ($images as $img) unlink($img);
            echo "done";
            break;

        case "purge_webcam_logs":
            $images = glob(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $_SESSION["img_path"] . "/*");
            foreach ($images as $file) {
                if (is_file($file) && strpos($file, "feed.jpg") === false)
                    unlink($file); // Delete the given file
            }
            $_SESSION["detection_id"] = 1;
            save_config();
            echo "done";
            break;

        case "purge_login_logs":
            unlink(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $_SESSION["json_path"] . "/login.json");
            unlink(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $_SESSION["json_path"] . "/false_login.json");
            echo "done";
            break;

        case "remove_user":
            $username = $_POST["username"];
            if ($username != $_SESSION["username"]) {
                remove_user($username);
                echo "done";
            }
            break;

        case "remove_computer":
            $computer = get_computer("name", $_POST["computer_name"]);
            if ($computer != null) {
                remove_computer($computer["name"]);
                echo "done";
            }
            break;

            // sudo apt install etherwake
        case "wake_computer":
            $computer = get_computer("name", $_POST["computer_name"]);
            if ($computer != null) {
                $output = shell_exec("etherwake " . $computer["mac"] . " 2>&1");

                // print_r($output);
                echo "done";
            }
            break;

            // https://www.peterdavehello.org/2015/05/remotely-shutdownrestart-windows-via-linux-on-debianubuntu-based-linux/
            // sudo apt-get install samba-common
            // for linux computers: sudo systemctl status ssh
        case "shutdown_computer":
            $computer = get_computer("name", $_POST["computer_name"]);
            if ($computer != null) {

                $command = "sshpass -p '" . $_POST["session_password"] . "' ssh " . $_POST["session_name"] .
                    "@" . $computer["ip"] . " 'echo " . '"' . $_POST["session_password"] . '"' .
                    " | sudo -S shutdown now' 2>&1";

                $output = shell_exec($command);

                $command = "net rpc shutdown --ipaddress " . $computer["ip"] .
                    " --user '" . $_POST["session_name"] . "%" . $_POST["session_password"] . "' 2>&1";

                $output2 = shell_exec($command);

                // print_r($output);
                // print_r($output2);

                echo "done";
            }
            break;

        case "ping_computer":
            $computer = get_computer("ip", $_POST["ip"]);
            if ($computer != null) {
                echo (ping_computer($computer["name"]) == 1 ? "ON" : "OFF");
            } else echo "OFF";
            break;
    }
}
