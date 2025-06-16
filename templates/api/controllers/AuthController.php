<?php
namespace Controllers;
use Core\Database;use Core\Response;use Core\Validator;
class AuthController{
    private $pdo;
    public function __construct(){session_start();$this->pdo=Database::get();}
    public function login(){try{$email=Validator::email($_POST['email']??'');$pwd=Validator::password($_POST['password']??'');}catch(\Exception $e){Response::json(['success'=>false,'message'=>$e->getMessage()],400);} $stmt=$this->pdo->prepare('SELECT * FROM users WHERE email=?');$stmt->execute([$email]);$u=$stmt->fetch();if(!$u||!password_verify($pwd,$u['password'])) Response::json(['success'=>false,'message'=>'Nepareizs e-pasts vai parole'],401);$_SESSION['user_id']=$u['id'];unset($u['password']);Response::json(['success'=>true,'user'=>$u]);}
    public function register(){try{$name=Validator::string($_POST['name']??'','Vārds');$email=Validator::email($_POST['email']??'');$pwd=Validator::password($_POST['password']??'');}catch(\Exception $e){Response::json(['success'=>false,'message'=>$e->getMessage()],400);} $hash=password_hash($pwd,PASSWORD_DEFAULT);try{
            $this->pdo->prepare('INSERT INTO users(name,email,password) VALUES (?,?,?)')->execute([$name,$email,$hash]);
        }catch(\PDOException $e){
            if($e->getCode()==23000){ 
                Response::json(['success'=>false,'message'=>'E-pasts jau eksistē'],409);
            }
            Response::json(['success'=>false,'message'=>'DB kļūda'],500);
        }
        Response::json(['success'=>true,'message'=>'Reģistrācija veiksmīga']);}
    public function logout(){session_start();session_destroy();Response::json(['success'=>true]);}
    public function me(){session_start();if(isset($_SESSION['user_id'])){$stmt=$this->pdo->prepare('SELECT id,name,email FROM users WHERE id=?');$stmt->execute([$_SESSION['user_id']]);Response::json(['success'=>true,'user'=>$stmt->fetch()]);}Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);} }
