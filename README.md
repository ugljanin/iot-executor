
# IoT Executor - IoT connector

This project is a part of the PhD thesis where I use ESP8266 microcontrollers and controll them with the web-based dashboard.
The dashboard allows the user to register devices using their chipid, and create programs (aka mutations) that will run on the device.
The devices are first loaded with basic sceleton that provides communication with this dashboard, and once the user assigns the script to specific ESP8266 device, it will load
notify that device about pending update, the device will stop current execution, restart, download the new code and compile it, and at the end start with new code.

So basically, the IoT Executor allows easy management of NodeMCU devices, without need for phisical connection.
## Configuration
To be able to run the system, some preparation is needed. At first to load the devices with proper firmware that allows use of MQTT, and HTTP protocols, then to load the framework that allows communication with IoT Executor, and then to configure the dashboard.
Each device that is planned to be used with IoT Executor, should apply the steps bellow.

### Configuring ESP8266 devices

#### Generating firmware

You can create your own NodeMCU custom builds at [cloud service](https://nodemcu-build.com/), or download [this](https://github.com/ugljanin/iot-executor/blob/master/esp8266/modules/nodemcu-master-12-modules-2019-11-07-00-14-54-float.bin). If you are creating your own custom build, it is important to check (MQTT, WiFi, file, gpio, net, node, RTC time, timer, UART).

#### Applying firmware to the devices

Instructions for flashing the firmware are available [here](https://nodemcu.readthedocs.io/en/latest/flash/).

For Windows, I used [NodeMCU flasher](https://github.com/nodemcu/nodemcu-flasher).


#### Adding IoT Executor framework

- To be able to add IoT Executor framework to your NodeMCU-based device, you need to have a program that will allow you communication with the devices. One good example of such program is [ESPlorer](https://esp8266.ru/esplorer/).
- Once the device is connected to the computer with a cable, and the ESPlorer is running, you will see the message on ESPlorer console that the device is formating, which means none of the files will exist on it.
- Add relevant data for WiFi connection and MQTT broker in config.txt stored at folder *esp8266*. Line that contain domain information in config.txt file, needs to contain the domain where dashboard will be hosted.
- You should upload both files, namely [init.lua](https://github.com/ugljanin/iot-executor/blob/master/esp8266/init.lua) and [config.txt](https://github.com/ugljanin/iot-executor/blob/master/esp8266/config.txt) to your device.

### Video demo

Detailed introduction on how to setup your device, install firmware, upload framework, work with the IoT Executor is presented [here](https://www.youtube.com/watch?v=CKHdBwNI1V8).

[![Video demo](https://img.youtube.com/vi/CKHdBwNI1V8/0.jpg)](https://www.youtube.com/watch?v=CKHdBwNI1V8)


### Configuring the dashboard

This is web-based dashboard, and to run it you need hosting with php (>7.4) and mysql and a composer.
You also need publically accessable domain, because it will be used so that devices could download the script when ready.

- Run `composer install` to install libraries required to work with MQTT.
- Import [database](https://github.com/ugljanin/iot-executor/blob/master/sql/esp.sql) on your MySQL server
- Add mysql server data and mqtt broker data in [config.php](https://github.com/ugljanin/iot-executor/blob/master/inc/config.php) file

## Access the dashboard

When you access your dashboard, there are 2 types of users. Manager and Engineer. To access any of them you will use `manager`/`manager` or `engineer`/`engineer` username and password.

## Working with devices

At first you need to register the devices with NodeMCU chipid, that will be used for its identification.
Once the devices are registered, you can create multiple scripts with the code that will be assigned to any devices.

It is important not to use cpu blocking functions such as tmr.delay() but to use alarm instead, and to avoid infinite loops, as they could block the script.

After adding the scripts (mutations), they could be assigned to any device. If the device is running, and is connected to WiFi and MQTT broker, it will receive signal that the update is pending, it will stop current execution, download and compile new code and restart. After restarting the device will have new function as per the engineer has defined.

## Screenshots

### List of registered devices
![Devices list](/assets/screenshots/devices.png "Devices list")
### List of registered mutations
![Mutations list](/assets/screenshots/mutations-list.png "Mutations list")
### Assign mutation to a device
![Assign mutation](/assets/screenshots/assign-mutation.png "Assign mutation")
### View mutation code
![Mutation code](/assets/screenshots/mutation-code.png "Mutation code")
