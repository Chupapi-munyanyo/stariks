<?php
namespace Controllers;

use Core\Database;
use Core\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController
{
    private $pdo;
    private int $uid;

    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            Response::json(['success' => false, 'message' => 'Nav autentificēts'], 401);
        }
        $this->uid = (int)$_SESSION['user_id'];
        $this->pdo = Database::get();

        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) require_once $autoload;
    }

    public function excel()
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::json(['success' => false, 'message' => 'Fails nav augšupielādēts'], 400);
        }
        $f = $_FILES['file'];
        if ($f['type'] !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            Response::json(['success' => false, 'message' => 'Atļauts tikai .xlsx fails'], 400);
        }

        $tmp = $f['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($tmp);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Nevar nolasīt Excel: ' . $e->getMessage()], 400);
        }

        $this->pdo->beginTransaction();
        try {
            $this->importCards($spreadsheet->getSheetByName('cards') ?? $spreadsheet->getSheet(0));
            $card=(int)($_POST['card_id'] ?? 0);
            if($card<=0){
                
                $cardStmt=$this->pdo->prepare('SELECT id FROM credit_cards WHERE user_id=? ORDER BY id LIMIT 1');
                $cardStmt->execute([$this->uid]);
                $card=(int)$cardStmt->fetchColumn();
            } else {
                
                $chk=$this->pdo->prepare('SELECT 1 FROM credit_cards WHERE id=? AND user_id=?');
                $chk->execute([$card,$this->uid]);
                if(!$chk->fetchColumn()) {
                    
                    $cardStmt=$this->pdo->prepare('SELECT id FROM credit_cards WHERE user_id=? ORDER BY id LIMIT 1');
                    $cardStmt->execute([$this->uid]);
                    $card=(int)$cardStmt->fetchColumn();
                }
            }
            $this->importTransactions($spreadsheet->getSheetByName('transactions') ?? $spreadsheet->getSheet(1),$card);
            $this->importInvestments($spreadsheet->getSheetByName('investments') ?? $spreadsheet->getSheet(2),$card);
            $this->pdo->commit();
            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            Response::json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function importCards($sheet): void
    {
        if (!$sheet) return;
        foreach ($sheet->toArray(null, true, true, true) as $i => $row) {
            if ($i === 1) continue; 
            [$bank, $last4, $bal] = [$row['B'] ?? '', $row['C'] ?? '', $row['D'] ?? ''];
            if (!$bank || !$last4) continue;
            $stmt = $this->pdo->prepare('INSERT INTO credit_cards(user_id,bank_name,last4,balance_amount) VALUES (?,?,?,?)');
            $stmt->execute([$this->uid, $bank, substr($last4, -4), (float)$bal]);
        }
    }

    private function importTransactions($sheet, int $cardId): void
    {
        if (!$sheet) return;
        $catStmt = $this->pdo->prepare('SELECT id FROM categories WHERE user_id=? AND card_id=? AND type=? AND label=?');
        $catInsert = $this->pdo->prepare('INSERT INTO categories(user_id,card_id,type,label) VALUES (?,?,?,?)');
        $txInsert = $this->pdo->prepare('INSERT INTO transactions(user_id,card_id,category_id,amount,happened_on,note) VALUES (?,?,?,?,?,?)');
        foreach ($sheet->toArray(null, true, true, true) as $i => $row) {
            if ($i === 1) continue;
            [$label, $type, $amount, $date, $note] = [$row['B'] ?? '', strtolower($row['C'] ?? ''), $row['D'] ?? 0, $row['E'] ?? '', $row['F'] ?? null];
            if (!$label || !in_array($type, ['income', 'expense'])) continue;
            $catStmt->execute([$this->uid, $cardId, $type, $label]);
            $cid = $catStmt->fetchColumn();
            if (!$cid) {
                $catInsert->execute([$this->uid,$cardId, $type, $label]);
                $cid = $this->pdo->lastInsertId();
            }
            $txInsert->execute([$this->uid,$cardId, $cid, (float)$amount, $date ?: date('Y-m-d'), $note]);
        }
    }

    private function importInvestments($sheet, int $cardId): void
    {
        if (!$sheet) return;
        $stmt = $this->pdo->prepare('INSERT INTO investments(user_id,card_id,ticker,name,type,invested_amount,quantity,current_value) VALUES (?,?,?,?,?,?,?,?)');
        foreach ($sheet->toArray(null, true, true, true) as $i => $row) {
            if ($i === 1) continue;
            [$ticker, $name, $type, $invested, $qty, $cur] = [$row['B'] ?? '', $row['C'] ?? '', strtolower($row['D'] ?? 'stocks'), $row['E'] ?? 0, $row['F'] ?? 0, $row['G'] ?? 0];
            if (!$ticker) continue;
            $stmt->execute([$this->uid,$cardId, $ticker, $name, $type, (float)$invested, (float)$qty, (float)$cur]);
        }
    }
}
