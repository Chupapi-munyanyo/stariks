<?php
namespace Controllers;
use Core\Database;use Core\Response;use Core\Validator;
class CardLoansController{private $pdo;private int $uid;public function __construct(){session_start();if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);$this->uid=$_SESSION['user_id'];$this->pdo=Database::get();}
public function list(){ $s=$this->pdo->prepare('SELECT l.id,l.card_id,c.bank_name,c.last4,l.description,l.amount,l.monthly_payment,l.paid_off_amount,l.start_date,l.end_date FROM credit_card_loans l JOIN credit_cards c ON c.id=l.card_id WHERE c.user_id=?');$s->execute([$this->uid]);Response::json(['success'=>true,'loans'=>$s->fetchAll()]);}
public function create(){try{$card=(int)($_POST['card_id']??0);
        $desc=Validator::string($_POST['description']??'','Apraksts',100);
        $amount=(float)($_POST['amount']??0);
        $monthly=(float)($_POST['monthly_payment']??0);
        $paid=(float)($_POST['paid_off_amount']??0);
        $start=$_POST['start_date']??'';
        $end=$_POST['end_date']??'';
        if(!$card||$amount<=0||$monthly<=0||!$start||!$end) throw new \Exception('Nepareizi dati');
        $this->pdo->prepare('INSERT INTO credit_card_loans(card_id,description,amount,monthly_payment,paid_off_amount,start_date,end_date) VALUES (?,?,?,?,?,?,?)')->execute([$card,$desc,$amount,$monthly,$paid,$start,$end]);Response::json(['success'=>true]);}catch(\Exception $e){Response::json(['success'=>false,'message'=>$e->getMessage()],400);} }
public function delete(){ $id=(int)($_POST['id']??0);$this->pdo->prepare('DELETE l FROM credit_card_loans l JOIN credit_cards c ON c.id=l.card_id WHERE l.id=? AND c.user_id=?')->execute([$id,$this->uid]);Response::json(['success'=>true]);}}
