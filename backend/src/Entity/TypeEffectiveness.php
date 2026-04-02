<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'type_effectiveness')]
class TypeEffectiveness
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'attacking_type_id', nullable: false, onDelete: 'RESTRICT')]
    private Type $attackingType;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'defending_type_id', nullable: false, onDelete: 'RESTRICT')]
    private Type $defendingType;

    #[ORM\Column(name: 'multiplier_x100', type: 'smallint')]
    private int $multiplierX100;

    public function getAttackingType(): Type
    {
        return $this->attackingType;
    }

    public function setAttackingType(Type $attackingType): self
    {
        $this->attackingType = $attackingType;

        return $this;
    }

    public function getDefendingType(): Type
    {
        return $this->defendingType;
    }

    public function setDefendingType(Type $defendingType): self
    {
        $this->defendingType = $defendingType;

        return $this;
    }

    public function getMultiplierX100(): int
    {
        return $this->multiplierX100;
    }

    public function setMultiplierX100(int $multiplierX100): self
    {
        $this->multiplierX100 = $multiplierX100;

        return $this;
    }
}

