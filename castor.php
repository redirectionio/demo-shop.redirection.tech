<?php

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\fs;
use function Castor\io;
use function Castor\run;

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
