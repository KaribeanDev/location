<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Vehicule;
use App\Form\CommandeType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class CommandeController extends AbstractController
{
    #[Route('/admin/commande/list', name: 'app_commande_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): Response
    {
        $commandes = $em->getRepository(Commande::class)->findAll();
        return $this->render('commande/list.html.twig', compact('commandes'));
    }



    #[Route('/admin/commande/new', name: 'app_commande_new')]
    #[Route('/admin/commande/update/{id}', name: 'app_commande_update', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, Commande $commande = null): Response
    {
        if ($commande === null) {
            $now = new \DateTime();

            $tomorrow = new \DateTime();

            $commande = new Commande();
        }

        $form = $this->createForm(CommandeType::class, $commande);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $dt_debut = $form->get("date_heure_depart")->getData();
            $dt_fin = $form->get("date_heure_fin")->getData();
            $interval = $dt_debut->diff($dt_fin);
            $interval->format("%d");
            $nbJours = $interval->days;

            if ($nbJours < 1) {
                $this->addFlash("message", "une reservation doit durer 24h au minimum");
            }


            $listevehiculeLoue = $em->getRepository(Commande::class)->listeVehiculeLoue($dt_debut, $dt_fin);
            $vehicule = $form->get("vehicule")->getData();
            if (in_array($vehicule->getId(), $listevehiculeLoue)) {

                $listevehiculeDisponible = $em->getRepository(Vehicule::class)->findByVehiculeDisponibles($listevehiculeLoue);
                // $listevehiculeDisponible
                $this->addFlash("message", "le véhicule demandé est déjà réservé");
                $this->addFlash("vehicules", ["disponibles" => $listevehiculeDisponible]);
            }


            if (!in_array($vehicule->getId(), $listevehiculeLoue) && $nbJours >= 1) {
                $prix_journalier = $vehicule->getPrixJournalier();

                $commande->setPrixTotal($nbJours * $prix_journalier);
                $em->persist($commande);
                $em->flush();
                return $this->redirectToRoute("app_commande_list");
            }
        }
        return $this->render('commande/new.html.twig', [
            'id' => $commande->getId(),
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/commande/suppr/{id}', name: 'app_commande_suppr', methods: ['POST'])]
    public function delete(Commande $commandeASupprimer, EntityManagerInterface $em): Response
    {
        if ($commandeASupprimer !== null) {
            $em->remove($commandeASupprimer);
            $em->flush();
        }
        return $this->redirectToRoute('app_commande_list');
    }
}
