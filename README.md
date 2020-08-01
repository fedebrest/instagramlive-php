# InstagramLive-PHP [![Discord](https://img.shields.io/discord/476526599232159780.svg?style=for-the-badge)](https://discord.gg/EpkKFt3)
A PHP script that allows for you to go live on Instagram with any streaming program that supports RTMP!

Built with [mgp25's amazing Instagram Private API Wrapper for PHP](https://github.com/mgp25/Instagram-API/).
# Note
Please read this **entire** document as it has *very* important information about the script. If you create an issue that can be solved by reading this document, it will be ignored.

# Setup
It is suggested you watch [this video](https://www.youtube.com/watch?v=J6lp8g3zQeE) for a step-by-step process on how to install this script.

1. Install PHP, of course...
2. [Install Composer](https://getcomposer.org/download/)
3. Goto the [most release release](https://github.com/JRoy/InstagramLive-PHP/releases/latest)
4. Download the `update.php` file and place it in its own folder
5. Run the script with `php update.php` and let it install the script
6. Edit the `USERNAME` and `PASSWORD` inside of the `config.php` file to your Instagram username/password.
7. Run the `goLive.php` script. (`php goLive.php`)
#### Video Tutorial
If you'd like a video version of this tutorial, see [this video](https://www.youtube.com/watch?v=J6lp8g3zQeE).
# OBS-Setup
If your system does not support OBS integration or you want to see what settings to use for another streaming program, look here.
1. Set your OBS canvas size to 720x1280. This can be done by going to Settings->Video and editing Base Canvas Resolution to "720x1280".
2. Go to the "Stream" section of your OBS Settings 
3. Set "Stream Type" to "Custom Streaming Server"
4. Set the "URL" field to the stream url you got from the script
5. Set the "Stream key" field to the stream key you got from the script
6. Make Sure "Use Authentication" is **unchecked** and press "OK"
7. Start Streaming in OBS
8. To stop streaming, run the "stop" command in your terminal and then press "Stop Streaming" in OBS
# Comment & Like Viewing
To view comments and likes as you are streaming, you'll need a Windows machine as this script's async support only works on Windows. When you run the script on Windows, after it logs you in, it will open a second screen where you can enter commands as the first screen will output comments and likes.

Linux/Mac support is planned for a future release.
# Commands & Command Line Arguments
InstagramLive-PHP has many commands to aid while streaming as well as command line arguments to change the behavior of the script. To view what they are and which ones work on what operating system: [click here for the streaming commands](https://github.com/JRoy/InstagramLive-PHP/wiki/Commands) or [click here for the command line arguments](https://github.com/JRoy/InstagramLive-PHP/wiki/Command-Line-Arguments). 
# FAQ
#### OBS gives a "Failed to connect" error
This is mostly due to an invalid stream key: The stream key changes **every** time you start a new stream so it must be replaced in OBS every time.
#### I've stopped streaming but Instagram still shows me as live
This is due to you not running the "stop" command inside the script. You cannot just close the command window to make Instagram stop streaming, you must run the stop command in the script. If you *do* close the command window however, start it again and just run the stop command, this should stop Instagram from listing to live content.
#### I get an error inside of Instagram when archiving my story
This is usually due to archiving a stream that had no content (video). Just delete the archive and be go on with your day.
#### I archived my live stream but I don't see it inside of Instagram
This is can be caused by 1 of 2 reasons:
* You did not stream anything. Please make sure you actually send a video to the stream url or the archive may fail to load.
* You did not change your stream content/canvas size. If you are using OBS, you can address this by following step one in [OBS Setup Section](https://github.com/JRoy/InstagramLive-PHP#obs-setup).
#### I get "CURL Error 60: SSL certificate problem" when trying to log into Instagram
This is due to CURL not having a valid CA. You can find a solution here: [https://stackoverflow.com/a/34883260](https://stackoverflow.com/a/34883260).
#### I get "CURL Error 28: Operation timed out after x milliseconds with 0 bytes received."
This means Instagram is refusing to connect to your proxy/computer. This could be for a wide verity of different reasons. I cannot help you when this if this happens, it's out of my control and there is nothing you can do to fix this other than changing your IP Address or actual computer you are using. Additionally, a proxy or VPN could create this issue.
### Question not listed here?
If your question is not listed here, [join our discord](https://discord.gg/EpkKFt3) so I can help support you faster. [https://discord.gg/EpkKFt3](https://discord.gg/EpkKFt3)
# Donate
If you would like to donate to me because you find what I do useful and would like to support me, you can do so through this methods:

Patreon: https://www.patreon.com/JRoy

PayPal.me: https://www.paypal.me/JoshuaRoy1

Bitcoin: `32J2AqJBDY1VLq6wfZcLrTYS8fCcHHVDKD`
# instagramlive-php
