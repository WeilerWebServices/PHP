/*
  +----------------------------------------------------------------------+
  | PHP Version 7                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006-2020 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Andrey Hristov <andrey@php.net>                             |
  +----------------------------------------------------------------------+
*/
#ifndef MYSQLX_BASE_RESULT_H
#define MYSQLX_BASE_RESULT_H

#include "util/allocator.h"

namespace mysqlx {

namespace drv {
struct st_xmysqlnd_stmt_result;
}

namespace devapi {

extern zend_class_entry * mysqlx_base_result_interface_entry;

struct st_mysqlx_base_result : public util::custom_allocable
{
	drv::st_xmysqlnd_stmt_result* result;
};

void mysqlx_register_base_result_interface(INIT_FUNC_ARGS, zend_object_handlers* mysqlx_std_object_handlers);
void mysqlx_unregister_base_result_interface(SHUTDOWN_FUNC_ARGS);

} // namespace devapi

} // namespace mysqlx

#endif /* MYSQLX_BASE_RESULT_H */
