<?php
namespace Controllers;
use Core\Response;
class MarketController{
    private string $cacheDir;
    public function __construct(){
        $this->cacheDir=sys_get_temp_dir().'/stariks_quotes';
        if(!is_dir($this->cacheDir)) mkdir($this->cacheDir,0777,true);
    }
    public function quote(){
        $ticker=strtoupper(trim($_GET['ticker']??''));
        if(!$ticker) Response::json(['success'=>false,'message'=>'Ticker required'],400);
        $cacheFile="$this->cacheDir/{$ticker}.json";
        if(file_exists($cacheFile)&& (time()-filemtime($cacheFile)<300)){ // 5 min cache
            $data=json_decode(file_get_contents($cacheFile),true);
        }else{
            $key=defined('FINNHUB_KEY')?FINNHUB_KEY:'';
            $url="http://127.0.0.1:5001/quote?symbol={$ticker}";
            $json=@file_get_contents($url);
            if($json===false) Response::json(['success'=>false,'message'=>'API error'],502);
            file_put_contents($cacheFile,$json);
            $data=json_decode($json,true);
        }
        $price=floatval($data['price']??0);
        if(!$price) Response::json(['success'=>false,'message'=>'Nav cena'],404);
        Response::json(['success'=>true,'price'=>$price]);
    }

    public function search(){
        $term=trim($_GET['term']??'');
        if(strlen($term)<2) Response::json(['success'=>false,'matches'=>[]]);
        $key=defined('FINNHUB_KEY')?FINNHUB_KEY:'';
        $url="http://127.0.0.1:5001/search?q=".urlencode($term);
        $json=@file_get_contents($url);
        if($json===false){Response::json(['success'=>false,'matches'=>[]],502);}        
        $arr=json_decode($json,true);
        $matches=array_map(fn($m)=>['symbol'=>$m['symbol'],'name'=>$m['name']],$arr['matches']??[]);
        Response::json(['success'=>true,'matches'=>$matches]);
    }
}
