<?php


class CUtilisateur
{

    // Attributs 
    private $idUtilisateur;
    private $nom;
    private $prenom;
    private $login;
    private $mdpHash;
    private $idRole;
    private $doitChangerMdp;

    // Constructeur
    public function __construct($idUtilisateur, $nom, $prenom, $login, $mdpHash, $idRole, $doitChangerMdp = 1)
    {

        $this->idUtilisateur = $idUtilisateur;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->login = $login;
        $this->mdpHash = $mdpHash;
        $this->idRole = $idRole;
        $this->doitChangerMdp = $doitChangerMdp;

    }


    // Getters / Setters 
    public function getIdUtilisateur()
    {
        return $this->idUtilisateur;
    }
    public function setIdUtilisateur($id)
    {
        $this->idUtilisateur = $id;
    }


    public function getLogin()
    {

        return $this->login;
    }

    public function setLogin($login)
    {

        $this->login = $login;

    }

    public function getMdpHash()
    {
        return $this->mdpHash;
    }

    public function setMdpHash($mdpHash)
    {
        $this->mdpHash = $mdpHash;
    }

    public function getNom()
    {
        return $this->nom;
    }
    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;
    }
    public function getIdRole()
    {
        return $this->idRole;
    }
    public function setIdRole($idRole)
    {
        $this->idRole = $idRole;
    }

    public function getDoitChangerMdp()
    {
        return $this->doitChangerMdp;
    }

    public function setDoitChangerMdp($doitChangerMdp)
    {
        $this->doitChangerMdp = $doitChangerMdp;
    }

}
