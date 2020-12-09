<?php
namespace Git;

abstract class ReceiveHook
{
    const INPUT_PATTERN = '@^([0-9a-f]{40}) ([0-9a-f]{40}) (.+)$@i';

    const TYPE_UPDATED = 0;
    const TYPE_CREATED = 1;
    const TYPE_DELETED = 2;

    const REF_BRANCH = 0;
    const REF_TAG = 1;

    const LOG_FILE_PATH = '/git/karmamail.log';

    private $isLog = false;

    private $repositoryName = '';
    protected $refs = [];

    /**
     * @param string $basePath Base path for all repositories
     */
    public function __construct($basePath)
    {
        $rel_path = str_replace($basePath, '', \Git::getRepositoryPath());
        if (preg_match('@/(.*\.git)$@', $rel_path, $matches)) {
            $this->repositoryName = $matches[1];
        }
    }

    public function enableLog() {
        $this->isLog = true;
    }

    /**
     * Escape array items by escapeshellarg function
     * @param array $args
     * @return array array with escaped items
     */
    protected function escapeArrayShellArgs(array $args)
    {
        return array_map('escapeshellarg', $args);
    }


    /**
     * Returns the repository name.
     *
     * A repository name is the path to the repository with the .git.
     *
     * @return string
     */
    public function getRepositoryName()
    {
        return $this->repositoryName;
    }

    /**
     * Returns the short repository name.
     *
     * A short repository name is the path to the repository without the .git.
     * e.g. php-src.git -> php-src
     *
     * @return string
     */
    public function getRepositoryShortName()
    {
        return preg_replace('@\.git$@', '', $this->repositoryName);
    }

    /**
     * Return array with changed paths as keys and change type as values
     * If commit is merge commit change type will have more than one char
     * (for example "MM")
     *
     * Required already escaped string in $revRange!!!
     *
     * @param string $revRange
     * @param bool $reverse reverse diff
     * @return array
     */
    protected function getChangedPaths($revRange, $reverse = false)
    {
        $raw = \Git::gitExec('diff-tree ' . ($reverse ? '-R ' : '') . '-r --no-commit-id -c --name-status --pretty="format:" %s', $revRange);
        $paths = [];
        $lines = explode("\n", $raw);
        foreach($lines as $line) {
            if (preg_match('/([ACDMRTUXB*]+)\s+([^\n\s]+)/', $line , $matches)) {
                $paths[$matches[2]] = $matches[1];
            }
        }

        return $paths;
    }


    /**
     * Return array with branches names in repository
     *
     * @return array
     */
    protected function getAllBranches()
    {
        $branches = explode("\n", trim(\Git::gitExec('for-each-ref --format="%%(refname)" "refs/heads/*"')));
        if ($branches[0] == '') $branches = [];
        return $branches;
    }


    protected function log($string) {
        if (!$this->isLog) return;
        $string = trim($string) . "\n";
        file_put_contents(self::LOG_FILE_PATH, $string, FILE_APPEND);
    }

    /**
     * Parses the input from git.
     *
     * Git pipes a list of oldrev, newrev and revname combinations
     * to the hook. We parse this input. For more information about
     * the input see githooks(5).
     *
     * Returns an array with 'old', 'new', 'refname', 'changetype', 'reftype'
     * keys for each ref that will be updated.
     * @return array
     */
    public function hookInput()
    {
        $this->log('New hook call ' . $this->getRepositoryName() . ' ' . date('r'));

        $parsed_input = [];
        while (!feof(STDIN)) {
            $line = fgets(STDIN);
            if (preg_match(self::INPUT_PATTERN, $line, $matches)) {

                $this->log($line);

                $ref = [
                    'old'     => $matches[1],
                    'new'     => $matches[2],
                    'refname' => $matches[3]
                ];

                if (preg_match('~^refs/heads/.+$~', $ref['refname'])) {
                    // git push origin branchname
                    $ref['reftype'] = self::REF_BRANCH;
                } elseif (preg_match('~^refs/tags/.+$~', $ref['refname'])) {
                    // git push origin tagname
                    $ref['reftype'] = self::REF_TAG;
                } else {
                    // not support by this script
                    $ref['reftype'] = -1;
                }

                if ($ref['old'] == \GIT::NULLREV) {
                    // git branch branchname && git push origin branchname
                    // git tag tagname rev && git push origin tagname
                    $ref['changetype'] = self::TYPE_CREATED;
                } elseif ($ref['new'] == \GIT::NULLREV) {
                    // git branch -d branchname && git push origin :branchname
                    // git tag -d tagname && git push origin :tagname
                    $ref['changetype'] =  self::TYPE_DELETED;
                } else {
                    // git push origin branchname
                    // git tag -f tagname rev && git push origin tagname
                    $ref['changetype'] =  self::TYPE_UPDATED;
                }


                $parsed_input[$ref['refname']] = $ref;
            }
        }
        $this->refs = $parsed_input;
        return $this->refs;
    }

}
