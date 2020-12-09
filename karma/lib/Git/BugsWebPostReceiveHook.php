<?php
namespace Git;

class BugsWebPostReceiveHook extends ReceiveHook
{

    public function getReceivedMessages()
    {
        $this->hookInput();

        $paths = [];
        foreach ($this->refs as $ref) {
            if ($ref['reftype'] == self::REF_BRANCH) {
                $paths[] = $this->getReceivedMessagesForRange($ref['old'], $ref['new']);
            }
        }

        /* remove empty lines, and flattern the array */
        $flattend = array_reduce($paths, 'array_merge', []);
        $paths    = array_filter($flattend);

        return array_unique($paths);
    }

    /**
     * Returns an array of commit messages between revision $old and $new.
     *
     * @param string $old The old revison number.
     * @parma string $new The new revison umber.
     *
     * @return array
     */
    private function getReceivedMessagesForRange($old, $new)
    {
        $repourl = \Git::getRepositoryPath();
        $output = [];

        if ($old == \Git::NULLREV) {
            $cmd = sprintf(
                "%s --git-dir=%s for-each-ref --format='%%(refname)' 'refs/heads/*'",
                \Git::GIT_EXECUTABLE,
                $repourl
            );
            exec($cmd, $heads);

            $not   = count($heads) > 0 ? ' --not ' . implode(' ', $this->escapeArrayShellArgs($heads)) : '';
            $cmd   = sprintf(
                '%s --git-dir=%s log --pretty=format:"[%%ae] %%H %%s" %s %s',
                \Git::GIT_EXECUTABLE,
                $repourl,
                escapeshellarg($new),
                $not
            );
            exec($cmd, $output);
        } elseif ($new != \Git::NULLREV) {
            $cmd = sprintf(
                '%s --git-dir=%s log --pretty=format:"[%%ae] %%H %%s" %s..%s',
                \Git::GIT_EXECUTABLE,
                $repourl,
                escapeshellarg($old),
                escapeshellarg($new)
            );
            exec($cmd, $output);
        }

        return $output;
    }
}
