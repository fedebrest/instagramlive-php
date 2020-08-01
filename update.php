<?php /** @noinspection PhpComposerExtensionStubsInspection */

if (exec(PHP_BINARY . " goLive.php --dumpFlavor") == 'custom') {
    logTxt("Custom build flavor located! Exiting updater...");
    exit();
}

$beta = false;
if (exec(PHP_BINARY . " goLive.php --dumpFlavor") == 'beta') {
    $beta = true;
}
if (in_array('-b', $argv) || in_array('--beta', $argv)) {
    $beta = true;
} elseif (in_array('-s', $argv) || in_array('--stable', $argv)) {
    $beta = false;
}

logTxt("Fetching Latest " . ($beta === true ? "Beta" : "Stable") . " Release Data");
$release = json_decode(file_get_contents("https://github.com/fedebrest/instagramlive-php" . ($beta === true ? "beta" : "stable") . ".json"), true);
logTxt("Fetched Version: " . $release['version']);

logTxt("Comparing Files...");
$queue = [];
$composer = false;
foreach ($release['files'] as $file) {
    if (!file_exists($file)) {
        logTxt("File Queued: " . $file);
        array_push($queue, $file);
        continue;
    }
    $localMd5 = md5(preg_replace("/\r|\n/", "", trim(file_get_contents($file))));
    $remoteMd5 = md5(preg_replace("/\r|\n/", "", trim(file_get_contents($release['links'][$file]))));
    logTxt($file . ": " . $localMd5 . " - " . $remoteMd5);
    if ($localMd5 !== $remoteMd5) {
        array_push($queue, $file);
    }
}

logTxt("Checking for config updates...");
if (file_exists("config.php")) {
    include_once 'config.php';
    if ((int)configVersionCode < (int)$release['config']['versionCode']) {
        logTxt("Outdated config version code, updating...");
        file_put_contents("config.php", file_get_contents($release['config']['url']));
        logTxt("Updated config, you'll need to re-populate your config username and password.");
    }
} else {
    logTxt("No config detected, downloading...");
    file_put_contents("config.php", file_get_contents($release['config']['url']));
    logTxt("Downloaded config!");
}

if (count($queue) != 0) {
    logTxt("Updating " . count($queue) . " files...");
    foreach ($queue as $file) {
        if ($file == 'composer.json') {
            $composer = true;
        }
        file_put_contents($file, file_get_contents($release['links'][$file]));
    }
    logTxt("Files Updated!");
}

if ($composer) {
    logTxt("Detected composer update, re-installing");
    exec((file_exists("composer.phar") ? (PHP_BINARY . " composer.phar") : "composer") . " update");
}

if (!file_exists("vendor/") || $composer) {
    logTxt($composer ? "Detected composer update, re-installing..." : "No vendor folder detected, attempting to recover...");
    exec((file_exists("composer.phar") ? (PHP_BINARY . " composer.phar") : "composer") . " update");
    if (!file_exists("vendor/")) {
        logTxt("Composer install was unsuccessful! Please make sure composer is ACTUALLY INSTALLED!");
        exit();
    }
}

logTxt("InstagramLive-PHP is now up-to-date!");

function logTxt($message)
{
    print($message . PHP_EOL);
}
