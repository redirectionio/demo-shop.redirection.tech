<?php

namespace Traffic;

/**
 * A vulnerability scanner, probing for the files of common applications: every site on the Internet gets them.
 */
final readonly class ScannerVisit
{
    private const array PROBES = [
        '/.aws/credentials', '/.DS_Store', '/.env', '/.env.local', '/.env.production', '/.git/config', '/.git/HEAD',
        '/.svn/entries', '/.vscode/sftp.json', '/.well-known/security.txt', '/_ignition/health-check', '/actuator/health',
        '/admin.php', '/admin/', '/administrator/', '/adminer.php', '/api/.env', '/backup.sql', '/backup.zip', '/cgi-bin/',
        '/config.json', '/config.php', '/db.sql', '/dump.sql', '/geoserver/web/', '/HNAP1/', '/info.php', '/login',
        '/owa/auth/logon.aspx', '/phpinfo.php', '/phpmyadmin/', '/pma/', '/server-status', '/site.tar.gz', '/solr/admin/',
        '/telescope/requests', '/test.php', '/user/login', '/vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php',
        '/wordpress/wp-login.php', '/wp-admin/', '/wp-admin/setup-config.php', '/wp-content/plugins/',
        '/wp-includes/wlwmanifest.xml', '/wp-login.php', '/xmlrpc.php',
    ];

    private const array USER_AGENTS = [
        'curl/8.5.0',
        'Fuzz Faster U Fool v2.1.0-dev',
        'Go-http-client/1.1',
        'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
        'Mozilla/5.0 zgrab/0.x',
        'python-requests/2.32.3',
    ];

    public function __construct(
        private int $number,
        private Browsers $browsers,
        private Journal $journal,
        private Random $random,
    ) {
    }

    /**
     * @return \Generator<int, float, mixed, void> The seconds to wait before each next step
     */
    public function run(): \Generator
    {
        $visitor = new Visitor('scanner', $this->number, $this->random->pick(self::USER_AGENTS));
        $browser = $this->browsers->forFirstVisit($visitor);
        $this->journal->visitStarted($visitor);

        foreach ($this->random->sample(self::PROBES, $this->random->int(8, 30)) as $index => $path) {
            if ($index > 0) {
                yield $this->random->float(0.1, 1.5);
            }
            $browser->navigate($path, null);
        }
    }
}
