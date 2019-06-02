# APRX Simple Statistics Website Generator

A simple statistics and information generator for APRX software, which allows to monitor load, frames, stations details, statistics from selected time window and more.


## Installation and usage

For installation just copy all files to the webistes folder in your WWW server directory. Make sure it supports PHP.

To configure, open config.php file with some text editor.

Enter the full path to your APRX-RF log file (aprx-rf.log, NOT aprx.log):

```
$logpath = "/some/path/aprx-rf.log";
```
Usually you can find it under /var/log/aprx/aprx-rf.log. Incorrect path will make the script unable to work.


This was the only required step and now the software should work.

It's recommended to set also another settings:

Your station latitude and longtitude for distance calculation (in decimal degrees):
```
$stationlat = 49.013855;
$stationlon = 28.762225;
```

And your regional country/state untraced (flood) digipeater alias, which helps the software to distinguish between direct and digipeated frames.
For example it's SP in Poland, CZ in Czech Republic or HU in Hungary.
```
$cntalias = "SP";
```

Normally every time you open the statistics website, you have to enter interface callsign. If you want to set static interface callsign (but it can be temporarily changed via website for one session), you can do this here:
```
$static_if = 1;
$static_call = "N0CALL-11;
$static_lang = "en";
```
Set $static_if to 1 to enable. Available languages are en for English and pl for Polish.

## Customization

Custom logo and text info can be set optionally. The logo will be displayed on all pages and text info will be displayed only on main (summary.php) page.
To set your logo you need to place a file (PNG format) named **aprslogo.png** in main website directory. The image will be automatically resized to fit the page.

To set your custom text info place them in the file named **custom.php**. It can contain HTML and PHP code.

## Software stability

This is a BETA software. It can contain some bugs and may be written in non-efficient way. Please contact authors if you find any bug.

## Authors

* **Peter SQ8VPS**
* **Alfredo IZ7BOJ**


## License

Project is free for non-commercial use. You can modify and publish this software, but you have to put an information about original authors.
