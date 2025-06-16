<?php
namespace Controllers;
use Core\Database;use Core\Response;

class ReceiptsController{
    private $pdo;private int $uid;
    public function __construct(){
        session_start();
        if(!isset($_SESSION['user_id'])) Response::json(['success'=>false,'message'=>'Nav autentificēts'],401);
        $this->uid=$_SESSION['user_id'];
        $this->pdo=Database::get();
    }

    private function periodBounds(string $type): array{
        $today=new \DateTime();
        if($type==='week'){
            $start=clone $today; $start->modify('monday this week');
            $end=clone $start; $end->modify('+6 days');
        }elseif($type==='year'){
            $start= new \DateTime($today->format('Y-01-01'));
            $end  = new \DateTime($today->format('Y-12-31'));
        }else{ 
            $start=new \DateTime($today->format('Y-m-01'));
            $end=clone $start; $end->modify('last day of this month');
        }
        return [$start->format('Y-m-d'),$end->format('Y-m-d')];
    }

    public function data(){
        $type=$_GET['period']??'month';
        $card=(int)($_GET['card_id']??0);
        [$from,$to]=$this->periodBounds($type);

        
        $stmt=$this->pdo->prepare("SELECT b.id,b.period,c.label,c.id AS cat_id,b.limit_amount,IFNULL(SUM(t.amount),0) spent
            FROM budgets b
            JOIN categories c ON c.id=b.category_id AND c.user_id=b.user_id
            LEFT JOIN transactions t ON t.category_id=b.category_id AND t.user_id=b.user_id AND t.happened_on BETWEEN ? AND ?
            WHERE b.user_id=? AND b.period BETWEEN DATE_FORMAT(?, '%Y-%m') AND DATE_FORMAT(?, '%Y-%m')".
            ($card>0?" AND b.card_id=?":"").
            " GROUP BY b.id, c.id");
        $tmpParams=[$from,$to,$this->uid,$from,$to];
        if($card>0) $tmpParams[]=$card;
        $stmt->execute($tmpParams);
        $budgets=$stmt->fetchAll();
        
        $txStmt=$this->pdo->prepare("SELECT id,note AS description,amount,happened_on FROM transactions WHERE user_id=? AND category_id=? AND happened_on BETWEEN ? AND ?".($card>0?" AND card_id=?":"")." ORDER BY happened_on");
        foreach($budgets as &$b){
            $txParams=[$this->uid,$b['cat_id'],$from,$to];
            if($card>0) $txParams[]=$card;
            $txStmt->execute($txParams);
            $b['transactions']=$txStmt->fetchAll();
            $b['remaining']=$b['limit_amount']-$b['spent'];
        }

        
        $inv=$this->pdo->prepare("SELECT id,ticker,name,quantity,invested_amount,current_value,(current_value-invested_amount) diff FROM investments WHERE user_id=?".($card>0?" AND card_id=?":""));
        $invParams=[$this->uid];
        if($card>0) $invParams[]=$card;
        $inv->execute($invParams);
        $investments=$inv->fetchAll();

        
        $tx=$this->pdo->prepare("SELECT t.id,c.label AS category,t.amount,t.happened_on,t.note FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND t.happened_on BETWEEN ? AND ?".($card>0?" AND t.card_id=?":"")." ORDER BY t.happened_on");
        $txParams=[$this->uid,$from,$to];
        if($card>0) $txParams[]=$card;
        $tx->execute($txParams);
        $transactions=$tx->fetchAll();

        Response::json(['success'=>true,'from'=>$from,'to'=>$to,'budgets'=>$budgets,'investments'=>$investments,'transactions'=>$transactions]);
    }

    public function export(){
        $period=$_GET['period']??'month';
        $card=(int)($_GET['card_id']??0);
        $report=$_GET['report']??'all'; 
        $type=$_GET['type']??'xlsx'; 
        [$from,$to]=$this->periodBounds($period);

        $data=['budgets'=>[], 'investments'=>[], 'transactions'=>[]];
        if(in_array($report,['budgets','all'])){
            $stmt=$this->pdo->prepare("SELECT c.label,b.limit_amount,IFNULL(SUM(t.amount),0) spent,(b.limit_amount-IFNULL(SUM(t.amount),0)) remaining
                FROM budgets b
                JOIN categories c ON c.id=b.category_id AND c.user_id=b.user_id
                LEFT JOIN transactions t ON t.category_id=b.category_id AND t.user_id=b.user_id AND t.happened_on BETWEEN ? AND ?
                WHERE b.user_id=? AND b.period BETWEEN DATE_FORMAT(?, '%Y-%m') AND DATE_FORMAT(?, '%Y-%m')".
                 ($card>0?" AND b.card_id=?":"").
                 " GROUP BY b.id, c.id, b.limit_amount, c.label");
            $tmpParams=[$from,$to,$this->uid,$from,$to];
        if($card>0) $tmpParams[]=$card;
        $stmt->execute($tmpParams);
            $data['budgets']=$stmt->fetchAll();
        }
        if(in_array($report,['investments','all'])){
            $invStmt=$this->pdo->prepare("SELECT ticker,name,quantity,invested_amount,current_value,(current_value-invested_amount) diff FROM investments WHERE user_id=?".($card>0?" AND card_id=?":""));
            $iParams=[$this->uid]; if($card>0) $iParams[]=$card; $invStmt->execute($iParams);
            $data['investments']=$invStmt->fetchAll();
        }
        if(in_array($report,['transactions','all'])){
            $txStmt=$this->pdo->prepare("SELECT t.happened_on,c.label,t.amount,t.note FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND t.happened_on BETWEEN ? AND ?".($card>0?" AND t.card_id=?":"")." ORDER BY t.happened_on");
            $txP=[$this->uid,$from,$to]; if($card>0) $txP[]=$card; $txStmt->execute($txP);
            $data['transactions']=$txStmt->fetchAll();
        }

        if($type==='doc'){
            header('Content-Type: application/msword');
            header('Content-Disposition: attachment; filename="report_'.date('Ymd').'.doc"');
            echo "<html><body>";
            echo "<h2>Atskaite (".htmlspecialchars($from).' - '.htmlspecialchars($to).")</h2>";
            foreach(['budgets'=>'Budžeti','investments'=>'Investīcijas','transactions'=>'Transakcijas'] as $key=>$label){
                if(!$data[$key]) continue;
                echo "<h3>".$label."</h3><table border='1' cellpadding='4' cellspacing='0'>";
                if($key==='budgets'){
                    echo "<tr><th>Kategorija</th><th>Limits</th><th>Izlietots</th><th>Atlikums</th></tr>";
                    foreach($data[$key] as $row){
                        echo "<tr><td>{$row['label']}</td><td>{$row['limit_amount']}</td><td>{$row['spent']}</td><td>{$row['remaining']}</td></tr>";
                    }
                }elseif($key==='investments'){
                    echo "<tr><th>Ticker</th><th>Nosaukums</th><th>Daudzums</th><th>Ieguldīts</th><th>Pašreizējā vērtība</th><th>Peļņa/Zaudējums</th></tr>";
                    foreach($data[$key] as $row){
                        echo "<tr><td>{$row['ticker']}</td><td>{$row['name']}</td><td>{$row['quantity']}</td><td>{$row['invested_amount']}</td><td>{$row['current_value']}</td><td>{$row['diff']}</td></tr>";
                    }
                }else{ 
                    echo "<tr><th>Datums</th><th>Kategorija</th><th>Summa</th><th>Piezīme</th></tr>";
                    foreach($data[$key] as $row){
                        $note=htmlspecialchars($row['note']??'');
                        echo "<tr><td>{$row['happened_on']}</td><td>{$row['category']}</td><td>{$row['amount']}</td><td>{$note}</td></tr>";
                    }
                }
                echo "</table><br/>";
            }
            echo "</body></html>";
            exit;
        }
        $autoload=__DIR__.'/../vendor/autoload.php';
        if(!file_exists($autoload)) Response::json(['success'=>false,'message'=>'Composer autoload nenoklāts'],500);
        require_once $autoload;
        $spreadsheet=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheetIndex=0;
        foreach(['budgets'=>'Budžeti','investments'=>'Investīcijas','transactions'=>'Transakcijas'] as $key=>$label){
            if(!$data[$key]) continue;
            if($sheetIndex>0) $spreadsheet->createSheet();
            $sheet=$spreadsheet->setActiveSheetIndex($sheetIndex);
            $sheet->setTitle(mb_substr($label,0,31));
            if($key==='budgets'){
                $sheet->fromArray(['Kategorija','Limits','Izlietots','Atlikums'],null,'A1');
                foreach($data[$key] as $i=>$row){
                    $sheet->fromArray([$row['label'],$row['limit_amount'],$row['spent'],$row['remaining']],null,'A'.($i+2));
                }
            }elseif($key==='investments'){
                $sheet->fromArray(['Ticker','Nosaukums','Daudzums','Ieguldīts','Pašreizējā vērtība','Peļņa/Zaudējums'],null,'A1');
                foreach($data[$key] as $i=>$row){
                    $sheet->fromArray([$row['ticker'],$row['name'],$row['quantity'],$row['invested_amount'],$row['current_value'],$row['diff']],null,'A'.($i+2));
                }
            }else{
                $sheet->fromArray(['Datums','Kategorija','Summa','Piezīme'],null,'A1');
                foreach($data[$key] as $i=>$row){
                    $sheet->fromArray([$row['happened_on'],$row['category'],$row['amount'],$row['note']],null,'A'.($i+2));
                }
            }
            $sheetIndex++;
        }
        $writer=new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        if(ob_get_length()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="report_'.date('Ymd').'.xlsx"');
        $writer->save('php://output');
        exit;
    }

}
