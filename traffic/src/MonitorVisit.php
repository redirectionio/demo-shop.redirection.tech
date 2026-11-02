<?php

namespace Traffic;

/**
 * An uptime monitor, checking the home page every five minutes, for as long as the simulation runs.
 */
final readonly class MonitorVisit
{
    private const string USER_AGENT = 'Mozilla/5.0+(compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)';
    private const float INTERVAL_IN_SECONDS = 300;

    public function __construct(
        private Browsers $browsers,
        private Journal $journal,
    ) {
    }

    /**
     * @return \Generator<int, float, mixed, never>
     */
    public function run(): \Generator
    {
        $visitor = new Visitor('UptimeRobot', 1, self::USER_AGENT);
        $browser = $this->browsers->forFirstVisit($visitor);
        $this->journal->visitStarted($visitor);

        while (true) {
            $browser->navigate('/', null, 'HEAD');

            yield self::INTERVAL_IN_SECONDS;
        }
    }
}
