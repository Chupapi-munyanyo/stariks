<?php
namespace Controllers;
use Core\Response;
use PDO;

class UserController {
    private $pdo;
    private $uid;
    public function __construct() {
        require_once __DIR__.'/../config/auth.php';
        $this->pdo = require __DIR__.'/../config/db.php';
        $this->uid = auth_check(); // expects function to return user id or error out
    }

    // GET /user/me – public profile
    public function me() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::json(['success'=>false,'message'=>'Method not allowed'],405);
        }
        $stmt = $this->pdo->prepare('SELECT id, name, email, DATE(created_at) AS registered_at FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$this->uid]);
        $user=$stmt->fetch(PDO::FETCH_ASSOC);
        if($user){
            Response::json(['success'=>true,'user'=>$user]);
        } else {
            Response::json(['success'=>false,'message'=>'Not found'],404);
        }
    }

    // GET/POST /user/profile
    public function profile() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $stmt = $this->pdo->prepare('SELECT name, email FROM users WHERE id=?');
            $stmt->execute([$this->uid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                Response::json(['success'=>true,'user'=>$user]);
            } else {
                Response::json(['success'=>false,'message'=>'Lietotājs nav atrasts']);
            }
                } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $name   = trim($_POST['name'] ?? '');
            $email  = trim($_POST['email'] ?? '');
            $newPwd = trim($_POST['password'] ?? '');
            $oldPwd = trim($_POST['old_password'] ?? '');

            if (!$name || !$email) {
                Response::json(['success'=>false,'message'=>'Vārds un e-pasts ir obligāti']);
            }

            // fetch current data
            $stmt = $this->pdo->prepare('SELECT name, email, password FROM users WHERE id=?');
            $stmt->execute([$this->uid]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                Response::json(['success'=>false,'message'=>'Lietotājs nav atrasts'],404);
            }

            // verify current password
            if (!password_verify($oldPwd, $current['password'])) {
                Response::json(['success'=>false,'message'=>'Nepareiza pašreizējā parole'],401);
            }

            // build dynamic SET parts only for changed fields
            $set = [];
            $params = [];
            if ($name !== $current['name']) {
                $set[] = 'name=?';
                $params[] = $name;
            }
            if ($email !== $current['email']) {
                $set[] = 'email=?';
                $params[] = $email;
            }
            if ($newPwd) {
                $set[] = 'password=?';
                $params[] = password_hash($newPwd, PASSWORD_DEFAULT);
            }

            if (empty($set)) {
                // nothing to update
                Response::json(['success'=>true,'message'=>'Nav izmaiņu']);
            }

            $sql = 'UPDATE users SET '.implode(', ', $set).' WHERE id=?';
            $params[] = $this->uid;

            try {
                error_log('Profile SQL: '.$sql.' | params: '.json_encode($params));
                $stmt = $this->pdo->prepare($sql);
                                $ok = $stmt->execute($params);
                if(!$ok){
                    $info = $stmt->errorInfo();
                    $msg = implode(' | ', $info);
                    Response::json(['success'=>false,'message'=>$msg],500);
                }
                Response::json(['success'=>true]);
            } catch (\PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    Response::json(['success'=>false,'message'=>'E-pasta adrese jau tiek izmantota'],409);
                }
                error_log('Profile update error: '.$ex->getMessage());
                Response::json(['success'=>false,'message'=>'DB error: '.$ex->getMessage()],500);
            }
        }
    }
}
