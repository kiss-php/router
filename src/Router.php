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
    
    public function getOption(string $key) {
        return $this->pathOptions[$key]??null;
    }

    private function executeString(string $execute) : bool {
        list($class, $method) = explode('::', $execute);

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
        if (strpos($file, '/') === 0 || strpos($file, '\\') === 0) {
            $file = dirname($_SERVER['SCRIPT_FILENAME']) . $file;
        }

        if (!is_file($file)) return false;

        require $file;
        return true;
    }

    private function checkPath($path, &$options=[]) : bool {       
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Fix: Detect and remove the subdirectory base path automatically
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($basePath !== '/' && $basePath !== '.') {
            if (strpos($currentPath, $basePath) === 0) {
                $currentPath = substr($currentPath, strlen($basePath));
            }
        }

        // Also strip index.php if it's at the start of the path
        if (stripos($currentPath, '/index.php') === 0) {
            $currentPath = substr($currentPath, 10);
        }

        $path=trim($path,'/ ');
        $checkingPathArray=explode('/',strtolower($path));

        $currentPath=trim($currentPath,'/ ');
        $actualPathArray=explode('/',strtolower($currentPath));
        
        if(Count($actualPathArray)!==Count($checkingPathArray)) return false;

        foreach ($checkingPathArray as $keyNum=>$pathPart) {

            //If is {variable-path} always is true we can continue
            if(preg_match('/^\{.*\}$/', $pathPart)) {
                $options[trim($pathPart,' {}')]=$actualPathArray[$keyNum];
                $this->pathOptions[trim($pathPart,' {}')]=$actualPathArray[$keyNum];
                continue;
            }

            //If not match we can return false to dont lose more time
            if ($pathPart !== $actualPathArray[$keyNum]) return false;
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
