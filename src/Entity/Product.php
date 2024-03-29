<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    public const DEFAULT_TAX_RATE = 20;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Veuillez saisir un nom.')]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $name = null;

    #[ORM\Column(length: 70, nullable: true)]
    #[Assert\Length(max: 70, maxMessage: 'La reference ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $reference = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 150, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Veuillez saisir un prix')]
    private ?float $price = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Company $company = null;
    
    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'productQuotes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Quote $quote = null;

    #[ORM\ManyToOne(targetEntity: Bill::class, inversedBy: 'productBills')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Bill $bill = null;


    #[ORM\Column(type: 'tsvector', nullable: true, options: ['default' => ''])]
    private ?string $searchVector = null;


    public function __construct()
    {
        $this->subCategories = new ArrayCollection();

    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }
    
    public function __toString(): string
    {
        return $this->name;
    }

    public function getSearchVector(): ?string
    {
        return $this->searchVector;
    }

    public function setSearchVector(?string $searchVector): static
    {
        $this->searchVector = $searchVector;

        return $this;
    }


  
}
