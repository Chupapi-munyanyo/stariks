<?php
namespace Controllers;use Core\Database;use Core\Response;class DashboardController{private $pdo;private int $uid;public function __construct(){session_start();if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);$this->uid=$_SESSION['user_id'];$this->pdo=Database::get();}
public function summary(){ $exp=$this->pdo->prepare("SELECT c.label, SUM(t.amount) total FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND c.type='expense' GROUP BY c.label");$exp->execute([$this->uid]);$inc=$this->pdo->prepare("SELECT c.label, SUM(t.amount) total FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND c.type='income' GROUP BY c.label");$inc->execute([$this->uid]);$mon=$this->pdo->prepare("SELECT DATE_FORMAT(happened_on,'%Y-%m') month, SUM(CASE WHEN c.type='income' THEN amount ELSE 0 END) income, SUM(CASE WHEN c.type='expense' THEN amount ELSE 0 END) expense FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? GROUP BY month ORDER BY month DESC LIMIT 12");$mon->execute([$this->uid]);$lat=$this->pdo->prepare("SELECT t.id, c.type, c.label category, t.amount, t.happened_on FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? ORDER BY t.happened_on DESC LIMIT 10");
$lat->execute([$this->uid]);
$bal=$this->pdo->prepare("SELECT COALESCE(SUM(balance_amount),0) FROM credit_cards WHERE user_id=?");
$bal->execute([$this->uid]);
$cardsBalance=$bal->fetchColumn();
Response::json(['success'=>true,'data'=>[
    'expenseByCategory'=>$exp->fetchAll(),
    'incomeByCategory'=>$inc->fetchAll(),
    'monthlyTotals'=>$mon->fetchAll(),
    'latest'=>$lat->fetchAll(),
    'cardsBalance'=>$cardsBalance
]]);} }
