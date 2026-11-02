<?php

namespace Traffic;

use Psr\Log\LoggerInterface;

/**
 * Runs the visits side by side, on a single agenda: a visit is a generator that yields how long it waits before its
 * next step, like the time a visitor spends reading a page.
 */
final class Simulation
{
    private const int SECONDS_PER_DAY = 86400;

    /** @var \SplPriorityQueue<array{float, int}, array{float, \Closure(): void}> The events to come, the earliest first */
    private readonly \SplPriorityQueue $agenda;

    /** Keeps the events of the same time in order. */
    private int $eventCount = 0;

    /** @var array<string, int> */
    private array $visitCountByStream = [];

    /**
     * @param list<Stream>                 $streams
     * @param list<\Generator<int, float>> $perpetualVisits
     * @param float                        $speed           Speeds everything up: arrivals and waits
     */
    public function __construct(
        private readonly array $streams,
        private readonly array $perpetualVisits,
        private readonly TrafficCurve $curve,
        private readonly Random $random,
        private readonly LoggerInterface $logger,
        private readonly float $speed = 1,
    ) {
        $this->agenda = new \SplPriorityQueue();
    }

    public function run(float $durationInSeconds): void
    {
        $end = microtime(true) + $durationInSeconds;
        foreach ($this->streams as $stream) {
            $this->scheduleNextArrival($stream);
        }
        foreach ($this->perpetualVisits as $visit) {
            $this->start($visit);
        }

        while (!$this->agenda->isEmpty()) {
            [$at, $event] = $this->agenda->extract();
            if ($at >= $end) {
                break;
            }
            $this->sleepUntil($at);
            $event();
        }
        $this->sleepUntil($end);
    }

    /**
     * Arrivals follow a Poisson process, whose rate follows the rhythm of the stream: candidates arrive at the highest
     * rate, and each is kept with the probability of the actual rate over the highest one.
     */
    private function scheduleNextArrival(Stream $stream): void
    {
        $maxActivity = match ($stream->rhythm) {
            Rhythm::Human => $this->curve->maxHumanActivity(),
            Rhythm::Constant => 1.0,
        };
        $meanInterval = self::SECONDS_PER_DAY / ($stream->visitsPerDay * $maxActivity * $this->speed);

        $this->schedule(microtime(true) + $this->random->exponential($meanInterval), function () use ($stream, $maxActivity): void {
            $this->scheduleNextArrival($stream);

            $activity = match ($stream->rhythm) {
                Rhythm::Human => $this->curve->humanActivity(new \DateTimeImmutable()),
                Rhythm::Constant => 1.0,
            };
            if ($this->random->chance($activity / $maxActivity)) {
                $number = $this->visitCountByStream[$stream->name] = ($this->visitCountByStream[$stream->name] ?? 0) + 1;
                $this->start(($stream->startVisit)($number));
            }
        });
    }

    /**
     * @param \Generator<int, float> $visit
     */
    private function start(\Generator $visit): void
    {
        $this->step($visit, $visit->current(...));
    }

    /**
     * @param \Generator<int, float> $visit
     */
    private function resume(\Generator $visit): void
    {
        $this->step($visit, $visit->next(...));
    }

    /**
     * Runs the visit up to its next wait, and schedules what follows.
     *
     * @param \Generator<int, float> $visit
     */
    private function step(\Generator $visit, \Closure $step): void
    {
        try {
            $step();
        } catch (VisitAborted $exception) {
            $this->logger->warning('A visit was aborted.', ['exception' => $exception]);

            return;
        }

        if ($visit->valid()) {
            $this->schedule(microtime(true) + $visit->current() / $this->speed, fn () => $this->resume($visit));
        }
    }

    private function schedule(float $at, \Closure $event): void
    {
        $this->agenda->insert([$at, $event], [-$at, -++$this->eventCount]);
    }

    private function sleepUntil(float $at): void
    {
        $wait = $at - microtime(true);
        if ($wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }
    }
}
