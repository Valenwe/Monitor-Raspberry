<?php
include "RSA.php";

/*
RUN program at startup:
    sudo nano /etc/rc.local
    sudo python /home/pi/sample.py &

Make all files available:
    sudo chmod 777 -R /var/www/html/
    sudo chmod 777 -R /var/www/html/*

    sudo usermod -G sudo -a www-data
    sudo chgrp -R www-data /var/www
    sudo chmod -R g+w /var/www

    sudo chmod -R 777 /dev/
*/

function debug($msg, $exit = true)
{
    echo '<pre>';
    print_r($msg);
    if ($exit) {
        exit;
    }
}

function recover_config()
{
    if (!isset($_SESSION["config_loaded"])) {
        $keys_unused = array(
            "max_size_folder_img", "webcam_port", "gmail_address", "gmail_password",
            "maximum_detection_wait", "webcam_minimum_square_size", "webcam_resolution"
        );
        // SEUL ENDROIT A MODIFIER
        $config = get_json_content("files/config.json", true);
        foreach ($config as $key => $value) {
            if (array_search($key, $keys_unused) === false)
                $_SESSION[$key] = $value;
        }

        $_SESSION["config_loaded"] = true;
    }
}

function attribute_session_variables()
{
    $_SESSION["username"] = $_POST["username"];
    $_SESSION["admin_level"] = get_user($_POST["username"])["admin_level"];
}

function check_session_variables()
{
    if (!at_least_one_user())
        header("location: register.php");
    else {
        if (!isset($_SESSION["username"]))
            header("location: login.php");
    }
}

function get_valid_str($str)
{
    return str_replace(' ', '', strip_tags($str));
}

function get_client_information($log_password = False)
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $date = new DateTime();
    $date = $date->format("d M Y | H:i:s");

    $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));

    $response = array(
        "date" => $date, "username" => $_POST["username"],
        "ip" => $ip
    );
    if ($log_password)
        $response["password"] = strip_tags($_POST["password"]);

    if (!empty($details->country) && !empty($details->city)) {
        $response["city"] = $details->city;
        $response["country"] = $details->country;
    }

    return $response;
}

function get_json_content($filename, $manual = False)
{
    if (!$manual)
        $filename = $_SESSION["json_path"] . "/" . $filename;

    if (!file_exists($filename)) {
        $file = fopen($filename, "w");
        fclose($file);
    }

    $file = file_get_contents($filename);
    $data = json_decode($file, true);
    unset($file);

    if ($data == null) $data = array();
    return array_reverse($data);
}

function register_connection($filename, $log_password = False)
{
    $data = get_json_content($filename);

    $data[] = get_client_information($log_password);
    file_put_contents($filename, json_encode($data));
    unset($data);
}

function add_user($username, $password, $admin_level, $first_user)
{
    $username = get_valid_str($username);
    $data = get_json_content("users.json");

    $admin_level_id = 0;
    if (strtolower($admin_level) == "level_overseer" || !$first_user)
        $admin_level_id = 1;

    $password = password_hash($password, PASSWORD_BCRYPT);

    $new_entry = array("username" => $username, "password" => $password, "admin_level" => $admin_level_id, "creation_date" => (new DateTime())->format("d M Y | H:i:s"));
    array_push($data, $new_entry);
    $data = json_encode($data);
    file_put_contents($_SESSION["json_path"] . "/users.json", $data);

    if (!$first_user) header("location: login.php");
}

function remove_user($username)
{
    $data = get_json_content("users.json");
    $user = get_user($username);

    if ($user != null) {
        unset($data[array_search($user, $data)]);
        $data = json_encode($data);
        file_put_contents($_SESSION["json_path"] . "/users.json", $data);
    }
}

function get_user($username)
{
    $data = get_json_content("users.json");
    foreach ($data as $user) {
        if ($user["username"] == $username) {
            return $user;
        }
    }
    return null;
}

function get_computer($key, $value)
{
    $data = get_json_content("computers.json");
    foreach ($data as $computer) {
        if ($computer[$key] == $value) {
            return $computer;
        }
    }
    return null;
}

function save_config()
{
    $not_to_save = array("username", "admin_level", "config_loaded");

    $data = get_json_content("config.json");
    foreach ($_SESSION as $key => $value) {
        if (!in_array($key, $not_to_save)) $data[$key] = $value;
    }

    $file = fopen($_SESSION["json_path"] . "/config.json", 'w');
    if (!$file) {
        print_r(json_encode($data));
    }

    fwrite($file, json_encode($data));
    fclose($file);
}

function add_computer($name, $mac, $ip)
{
    $name = get_valid_str($name);
    $data = get_json_content("computers.json");
    array_push($data, array("name" => $name, "mac" => $mac, "ip" => $ip));

    $file = fopen($_SESSION["json_path"] . "/computers.json", 'w');
    fwrite($file, json_encode($data));
    fclose($file);
}

function remove_computer($name)
{
    $data = get_json_content("computers.json");
    $computer = get_computer("name", $name);
    if ($computer != null) {
        unset($data[array_search($computer, $data)]);
        $file = fopen($_SESSION["json_path"] . "/computers.json", 'w');
        fwrite($file, json_encode($data));
        fclose($file);
    }
}

function ping_computer($name)
{
    $computer = get_computer("name", $name);
    $address = $computer["ip"];
    if (strtolower(PHP_OS) == 'winnt') {
        $command = "ping -n 1 $address";
        exec($command, $output, $status);
    } else {
        $command = "ping -c 1 $address";
        exec($command, $output, $status);
    }
    if ($status === 0) {
        return true;
    } else {
        return false;
    }
}

function decrypt($text)
{
    $rsa = new RSA("", $_SESSION["json_path"] . "/key.pem");
    return $rsa->decrypt($text);
}

function encrypt($text)
{
    $rsa = new RSA($_SESSION["json_path"] . "/key_public.pem");
    return $rsa->encrypt($text);
}

function decrypt_post()
{
    foreach ($_POST as $key => $value) {
        try {
            $_POST[$key] = decrypt($value);
        } catch (Exception $e) {
            continue;
        }
    }
}

function at_least_one_user()
{
    $users = get_json_content("users.json");
    if (count($users) == 0) return false;
    else return true;
}

function echo_images($images)
{
    $has_detected = False;
    $used_detection_id = array();

    for ($i = 0; $i < count($images); $i++) {
        $img = basename($images[$i]);
        $detection_id = explode("_", $img)[0];

        if (array_search($detection_id, $used_detection_id) === false) {
            array_push($used_detection_id, $detection_id);
            $img_no_id = explode("_", $img)[1] . "_" . explode("_", $img)[2];

            $date = substr($img_no_id, 4, 2) . "/" . substr($img_no_id, 6, 2) . "/" . substr($img_no_id, 0, 4) . " " .
                substr($img_no_id, 9, 2) . ":" . substr($img_no_id, 11, 2) . ":" . substr($img_no_id, 13, 2);

            echo "<details id='" . $detection_id . "'> <summary class='unselectable' style='cursor: pointer; color: red'>" . (new DateTime($date))->format("d M Y | H:i:s") .
                " <button class='draw-border violet' title='Delete' id='delete_log'>X</button> " .
                " <button class='draw-border blue' title='View video' id='view_log'>⟹</button>" . "</summary>";

            $used_imgs = array();
            for ($I = $i; $I < count($images); $I++) {
                $img2 = basename($images[$I]);
                $detection_id2 = explode("_", $img2)[0];
                $img_no_id = explode("_", $img2)[1] . "_" . explode("_", $img2)[2];

                // si même série de détections
                if ($detection_id == $detection_id2 && array_search($img_no_id, $used_imgs) === false) {
                    $date = substr($img_no_id, 4, 2) . "/" . substr($img_no_id, 6, 2) . "/" . substr($img_no_id, 0, 4) . " " .
                        substr($img_no_id, 9, 2) . ":" . substr($img_no_id, 11, 2) . ":" . substr($img_no_id, 13, 2);

                    $original = $_SESSION["img_path"] . "/" . str_replace("_COLLIDE", "_ORIGINAL", $img2);
                    $collide = $_SESSION["img_path"] . "/" . str_replace("_ORIGINAL", "_COLLIDE", $img2);

                    array_push($used_imgs, $img_no_id);

                    echo "<img class='webcam_log' title='" . (new DateTime($date))->format("d M Y | H:i:s") . "' onmouseover='this.src=" . '"' .
                        $collide . '"' . "' onmouseout='this.src=" . '"' .  $original .  '"' . "' src='$original'>";
                }
            }

            echo "</details>";
            $has_detected = True;
            $i++;
        }
    }
    if (!$has_detected) echo "<h2 class='error'>No detections for the moment</h2>";
}
