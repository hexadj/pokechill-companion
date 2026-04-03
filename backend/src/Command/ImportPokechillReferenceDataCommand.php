<?php

declare(strict_types=1);

namespace App\Command;

use App\ReferenceData\Import\PokechillAreasObtainabilityExtractor;
use App\ReferenceData\Import\PokechillEvolutionGraphBuilder;
use App\ReferenceData\Import\PokechillObtainabilityResolver;
use App\ReferenceData\Import\PokechillPokemonExtractor;
use App\ReferenceData\Import\PokechillPokemonKeyLister;
use App\ReferenceData\Import\PokechillPokemonNormalizer;
use App\ReferenceData\Import\PokechillShopMartExtractor;
use App\ReferenceData\Import\PokechillSiblingScriptUrlResolver;
use App\ReferenceData\Import\PokechillWildlifeAndFrontierPoolsParser;
use App\ReferenceData\Import\PokemonReferenceImporter;
use App\ReferenceData\Import\PokechillSourceFetcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:pokechill:import-reference-data',
    description: 'Import reference Pokemon data from Pokechill (admin command).',
)]
final class ImportPokechillReferenceDataCommand extends Command
{
    public function __construct(
        private readonly PokechillSourceFetcher $sourceFetcher,
        private readonly PokechillPokemonExtractor $extractor,
        private readonly PokechillPokemonNormalizer $normalizer,
        private readonly PokemonReferenceImporter $importer,
        private readonly PokechillEvolutionGraphBuilder $evolutionGraphBuilder,
        private readonly PokechillWildlifeAndFrontierPoolsParser $wildlifeAndFrontierPoolsParser,
        private readonly PokechillShopMartExtractor $shopMartExtractor,
        private readonly PokechillAreasObtainabilityExtractor $areasObtainabilityExtractor,
        private readonly PokechillObtainabilityResolver $obtainabilityResolver,
        private readonly PokechillPokemonKeyLister $pokemonKeyLister,
        private readonly PokechillSiblingScriptUrlResolver $siblingScriptUrlResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'source',
                null,
                InputOption::VALUE_OPTIONAL,
                'URL GitHub raw or local file path (defaults to env POKECHILL_SOURCE_URL).',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Validate and compute the diff without persisting.',
            )
            ->addOption(
                'disable-missing',
                null,
                InputOption::VALUE_NONE,
                'Disable Pokemon present in DB but missing from the imported dataset.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Pokechill reference data import');

        $source = $input->getOption('source');
        $dryRun = (bool) $input->getOption('dry-run');
        $disableMissing = (bool) $input->getOption('disable-missing');

        if (!\is_string($source) || trim($source) === '') {
            $source = $_ENV['POKECHILL_SOURCE_URL'] ?? '';
        }
        $source = trim((string) $source);

        if ($source === '') {
            $io->error('Missing Pokechill source. Provide --source or set env POKECHILL_SOURCE_URL.');
            return Command::FAILURE;
        }

        try {
            $io->writeln(sprintf('Source: %s', $source));

            $io->section('Fetch');
            $rawJs = $this->sourceFetcher->fetch($source);

            $areasSource = trim((string) ($_ENV['POKECHILL_AREAS_SOURCE_URL'] ?? ''));
            if ($areasSource === '') {
                $areasSource = $this->siblingScriptUrlResolver->siblingFileUrl($source, 'areasDictionary.js');
            }
            $shopSource = trim((string) ($_ENV['POKECHILL_SHOP_SOURCE_URL'] ?? ''));
            if ($shopSource === '') {
                $shopSource = $this->siblingScriptUrlResolver->siblingFileUrl($source, 'shop.js');
            }

            $io->writeln(sprintf('Areas source: %s', $areasSource));
            $io->writeln(sprintf('Shop source: %s', $shopSource));

            $areasJs = $this->sourceFetcher->fetch($areasSource);
            $shopJs = $this->sourceFetcher->fetch($shopSource);

            $io->section('Obtainability (setSearchTags parity)');
            $adjacency = $this->evolutionGraphBuilder->buildUndirectedAdjacency($rawJs);
            $allPkmnKeys = $this->pokemonKeyLister->listAllKeys($rawJs);
            $pools = $this->wildlifeAndFrontierPoolsParser->parse($areasJs);
            $areaRows = $this->areasObtainabilityExtractor->extractRows($areasJs);
            $martKeys = $this->shopMartExtractor->extractMartSourceKeys($shopJs);
            $resolvedObtainability = $this->obtainabilityResolver->resolve(
                $allPkmnKeys,
                $areaRows,
                $pools,
                $martKeys,
                $adjacency,
            );

            $io->section('Extract + normalize');
            $extractionResult = $this->extractor->extract($rawJs);
            $normalized = $this->normalizer->normalize($extractionResult['pokemons']);

            $importPayload = [];
            $unobtainableImported = 0;
            foreach ($normalized as $row) {
                $o = $resolvedObtainability[$row->sourceKey] ?? ['code' => null, 'isObtainable' => true];
                $importPayload[] = $row->withObtainability($o['isObtainable'], $o['code']);
                if ($o['code'] === 'unobtainable') {
                    $unobtainableImported++;
                }
            }

            $io->section('Import');
            $importResult = $this->importer->import($importPayload, $disableMissing, $dryRun);

            $io->success('Import finished.');

            $io->table(
                headers: ['Metric', 'Value'],
                rows: [
                    ['source_pokemon_count', (string) $extractionResult['sourcePokemonCount']],
                    ['extracted_pokemon_count', (string) $extractionResult['extractedPokemonCount']],
                    ['ignored_source_pokemon_count', (string) $extractionResult['ignoredPokemonCount']],
                    ['deduplicated_source_key_count', (string) $importResult['deduplicated']],
                    ['imported_unobtainable_count', (string) $unobtainableImported],
                    ['created', (string) $importResult['created']],
                    ['updated', (string) $importResult['updated']],
                    ['ignored', (string) $importResult['ignored']],
                    ['disabled', (string) $importResult['disabled']],
                    ['dry_run', $dryRun ? 'true' : 'false'],
                    ['disable_missing', $disableMissing ? 'true' : 'false'],
                ],
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
