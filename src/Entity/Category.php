<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: "category")]
class Category
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 50)]
    private ?string $name = null;


    #[ORM\Column(
        type: "boolean",
        options: ["default" => 1]
    )]
    private bool $isActive = true;


    #[ORM\OneToMany(mappedBy: "category", targetEntity: Product::class)]
    private Collection $products;



    public function __construct()
    {
        $this->products = new ArrayCollection();
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


    public function isActive(): bool
    {
        return $this->isActive;
    }


    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }


    public function getProducts(): Collection
    {
        return $this->products;
    }
}
