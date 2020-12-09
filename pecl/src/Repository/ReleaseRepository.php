<?php

/*
  +----------------------------------------------------------------------+
  | The PECL website                                                     |
  +----------------------------------------------------------------------+
  | Copyright (c) 1999-2019 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | https://php.net/license/3_01.txt                                     |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Stig S. Bakken <ssb@fast.no>                                |
  |          Tomas V.V.Cox <cox@php.net>                                 |
  |          Martin Jansen <mj@php.net>                                  |
  |          Gregory Beaver <cellog@php.net>                             |
  |          Richard Heyes <richard@php.net>                             |
  |          Peter Kokot <petk@php.net>                                  |
  +----------------------------------------------------------------------+
*/

namespace App\Repository;

use App\Database;

/**
 * Repository class for releases.
 */
class ReleaseRepository
{
    private $database;

    /**
     * Number of recent releases returned.
     */
    const MAX_ITEMS_RETURNED = 5;

    /**
     * Class constructor.
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get recent releases
     *
     * @param  integer Number of releases to return
     * @return array
     */
    public function findRecent($max = self::MAX_ITEMS_RETURNED)
    {
        $sql = "SELECT packages.id AS id,
                    packages.name AS name,
                    packages.summary AS summary,
                    releases.version AS version,
                    releases.releasedate AS releasedate,
                    releases.releasenotes AS releasenotes,
                    releases.doneby AS doneby,
                    releases.state AS state
                FROM packages, releases
                WHERE packages.id = releases.package
                AND packages.approved = 1
                AND packages.package_type = 'pecl'
                ORDER BY releases.releasedate DESC
                LIMIT :limit
        ";

        return $this->database->run($sql, [':limit' => $max])->fetchAll();
    }

    /**
     * Get list of recent releases for the given category
     *
     * @param  string Name of the category
     * @param  int Number of releases to return
     * @return array
     */
    public function findRecentByCategoryName($categoryName, $max = self::MAX_ITEMS_RETURNED)
    {
        $sql = "SELECT p.id AS id,
                    p.name AS name,
                    p.summary AS summary,
                    r.version AS version,
                    r.releasedate AS releasedate,
                    r.releasenotes AS releasenotes,
                    r.doneby AS doneby,
                    r.state AS state
                FROM packages p, releases r, categories c
                WHERE p.id = r.package
                AND p.package_type = 'pecl'
                AND p.category = c.id
                AND c.name = :category
                ORDER BY r.releasedate DESC
                LIMIT :limit";

        $arguments = [
            ':category' => $categoryName,
            ':limit'    => $max
        ];

        return $this->database->run($sql, $arguments)->fetchAll();
    }

    /**
     * Find all releases by package id.
     */
    public function findByPackageId($packageId)
    {
        $sql = "SELECT id, version FROM releases WHERE package = :package_id";

        $releases = $this->database->run($sql, ['package_id' => $packageId])->fetchAll();

        usort($releases, [$this, 'sortVersions']);

        return $releases;
    }

    /**
     * Sorting function for usort.
     */
    private function sortVersions($a, $b)
    {
        return version_compare($b['version'], $a['version']);
    }

    /**
     * Get recent releases for the given user
     *
     * @param  string Handle of the user
     * @param  int    Number of releases
     * @return array
     */
    public function getRecentByUser($handle, $max = MAX_ITEMS_RETURNED)
    {
        $sql = "SELECT p.id AS id,
                    p.name AS name,
                    p.summary AS summary,
                    r.version AS version,
                    r.releasedate AS releasedate,
                    r.releasenotes AS releasenotes,
                    r.doneby AS doneby,
                    r.state AS state
                FROM packages p, releases r, maintains m
                WHERE p.package_type = 'pecl' AND p.id = r.package
                AND p.id = m.package AND m.handle = :handle
                ORDER BY r.releasedate DESC
                LIMIT :limit";

        return $this->database->run($sql, [':handle' => $handle, ':limit' => $max])->fetchAll();
    }

    /**
     * Get list of recent releases for the given package
     *
     * @param  string Name of the package
     * @param  int Number of releases to return
     * @return array
     */
    public function findRecentByPackageName($packageName, $max = MAX_ITEMS_RETURNED)
    {
        $sql = "SELECT p.id AS id,
                    p.name AS name,
                    p.summary AS summary,
                    r.version AS version,
                    r.releasedate AS releasedate,
                    r.releasenotes AS releasenotes,
                    r.doneby AS doneby,
                    r.state AS state
                FROM packages p, releases r
                WHERE p.id = r.package
                AND p.package_type = 'pecl'
                AND p.approved = 1
                AND p.name = :package_name
                ORDER BY r.releasedate DESC LIMIT :limit";

        $arguments = [
            ':package_name' => $packageName,
            ':limit'        => $max
        ];

        return $this->database->run($sql, $arguments)->fetchAll();
    }

    /**
     * Find all releases for download by given package id grouped in an array by
     * release version.
     *
     * @param int $packageId ID of the package.
     *
     * @return array
     */
    public function findDownloads($packageId)
    {
        $sql = "SELECT f.id AS `id`, f.release AS `release`,
                    f.platform AS platform, f.format AS format,
                    f.md5sum AS md5sum, f.basename AS basename,
                    f.fullpath AS fullpath, r.version AS version
                FROM files f, releases r
                WHERE f.package = :package_id AND f.release = r.id
        ";

        $statement = $this->database->run($sql, [':package_id' => $packageId]);

        $downloads = [];
        foreach ($statement->fetchAll() as $row) {
            $downloads[$row['version']][] = $row;
        }

        return $downloads;
    }
}
