<?php

class CMusique
{
    // Attributs
    private $idMusique;
    private $titre;
    private $artiste;
    private $album;
    private $duree;
    private $pochette;
    private $dateSortie;
    private $idGenre;
    private $libelleGenre;


    // Constructeur 
    public function __construct($idMusique, $titre, $artiste, $album, $duree, $pochette, $dateSortie, $idGenre, $libelleGenre)
    {
        $this->idMusique = $idMusique;
        $this->titre = $titre;
        $this->artiste = $artiste;
        $this->album = $album;
        $this->duree = $duree;
        $this->pochette = $pochette;
        $this->dateSortie = $dateSortie;
        $this->idGenre = $idGenre;
        $this->libelleGenre = $libelleGenre;
    }


    // Getters et Setters
    public function getIdMusique()
    {

        return $this->idMusique;

    }

    public function setIdMusique($id)
    {
        $this->idMusique = $id;
    }


    public function getTitre()
    {

        return $this->titre;

    }
    public function getArtiste()
    {

        return $this->artiste;

    }

    public function getAlbum()
    {

        return $this->album;

    }

    public function getDuree()
    {

        return $this->duree;

    }

    public function getPochette()
    {

        return $this->pochette;

    }

    public function getDateSortie()
    {

        return $this->dateSortie;

    }

    public function getIdGenre()
    {

        return $this->idGenre;

    }

    public function getLibelleGenre()
    {

        return $this->libelleGenre;

    }


    public function setTitre($titre)
    {
        $this->titre = $titre;
    }
    public function setArtiste($artiste)
    {
        $this->artiste = $artiste;
    }
    public function setAlbum($album)
    {
        $this->album = $album;
    }
    public function setDuree($duree)
    {
        $this->duree = $duree;
    }
    public function setPochette($pochette)
    {
        $this->pochette = $pochette;
    }
    public function setDateSortie($dateSortie)
    {
        $this->dateSortie = $dateSortie;
    }
    public function setIdGenre($idGenre)
    {
        $this->idGenre = $idGenre;
    }
    public function setLibelleGenre($libelleGenre)
    {
        $this->libelleGenre = $libelleGenre;
    }





}

?>