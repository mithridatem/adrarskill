<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\RegisterType;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountRepository $account,
        private readonly SluggerInterface $slugger
    ) {}

    #[Route('/register', name: 'app_register')]
    public function addUser(Request $request): Response
    {
        $account = new Account();
        $form = $this->createForm(RegisterType::class, $account);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $imgFile = $form->get('img')->getData();

            if($imgFile) {
                $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imgFile->guessExtension();

                try {
                    $imgFile->move(
                        $this->getParameter('avatar_dir'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception($e->getMessage());
                }

                $account->setImg($newFilename);
            }
            $account->setRoles(['ROLE_USER']);
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        }
        return $this->render('register/index.html.twig', [
            'form' => $form,
        ]);
    }
}
