<?php
namespace Git;

class PostReceiveHook extends ReceiveHook
{

    private $pushAuthor = '';
    private $pushAuthorName = '';
    private $mailingList = '';
    private $emailPrefix = '';
    private $usersFile = '';

    private $alreadyExistsBranches = [];
    private $updatedBranches = [];
    private $revisions = [];

    private $allBranches = [];

    /**
     * @param string $basePath base path for all repositories
     * @param string $pushAuthor user who make push
     * @param string $usersFile path to file with users data
     * @param string $mailingList mail recipient
     * @param string $emailPrefix prefix for mail subject
     */
    public function __construct($basePath, $pushAuthor, $usersFile, $mailingList, $emailPrefix)
    {
        parent::__construct($basePath);

        $this->usersFile = $usersFile;
        $this->pushAuthor = $pushAuthor;
        $this->pushAuthorName = $this->getUserName($pushAuthor);
        $this->mailingList = $mailingList;
        $this->emailPrefix = $emailPrefix;

        $this->allBranches = $this->getAllBranches();
    }

    /**
     * Find user name by nickname in users data file
     * @param string $user user nickname
     * @return string user name
     */
    public function getUserName($user)
    {
        $usersDB = file($this->usersFile);
        foreach ($usersDB as $userline) {
            list ($username, $fullname, $email) = explode(":", trim($userline));
            if ($username === $user) {
                return $fullname;
            }
        }
        return '';
    }



    /**
     * Parse input from STDIN
     * Mail about changes in heads(branches) and tags
     * Mail about new commits
     */
    public function process()
    {
        $this->hookInput();

        //cache list of old and updated branches
        $newBranches = [];
        foreach ($this->refs as $ref) {
            if ($ref['reftype'] == self::REF_BRANCH){
                if ($ref['changetype'] == self::TYPE_UPDATED) {
                    $this->updatedBranches[] = $ref['refname'];
                } elseif ($ref['changetype'] == self::TYPE_CREATED) {
                    $newBranches[] = $ref['refname'];
                }
            }
        }
        $this->alreadyExistsBranches = array_diff($this->allBranches, $newBranches);


        foreach ($this->refs as $ref) {
            if ($ref['reftype'] == self::REF_TAG) {
                // tag mail
                $this->sendTagMail($ref['refname'], $ref['changetype'], $ref['old'], $ref['new']);
            } elseif ($ref['reftype'] == self::REF_BRANCH) {
                if ($ref['changetype'] != self::TYPE_DELETED) {
                    // magic populate the $this->revisions
                    $this->getBranchRevisions($ref['refname'], $ref['changetype'], $ref['old'], $ref['new']);

                    if ($ref['changetype'] == self::TYPE_UPDATED) {
                        $this->sendDeletedCommitsMail($ref['refname'], $ref['old'], $ref['new']);
                    }
                } else {
                    $this->sendDeletedBranchMail($ref['refname']);
                }
            }
        }

        $this->log('Found revisions: '. implode(' ', array_keys($this->revisions)));
        //send mails per commit
        foreach ($this->revisions as $revision => $branches) {
            // check if it commit was already in other branches
            if (!$this->isRevExistsInBranches($revision, array_diff($this->allBranches, $branches))) {
                $this->sendCommitMail($revision, $branches);
            }
        }

    }

    /**
     * Send mail about force deleted commits.
     * Subject: del %PROJECT%: %PATHS%
     * Body:
     * Branch: %BRANCH%
     * Deleted commits count: %REV_COUNT%
     * User: %USER%                               Thu, 08 Mar 2012 12:39:48 +0000
     *
     * --part1--
     * Changed paths:
     * %PATHS%
     * --/part1--
     *
     * --part2--
     * Diff:
     * %DIFF%
     * --/part2--
     *
     * @param string $name branch fullname (refs/heads/example)
     * @param string $oldrev old revision
     * @param string $newrev new revision
     */
    private function sendDeletedCommitsMail($name, $oldrev, $newrev)
    {

        $deletedRevisionsCount = count($this->getRevisions(escapeshellarg($newrev . '..' . $oldrev)));

        if ($deletedRevisionsCount > 0) {
            $shortName = str_replace('refs/heads/', '', $name);

            $paths = $this->getChangedPaths(escapeshellarg($newrev . '..' . $oldrev), true);
            $pathsString = '';
            foreach ($paths as $path => $action)
            {
                $pathsString .= '  ' . $action . '  ' . $path . "\n";
            }

            $isTrivialMerge = count($paths) <= 0;

            if (!$isTrivialMerge) {

                $diff =  \Git::gitExec('diff-tree --cc -r -R --no-commit-id %s', escapeshellarg($newrev . '..' . $oldrev));

                $mail = new \Mail();
                $mail->setSubject($this->emailPrefix . 'del ' . $this->getRepositoryShortName() . ': '. implode(' ', array_keys($paths)));
                $mail->setTimestamp(strtotime(date('r')));

                $message = 'Branch: ' . $shortName . "\n";
                $message .= 'Deleted commits count: ' . $deletedRevisionsCount . "\n";
                $message .= 'User: ' . $this->pushAuthorName . ' <' . $this->pushAuthor . '@php.net>         ' . date('r') . "\n";

                if (strlen($pathsString) < 8192) {
                    // inline changed paths
                    $message .= "\nChanged paths:\n" . $pathsString . "\n";
                    if ((strlen($pathsString) + strlen($diff)) < 8192) {
                        // inline diff
                        $message .= "\nDiff:\n" . $diff . "\n";
                    } else {
                        // diff attach
                        $diffFile = 'diff_' . $newrev . '_' . $oldrev . '.txt';
                        $mail->addTextFile($diffFile, $diff);
                        if ((strlen($message) + $mail->getFileLength($diffFile)) > 262144) {
                            // diff attach exceeded max size
                            $mail->dropFile($diffFile);
                            $message .= "\nDiff: <Diff exceeded maximum size>";
                        }
                    }
                } else {
                    // changed paths attach
                    $pathsFile = 'paths_' . $newrev . '_' . $oldrev . '.txt';
                    $mail->addTextFile($pathsFile, $pathsString);
                    if ((strlen($message) + $mail->getFileLength($pathsFile)) > 262144) {
                        // changed paths attach exceeded max size
                        $mail->dropFile($pathsFile);
                        $message .= "\nChanged paths: <changed paths exceeded maximum size>";
                    } else {
                        // diff attach
                        $diffFile = 'diff_' . $newrev . '_' . $oldrev . '.txt';
                        $mail->addTextFile($diffFile, $diff);
                        if ((strlen($message) + $mail->getFileLength($pathsFile) + $mail->getFileLength($diffFile)) > 262144) {
                            // diff attach exceeded max size
                            $mail->dropFile($diffFile);
                        }
                    }
                }

                $mail->setMessage($message);

                $mail->setFrom($this->pushAuthor . '@php.net', $this->pushAuthorName);
                $mail->addTo($this->mailingList);

                $result = $mail->send();
                $this->log('revisions deleted ' . $newrev . '..' . $oldrev . ($result ? ' was send' : ' error while sending'));
            }


        }
    }


    /**
     * Send mail about deleted branch.
     * Subject: branch %PROJECT%: %BRANCH_NAME% deleted
     * Body:
     * Deleted branch: %BRANCH%
     * User: %USER%                               Thu, 08 Mar 2012 12:39:48 +0000
     *
     * @param string $name branch fullname (refs/heads/example)
     */
    private function sendDeletedBranchMail($name)
    {
        $shortName = str_replace('refs/heads/', '', $name);

        $mail = new \Mail();
        $mail->setSubject($this->emailPrefix . 'branch ' . $this->getRepositoryShortName() . ': '. $shortName . ' deleted');
        $mail->setTimestamp(strtotime(date('r')));

        $message = 'Deleted branch: ' . $shortName . "\n";
        $message .= 'User: ' . $this->pushAuthorName . ' <' . $this->pushAuthor . '@php.net>         ' . date('r') . "\n";

        $mail->setMessage($message);

        $mail->setFrom($this->pushAuthor . '@php.net', $this->pushAuthorName);
        $mail->addTo($this->mailingList);

        $result = $mail->send();
        $this->log('branch deleted ' . $shortName . ($result ? ' was send' : ' error while sending'));

    }


    /**
     * Cache revisions per branche for use it later
     * @param string $branchName branch fullname
     * @param array $revisions revisions array
     */
    private function cacheRevisions($branchName, array $revisions)
    {
        foreach ($revisions as $revision)
        {
            $this->revisions[$revision][$branchName] = $branchName;
        }
    }


    /**
     * Send mail about tag.
     * Subject: tag %PROJECT%: %STATUS% tag %TAGNAME%
     * Body:
     * Tag %TAGNAME% in %PROJECT% was %STATUS% (if sha was changed)from %OLD_SHA%
     * Tag(if annotaded): %SHA%
     * Tagger(if annotaded): %USER%                               Thu, 08 Mar 2012 12:39:48 +0000
     *
     * Log(if annotaded):
     * %MESSAGE%
     *
     * Link: http://git.php.net/?p=%PROJECT_PATH%;a=tag;h=%SHA%
     *
     * Target: %SHA%
     * Author: %USER%                               Thu, 08 Mar 2012 12:39:48 +0000
     * Committer: %USER%                               Thu, 08 Mar 2012 12:39:48 +0000
     * Parents: %SHA_PARENTS%
     * Target link: http://git.php.net/?p=%PROJECT_PATH%;a=commitdiff;h=%SHA%
     * Target log:
     * %MESSAGE%
     *
     * --part1--
     * Changed paths:
     * %PATHS%
     * --/part1--
     *
     * @param string $name tag fullname (refs/tags/example)
     * @param int $changeType delete, create or update
     * @param string $oldrev old revision
     * @param string $newrev new revision
     */
    private function sendTagMail($name, $changeType, $oldrev, $newrev)
    {

        $status = [self::TYPE_UPDATED => 'update', self::TYPE_CREATED => 'create', self::TYPE_DELETED => 'delete'];
        $shortname = str_replace('refs/tags/', '', $name);
        $mail = new \Mail();
        $mail->setSubject($this->emailPrefix . 'tag ' . $this->getRepositoryShortName() . ': ' . $status[$changeType] . ' tag ' . $shortname);

        $message = 'Tag ' . $shortname . ' in ' . $this->getRepositoryName() . ' was ' . $status[$changeType] . 'd' .
            (($changeType != self::TYPE_CREATED) ? ' from ' . $oldrev : '' ) . "\n";

        if ($changeType != self::TYPE_DELETED) {
            $info = $this->getTagInfo($name);
            $targetInfo = $this->getCommitInfo($info['target']);
            $targetPaths = $this->getChangedPaths(escapeshellarg($info['target']));
            $pathsString = '';
            foreach ($targetPaths as $path => $action)
            {
                $pathsString .= '  ' . $action . '  ' . $path . "\n";
            }

            if ($info['annotated']) {
                $message .= 'Tag:         ' . $info['revision'] . "\n";
                $message .= 'Tagger:      ' . $info['tagger'] . $info['tagger_email'] . '         ' . $info['tagger_date'] . "\n";
                $message .= "Log:\n" . $info['log'] . "\n";
                $mail->setTimestamp(strtotime($info['tagger_date']));
            }

            $message .= "\n";
            $message .= "Link: http://git.php.net/?p=" . $this->getRepositoryName() . ";a=tag;h=" . $info['revision'] . "\n";
            $message .= "\n";

            $message .= 'Target:      ' . $info['target'] . "\n";
            $message .= 'Author:      ' . $targetInfo['author'] . ' <' . $targetInfo['author_email'] . '>         ' . $targetInfo['author_date'] . "\n";
            if (($targetInfo['author'] != $targetInfo['committer']) || ($targetInfo['author_email'] != $targetInfo['committer_email'])) {
                $message .= 'Committer:   ' . $targetInfo['committer'] . ' <' . $targetInfo['committer_email'] . '>      ' . $targetInfo['committer_date'] . "\n";
            }
            if ($targetInfo['parents']) $message .= 'Parents: ' . $targetInfo['parents'] . "\n";
            $message .= "Target link: http://git.php.net/?p=" . $this->getRepositoryName() . ";a=commitdiff;h=" . $info['target'] . "\n";
            $message .= "Target log:\n" . $targetInfo['log'] . "\n";


            if (strlen($pathsString) < 8192) {
                // inline changed paths
                $message .= "\nChanged paths:\n" . $pathsString . "\n";
            } else {
                // changed paths attach
                $pathsFile = 'paths_' . $info['target'] . '.txt';
                $mail->addTextFile($pathsFile, $pathsString);
                if ((strlen($message) + $mail->getFileLength($pathsFile)) > 262144) {
                    // changed paths attach exceeded max size
                    $mail->dropFile($pathsFile);
                    $message .= "\nChanged paths: <changed paths exceeded maximum size>";
                }
            }
        }

        $mail->setMessage($message);

        $mail->setFrom($this->pushAuthor . '@php.net', $this->pushAuthorName);
        $mail->addTo($this->mailingList);

        $result = $mail->send();
        $this->log('tag ' . $name . ($result ? ' was send' : ' error while sending'));
    }

    /**
     * Get info for tag
     * It return array with items:
     * 'annotated' flag,
     * 'revision' - tag sha,
     * 'target' - target sha (if tag not annotated it equal 'revision')
     * only for annotated tag:
     * 'tagger', 'tagger_email', 'tagger_date' - info about tagger person
     * 'log' - tag message
     * @param string $tag tag fullname
     * @return array array with tag info
     */
    private function getTagInfo($tag)
    {
        $temp = \Git::gitExec("for-each-ref --format=\"%%(objecttype)\n%%(objectname)\n%%(taggername)\n%%(taggeremail)\n%%(taggerdate)\n%%(*objectname)\n%%(contents)\" %s", escapeshellarg($tag));
        $temp = explode("\n", trim($temp), 7); //6 elements separated by \n, last element - log message
        if ($temp[0] == 'tag') {
            $info = [
                'annotated'     => true,
                'revision'      => $temp[1],
                'tagger'        => $temp[2],
                'tagger_email'  => $temp[3],
                'tagger_date'   => $temp[4],
                'target'        => $temp[5],
                'log'           => $temp[6]
            ];
        } else {
            $info = [
                'annotated'     => false,
                'revision'      => $temp[1],
                'target'        => $temp[1]
            ];
        }
        return $info;
    }

    /**
     * Find revisions for branch change
     * Also cache revisions list for revisions mails
     * @param string $name branch fullname (refs/heads/example)
     * @param int $changeType delete, create or update
     * @param string $oldrev old revision
     * @param string $newrev new revision
     * @return array revisions list
     */
    private function getBranchRevisions($name, $changeType, $oldrev, $newrev)
    {
        if ($changeType == self::TYPE_UPDATED) {
            // git rev-list old..new
            $revisions = $this->getRevisions(escapeshellarg($oldrev . '..' . $newrev));
        } else {
            // for new branch we write log about new commits only
            $revisions = $this->getRevisions(
                escapeshellarg($newrev) . ' --not ' . implode(' ', $this->escapeArrayShellArgs($this->alreadyExistsBranches))
            );

            // for new branches we check if they was separated from other branches in same push
            // see README.POST_RECEIVE_MAIL  "commit mail" part.
            foreach ($this->updatedBranches as $refname) {
                if ($this->isRevExistsInBranches($this->refs[$refname]['old'], [$name])) {
                    $this->cacheRevisions($name, $this->getRevisions(escapeshellarg($this->refs[$refname]['old'] . '..' . $newrev)));
                }
            }
        }

        $this->cacheRevisions($name, $revisions);

        return $revisions;
    }


    /**
     * Get list of revisions for $revRange
     *
     * Required already escaped string in $revRange!!!
     *
     * @param string $revRange A..B or A ^B C --not D   etc.
     * @return array revsions list
     */
    private function getRevisions($revRange)
    {
        $output = \Git::gitExec(
            'rev-list %s',
            $revRange
        );
        $revisions = $output ? explode("\n", trim($output)) : [];
        return $revisions;
    }


    /**
     * Get info for commit
     * It return array with items:
     * 'parents' -list of parents sha,
     * 'author', 'author_email', 'author_date' - info about author person
     * 'committer', 'committer_email', 'committer_date' - info about committer person
     * 'subject' - commit subject line
     * 'log' - full commit message
     *
     * Also cache revision info
     * @param string $revision revision
     * @return array commit info array
     */
    private function getCommitInfo($revision)
    {
        $raw = \Git::gitExec('rev-list -n 1 --format="%%P%%n%%an%%n%%ae%%n%%aD%%n%%cn%%n%%ce%%n%%cD%%n%%s%%n%%B" %s', escapeshellarg($revision));
        $raw = explode("\n", trim($raw), 10); //10 elements separated by \n, last element - log message, first(skipped) element - "commit sha"
        $data = [
            'parents'           => $raw[1],  // %P
            'author'            => $raw[2],  // %an
            'author_email'      => $raw[3],  // %ae
            'author_date'       => $raw[4],  // %aD
            'committer'         => $raw[5],  // %cn
            'committer_email'   => $raw[6],  // %ce
            'committer_date'    => $raw[7],  // %cD
            'subject'           => $raw[8],  // %s
            'log'               => $raw[9]   // %B
        ];
        return $data;
    }

    /**
     * Find info about bugs in log message
     * @param string $log log message
     * @return array array with bug numbers and links in values
     */
    private function getBugs($log)
    {
        $bugUrlPrefixes = [
            'pear' => 'http://pear.php.net/bugs/',
            'pecl' => 'https://bugs.php.net/',
            'php' => 'https://bugs.php.net/',
            '' => 'https://bugs.php.net/'
        ];
        $bugs = [];
        if (preg_match_all('/(?:(pecl|pear|php)\s*)?(?:bug|#)[\s#:]*([0-9]+)/iuX', $log, $matchedBugs, PREG_SET_ORDER)) {
            foreach($matchedBugs as $bug) {
                $bugs[$bug[2]] = $bugUrlPrefixes[strtolower($bug[1])] . $bug[2];
            }
        }
        return $bugs;
    }

    /**
     * Send mail about commit.
     * Subject: com %PROJECT%: %PATHS%
     * Body:
     * Commit: %SHA%
     * Author: %USER%                               Thu, 08 Mar 2012 12:39:48 +0000
     * Committer: %USER%                               Thu, 08 Mar 2012 12:39:48 +0000
     * Parents: %SHA_PARENTS%
     * Branches: %BRANCHES%
     *
     * Link: http://git.php.net/?p=%PROJECT_PATH%;a=commitdiff;h=%SHA%
     *
     * Log:
     * %MESSAGE%
     *
     * Bug: %BUG%
     *
     * --part1--
     * Changed paths:
     * %PATHS%
     * --/part1--
     *
     * --part2--
     * Diff:
     * %DIFF%
     * --/part2--
     *
     * @param string $revision commit revision
     * @param array $branches branches in current push with this commit
     */
    private function sendCommitMail($revision, $branches)
    {

        $paths = $this->getChangedPaths(escapeshellarg($revision));
        $pathsString = '';
        foreach ($paths as $path => $action)
        {
            $pathsString .= '  ' . $action . '  ' . $path . "\n";
        }

        $isTrivialMerge = count($paths) <= 0;

        if (!$isTrivialMerge) {

            $bnames = array_map(
                function($x) {
                    return str_replace('refs/heads/', '', $x);
                },
                $branches
            );

            $info = $this->getCommitInfo($revision);

            $diff =  \Git::gitExec('diff-tree --cc -r --no-commit-id %s', escapeshellarg($revision));

            $mail = new \Mail();
            $mail->setSubject($this->emailPrefix . 'com ' . $this->getRepositoryShortName() . ': ' . $info['subject'] . ': '. implode(' ', array_keys($paths)));
            $mail->setTimestamp(strtotime($info['committer_date']));

            $message = '';

            $message .= 'Commit:    ' . $revision . "\n";
            $message .= 'Author:    ' . $info['author'] . ' <' . $info['author_email'] . '>         ' . $info['author_date'] . "\n";
            if (($info['author'] != $info['committer']) || ($info['author_email'] != $info['committer_email'])) {
                $message .= 'Committer: ' . $info['committer'] . ' <' . $info['committer_email'] . '>      ' . $info['committer_date'] . "\n";
            }
            if ($info['parents']) $message .= 'Parents:   ' . $info['parents'] . "\n";

            $message .= "Branches:  " . implode(' ', $bnames) . "\n";
            $message .= "\n" . "Link:       http://git.php.net/?p=" . $this->getRepositoryName() . ";a=commitdiff;h=" . $revision . "\n";

            $message .= "\nLog:\n" . $info['log'] . "\n";

            if ($bugs = $this->getBugs($info['log'])) {
                $message .= "\nBugs:\n" . implode("\n", $bugs) . "\n";
            }

            if (strlen($pathsString) < 8192) {
                // inline changed paths
                $message .= "\nChanged paths:\n" . $pathsString . "\n";
                if ((strlen($pathsString) + strlen($diff)) < 8192) {
                    // inline diff
                    $message .= "\nDiff:\n" . $diff . "\n";
                } else {
                    // diff attach
                    $diffFile = 'diff_' . $revision . '.txt';
                    $mail->addTextFile($diffFile, $diff);
                    if ((strlen($message) + $mail->getFileLength($diffFile)) > 262144) {
                        // diff attach exceeded max size
                        $mail->dropFile($diffFile);
                        $message .= "\nDiff: <Diff exceeded maximum size>";
                    }
                }
            } else {
                // changed paths attach
                $pathsFile = 'paths_' . $revision . '.txt';
                $mail->addTextFile($pathsFile, $pathsString);
                if ((strlen($message) + $mail->getFileLength($pathsFile)) > 262144) {
                    // changed paths attach exceeded max size
                    $mail->dropFile($pathsFile);
                    $message .= "\nChanged paths: <changed paths exceeded maximum size>";
                } else {
                    // diff attach
                    $diffFile = 'diff_' . $revision . '.txt';
                    $mail->addTextFile($diffFile, $diff);
                    if ((strlen($message) + $mail->getFileLength($pathsFile) + $mail->getFileLength($diffFile)) > 262144) {
                        // diff attach exceeded max size
                        $mail->dropFile($diffFile);
                    }
                }
            }

            $mail->setMessage($message);

            $mail->setFrom($this->pushAuthor . '@php.net', $this->pushAuthorName);
            $mail->addTo($this->mailingList);

            $result = $mail->send();
            $this->log('revision ' . $revision . ($result ? ' was send' : ' error while sending'));
        }
    }


    /**
     * Check if revision exists in branches list
     * @param string $revision revision
     * @param array $branches branches
     * @return bool
     */
    private function isRevExistsInBranches($revision, array $branches) {
        $output = \Git::gitExec('rev-list --max-count=1 %s --not %s', escapeshellarg($revision), implode(' ', $this->escapeArrayShellArgs($branches)));
        return empty($output);
    }

}
