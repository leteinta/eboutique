<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\LigneCommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PanierController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    #[Route('/panier', name: 'panier')]
    public function panier(CategorieRepository $categorieRepository): Response
    {
        // Récupérer l'utilisateur connecté
        /** @var User|null $user */
        $user = $this->getUser();

        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            // Gérer le cas où l'utilisateur n'est pas connecté
            // Rediriger l'utilisateur vers la page de connexion, par exemple
            return $this->redirectToRoute('app_login');
        }

        // Récupérer la commande de l'utilisateur
        $commande = $user->getCommande();

        // Si l'utilisateur n'a pas de commande, en créer une nouvelle
        if (!$commande) {
            $commande = new Commande();
            // Associer la commande à l'utilisateur
            $user->setCommande($commande);
                   
        }

        // Récupérer les lignes de commande associées à cette commande
        $ligneCommandes = $commande->getLigneCommandes();
        $categories = $categorieRepository->findAll();
        
        $totalPanier = 0;
        foreach ($ligneCommandes as $ligneCommande) {
        $totalPanier += $ligneCommande->getQuantite() * $ligneCommande->getPrixUnitaire();
    }
        
        // Afficher la page du panier
        return $this->render('panier/index.html.twig', [
            'commande' => $commande,
            'ligneCommandes' => $ligneCommandes,
            'categories' => $categories,
            'totalPanier' => $totalPanier,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'ajouter_produit_panier' , methods: ['POST'])]
    public function ajouterProduitPanier(Request $request, ProduitRepository $produitRepository): Response
    {
        // Récupérer l'utilisateur connecté
        /** @var User|null $user */
        $user = $this->getUser();

        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            // Gérer le cas où l'utilisateur n'est pas connecté
            // Rediriger l'utilisateur vers la page de connexion
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'id du produit à ajouter depuis la requête
        $produitId = $request->request->get('produit_id');

        // Récupérer le produit depuis la base de données
        $produit = $produitRepository->find($produitId);

        // Si le produit n'existe pas, rediriger vers une page d'erreur ou afficher un message d'erreur
        if (!$produit) {
            // Gérer le cas où le produit n'existe pas
            // Rediriger l'utilisateur vers une page d'erreur, par exemple
            return $this->redirectToRoute('page_not_found');
        }

        // Récupérer la commande de l'utilisateur
        $commande = $user->getCommande();

        // Si l'utilisateur n'a pas de commande, en créer une nouvelle
        if (!$commande) {
            $commande = new Commande();
            // Associer la commande à l'utilisateur
            $user->setCommande($commande);
        }

        // Vérifier si le produit est déjà présent dans le panier
        $ligneCommande = null;
        foreach ($commande->getLigneCommandes() as $ligne) {
            if ($ligne->getProduit()->getId() === $produit->getId()) {
                $ligneCommande = $ligne;
                break;
            }
        }

        // Si le produit est déjà présent dans le panier, augmenter sa quantité
        if ($ligneCommande) {
            $ligneCommande->setQuantite($ligneCommande->getQuantite() + 1);
            
        } else {
            // Sinon, créer une nouvelle ligne de commande pour ce produit
            $ligneCommande = new LigneCommande();
            $ligneCommande->setQuantite(1);
            $ligneCommande->setPrixUnitaire($produit->getPrix());
            $ligneCommande->setProduit($produit);
            $ligneCommande->setCommande($commande);

            // Ajouter la nouvelle ligne de commande à la commande de l'utilisateur
            $commande->addLigneCommande($ligneCommande);

        }
        $this->entityManager->persist($ligneCommande);
        $this->entityManager->flush();
        

        // Rediriger l'utilisateur vers la page du panier après l'ajout du produit
        return $this->redirectToRoute('panier');
    }

    #[Route('/modifier-quantite/{id}', name: 'modifier_quantite_panier', methods: ['POST'])]
    public function modifierQuantite(Request $request, LigneCommandeRepository $ligneCommandeRepository): Response
    {
        $ligneCommandeId = $request->attributes->get('id');
        $quantite = $request->request->get('quantite');

        $ligneCommande = $ligneCommandeRepository->find($ligneCommandeId);

        if (!$ligneCommande) {
            return $this->redirectToRoute('page_not_found');
        }

        $ligneCommande->setQuantite($quantite);

        $this->entityManager->flush();

        return $this->redirectToRoute('panier');
    }


    #[Route('/supprimer/{id}', name: 'supprimer_produit_panier', methods: ['POST'])]
    public function supprimerProduit(Request $request, LigneCommandeRepository $ligneCommandeRepository): Response
    {
        // Récupérer l'id de la ligne de commande à supprimer depuis la requête
        $ligneCommandeId = $request->request->get('ligneCommande_id');

        // Récupérer la ligne de commande depuis la base de données
        $ligneCommande = $ligneCommandeRepository->findOneById($ligneCommandeId);
        
        // Suppression de la ligne de commande
        $this->entityManager->remove($ligneCommande);
        $this->entityManager->flush();
        // Rediriger l'utilisateur vers la page du panier après la suppression du produit
        return $this->redirectToRoute('panier');
    }
    #[Route('/confirmation', name: 'confirmation', methods: ['GET'])]
    public function confirmation(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();
        // Afficher la page de confirmation de commande
        return $this->render('panier/confirmation.html.twig',[
            'categories' => $categories,
        ]);
    }
}