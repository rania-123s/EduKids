<?php
declare(strict_types=1);

class Commande
{
    public ?int $id;
    public int $user_id;
    public string $date;
    public float $montant_total;
    public string $statut;

    public function __construct(
        ?int $id,
        int $user_id,
        string $date,
        float $montant_total,
        string $statut
    ) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->date = $date;
        $this->montant_total = $montant_total;
        $this->statut = $statut;
    }
}
