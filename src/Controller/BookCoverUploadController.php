<?php

namespace App\Controller;

use App\Service\BookCoverUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class BookCoverUploadController extends AbstractController
{
    public function __construct(
        private readonly BookCoverUploadService $bookCoverUploadService,
    ) {
    }

    public function __invoke(int $id, Request $request): Response
    {
        $file = $request->files->get('coverImage')
            ?? $request->files->get('file')
            ?? ($request->files->all() ? array_values($request->files->all())[0] : null);

        if (!$file) {
            throw new BadRequestHttpException('Le champ "coverImage" est requis.');
        }

        $book = $this->bookCoverUploadService->upload($id, $file);

        return new JsonResponse([
            'id' => $book->getId(),
            'coverImagePath' => $book->getCoverImagePath(),
        ]);
    }
}
