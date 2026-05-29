<?php

class CGenre
{

    // Attributs
    private $idGenre;
    private $libelle;


    // Constructeur 
    public function __construct($idGenre, $libelle)
    {
        $this->idGenre = $idGenre;
        $this->libelle = $libelle;
    }


    // Getters et Setters
    public function getIdGenre()
    {

        return $this->idGenre;

    }

    public function setIdGenre($id)
    {
        $this->idGenre = $id;
    }

    public function getLibelle()
    {

        return $this->libelle;

    }

    public function setLibelle($libelle)
    {

        $this->libelle = $libelle;
    }

}
