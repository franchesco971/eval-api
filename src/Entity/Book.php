<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\BookCoverUploadController;
use App\Repository\BookRepository;
use DateTimeImmutable;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['book:read']],
    denormalizationContext: ['groups' => ['book:write']],
    operations: [
        new GetCollection(),
        new Post(normalizationContext: ['groups' => ['book:write:output']]),
        new Get(),
        new Put(normalizationContext: ['groups' => ['book:write:output']]),
        new Delete(),
        new Post(
            uriTemplate: '/books/{id}/cover',
            controller: BookCoverUploadController::class,
            deserialize: false,
            inputFormats: ['multipart' => ['multipart/form-data']],
            name: 'book_cover_upload',
        ),
    ]
)]
#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'app_book')]
#[ORM\HasLifecycleCallbacks]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['book:read', 'book:write:output'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['book:read', 'book:write', 'book:write:output'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['book:read', 'book:write', 'book:write:output'])]
    private ?int $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['book:read', 'book:write', 'book:write:output'])]
    private ?string $resume = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['book:read', 'book:write:output'])]
    private ?string $coverImagePath = null;

    #[Assert\Image(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png'],
        mimeTypesMessage: 'Seuls les formats JPEG et PNG sont autorisés.',
        maxSizeMessage: 'L\'image ne doit pas dépasser 5 Mo.',
    )]
    private ?File $coverImageFile = null;

    #[ORM\Column]
    #[Groups(['book:read'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['book:read'])]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function initCreatedAt(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateUpdatedAt(PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('createdAt')) {
            $this->createdAt = $args->getOldValue('createdAt');
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

        return $this;
    }

    public function getCoverImagePath(): ?string
    {
        return $this->coverImagePath;
    }

    public function setCoverImagePath(?string $coverImagePath): static
    {
        $this->coverImagePath = $coverImagePath;

        return $this;
    }

    public function getCoverImageFile(): ?File
    {
        return $this->coverImageFile;
    }

    public function setCoverImageFile(?File $coverImageFile): static
    {
        $this->coverImageFile = $coverImageFile;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

}

