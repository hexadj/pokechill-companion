<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pokemon')]
class Pokemon
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'source_key', type: 'string', length: 128, unique: true)]
    private string $sourceKey;

    #[ORM\Column(name: 'name', type: 'string', length: 128)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'primary_type_id', nullable: false, onDelete: 'RESTRICT')]
    private Type $primaryType;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'secondary_type_id', nullable: true, onDelete: 'RESTRICT')]
    private ?Type $secondaryType = null;

    #[ORM\Column(name: 'hp', type: 'smallint')]
    private int $hp;

    #[ORM\Column(name: 'atk', type: 'smallint')]
    private int $atk;

    #[ORM\Column(name: 'def', type: 'smallint')]
    private int $def;

    #[ORM\Column(name: 'satk', type: 'smallint')]
    private int $satk;

    #[ORM\Column(name: 'sdef', type: 'smallint')]
    private int $sdef;

    #[ORM\Column(name: 'spe', type: 'smallint')]
    private int $spe;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceKey(): string
    {
        return $this->sourceKey;
    }

    public function setSourceKey(string $sourceKey): self
    {
        $this->sourceKey = $sourceKey;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrimaryType(): Type
    {
        return $this->primaryType;
    }

    public function setPrimaryType(Type $primaryType): self
    {
        $this->primaryType = $primaryType;

        return $this;
    }

    public function getSecondaryType(): ?Type
    {
        return $this->secondaryType;
    }

    public function setSecondaryType(?Type $secondaryType): self
    {
        $this->secondaryType = $secondaryType;

        return $this;
    }

    public function getHp(): int
    {
        return $this->hp;
    }

    public function setHp(int $hp): self
    {
        $this->hp = $hp;

        return $this;
    }

    public function getAtk(): int
    {
        return $this->atk;
    }

    public function setAtk(int $atk): self
    {
        $this->atk = $atk;

        return $this;
    }

    public function getDef(): int
    {
        return $this->def;
    }

    public function setDef(int $def): self
    {
        $this->def = $def;

        return $this;
    }

    public function getSatk(): int
    {
        return $this->satk;
    }

    public function setSatk(int $satk): self
    {
        $this->satk = $satk;

        return $this;
    }

    public function getSdef(): int
    {
        return $this->sdef;
    }

    public function setSdef(int $sdef): self
    {
        $this->sdef = $sdef;

        return $this;
    }

    public function getSpe(): int
    {
        return $this->spe;
    }

    public function setSpe(int $spe): self
    {
        $this->spe = $spe;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }
}

