<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_home')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('userId');

        if (!$id) {
            throw new \Exception('User ID is required');
        }

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('profile/profile.html.twig', [
            'userInfo' => $user,
        ]);
    }

}
