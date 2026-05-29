<?php

class MusiquePlaylist
{
    
    // Attributs
    private $idMusique;
    private $idPlaylist;
    private $titre;


    // Constructeurs
    public function __construct($idMusique, $idPlaylist)
    {
        $this->idMusique = $idMusique;
        $this->idPlaylist = $idPlaylist;

    }

    // Getters / Setters
    public function getIdMusique()
    {
        return $this->idMusique;
    }
    public function getIdPlaylist()
    {
        return $this->idPlaylist;
    }

    public function setIdMusique($idMusique)
    {
        $this->idMusique = $idMusique;
    }
    public function setIdPlaylist($idPlaylist)
    {
        $this->idPlaylist = $idPlaylist;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($titre)
    {
        $this->titre = $titre;
    }

}