<?php
require_once __DIR__.'/core/Response.php';
require_once __DIR__.'/core/Database.php';
require_once __DIR__.'/core/Validator.php';
use Core\Response;
$path=trim($_SERVER['PATH_INFO']??($_GET['r']??''),'/');
[$resource,$action]=array_pad(explode('/',$path,2),2,'');
$controllerFile=__DIR__.'/controllers/'.ucfirst($resource).'Controller.php';
if(!file_exists($controllerFile)) Response::json(['success'=>false,'message'=>'Neeksistējošs resurss'],404);
require_once $controllerFile;
$controllerClass='Controllers\\'.ucfirst($resource).'Controller';
$controller=new $controllerClass();

if(!method_exists($controller,$action)) Response::json(['success'=>false,'message'=>'Nederīga darbība'],400);
$controller->$action();
