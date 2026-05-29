<?php


class CPlaylist
{

    // Attributs 
    private $idPlaylist;
    private $nom;
    private $idUtilisateur;

    private $imageCouv;

    private $loginUtilisateur; /* Facultatif pour les fonctions qui n'utilisent que les 4 arguments 
                                Obligatoire pour afficher le nom de la personne possédant la playlist dans "Gérer les playlists utilisateurs 
                                quand je suis connecté en tant qu'Admin */

    // Constructeur
    public function __construct($idPlaylist, $nom, $idUtilisateur, $imageCouv, $loginUtilisateur = null)
    {
        $this->idPlaylist = $idPlaylist;
        $this->nom = $nom;
        $this->idUtilisateur = $idUtilisateur;
        $this->imageCouv = $imageCouv;
        $this->loginUtilisateur = $loginUtilisateur;
    }


    // Getters / Setters 
    public function getIdPlaylist()
    {
        return $this->idPlaylist;
    }
    public function getNom()
    {
        return $this->nom;
    }
    public function getIdUtilisateur()
    {
        return $this->idUtilisateur;
    }
    public function setIdPlaylist($id)
    {
        $this->idPlaylist = $id;
    }
    public function setNom($nom)
    {
        $this->nom = $nom;
    }
    public function setIdUtilisateur($id)
    {
        $this->idUtilisateur = $id;
    }

    public function getImageCouv()
    {
        return $this->imageCouv;
    }

    public function setImageCouv($imageCouv)
    {
        $this->imageCouv = $imageCouv;
    }

    public function getLoginUtilisateur()
    {
        return $this->loginUtilisateur;
    }

    public function setLoginUtilisateur($loginUtilisateur)
    {
        $this->loginUtilisateur = $loginUtilisateur;
    }


}
