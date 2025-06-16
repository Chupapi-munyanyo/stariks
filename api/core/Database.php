<?php
namespace Core;
use PDO;
use PDOException;
class Database{
    private static ?PDO $pdo=null;
    public static function get():PDO{
        if(self::$pdo===null){
            require_once __DIR__.'/../../config/config.php';
            $dsn='mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4';
            try{
                self::$pdo=new PDO($dsn,DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
            }catch(PDOException $e){
                if(!headers_sent()){
                    http_response_code(500);
                    header('Content-Type: application/json');
                }
                echo json_encode(['success'=>false,'message'=>'DB savienojuma kļūda']);
                exit;
            }
        }
        return self::$pdo;
    }
}
