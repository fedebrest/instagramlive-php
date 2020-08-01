<?php /** @noinspection PhpComposerExtensionStubsInspection */ /** @noinspection PhpUndefinedConstantInspection */

class Utils
{

    /**
     * Sanitizes a stream key for clip command on Windows.
     * @param string $streamKey The stream key to sanitize.
     * @return string The sanitized stream key.
     */
    public static function sanitizeStreamKey($streamKey): string
    {
        return str_replace("&", "^^^&", $streamKey);
    }

    /**
     * Logs information about the current environment.
     * @param string $exception Exception message to log.
     */
    public static function dump(string $exception = null)
    {
        clearstatcache();
        Utils::log("===========BEGIN DUMP===========");
        Utils::log("InstagramLive-PHP Version: " . scriptVersion);
        Utils::log("InstagramLive-PHP Flavor: " . scriptFlavor);
        Utils::log("Operating System: " . PHP_OS);
        Utils::log("PHP Version: " . PHP_VERSION);
        Utils::log("PHP Runtime: " . php_sapi_name());
        Utils::log("PHP Binary: " . PHP_BINARY);
        Utils::log("Bypassing OS-Check: " . (bypassCheck == true ? "true" : "false"));
        Utils::log("Composer Lock: " . (file_exists("composer.lock") == true ? "true" : "false"));
        Utils::log("Vendor Folder: " . (file_exists("vendor/") == true ? "true" : "false"));
        if ($exception !== null) {
            Utils::log("Exception: " . $exception);
        }
        Utils::log("============END DUMP============");
    }

    /**
     * Helper function to check if the current OS is Windows.
     * @return bool Returns true if running Windows.
     */
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Logs message to a output file.
     * @param string $message message to be logged to file.
     */
    public static function logOutput($message)
    {
        file_put_contents('output.txt', $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Checks for a file existance, if it doesn't exist throw a dump and exit the script.
     * @param $path string Path to the file.
     * @param $reason string Reason the file is needed.
     */
    public static function existsOrError($path, $reason)
    {
        if (!file_exists($path)) {
            Utils::log("The following file, `" . $path . "` is required and not found by the script for the following reason: " . $reason);
            Utils::log("Please make sure you follow the setup guide correctly.");
            Utils::dump();
            exit();
        }
    }

    /**
     * Checks to see if characters are at the start of the string.
     * @param string $haystack The string to for the needle.
     * @param string $needle The string to search for at the start of haystack.
     * @return bool Returns true if needle is at start of haystack.
     */
    public static function startsWith($haystack, $needle)
    {
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }

    /**
     * Logs a message in console but it actually uses new lines.
     * @param string $message message to be logged.
     */
    public static function log($message)
    {
        print $message . "\n";
    }
}