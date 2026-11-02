<?php

namespace Traffic;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prints what the visitors do, and sums it up at the end.
 */
final class Journal
{
    /** @var array<string, int> */
    private array $visitsByKind = [];

    /** @var array<int, int> */
    private array $responsesByStatus = [];

    private int $pageCount = 0;
    private int $assetCount = 0;
    private int $orderCount = 0;

    public function __construct(
        private readonly SymfonyStyle $io,
    ) {
    }

    public function visitStarted(Visitor $visitor, string $details = ''): void
    {
        $this->visitsByKind[$visitor->kind] = ($this->visitsByKind[$visitor->kind] ?? 0) + 1;
        $this->write($visitor, '<comment>arrives</comment>' . ('' === $details ? '' : " ({$details})"));
    }

    public function page(Visitor $visitor, string $method, string $path, int $status): void
    {
        ++$this->pageCount;
        $this->count($status);
        $color = match (true) {
            $status >= 500 => 'red',
            $status >= 400 => 'yellow',
            $status >= 300 => 'cyan',
            default => 'green',
        };
        $this->write($visitor, \sprintf('<fg=%s>%d</> %s %s', $color, $status, $method, $path));
    }

    public function asset(int $status): void
    {
        ++$this->assetCount;
        $this->count($status);
    }

    public function order(Visitor $visitor): void
    {
        ++$this->orderCount;
        $this->write($visitor, '<info>places an order</info>');
    }

    public function summarize(): void
    {
        arsort($this->visitsByKind);
        ksort($this->responsesByStatus);

        $this->io->section('Summary');
        $this->io->definitionList(
            ['Visits' => array_sum($this->visitsByKind)],
            ['Requests' => \sprintf('%d pages, %d assets', $this->pageCount, $this->assetCount)],
            ['Orders' => $this->orderCount],
        );
        $this->io->table(['Visitor', 'Visits'], array_map(null, array_keys($this->visitsByKind), $this->visitsByKind));
        $this->io->table(['Status', 'Responses'], array_map(null, array_keys($this->responsesByStatus), $this->responsesByStatus));
    }

    private function count(int $status): void
    {
        $this->responsesByStatus[$status] = ($this->responsesByStatus[$status] ?? 0) + 1;
    }

    private function write(Visitor $visitor, string $message): void
    {
        $this->io->writeln(\sprintf('%s  %-24s %s', date('H:i:s'), $visitor->label(), $message));
    }
}
