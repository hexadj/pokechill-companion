<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use App\Entity\Pokemon;
use App\Entity\Type;
use App\ReferenceData\Dto\PokemonReferenceData;
use App\Repository\PokemonRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Imports normalized Pokemon reference data into the local PostgreSQL DB.
 */
final class PokemonReferenceImporter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PokemonRepository $pokemonRepository,
        private readonly TypeRepository $typeRepository,
    ) {
    }

    /**
     * @param PokemonReferenceData[] $pokemon
     * @return array{
     *   created:int,
     *   updated:int,
     *   ignored:int,
     *   disabled:int,
     *   deduplicated:int,
     *   dryRunApplied:bool
     * }
     */
    public function import(array $pokemon, bool $disableMissing, bool $dryRun): array
    {
        if ($pokemon === []) {
            throw new RuntimeException('Nothing to import.');
        }

        // Defensive: the upstream file may contain multiple occurrences of the same pkmn.<key>.
        // De-duplicate to keep the upsert deterministic and avoid unique constraint violations.
        /** @var array<string, PokemonReferenceData> $bySourceKey */
        $bySourceKey = [];
        foreach ($pokemon as $p) {
            $bySourceKey[$p->sourceKey] = $p;
        }

        $deduplicated = \count($pokemon) - \count($bySourceKey);
        $pokemon = array_values($bySourceKey);

        $sourceKeys = array_values(array_map(static fn (PokemonReferenceData $p): string => $p->sourceKey, $pokemon));

        $existing = $this->pokemonRepository->findBySourceKeys($sourceKeys);
        /** @var array<string, Pokemon> $existing */

        $typeCodes = [];
        foreach ($pokemon as $p) {
            $typeCodes[] = $p->primaryTypeCode;
            if ($p->secondaryTypeCode !== null) {
                $typeCodes[] = $p->secondaryTypeCode;
            }
        }

        $typeCodes = array_values(array_unique(array_map('strtolower', $typeCodes)));
        $typeByCode = $this->typeRepository->findByCodes($typeCodes);

        $missingTypeCodes = array_values(array_diff($typeCodes, array_keys($typeByCode)));
        if ($missingTypeCodes !== []) {
            throw new RuntimeException(sprintf('Unknown type codes in import: %s', implode(', ', $missingTypeCodes)));
        }

        $created = 0;
        $updated = 0;
        $ignored = 0;
        $disabled = 0;

        $this->entityManager->wrapInTransaction(function () use (
            $pokemon,
            $existing,
            $typeByCode,
            $disableMissing,
            $dryRun,
            &$created,
            &$updated,
            &$ignored,
            &$disabled,
            $sourceKeys,
        ): void {
            if ($dryRun) {
                foreach ($pokemon as $p) {
                    $primaryType = $typeByCode[$p->primaryTypeCode];
                    $secondaryType = $p->secondaryTypeCode !== null ? $typeByCode[$p->secondaryTypeCode] : null;

                    if (!isset($existing[$p->sourceKey])) {
                        $created++;
                        continue;
                    }

                    if ($this->requiresImportUpdate($existing[$p->sourceKey], $p, $primaryType, $secondaryType)) {
                        $updated++;
                    } else {
                        $ignored++;
                    }
                }

                if ($disableMissing) {
                    $disabled = (int) $this->entityManager->createQuery(
                        '
                        SELECT COUNT(p.id)
                        FROM ' . Pokemon::class . ' p
                        WHERE p.isActive = true
                        AND p.sourceKey NOT IN (:sourceKeys)
                        '
                    )->setParameter('sourceKeys', $sourceKeys)->getSingleScalarResult();
                }

                return;
            }

            foreach ($pokemon as $p) {
                $primaryType = $typeByCode[$p->primaryTypeCode];
                $secondaryType = $p->secondaryTypeCode !== null ? $typeByCode[$p->secondaryTypeCode] : null;

                if (isset($existing[$p->sourceKey])) {
                    $entity = $existing[$p->sourceKey];
                    if ($this->requiresImportUpdate($entity, $p, $primaryType, $secondaryType)) {
                        $this->applyImportedState($entity, $p, $primaryType, $secondaryType);
                        $updated++;
                    } else {
                        $ignored++;
                    }
                    continue;
                }

                $entity = new Pokemon();
                $entity->setSourceKey($p->sourceKey);
                $this->applyImportedState($entity, $p, $primaryType, $secondaryType);
                $this->entityManager->persist($entity);
                $created++;
            }

            // Flush all upserts before disabling missing Pokemon.
            $this->entityManager->flush();

            if ($disableMissing) {
                // Disable only currently active Pokemon that are absent from the imported set.
                $disabled = $this->disableMissingActive($sourceKeys);
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'ignored' => $ignored,
            'disabled' => $disabled,
            'deduplicated' => $deduplicated,
            'dryRunApplied' => $dryRun,
        ];
    }

    private function requiresImportUpdate(
        Pokemon $entity,
        PokemonReferenceData $pokemon,
        Type $primaryType,
        ?Type $secondaryType,
    ): bool {
        if ($entity->getName() !== $pokemon->name) {
            return true;
        }

        if ($entity->getHp() !== $pokemon->hp
            || $entity->getAtk() !== $pokemon->atk
            || $entity->getDef() !== $pokemon->def
            || $entity->getSatk() !== $pokemon->satk
            || $entity->getSdef() !== $pokemon->sdef
            || $entity->getSpe() !== $pokemon->spe
        ) {
            return true;
        }

        if ($entity->getPrimaryType()->getCode() !== $primaryType->getCode()) {
            return true;
        }

        $currentSecondaryCode = $entity->getSecondaryType()?->getCode();
        $nextSecondaryCode = $secondaryType?->getCode();
        if ($currentSecondaryCode !== $nextSecondaryCode) {
            return true;
        }

        if ($entity->isActive() !== $pokemon->isActive) {
            return true;
        }

        if ($entity->isObtainable() !== $pokemon->isObtainable) {
            return true;
        }

        $currentCode = $entity->getObtainabilityCode();
        $nextCode = $pokemon->obtainabilityCode;
        if ($currentCode !== $nextCode) {
            return true;
        }

        return false;
    }

    private function applyImportedState(
        Pokemon $entity,
        PokemonReferenceData $pokemon,
        Type $primaryType,
        ?Type $secondaryType,
    ): void {
        $entity->setName($pokemon->name);
        $entity->setHp($pokemon->hp);
        $entity->setAtk($pokemon->atk);
        $entity->setDef($pokemon->def);
        $entity->setSatk($pokemon->satk);
        $entity->setSdef($pokemon->sdef);
        $entity->setSpe($pokemon->spe);
        $entity->setPrimaryType($primaryType);
        $entity->setSecondaryType($secondaryType);
        $entity->setIsActive($pokemon->isActive);
        $entity->setIsObtainable($pokemon->isObtainable);
        $entity->setObtainabilityCode($pokemon->obtainabilityCode);
    }

    private function disableMissingActive(array $sourceKeys): int
    {
        if ($sourceKeys === []) {
            return 0;
        }

        // Use DQL bulk update for efficiency and determinism.
        $q = $this->entityManager->createQuery(
            '
            UPDATE ' . Pokemon::class . ' p
            SET p.isActive = false
            WHERE p.isActive = true
            AND p.sourceKey NOT IN (:sourceKeys)
            '
        )->setParameter('sourceKeys', $sourceKeys);

        return $q->execute();
    }
}
