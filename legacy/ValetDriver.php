<?php

class ValetDriver extends \Valet\Drivers\ValetDriver
{
    public function serves($sitePath, $siteName, $uri)
    {
        file_put_contents($sitePath . '/driver_log.txt', "Serves: $uri
", FILE_APPEND);
        return true;
    }

    public function isStaticFile($sitePath, $siteName, $uri)
    {
        file_put_contents($sitePath . '/driver_log.txt', "IsStatic: $uri
", FILE_APPEND);
        $staticFilePath = $sitePath . $uri;

        if (file_exists($staticFilePath) && !is_dir($staticFilePath)) {
            $extension = pathinfo($staticFilePath, PATHINFO_EXTENSION);
            if ($extension === 'php') {
                return false;
            }
            file_put_contents($sitePath . '/driver_log.txt', "Found Static: $staticFilePath
", FILE_APPEND);
            return $staticFilePath;
        }

        return false;
    }

    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        file_put_contents($sitePath . '/driver_log.txt', "FrontController: $uri
", FILE_APPEND);
        return $sitePath . '/index.php';
    }
}
