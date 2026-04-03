<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Pokemon;
use App\Entity\Type;

final class PokemonTestFactory
{
    public static function type(string $code, ?string $label = null): Type
    {
        $t = new Type();
        $t->setCode($code);
        $t->setLabel($label ?? ucfirst($code));

        return $t;
    }

    public static function pokemon(
        string $sourceKey,
        string $name,
        Type $primary,
        ?Type $secondary,
        int $atk,
        int $def,
        int $satk,
        int $sdef,
        int $hp = 50,
        int $spe = 50,
        bool $active = true,
        bool $isObtainable = true,
    ): Pokemon {
        $p = new Pokemon();
        $p->setSourceKey($sourceKey);
        $p->setName($name);
        $p->setPrimaryType($primary);
        $p->setSecondaryType($secondary);
        $p->setHp($hp);
        $p->setAtk($atk);
        $p->setDef($def);
        $p->setSatk($satk);
        $p->setSdef($sdef);
        $p->setSpe($spe);
        $p->setIsActive($active);
        $p->setIsObtainable($isObtainable);

        return $p;
    }
}
