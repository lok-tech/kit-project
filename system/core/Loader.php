<?php
/*
 * #### Warning this is a SYSTEM FILE ####
 */

namespace System\Core;

if(!defined('KIT_KEY')) exit('Access denied.');

final class Loader{
    public static $controller = false;
    public static $errorHandler = false;
    public static $autoLoad = false;
    public static $reserved = array(
        'kit','loader','shutdown','errors','router','output',
        'config','route','views'
    );
    public static $duplicate = false;

    function __construct(){
        self::update();
        spl_autoload_register(__NAMESPACE__ .'\Loader::get');
    }

    public static function get($class){
        $class = strtolower($class);
        if(!isset(self::$autoLoad[$class]) || !file_exists(self::$autoLoad[$class].'/'.$class.'.php')){
            if($class == 'config' || \Config::get('environment') == 'development') self::dumpAutoLoad();
        }
        if(isset(self::$autoLoad[$class])){
            require_once self::$autoLoad[$class].'/'.$class.'.php'.'';
            return true;
        }
        else return false;
    }

    public static function update(){
        $system = array(
            'config' => 'system/shell',
            'route' => 'system/shell',
            'views' => 'system/shell'
        );
        $autoLoad = array();
        require 'app/settings/AutoLoad.php';
        self::$autoLoad = array_merge($system, (array)$autoLoad);
    }

    public static function dumpAutoLoad(){
        $folders = array(
            'controllers',
            'helpers',
            'models'
        );
        $cleanAppFiles = false;
        $arrayContent = '';
        $appFiles = array();
        foreach($folders as $folderName){
            $appFiles = array_merge($appFiles, self::scanFolder('app/'.$folderName));
        }
        if(is_array($appFiles)){
            foreach($appFiles as $file){
                $file = explode('.', $file);
                if(array_pop($file) == 'php'){
                    $path = explode('/', implode('.',$file));
                    $key = array_pop($path);
                    if(in_array($key, self::$reserved)){
                        Errors::make($key.' is Reserved file name', true);
                        continue;
                    }
                    if(isset($cleanAppFiles[$key])){
                        if(!isset(self::$duplicate[$key])) self::$duplicate[$key][] = $cleanAppFiles[$key];
                        self::$duplicate[$key][] = implode('/',$path);
                        continue;
                    }
                    $cleanAppFiles[$key] = implode('/',$path);
                    if($arrayContent != '') $arrayContent .= ','.PHP_EOL;
                    $arrayContent .= "\t".'\''.$key.'\' => \''.$cleanAppFiles[$key].'\'';
                }
            }
        }
        if(self::$duplicate){
            $errorMsg = '';
            foreach((array)self::$duplicate as $name => $paths){
                $errorMsg .= '<br />`'.$name.'` -> ';
                foreach($paths as $key => $path){
                    if($key) $errorMsg .= '& ';
                    $errorMsg .= '`'.$path.'` ';
                }
            }
            Errors::make('Duplicate files! more details in the list:'.$errorMsg, true);
        }
        $file = 'app\settings\AutoLoad.php';
        $content = '<?php'.PHP_EOL;
        if($cleanAppFiles){
            $content .= '$autoLoad = array('.PHP_EOL;
            $content .= $arrayContent;
            $content .= PHP_EOL.');';
        }
        file_put_contents($file, $content);
        self::update();
    }

    public static function scanFolder($folderPath){
        $result = array();
        $folderFiles = array_diff(scandir($folderPath), Array('.','..'));
        foreach($folderFiles as $value){
            $value = $folderPath.'/'.$value;
            if(is_dir($value)){
               $result =  array_merge($result, self::scanFolder($value));
            }
            else $result[] = strtolower($value);
        }
        return $result;
    }
}