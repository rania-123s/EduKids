<?php
declare(strict_types=1);

class Produit
{
    public ?int $id;
    public string $nom;
    public string $description;
    public float $prix;
    public string $type;

    public function __construct(
        ?int $id,
        string $nom,
        string $description,
        float $prix,
        string $type
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->description = $description;
        $this->prix = $prix;
        $this->type = $type;
    }
}
