<?php

Class DatabaseUtils
{
    private static $instance = null;
    
    private $pdo;
    
    private function __construct() {
        $this->pdo = new PDO(
            // 'mysql:host=localhost;dbname=ischool','ischool','!@#$ASDvgrrz7*9jqB',
            'mysql:host=127.0.0.1;dbname=ischool','root','hnzf123456',
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
        );
        $this->pdo->setAttribute( PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION );
        $this->pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC);
    }
    
    public static function getInstance()
    {
        if(self::$instance == null)
        {
            self::$instance = new DatabaseUtils();
        }
    
        return self::$instance;
    }
    
    public static function getDatabase()
    {
        return self::getInstance()->pdo;
    }
}