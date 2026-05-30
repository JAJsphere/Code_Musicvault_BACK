<?php 

class CDao {

    // Porte d'entrée vers la BDD

    // Attributs
    private $pdo;   


    public function __construct() {

        // Connexion à la BDD
        // $this->pdo = new PDO('mysql:host=localhost;dbname=musicvault;charset=utf8', 'root', 'P@ssw0rd');
        $this->pdo = new PDO('mysql:host=172.20.126.2;dbname=musicvault;charset=utf8', 'apiUser', 'Jadeuser123@!!');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }


    // Getter -> Lire la connexion à la BDD (pour les autres classes)
    public function getPdo(){

        return $this->pdo;  

    }


}