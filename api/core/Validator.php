<?php
namespace Core;
class Validator{
    public static function email(string $e):string{
        $e=trim($e);
        if(!filter_var($e,FILTER_VALIDATE_EMAIL)) throw new \Exception('Nederīgs e-pasts');
        return $e;
    }
    public static function password(string $p):string{
        if(strlen($p)<6) throw new \Exception('Parole par īsu');
        return $p;
    }
    public static function string(string $v,string $field,int $max=100):string{
        $v=trim($v);
        if($v==='') throw new \Exception($field.' ir obligāts');
        if(mb_strlen($v)>$max) throw new \Exception($field.' par garu');
        return $v;
    }
}
