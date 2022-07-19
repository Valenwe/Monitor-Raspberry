import cv2
import os
import datetime
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email import encoders
import threading
import json
import time
from pygame import mixer
from math import floor

# variables
display = False

global webcam_motion
webcam_motion = False

global webcam_send_email
webcam_send_email = False

global img_path
img_path = "imgs"

global json_path
json_path = "files"

global webcam_capture
webcam_capture = True

global webcam_port
webcam_port = -1

global webcam_resolution
webcam_resolution = "640x480"

global max_size_folder_img
max_size_folder_img = 500

global current_size_folder_img
current_size_folder_img = 0

global dectection_id
detection_id = 1

global webcam_minimum_square_size
webcam_minimum_square_size = 75

global maximum_detection_wait
maximum_detection_wait = 60

global gmail_address
global gmail_password

global detection_counter
detection_counter = 0

root_folder = os.getcwd()

try:
    mixer.init()
    mixer.music.load(json_path + "/alarm_30s.mp3")
except:
    print("Unable to load alarm sound file")


def refreh_filesize():
    global current_size_folder_img
    current_size_folder_img = 0
    for path, dirs, files in os.walk(os.getcwd()):
        for f in files:
            current_size_folder_img += os.path.getsize(os.getcwd() + "/" + f)


def refresh_config(first_time=False):
    try:
        with open(root_folder + "/files/config.json") as file:
            data = json.load(file)

        global img_path
        if os.path.isdir(root_folder + "/" + data["img_path"]):
            img_path = root_folder + "/" + data["img_path"]

        global json_path
        if os.path.isdir(root_folder + "/" + data["json_path"]):
            json_path = root_folder + "/" + data["json_path"]

        global webcam_motion
        if isinstance(data["webcam_motion"], bool):
            webcam_motion = data["webcam_motion"]

        global webcam_send_email
        if isinstance(data["webcam_send_email"], bool):
            webcam_send_email = data["webcam_send_email"]

        global webcam_capture
        if isinstance(data["webcam_capture"], bool):
            webcam_capture = data["webcam_capture"]

        global webcam_port
        webcam_port = int(data["webcam_port"])

        global webcam_resolution
        webcam_resolution = data["webcam_resolution"]

        global detection_id
        try:
            if int(data["detection_id"]) > 0:
                detection_id = int(data["detection_id"])
                if first_time and detection_id > 1:
                    detection_id += 1

        except:
            pass

        global webcam_minimum_square_size
        try:
            if int(data["webcam_minimum_square_size"]) > 0:
                webcam_minimum_square_size = int(
                    data["webcam_minimum_square_size"])
        except:
            pass

        global maximum_detection_wait
        try:
            if int(data["maximum_detection_wait"]) > 0:
                maximum_detection_wait = int(data["maximum_detection_wait"])
        except:
            pass

        # mega octet -> octet
        global max_size_folder_img
        try:
            if int(data["max_size_folder_img"]) > 0:
                max_size_folder_img = int(
                    data["max_size_folder_img"]) * 1000 * 1000
        except:
            pass

        global gmail_address
        gmail_address = data["gmail_address"]

        global gmail_password
        gmail_password = data["gmail_password"]
    except Exception as e:
        print("Unable to read config file...")
        print(e)

    if first_time:
        # check path available
        if not os.path.isdir(img_path):
            os.mkdir(img_path)

        os.chdir(img_path)

    # refresh the size used for the images
    refreh_filesize()


def save_config(key, value):
    try:
        with open(root_folder + "/files/config.json") as file:
            data = json.load(file)

        data[key] = value

        with open(root_folder + "/files/config.json", "w") as file:
            json.dump(data, file)

    except:
        pass


def alarm(subject, content, files):
    global json_path
    global gmail_address
    global gmail_password

    try:
        mixer.music.play()
    except:
        print("Unable to play sound")

    try:
        message = MIMEMultipart()
        message["From"] = gmail_address
        message['To'] = gmail_address
        message['Subject'] = subject

        # file attachement
        for file in files:
            attachment = open(file, 'rb')
            obj = MIMEBase('application', 'octet-stream')
            obj.set_payload((attachment).read())
            encoders.encode_base64(obj)
            obj.add_header('Content-Disposition',
                           "attachment; filename= " + os.path.basename(file))
            message.attach(obj)

        # body
        plain_text = MIMEText(content, _subtype='plain', _charset='UTF-8')
        message.attach(plain_text)

        my_message = message.as_string()
        email_session = smtplib.SMTP('smtp.gmail.com', 587)
        email_session.starttls()
        email_session.login(gmail_address, gmail_password)
        email_session.sendmail(gmail_address, gmail_address, my_message)
        email_session.quit()
    except:
        print("Unable to send email")


def list_ports():
    """
    Test the ports and returns a tuple with the available ports and the ones that are working.
    """
    print("Searching available webcams...")
    non_working_ports = []
    dev_port = 0
    working_ports = []
    available_ports = []
    # if there are more than 5 non working ports stop the testing.
    while len(non_working_ports) < 6:
        camera = cv2.VideoCapture(dev_port)  # cv2.CAP_DSHOW
        if not camera.isOpened():
            non_working_ports.append(dev_port)
            print("Port %s is not working." % dev_port)
        else:
            is_reading, img = camera.read()
            width = camera.get(cv2.CAP_PROP_FRAME_WIDTH)
            height = camera.get(cv2.CAP_PROP_FRAME_HEIGHT)
            if is_reading:
                print("Port %s is working and reads images (%s x %s)" %
                      (dev_port, height, width))
                working_ports.append(dev_port)
            else:
                print("Port %s for camera ( %s x %s) is present but cannot read images." % (
                    dev_port, height, width))
                available_ports.append(dev_port)
        camera.release()
        dev_port += 1
    return available_ports, working_ports, non_working_ports


def get_resolutions(port):
    # v4l2-ctl -d /dev/video0 --list-formats-ext
    print("Getting working resolutions...")
    possible_res = ["640x480", "800x600", "960x540",
                    "1280x720", "1280x1024", "1600x896", "1920x1080"]
    camera = cv2.VideoCapture(port)
    resolutions = []

    for res in possible_res:
        possible_width, possible_height = tuple(res.split("x"))
        possible_width = int(possible_width)
        possible_height = int(possible_height)

        camera.set(cv2.CAP_PROP_FRAME_WIDTH, possible_width)
        camera.set(cv2.CAP_PROP_FRAME_HEIGHT, possible_height)

        width = camera.get(cv2.CAP_PROP_FRAME_WIDTH)
        height = camera.get(cv2.CAP_PROP_FRAME_HEIGHT)

        print(res + " : ", end="")
        if height == possible_height and width == possible_width:
            resolutions.append(res)
            print("OK")
        else:
            print("UNAVAILABLE")

    camera.release()

    return resolutions


def movement_detected(without_rect, with_rect, last_alert, last_warning, last_detection):
    global current_size_folder_img
    global max_size_folder_img
    global detection_id
    global maximum_detection_wait
    global webcam_send_email
    global detection_counter

    can_alert = last_alert == -1 or floor(datetime.datetime.now().timestamp() -
                                          last_alert) > maximum_detection_wait

    can_warn = last_warning == -1 or floor(datetime.datetime.now().timestamp() -
                                           last_warning) > maximum_detection_wait

    can_detect_new_one = last_detection != -1 and floor(datetime.datetime.now().timestamp() -
                                                        last_detection) > maximum_detection_wait

    if current_size_folder_img < max_size_folder_img:

        # si la dernière détection date de plus de 1min
        if can_detect_new_one:
            # on ajoute +1 au detection_id pour la prochaine série
            detection_id += 1
            save_config("detection_id", detection_id)

            # on remet à 0 le compteur de frame
            detection_counter = 0

        last_detection = datetime.datetime.now().timestamp()
        title = str(detection_id) + \
            "_{:%Y%m%d_%H%M%S}".format(datetime.datetime.now())
        cv2.imwrite(title + "_ORIGINAL.jpg", without_rect)
        cv2.imwrite(title + "_COLLIDE.jpg", with_rect)

        # si la dernière alerte s'est déroulée il y a une minute minimum et qu'il y a plus d'une frame d'image
        if can_alert and webcam_send_email and detection_counter >= 2:
            th = threading.Thread(target=alarm, args=("Camera detected movement!",
                                                      "The camera detected movement at " + datetime.datetime.now().strftime(
                                                          '%d/%m/%Y %H:%M:%S'), [title + "_ORIGINAL.jpg", title + "_COLLIDE.jpg"]))
            th.start()
            last_alert = datetime.datetime.now().timestamp()

        detection_counter += 1

    elif can_warn:
        print("Failed trying to save the detection images. Reached max size")
        last_warning = datetime.datetime.now().timestamp()

    return last_alert, last_warning, last_detection


refresh_config(first_time=True)

available_ports, working_ports, non_working_ports = list_ports()
print("Available camera ports:")
print(working_ports)
if len(working_ports) > 0:
    # va en priorité privilégier le port de config.json
    while webcam_port not in working_ports:
        try:
            webcam_port = int(input("Which camera port do you choose?\n>>> "))
        except:
            print("Error, please enter a correct number")

    save_config("webcam_port", webcam_port)

    resolutions = get_resolutions(webcam_port)
    print("Available camera resolutions:")
    print(resolutions)
    # va en priorité privilégier la résolution de config.json
    while webcam_resolution not in resolutions:
        try:
            webcam_resolution = resolutions[int(
                input("Which resolution do you choose?\n(index in [0, lenght - 1])\n>>> "))]
        except:
            print("Error, please enter a correct number")

    used_width, used_height = tuple(webcam_resolution.split("x"))
    used_width = int(used_width)
    used_height = int(used_height)

    save_config("webcam_resolution", webcam_resolution)

    print("Beginning image sequence capture...")
    last_alert = -1
    last_warning = -1
    last_detection = -1
    cam = cv2.VideoCapture(webcam_port)  # cv2.CAP_DSHOW

    cam.set(cv2.CAP_PROP_FRAME_WIDTH, used_width)
    cam.set(cv2.CAP_PROP_FRAME_HEIGHT, used_height)

    # first frame as first model
    ret1, frame1 = cam.read()
    gray1 = cv2.cvtColor(frame1, cv2.COLOR_BGR2GRAY)
    gray1 = cv2.equalizeHist(gray1)
    gray1 = cv2.GaussianBlur(gray1, (21, 21), 0)

    if display:
        cv2.imshow('window', frame1)

    count_refresh_config = 0
    while True:
        # checkup de la config
        if count_refresh_config >= 20:
            thread = threading.Thread(target=refresh_config)
            thread.start()
            count_refresh_config = 0
        else:
            count_refresh_config += 1

        if not webcam_capture:
            continue

        # now begins all the image analysis
        ret2, frame2 = cam.read()
        frame2_untouched = frame2.copy()
        gray2 = cv2.cvtColor(frame2, cv2.COLOR_BGR2GRAY)
        gray2 = cv2.equalizeHist(gray2)
        gray2 = cv2.GaussianBlur(gray2, (21, 21), 0)

        deltaframe = cv2.absdiff(gray1, gray2)
        # cv2.imshow('delta', deltaframe)

        threshold = cv2.threshold(deltaframe, 25, 255, cv2.THRESH_BINARY)[1]
        threshold = cv2.dilate(threshold, None)
        # cv2.imshow('threshold', threshold)

        countour, heirarchy = cv2.findContours(
            threshold, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

        cv2.imwrite("feed.jpg", frame2_untouched)

        squareHighEnough = False
        for i in countour:
            # if the area detected is bigger than the smallest square size, and smaller than 70% of the webcam screen
            if cv2.contourArea(i) < webcam_minimum_square_size or cv2.contourArea(i) >= 0.7 * used_width * used_height:
                continue

            squareHighEnough = True
            (x, y, w, h) = cv2.boundingRect(i)
            cv2.rectangle(frame2, (x, y), (x + w, y + h), (255, 0, 0), 2)

        # detected
        if squareHighEnough and webcam_motion:
            last_alert, last_warning, last_detection = movement_detected(
                frame2_untouched, frame2, last_alert, last_warning, last_detection)

        if display:
            cv2.imshow('window', frame2)
            # escape key
            if cv2.waitKey(1) == 27:
                break

        frame1 = frame2
        gray1 = gray2
        time.sleep(0.05)

    cam.release()

else:
    print("No available webcam detected")

cv2.destroyAllWindows()
