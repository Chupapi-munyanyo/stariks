<?php
namespace Controllers;
use Core\Database;use Core\Response;use Core\Validator;
class TransactionsController{private $pdo;private int $uid;public function __construct(){session_start();if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);$this->uid=$_SESSION['user_id'];$this->pdo=Database::get();}
public function list(){
    $sql="SELECT t.id, t.amount, t.happened_on, t.note, c.label, c.type, t.card_id FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=?";
    $params=[ $this->uid ];
    $card=(int)($_GET['card_id'] ?? 0);
    if($card>0){
        $sql.=" AND t.card_id=?";
        $params[]=$card;
    }
    $sql.=" ORDER BY t.happened_on DESC, t.id DESC";
    $stmt=$this->pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['success'=>true,'transactions'=>$stmt->fetchAll()]);
}
public function create(){try{$cat=(int)($_POST['category_id']??0);$amount=(float)($_POST['amount']??0);$date=$_POST['happened_on']??'';$note=trim($_POST['note']??'');$card=(int)($_POST['card_id']??0);if(!$cat||!$amount||!$date||!$card) throw new \Exception('Nepilnīgi dati');// insert transaction
            $this->pdo->prepare('INSERT INTO transactions(user_id,card_id,category_id,amount,happened_on,note) VALUES (?,?,?,?,?,?)')->execute([$this->uid,$card,$cat,$amount,$date,$note]);
            
            $typeStmt=$this->pdo->prepare('SELECT type FROM categories WHERE id=? AND user_id=? AND card_id=?');
            $typeStmt->execute([$cat,$this->uid,$card]);
            $row=$typeStmt->fetch();
            if($row){$delta=($row['type']=='income'? $amount : -$amount);
                $this->pdo->prepare('UPDATE credit_cards SET balance_amount=balance_amount+? WHERE user_id=? AND id=?')->execute([$delta,$this->uid,$card]);}
            Response::json(['success'=>true,'message'=>'Transakcija pievienota']);}catch(\Exception $e){Response::json(['success'=>false,'message'=>$e->getMessage()],400);}}
public function delete(){ $id=(int)($_POST['id']??0);$card=(int)($_POST['card_id']??0);
        $row=$this->pdo->prepare('SELECT t.amount,c.type FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.id=? AND t.user_id=? AND t.card_id=?');
        $row->execute([$id,$this->uid,$card]);
        $info=$row->fetch();
        $this->pdo->prepare('DELETE FROM transactions WHERE id=? AND user_id=? AND card_id=?')->execute([$id,$this->uid,$card]);
        if($info){$delta=($info['type']=='income'? -$info['amount'] : $info['amount']);
            $this->pdo->prepare('UPDATE credit_cards SET balance_amount=balance_amount+? WHERE user_id=? AND id=?')->execute([$delta,$this->uid,$card]);}
        Response::json(['success'=>true]);}}
