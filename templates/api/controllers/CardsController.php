<?php
namespace Controllers;
use Core\Database;use Core\Response;use Core\Validator;
class CardsController{private $pdo;private int $uid;public function __construct(){session_start();if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);$this->uid=$_SESSION['user_id'];$this->pdo=Database::get();}
public function list(){ $s=$this->pdo->prepare('SELECT id,bank_name,last4,balance_amount FROM credit_cards WHERE user_id=?');$s->execute([$this->uid]);Response::json(['success'=>true,'cards'=>$s->fetchAll()]);}
public function create(){try{$bank=Validator::string($_POST['bank_name']??'','Banka',100);
        $last4=substr(preg_replace('/\D/','',$_POST['last4']??''),-4);
        $balance=(float)($_POST['balance_amount']??0);
        if(strlen($last4)!=4) throw new \Exception('Nepareizi kartes numurs');
        $this->pdo->prepare('INSERT INTO credit_cards(user_id,bank_name,last4,balance_amount) VALUES (?,?,?,?)')->execute([$this->uid,$bank,$last4,$balance]);Response::json(['success'=>true]);}catch(\Exception $e){Response::json(['success'=>false,'message'=>$e->getMessage()],400);} }
public function delete(){ $id=(int)($_POST['id']??0);$this->pdo->prepare('DELETE FROM credit_cards WHERE id=? AND user_id=?')->execute([$id,$this->uid]);Response::json(['success'=>true]);}
public function view(){
  $id=(int)($_GET['id']??0);
  $stmt=$this->pdo->prepare('SELECT id, bank_name, last4, balance_amount FROM credit_cards WHERE id=? AND user_id=?');
  $stmt->execute([$id,$this->uid]);
  $card=$stmt->fetch();
  if($card){Response::json(['success'=>true,'card'=>$card]);}
  else Response::json(['success'=>false,'message'=>'Karte nav atrasta'],404);
}}
