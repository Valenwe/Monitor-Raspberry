# Monitor-Raspberry
 Homemade smart home web interface

Everything is planned to be hosted on a PHP server in a Raspberry PI 4. Though, it is possible to host it on any device that has internet access and a webcam.

In order to see the webcam content, I developped 'webcam.py' that constantly updates a 'feed.png' file, allowing to process itself for motion detection.

The interface uses Linux packages to switch ON or OFF computers registered. Though I never tried the feature, because it depends on hardware elements that I do not have.

Furthermore, your activity in the interface is logged, even if you fail to log-in. There are two different roles you can give to a user: the Overseer and the Observer. Only the Overseer is able to change any settings in the interface.
