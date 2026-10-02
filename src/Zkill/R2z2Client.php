<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use RuntimeException;

final class R2z2Client implements FeedClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://r2z2.zkillboard.com/ephemeral',
        private readonly string $userAgent = 'CrimsonHarvest/1.0 (+https://eve-crimson-pvp.development)',
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    public function startingSequence(): int
    {
        $data = $this->getJson($this->baseUrl . '/sequence.json');
        $sequence = filter_var($data['sequence'] ?? null, FILTER_VALIDATE_INT);
        if ($sequence === false || $sequence < 1) {
            throw new RuntimeException('R2Z2 sequence.json did not contain a valid sequence.');
        }
        return $sequence;
    }

    public function fetchSequence(int $sequence): ?array
    {
        $response = $this->request($this->baseUrl . '/' . $sequence . '.json');
        if ($response['status'] === 404) {
            return null;
        }
        if ($response['status'] !== 200) {
            throw new RuntimeException('R2Z2 returned HTTP ' . $response['status'] . '.');
        }
        $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('R2Z2 returned a non-object payload.');
        }
        return $data;
    }

    public function messages(?string $cursor): iterable
    {
        $sequence = $cursor === null ? $this->startingSequence() : (int) $cursor;
        while (true) {
            $payload = $this->fetchSequence($sequence);
            if ($payload === null) {
                sleep(6);
                continue;
            }
            yield ['sequence' => $sequence, 'payload' => $payload];
            $sequence++;
        }
    }

    private function getJson(string $url): array
    {
        $response = $this->request($url);
        if ($response['status'] !== 200) {
            throw new RuntimeException('R2Z2 returned HTTP ' . $response['status'] . '.');
        }
        return json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return array{status: int, body: string} */
    private function request(string $url): array
    {
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => $this->timeoutSeconds,
            'ignore_errors' => true,
            'header' => "User-Agent: {$this->userAgent}\r\nAccept: application/json\r\n",
        ]]);
        $body = file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }
        if ($body === false) {
            throw new RuntimeException('Unable to retrieve ' . $url . '.');
        }
        return ['status' => $status, 'body' => $body];
    }
}
