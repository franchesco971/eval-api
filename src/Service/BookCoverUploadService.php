<?php

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookCoverUploadService
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly string $coverImagesDir,
    ) {
    }

    public function upload(int $bookId, File $file): Book
    {
        $book = $this->bookRepository->find($bookId);

        if (!$book) {
            throw new NotFoundHttpException('Livre introuvable.');
        }

        $book->setCoverImageFile($file);

        $errors = $this->validator->validate($book);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

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

        return $book;
    }
}
