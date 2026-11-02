<?php

namespace Traffic;

use function Castor\capture;
use function Castor\context;
use function Castor\run;

/**
 * Stores the URL memory on an orphan branch, in a single commit replaced on each save: it shares no history with the
 * other branches. The working tree and the index are left untouched.
 */
final readonly class UrlMemoryStore
{
    private const string BRANCH = 'refs/heads/traffic-state';
    private const string FILE = 'urls.json';
    private const string README = <<<'MARKDOWN'
        # traffic-state

        Written by `castor traffic:memorize` (see the README of the main branch), never merged.

        `urls.json` lists every page the site has ever had in its sitemap, with the day it left it. The traffic simulator
        keeps sending visitors and bots to these pages, like search engines and old bookmarks do.

        The branch holds a single commit, replaced on each update. The file can be edited by hand, to forget a page for
        instance: the next update keeps the change.

        MARKDOWN;

    public function __construct(
        private string $remote = 'origin',
    ) {
    }

    public function load(): UrlMemorySnapshot
    {
        $revision = $this->remoteRevision();
        if (null === $revision) {
            return new UrlMemorySnapshot(UrlMemory::empty(), null);
        }

        run(['git', 'fetch', '--quiet', $this->remote, $revision]);

        return new UrlMemorySnapshot(UrlMemory::fromJson(capture(['git', 'show', $revision . ':' . self::FILE])), $revision);
    }

    /**
     * @param ?string $replacedRevision The revision the memory was loaded from: the push fails if the branch has moved since
     */
    public function save(UrlMemory $memory, ?string $replacedRevision): void
    {
        $tree = capture(['git', 'mktree'], context()->withInput(implode('', [
            \sprintf("100644 blob %s\t%s\n", $this->writeBlob(self::README), 'README.md'),
            \sprintf("100644 blob %s\t%s\n", $this->writeBlob($memory->toJson()), self::FILE),
        ])));
        $commit = capture(['git', 'commit-tree', $tree, '-m', 'Remember the pages of the site']);

        run([
            'git', 'push', '--quiet',
            // An empty value requires the branch not to exist yet.
            \sprintf('--force-with-lease=%s:%s', self::BRANCH, $replacedRevision ?? ''),
            $this->remote,
            \sprintf('%s:%s', $commit, self::BRANCH),
        ]);
    }

    private function remoteRevision(): ?string
    {
        $line = capture(['git', 'ls-remote', $this->remote, self::BRANCH]);

        return '' === $line ? null : strtok($line, "\t");
    }

    private function writeBlob(string $content): string
    {
        return capture(['git', 'hash-object', '-w', '--stdin'], context()->withInput($content));
    }
}
