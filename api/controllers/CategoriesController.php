<?php
namespace Controllers;
use Core\Database;use Core\Response;use Core\Validator;
class CategoriesController{private $pdo;private int $uid;public function __construct(){session_start();if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);$this->uid=$_SESSION['user_id'];$this->pdo=Database::get();}
public function list(){ $stmt=$this->pdo->prepare('SELECT id,type,label FROM categories WHERE user_id=? ORDER BY type,label');$stmt->execute([$this->uid]);Response::json(['success'=>true,'categories'=>$stmt->fetchAll()]);}
public function create(){try{$type=$_POST['type']??'';$label=Validator::string($_POST['label']??'','Etiķete',50);if(!in_array($type,['income','expense'])) throw new \Exception('Nederīgs tips');}catch(\Exception $e){Response::json(['success'=>false,'message'=>$e->getMessage()],400);} $this->pdo->prepare('INSERT INTO categories(user_id,type,label) VALUES (?,?,?)')->execute([$this->uid,$type,$label]);Response::json(['success'=>true,'message'=>'Kategorija izveidota']);}}
