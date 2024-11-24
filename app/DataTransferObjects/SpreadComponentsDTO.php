<?php

namespace App\DataTransferObjects;

class SpreadComponentsDTO
{
    public readonly float $fpi;
    public readonly float $elo;
    public readonly float $sagarin;
    public readonly float $advanced;

    public function __construct(array $data)
    {
        $this->fpi = $data['fpi'];
        $this->elo = $data['elo'];
        $this->sagarin = $data['sagarin'];
        $this->advanced = $data['advanced'];
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return [
            'fpi' => $this->fpi,
            'elo' => $this->elo,
            'sagarin' => $this->sagarin,
            'advanced' => $this->advanced
        ];
    }
}