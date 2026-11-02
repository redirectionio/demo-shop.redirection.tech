<?php

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Traffic\Browsers;
use Traffic\Journal;
use Traffic\KnownUrls;
use Traffic\Population;
use Traffic\Random;
use Traffic\Simulation;
use Traffic\Site;
use Traffic\Sitemap;
use Traffic\TrafficCurve;
use Traffic\UrlMemoryStore;
use Traffic\UserAgents;

use function Castor\context;
use function Castor\fs;
use function Castor\http_client;
use function Castor\io;
use function Castor\logger;
use function Castor\run;

// Keep in sync with "site" in astro.config.mjs.
const SITE_URL = 'https://demo-shop.redirection.tech';

#[AsTask(namespace: 'site', description: 'Install the dependencies')]
function install(): void
{
    run('npm ci');
}

#[AsTask(namespace: 'site', description: 'Start the development server (search does not work there, use site:preview)')]
function dev(): void
{
    ensure_dependencies();
    run('npx astro dev', context: context()->withTty());
}

#[AsTask(namespace: 'site', description: 'Check the types and the catalog data')]
function check(): void
{
    ensure_dependencies();
    run('npx astro check');
}

#[AsTask(namespace: 'site', description: 'Build the static site into dist/')]
function build(): void
{
    ensure_dependencies();
    run('npx astro build');
    run('npx pagefind --site dist');
    io()->success('The site is built in dist/.');
}

#[AsTask(namespace: 'site', description: 'Build the site, then serve dist/ as GitHub Pages would')]
function preview(): void
{
    build();
    run('npx astro preview', context: context()->withTty());
}

function ensure_dependencies(): void
{
    // npm writes node_modules/.package-lock.json on each install.
    $installedLock = __DIR__ . '/node_modules/.package-lock.json';
    if (!fs()->exists($installedLock) || filemtime(__DIR__ . '/package-lock.json') > filemtime($installedLock)) {
        install();
    }
}

#[AsTask(namespace: 'traffic', description: 'Record the pages of the live site, and the ones it no longer has, on the traffic-state branch')]
function memorize(): void
{
    $store = new UrlMemoryStore();
    $snapshot = $store->load();
    // Fetching the sitemap passes for one of the regular visits of Googlebot.
    $sitemap = new Sitemap(http_client()->withOptions(['timeout' => 15]), new Site(SITE_URL), new UserAgents(new DateTimeImmutable(), new Random())->googlebotSmartphone());
    $memory = $snapshot->memory->withSitemap($sitemap->paths(), new DateTimeImmutable());

    if ($memory->equals($snapshot->memory)) {
        io()->success('The pages of the site have not changed.');

        return;
    }

    $store->save($memory, $snapshot->revision);
    io()->success('The pages of the site are recorded on the traffic-state branch.');
}

#[AsTask(namespace: 'traffic', description: 'Simulate the visitors of the site: humans, bots and vulnerability scanners')]
function simulate(
    #[AsOption(description: 'How long to run, in minutes')]
    int $duration = 55,
    #[AsOption(description: 'The site to visit, to try the simulation out on "castor site:preview" for instance')]
    string $site = SITE_URL,
    #[AsOption(description: 'Speeds everything up, to try the simulation out')]
    float $speed = 1,
): void {
    $now = new DateTimeImmutable();
    $random = new Random();
    $journal = new Journal(io());
    $knownUrls = KnownUrls::from(
        new UrlMemoryStore()->load()->memory,
        KnownUrls::readLegacyPaths(__DIR__ . '/traffic/legacy-urls.txt'),
        $now,
    );
    $client = http_client()->withOptions(['max_redirects' => 0, 'max_duration' => 60, 'timeout' => 15]);
    $population = new Population(new Browsers($client, new Site($site), $journal), $knownUrls, new UserAgents($now, $random), $journal, $random);

    new Simulation($population->streams(), $population->perpetualVisits(), new TrafficCurve(), $random, logger(), $speed)->run($duration * 60);
    $journal->summarize();
}
