<?php
namespace Controllers;
use Core\Database;use Core\Response;use Core\Validator;
class InvestmentsController{private $pdo;private int $uid;public function __construct(){session_start();if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);$this->uid=$_SESSION['user_id'];$this->pdo=Database::get();}
public function list(){
        $sql='SELECT id,card_id,ticker,name,type,quantity,invested_amount,current_value,(current_value-invested_amount) profit FROM investments WHERE user_id=?';
        $params=[ $this->uid ];
        $card=(int)($_GET['card_id'] ?? 0);
        if($card>0){$sql.=' AND card_id=?';$params[]=$card;}
        $s=$this->pdo->prepare($sql);
        $s->execute($params);
        Response::json(['success'=>true,'investments'=>$s->fetchAll()]);}
public function create(){try{$ticker=strtoupper(trim($_POST['ticker']??''));if(!$ticker) throw new \Exception('Nav ticker');
        $name=Validator::string($_POST['name']??'','Nosaukums',100);
        $type=$_POST['type']??'stocks';
        $qty=(float)($_POST['quantity']??0);
        $invested=(float)($_POST['invested_amount']??0);
        $current=(float)($_POST['current_value']??0);
        if($qty<=0||$invested<=0||$current<0) throw new \Exception('Nepareizi dati');
        $card=(int)($_POST['card_id']??0);
        if(!$card) throw new \Exception('Nav kartes');
        // verify card ownership
        $chk=$this->pdo->prepare('SELECT 1 FROM credit_cards WHERE id=? AND user_id=?');
        $chk->execute([$card,$this->uid]);
        if(!$chk->fetchColumn()) throw new \Exception('Karte nav atrasta');
        $this->pdo->prepare('INSERT INTO investments(user_id,card_id,ticker,name,type,quantity,invested_amount,current_value) VALUES (?,?,?,?,?,?,?,?)')->execute([$this->uid,$card,$ticker,$name,$type,$qty,$invested,$current]);
            // deduct balance
            $this->pdo->prepare('UPDATE credit_cards SET balance_amount=balance_amount-? WHERE id=? AND user_id=?')->execute([$invested,$card,$this->uid]);
            Response::json(['success'=>true]);}catch(\Exception $e){Response::json(['success'=>false,'message'=>$e->getMessage()],400);} }
public function delete(){ $id=(int)($_POST['id']??0);$card=(int)($_POST['card_id']??0);
        $stmt=$this->pdo->prepare('SELECT invested_amount,card_id FROM investments WHERE id=? AND user_id=?');
        $stmt->execute([$id,$this->uid]);$row=$stmt->fetch();
        $this->pdo->prepare('DELETE FROM investments WHERE id=? AND user_id=? AND card_id=?')->execute([$id,$this->uid,$card?:($row['card_id']??0)]);
        if($row){$this->pdo->prepare('UPDATE credit_cards SET balance_amount=balance_amount+? WHERE id=? AND user_id=?')->execute([$row['invested_amount'],$row['card_id'],$this->uid]);}
        Response::json(['success'=>true]);}}
