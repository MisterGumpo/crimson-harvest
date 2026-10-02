<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use JsonException;
use RuntimeException;

final class HistoricalImporter
{
    public function __construct(
        private readonly R2z2PayloadParser $parser,
        private readonly KillmailStore $store,
    ) {
    }

    public function importFile(string $path): int
    {
        if (!is_file($path)) {
            throw new RuntimeException('Historical import file does not exist: ' . $path);
        }
        try {
            $records = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Historical import file must contain a JSON array.', 0, $exception);
        }
        if (!is_array($records) || !array_is_list($records)) {
            throw new RuntimeException('Historical import file must contain a JSON array of R2Z2 objects.');
        }
        $imported = 0;
        foreach ($records as $index => $record) {
            if (!is_array($record)) {
                continue;
            }
            $sequence = (int) ($record['sequence_id'] ?? $record['sequence'] ?? $index + 1);
            $killmail = $this->parser->normalize($record, $sequence);
            $killmail['data_source'] = 'historical';
            $this->store->import($killmail);
            $imported++;
        }
        return $imported;
    }
}
