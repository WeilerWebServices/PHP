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
  | Authors:                                                             |
  +----------------------------------------------------------------------+
*/

require_once __DIR__.'/../include/pear-prepend.php';

// expected url vars: pacid package
if (isset($_GET['package']) && empty($_GET['pacid'])) {
    $key = $_GET['package'];
} else {
    $key = (isset($_GET['pacid'])) ? (int) $_GET['pacid'] : null;
}

$package = $packageEntity->info($key);

if (empty($package['name'])) {
    PEAR::raiseError('Invalid package');
}

echo $template->render('pages/package_changelog.php', [
    'package' => $package,
]);
