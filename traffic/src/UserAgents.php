<?php

namespace Traffic;

/**
 * The browsers in use today. Their versions follow the release cycles of the browsers, so that the traffic never looks
 * outdated.
 */
final readonly class UserAgents
{
    // Chrome and Firefox release a major version every four weeks.
    private const int RELEASE_CYCLE_IN_DAYS = 28;
    private const int CHROME_REFERENCE_VERSION = 140;
    private const string CHROME_REFERENCE_DATE = '2025-09-02';
    private const int FIREFOX_REFERENCE_VERSION = 143;
    private const string FIREFOX_REFERENCE_DATE = '2025-09-16';

    /** Not everybody runs the latest version: how many versions behind, and how often. */
    private const array VERSION_LAG = [0 => 60, 1 => 25, 2 => 10, 3 => 5];

    private const array BROWSERS = [
        'desktop' => [
            'chrome-windows' => 40,
            'edge-windows' => 14,
            'safari-mac' => 15,
            'chrome-mac' => 13,
            'firefox-windows' => 9,
            'firefox-mac' => 3,
            'chrome-linux' => 3,
            'firefox-linux' => 3,
        ],
        'mobile' => [
            'safari-iphone' => 52,
            'chrome-android' => 38,
            'chrome-iphone' => 7,
            'firefox-android' => 3,
        ],
        'tablet' => [
            'safari-ipad' => 75,
            'chrome-android-tablet' => 25,
        ],
    ];

    // Frozen by the browsers, whatever the actual version of the system.
    private const string ANDROID = 'Linux; Android 10; K';
    private const string IOS = 'CPU iPhone OS 18_6 like Mac OS X';
    private const string MACOS = 'Macintosh; Intel Mac OS X 10_15_7';
    private const string WINDOWS = 'Windows NT 10.0; Win64; x64';

    public function __construct(
        private \DateTimeImmutable $today,
        private Random $random,
    ) {
    }

    public function forHuman(Device $device): HumanAgent
    {
        return match ($this->random->weighted(self::BROWSERS[$device->value])) {
            'chrome-windows' => HumanAgent::other($this->chrome(self::WINDOWS)),
            'edge-windows' => HumanAgent::other($this->edge()),
            'safari-mac', 'safari-ipad' => HumanAgent::safari(\sprintf('Mozilla/5.0 (%s) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/%s Safari/605.1.15', self::MACOS, $this->safariVersion())),
            'chrome-mac' => HumanAgent::other($this->chrome(self::MACOS)),
            'firefox-windows' => HumanAgent::other($this->firefox(self::WINDOWS)),
            'firefox-mac' => HumanAgent::other($this->firefox('Macintosh; Intel Mac OS X 10.15')),
            'chrome-linux' => HumanAgent::other($this->chrome('X11; Linux x86_64')),
            'firefox-linux' => HumanAgent::other($this->firefox('X11; Linux x86_64')),
            'safari-iphone' => HumanAgent::safari(\sprintf('Mozilla/5.0 (iPhone; %s) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/%s Mobile/15E148 Safari/604.1', self::IOS, $this->safariVersion())),
            'chrome-android' => HumanAgent::other(\sprintf('Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%d.0.0.0 Mobile Safari/537.36', self::ANDROID, $this->chromeVersion())),
            'chrome-iphone' => HumanAgent::other(\sprintf('Mozilla/5.0 (iPhone; %s) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/%d.0.0.0 Mobile/15E148 Safari/604.1', self::IOS, $this->chromeVersion())),
            'firefox-android' => HumanAgent::other(\sprintf('Mozilla/5.0 (Android 15; Mobile; rv:%1$d.0) Gecko/%1$d.0 Firefox/%1$d.0', $this->firefoxVersion())),
            'chrome-android-tablet' => HumanAgent::other(\sprintf('Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%d.0.0.0 Safari/537.36', self::ANDROID, $this->chromeVersion())),
        };
    }

    public function googlebotSmartphone(): string
    {
        return \sprintf('Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', $this->latestChromeBuild());
    }

    public function googlebotDesktop(): string
    {
        return \sprintf('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/%s Safari/537.36', $this->latestChromeBuild());
    }

    public function bingbot(): string
    {
        return \sprintf('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/%s Safari/537.36', $this->latestChromeBuild());
    }

    private function chrome(string $platform): string
    {
        return \sprintf('Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%d.0.0.0 Safari/537.36', $platform, $this->chromeVersion());
    }

    private function edge(): string
    {
        $version = $this->chromeVersion();

        return \sprintf('Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%d.0.0.0 Safari/537.36 Edg/%d.0.0.0', self::WINDOWS, $version, $version);
    }

    private function firefox(string $platform): string
    {
        return \sprintf('Mozilla/5.0 (%1$s; rv:%2$d.0) Gecko/20100101 Firefox/%2$d.0', $platform, $this->firefoxVersion());
    }

    private function chromeVersion(): int
    {
        return $this->latestVersion(self::CHROME_REFERENCE_VERSION, self::CHROME_REFERENCE_DATE) - $this->random->weighted(self::VERSION_LAG);
    }

    private function firefoxVersion(): int
    {
        return $this->latestVersion(self::FIREFOX_REFERENCE_VERSION, self::FIREFOX_REFERENCE_DATE) - $this->random->weighted(self::VERSION_LAG);
    }

    /**
     * Bots run an up-to-date Chrome, and show its full version.
     */
    private function latestChromeBuild(): string
    {
        $version = $this->latestVersion(self::CHROME_REFERENCE_VERSION, self::CHROME_REFERENCE_DATE);

        // Chrome 140 was build 7339, and each version adds about 55 builds.
        return \sprintf('%d.0.%d.%d', $version, 7339 + ($version - self::CHROME_REFERENCE_VERSION) * 55, $this->random->int(40, 220));
    }

    private function latestVersion(int $referenceVersion, string $referenceDate): int
    {
        return $referenceVersion + intdiv(new \DateTimeImmutable($referenceDate)->diff($this->today)->days, self::RELEASE_CYCLE_IN_DAYS);
    }

    /**
     * Safari is numbered after the year that follows its release, each September (Safari 26 came out in 2025), and
     * gets a minor version every couple of months.
     */
    private function safariVersion(): string
    {
        $year = (int) $this->today->format('Y');
        $month = (int) $this->today->format('n');
        $major = $month >= 9 ? $year - 1999 : $year - 2000;

        // Some visitors have not upgraded their system to the latest major version yet.
        if ($this->random->chance(0.2)) {
            return \sprintf('%d.%d', $major - 1, $this->random->int(3, 6));
        }

        $monthsSinceRelease = ($month + 3) % 12;

        return \sprintf('%d.%d', $major, intdiv($monthsSinceRelease, 2));
    }
}
