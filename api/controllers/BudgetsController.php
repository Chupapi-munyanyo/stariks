<?php
namespace Controllers;
use Core\Database;use Core\Response;use Core\Validator;
class BudgetsController{private $pdo;private int $uid;public function __construct(){session_start();if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);$this->uid=$_SESSION['user_id'];$this->pdo=Database::get();}
private function period():string{return $_GET['period']??($_POST['period']??date('Y-m'));}
public function list(){ $p=$this->period();$card=(int)($_GET['card_id']??0);
        $sql="SELECT b.id,b.card_id,c.label,c.type,b.limit_amount,IFNULL(SUM(t.amount),0) spent,(b.limit_amount-IFNULL(SUM(t.amount),0)) remaining FROM budgets b JOIN categories c ON c.id=b.category_id AND c.user_id=b.user_id LEFT JOIN transactions t ON t.category_id=b.category_id AND t.user_id=b.user_id AND DATE_FORMAT(t.happened_on,'%Y-%m')=?";
        $params=[$p];
        if($card>0){$sql.=" AND t.card_id=?";$params[]=$card;}
        $sql.=" WHERE b.user_id=? AND b.period=?";
        $params[]=$this->uid;$params[]=$p;
        if($card>0){$sql.=" AND b.card_id=?";$params[]=$card;}
        $sql.=" GROUP BY b.id";
        $stmt=$this->pdo->prepare($sql);
        $stmt->execute($params);Response::json(['success'=>true,'budgets'=>$stmt->fetchAll()]);}
public function create(){
        try{
            $cat=(int)($_POST['category_id']??0);
            $limit=(float)($_POST['limit_amount']??0);
            $card=(int)($_POST['card_id']??0);
            if(!$cat||$limit<=0||!$card) throw new \Exception('Nepareizi dati');
            $period=$_POST['period']??date('Y-m');
            // verify card ownership
            $chk=$this->pdo->prepare('SELECT id FROM credit_cards WHERE id=? AND user_id=?');
            $chk->execute([$card,$this->uid]);
            if(!$chk->fetch()) throw new \Exception('Nepareiza karte');
            $this->pdo->prepare('INSERT INTO budgets(user_id,card_id,category_id,period,limit_amount) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE limit_amount=VALUES(limit_amount)')
                ->execute([$this->uid,$card,$cat,$period,$limit]);
            Response::json(['success'=>true]);
        }catch(\Exception $e){
            Response::json(['success'=>false,'message'=>$e->getMessage()],400);
        }
    }
public function delete(){
        $id=(int)($_POST['id']??0);
        $card=(int)($_POST['card_id']??0);
        $this->pdo->prepare('DELETE FROM budgets WHERE id=? AND user_id=? AND card_id=?')->execute([$id,$this->uid,$card]);
        Response::json(['success'=>true]);
    }}
