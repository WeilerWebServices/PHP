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
  | Authors: Peter Kokot <petk@php.net>                                  |
  +----------------------------------------------------------------------+
*/

/**
 * Application configuration parameters.
 */

return [
    'db_host' => $config->get('db_host'),
    'db_name' => $config->get('db_name'),
    'db_username' => $config->get('db_username'),
    'db_password' => $config->get('db_password'),
    'scheme' => $config->get('scheme'),
    'host' => $config->get('host'),
    'tmp_dir' => $config->get('tmp_dir'),
    'rest_dir' => $config->get('rest_dir'),
    'packages_dir' => $config->get('packages_dir'),
    'valid_extension_name_regex' => $config->get('valid_extension_name_regex'),
    'max_username_length' => $config->get('max_username_length'),
    'php_master_api_url' => $config->get('php_master_api_url'),
    'valid_usernames_regex' => $config->get('valid_usernames_regex'),
    'max_file_size' => $config->get('max_file_size'),
    'tmp_uploads_dir' => $config->get('tmp_uploads_dir'),
];
