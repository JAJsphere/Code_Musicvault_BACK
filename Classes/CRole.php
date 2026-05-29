<?php 


class CRole {

    // Attributs 
    private $idRole;
    private $libelle;


    // Constructeur
    public function __construct($idRole, $libelle){

    $this->idRole = $idRole;
    $this->libelle = $libelle;

    }


    // Getters et Setters
    public function getIdRole() {
        return $this->idRole;
    }

    public function setIdRole($id) {
        $this->idRole = $id;
    }

    public function getLibelle() {
        return $this->libelle;
    }

    public function setLibelle($libelle) {
        $this->libelle = $libelle;
    }
}