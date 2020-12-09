<?php

namespace App\Repository;

/**
 * Repository for retrieving data from the bugdb_patchtracker database table.
 */
class PatchRepository
{
    /**
     * Database handler.
     * @var \PDO
     */
    private $dbh;

    /**
     * Parent directory where patches are uploaded.
     * @var string
     */
    private $uploadsDir;

    /**
     * Class constructor.
     */
    public function __construct(\PDO $dbh, string $uploadsDir)
    {
        $this->dbh = $dbh;
        $this->uploadsDir = $uploadsDir;
    }

    /**
     * Retrieve a list of all patches and their revisions by given bug id.
     */
    public function findAllByBugId(int $bugId): array
    {
        $sql = 'SELECT patch, revision, developer
                FROM bugdb_patchtracker
                WHERE bugdb_id = ?
                ORDER BY revision DESC
        ';

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$bugId]);

        return $statement->fetchAll();
    }

    /**
     * Retrieve the developer by patch.
     */
    public function findDeveloper(int $bugId, string $patch, int $revision): string
    {
        $sql = 'SELECT developer
                FROM bugdb_patchtracker
                WHERE bugdb_id = ? AND patch = ? AND revision = ?
        ';

        $arguments = [$bugId, $patch, $revision];

        $statement = $this->dbh->prepare($sql);
        $statement->execute($arguments);

        return $statement->fetch(\PDO::FETCH_NUM)[0];
    }

    /**
     * Retrieve a list of all patches and their revisions.
     */
    public function findRevisions(int $bugId, string $patch): array
    {
        $sql = 'SELECT revision
                FROM bugdb_patchtracker
                WHERE bugdb_id = ? AND patch = ?
                ORDER BY revision DESC
        ';

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$bugId, $patch]);

        return $statement->fetchAll();
    }

    /**
     * Retrieve the actual contents of the patch.
     */
    public function getPatchContents(int $bugId, string $name, int $revision): string
    {
        $sql = 'SELECT bugdb_id
                FROM bugdb_patchtracker
                WHERE bugdb_id = ? AND patch = ? AND revision = ?
        ';

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$bugId, $name, $revision]);

        if ($statement->fetch(\PDO::FETCH_NUM)[0]) {
            $contents = @file_get_contents($this->getPatchPath($bugId, $name, $revision));

            if (!$contents) {
                throw new \Exception('Cannot retrieve patch revision "'.$revision.'" for patch "'.$name.'"');
            }

            return $contents;
        }

        throw new \Exception('No such patch revision "'.$revision.'", or no such patch "'.$name.'"');
    }

    /**
     * Get absolute patch file name.
     */
    private function getPatchPath(int $bugId, string $name, int $revision): string
    {
        return $this->uploadsDir.'/p'.$bugId.'/'.$name.'/'.'p'.$revision.'.patch.txt';
    }
}
