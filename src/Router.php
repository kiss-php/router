<?php
namespace Kiss\Http;

use Error;
use Exception;

class Router {
    public static function __callStatic($method, $arguments) {
        if(!isset($GLOBALS['x-open-source-router'])) $GLOBALS['x-open-source-router'] = new TrueRouter();
        return $GLOBALS['x-open-source-router']->$method(...$arguments);
    }
}

class TrueRouter {
    private $errorCallback;
    private $warningCallback;
    private array $routes;
    private array $pathOptions=[];
    private bool $served=false;

    public function notFound(string $execute) {
        $this->executeString($execute);
    }

    public function use(string $path, string $execute) : bool {
        //If is served the user or dont check we return false
        if($this->served || !$this->checkPath($path)) return false;
        //We mark as served an execute the string
        $this->served=true;
        return $this->executeString($execute);
    }

    public function execute(string $path, string $file) : bool {
        //If is served the user or dont check we return false
        if($this->served || !$this->checkPath($path)) return false;
        //We mark as served an execute the file
        $this->served=true;
        return $this->executeFile($file);
    }

    public function useFolder(string $path, string $folder) : bool {
        if($this->served) return false;

        $file = $this->getFolderFile($path);
        if($file === false) return false;

        if(!$this->serveFolderFile($folder, $file)) return false;

        $this->served=true;
        return true;
    }
    
    public function getOption(string $key) {
        return $this->pathOptions[$key]??null;
    }

    private function executeString(string $execute) : bool {
        // If no method is provided, call main by default.
        $parts = explode('::', $execute, 2);
        $class = $parts[0];
        $method = $parts[1]??'main';

        if(!$method) $method='main';

        if (class_exists($class)) {
            $instance = new $class();

            if (method_exists($instance, $method)) {
                $instance->$method();
                return true;
            }
        } 

        return false;
    }

    private function executeFile(string $file) : bool {
        // Execute files from the front controller directory only.
        $basePath = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
        if ($basePath === false) return false;

        $isAbsolutePath = strpos($file, '/') === 0
            || strpos($file, '\\') === 0
            || preg_match('/^[A-Z]:[\\\\\/]/i', $file);

        if (!$isAbsolutePath) $file = '/' . $file;
        if (strpos($file, '/') === 0 || strpos($file, '\\') === 0) $file = $basePath . $file;

        // Resolve the final path before requiring it, so ../ cannot escape.
        $realFile = realpath($file);
        if ($realFile === false || !is_file($realFile)) return false;

        // Only allow files inside the front controller directory.
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($realFile, $basePath) !== 0) return false;

        require $realFile;
        return true;
    }

    private function getFolderFile(string $path) {
        $path = trim($path, '/ ');
        $currentPath = trim($this->getCurrentPath(), '/ ');

        if ($path === '') return false;

        $pathLower = strtolower($path);
        $currentPathLower = strtolower($currentPath);

        if (strpos($currentPathLower, $pathLower . '/') !== 0) return false;

        return substr($currentPath, strlen($path) + 1);
    }

    private function serveFolderFile(string $folder, string $file) : bool {
        // Serve files only from the configured folder.
        $basePath = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
        if ($basePath === false) return false;

        $folder = trim($folder, '/\\ ');
        $realFolder = realpath($basePath . DIRECTORY_SEPARATOR . $folder);
        if ($realFolder === false || !is_dir($realFolder)) return false;

        // Resolve the requested file before reading it, so ../ cannot escape.
        $realFile = realpath($realFolder . DIRECTORY_SEPARATOR . $file);
        if ($realFile === false || !is_file($realFile)) return false;

        $realFolder = rtrim($realFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($realFile, $realFolder) !== 0) return false;

        if (!headers_sent()) {
            header('Content-Type: ' . $this->getMimeType($realFile));
            header('Content-Length: ' . filesize($realFile));
        }

        readfile($realFile);
        return true;
    }

    private function getMimeType(string $file) : string {
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file);
            if ($mimeType !== false) return $mimeType;
        }

        return 'application/octet-stream';
    }

    private function getCurrentPath() : string {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Fix: Detect and remove the subdirectory base path automatically
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($basePath !== '/' && $basePath !== '.') {
            // Match only the exact base path or a child path.
            if ($currentPath === $basePath || strpos($currentPath, $basePath . '/') === 0) {
                $currentPath = substr($currentPath, strlen($basePath));
            }
        }

        // Also strip index.php if it's at the start of the path
        if ($currentPath === '/index.php' || stripos($currentPath, '/index.php/') === 0) {
            $currentPath = substr($currentPath, 10);
        }

        return $currentPath;
    }

    private function checkPath($path, &$options=[]) : bool {       
        $currentPath = $this->getCurrentPath();

        $path=trim($path,'/ ');
        $checkingPathArray=explode('/',strtolower($path));

        $currentPath=trim($currentPath,'/ ');
        $actualPathArray=explode('/',$currentPath);
        // Compare routes case-insensitively, but keep original values for options.
        $actualPathCompareArray=explode('/',strtolower($currentPath));
        
        if(Count($actualPathArray)!==Count($checkingPathArray)) return false;

        foreach ($checkingPathArray as $keyNum=>$pathPart) {

            //If is {variable-path} always is true we can continue
            if(preg_match('/^\{.*\}$/', $pathPart)) {
                $options[trim($pathPart,' {}')]=$actualPathArray[$keyNum];
                $this->pathOptions[trim($pathPart,' {}')]=$actualPathArray[$keyNum];
                continue;
            }

            //If not match we can return false to dont lose more time
            if ($pathPart !== $actualPathCompareArray[$keyNum]) return false;
        }
        return true;
    }

    public function warning($msg, $file, $line) {
        if(is_callable($this->warningCallback)) call_user_func($this->warningCallback,$msg, $file, $line);
    }
    public function error($error) {
        if(is_callable($this->errorCallback)) call_user_func($this->errorCallback,$error);
    }
}
