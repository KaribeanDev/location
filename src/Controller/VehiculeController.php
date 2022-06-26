<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Form\VehiculeType;
use App\Services\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;




#[Route('/vehicule')]
class VehiculeController extends AbstractController
{

    private $em;
    private $imgService;

    public function __construct(EntityManagerInterface $em, ImageService $imgService)
    {
        $this->em = $em;
        $this->imgService = $imgService;
    }
 
    #[Route('/', name: 'app_vehicule_list', methods: ['GET'])]
    public function list(): Response
    {
        $vehicules = $this->em->getRepository(Vehicule::class)->findAll();
        return $this->render('vehicule/list.html.twig', [
            'vehicules' =>$vehicules
        ]);
    }


    #[Route('/update{id}', name: 'app_vehicule_update', methods: ['GET'])]
    public function show(Request $request , $id): Response
    {
        $vehicule = $this->em->getRepository(Vehicule::class)->find($id);
        if($vehicule === null) return $this->redirectToRoute("vehicule_list"); 

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){

            // récupérer le fichier 
            // le nommer le déplacer 
            //$file = $request->files->get("vehicule")["photo"];
            $file = $form["photo"]->getData();
            if($file){
                $this->imgService->updateImage($file , $vehicule );
            }
           
            $this->em->persist($vehicule);
            $this->em->flush();
            return $this->redirectToRoute("vehicule_list");
        }
        return $this->render('vehicule/show.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $vehicule = new Vehicule();
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){

            $file = $form["photo"]->getData();

            $this->imgService->moveImage($file , $vehicule );

            $this->em->persist($vehicule);
            $this->em->flush();
            return $this->redirectToRoute("app_vehicule_list");        }

        return $this->render('vehicule/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'app_vehicule_suppr', methods: ['POST'])]
    public function suppr($id): RedirectResponse
    {
        $vehiculeASupprimer = $this->em->getRepository(Vehicule::class)->find($id);
        if($vehiculeASupprimer){
           
            $this->imgService->deleteImage($vehiculeASupprimer);
            $this->em->remove($vehiculeASupprimer);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_vehicule_list');
    }
}
