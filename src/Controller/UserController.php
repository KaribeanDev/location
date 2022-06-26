<?php 

namespace App\Controller ;

use App\Entity\User;
use App\Entity\Commande;
use App\Entity\Vehicule;
use App\Form\CommandeType;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController{

    #[Route("/" , name:"app_index")]
    public function index () :Response{
       
        return $this->render("user/index.html.twig");
    }

    #[Route("/search" , name:"app_search")]
    public function search (Request $request , EntityManagerInterface $em ) :Response{
        $dtDebut = new \DateTime($request->request->get("dt_debut"));
        $dtFin = new \DateTime($request->request->get("dt_fin"));
        $listevehiculeLoue = $em->getRepository(Commande::class)->listeVehiculeLoue($dtDebut ,$dtFin );
        $listevehiculeDisponible = $em->getRepository(Vehicule::class)->findByVehiculeDisponibles( $listevehiculeLoue );
        
       // dump($dtDebut , $dtFin, $listevehiculeLoue , $listevehiculeDisponible);

        return $this->render("user/resultats.html.twig" , [
            "vehicules" => $listevehiculeDisponible
        ]);
    }

    #[Route("/louer" , name:"app_rent")]
    public function rent(AuthenticationUtils $authenticationUtils){

        $user = new User();
        $formInscription = $this->createForm(RegistrationFormType::class , $user);

        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render("user/registration.html.twig" , [
            "formInscription" => $formInscription->createView(),
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route("/commande" , name:"app_end")]
    public function commande(){

        $commande = new Commande();

        $form = $this->createForm(CommandeType::class , $commande);

        return $this->render("user/commande.html.twig" , [
            "form" => $form->createView()
        ] );
    }

}