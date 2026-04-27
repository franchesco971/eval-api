<?php

namespace App\Controller;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class BookCoverUploadController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly string $coverImagesDir,
    ) {
    }

    public function __invoke(int $id, Request $request): Response
    {
        $book = $this->em->getRepository(Book::class)->find($id);

        if (!$book) {
            throw new NotFoundHttpException('Livre introuvable.');
        }

        $file = $request->files->get('coverImage')
            ?? $request->files->get('file')
            ?? ($request->files->all() ? array_values($request->files->all())[0] : null);

        if (!$file) {
            throw new BadRequestHttpException('Le champ "coverImage" est requis.');
        }

        $book->setCoverImageFile($file);

        $errors = $this->validator->validate($book);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

        // Supprimer l'ancienne image si elle existe
        if ($book->getCoverImagePath()) {
            $oldFile = $this->coverImagesDir.'/'.$book->getCoverImagePath();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $fileName = sprintf('%s.%s', bin2hex(random_bytes(16)), $file->guessExtension());
        $file->move($this->coverImagesDir, $fileName);

        $book->setCoverImageFile(null);
        $book->setCoverImagePath($fileName);
        $book->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return new JsonResponse([
            'id' => $book->getId(),
            'coverImagePath' => $book->getCoverImagePath(),
        ]);
    }
}
