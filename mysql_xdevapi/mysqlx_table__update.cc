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
#include "php_api.h"
#include "mysqlnd_api.h"
#include "xmysqlnd/xmysqlnd.h"
#include "xmysqlnd/xmysqlnd_session.h"
#include "xmysqlnd/xmysqlnd_schema.h"
#include "xmysqlnd/xmysqlnd_stmt.h"
#include "xmysqlnd/xmysqlnd_table.h"
#include "xmysqlnd/xmysqlnd_crud_table_commands.h"
#include "php_mysqlx.h"
#include "mysqlx_crud_operation_bindable.h"
#include "mysqlx_crud_operation_limitable.h"
#include "mysqlx_crud_operation_sortable.h"
#include "mysqlx_class_properties.h"
#include "mysqlx_exception.h"
#include "mysqlx_executable.h"
#include "mysqlx_expression.h"
#include "mysqlx_sql_statement.h"
#include "mysqlx_table__update.h"
#include "util/allocator.h"
#include "util/object.h"
#include "util/zend_utils.h"

namespace mysqlx {

namespace devapi {

using namespace drv;

static zend_class_entry *mysqlx_table__update_class_entry;

ZEND_BEGIN_ARG_INFO_EX(arginfo_mysqlx_table__update__set, 0, ZEND_RETURN_VALUE, 2)
	ZEND_ARG_TYPE_INFO(no_pass_by_ref, table_field, IS_STRING, dont_allow_null)
	ZEND_ARG_INFO(no_pass_by_ref, expression_or_literal)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_mysqlx_table__update__where, 0, ZEND_RETURN_VALUE, 1)
	ZEND_ARG_INFO(no_pass_by_ref, where_expr)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_mysqlx_table__update__orderby, 0, ZEND_RETURN_VALUE, 1)
	ZEND_ARG_INFO(no_pass_by_ref, orderby_expr)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_mysqlx_table__update__limit, 0, ZEND_RETURN_VALUE, 1)
	ZEND_ARG_TYPE_INFO(no_pass_by_ref, rows, IS_LONG, dont_allow_null)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_mysqlx_table__update__bind, 0, ZEND_RETURN_VALUE, 1)
	ZEND_ARG_TYPE_INFO(no_pass_by_ref, placeholder_values, IS_ARRAY, dont_allow_null)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_mysqlx_table__update__execute, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()


struct st_mysqlx_table__update : public util::custom_allocable
{
	~st_mysqlx_table__update();
	XMYSQLND_CRUD_TABLE_OP__UPDATE* crud_op;
	xmysqlnd_table* table;
};

st_mysqlx_table__update::~st_mysqlx_table__update()
{
	xmysqlnd_table_free(table, nullptr, nullptr);
	xmysqlnd_crud_table_update__destroy(crud_op);
}


MYSQL_XDEVAPI_PHP_METHOD(mysqlx_table__update, __construct)
{
	UNUSED_INTERNAL_FUNCTION_PARAMETERS();
}

#define TWO_PARAM_OP__SET 1
#define TWO_PARAM_OP__ARRAY_INSERT 2
#define TWO_PARAM_OP__ARRAY_APPEND 3

static void
mysqlx_table__update__2_param_op(INTERNAL_FUNCTION_PARAMETERS, const unsigned int op_type)
{
	DBG_ENTER("mysqlx_table__update__2_param_op");

	zval* object_zv{nullptr};
	zval* value{nullptr};
	util::param_string table_field;
	zend_bool is_expression{FALSE};
	const zend_bool is_document = FALSE;
	if (FAILURE == util::zend::parse_method_parameters(execute_data, getThis(), "Osz",
												&object_zv, mysqlx_table__update_class_entry,
												&table_field.str, &table_field.len,
												&value))
	{
		DBG_VOID_RETURN;
	}
	switch (Z_TYPE_P(value)) {
		case IS_OBJECT:
			if (op_type == TWO_PARAM_OP__SET) {
				if (is_a_mysqlx_expression(value)) {
					/* get the string */
					value = get_mysqlx_expression(value);
					is_expression = TRUE;
				}
				break;
			}
			/* fall-through */
		case IS_STRING:
		case IS_DOUBLE:
		case IS_TRUE:
		case IS_FALSE:
		case IS_LONG:
		case IS_NULL:
			break;
		default:{
			RAISE_EXCEPTION(err_msg_invalid_type);
			DBG_VOID_RETURN;
		}

	}

	auto& data_object{ util::fetch_data_object<st_mysqlx_table__update>(object_zv) };

	RETVAL_FALSE;

	if (data_object.crud_op) {
		enum_func_status ret{FAIL};
		switch (op_type) {
			case TWO_PARAM_OP__SET:
				ret = xmysqlnd_crud_table_update__set(
					data_object.crud_op,
					table_field.to_view(),
					value,
					is_expression,
					is_document);
				break;
		}

		if (PASS == ret) {
			ZVAL_COPY(return_value, object_zv);
		}
	}
	DBG_VOID_RETURN;
}

MYSQL_XDEVAPI_PHP_METHOD(mysqlx_table__update, set)
{
	mysqlx_table__update__2_param_op(INTERNAL_FUNCTION_PARAM_PASSTHRU, TWO_PARAM_OP__SET);
}

MYSQL_XDEVAPI_PHP_METHOD(mysqlx_table__update, where)
{
	DBG_ENTER("mysqlx_table__update::where");

	zval* object_zv{nullptr};
	util::param_string where_expr;
	if (FAILURE == util::zend::parse_method_parameters(execute_data, getThis(), "Os",
												&object_zv, mysqlx_table__update_class_entry,
												&where_expr.str, &where_expr.len))
	{
		DBG_VOID_RETURN;
	}

	auto& data_object{ util::fetch_data_object<st_mysqlx_table__update>(object_zv) };

	RETVAL_FALSE;

	if (!where_expr.empty()) {
		if (PASS == xmysqlnd_crud_table_update__set_criteria(data_object.crud_op, where_expr.to_view())) {
			ZVAL_COPY(return_value, object_zv);
		}
	}

	DBG_VOID_RETURN;
}

MYSQL_XDEVAPI_PHP_METHOD(mysqlx_table__update, orderby)
{
	DBG_ENTER("mysqlx_table__update::orderby");

	zval* object_zv{nullptr};
	zval* orderby_expr{nullptr};
	int num_of_expr{0};
	if (FAILURE == util::zend::parse_method_parameters(execute_data, getThis(), "O+",
												&object_zv,
												mysqlx_table__update_class_entry,
												&orderby_expr,
												&num_of_expr))
	{
		DBG_VOID_RETURN;
	}

	auto& data_object{ util::fetch_data_object<st_mysqlx_table__update>(object_zv) };

	RETVAL_FALSE;

	if (!( data_object.crud_op && orderby_expr ) ) {
		DBG_VOID_RETURN;
	}

	for(int i{0}; i < num_of_expr ; ++i ) {
		switch (Z_TYPE(orderby_expr[i])) {
		case IS_STRING:
			{
				const util::string_view orderby_expr_str{ Z_STRVAL(orderby_expr[i]),
												Z_STRLEN(orderby_expr[i]) };
				if (PASS == xmysqlnd_crud_table_update__add_orderby(data_object.crud_op, orderby_expr_str)) {
					ZVAL_COPY(return_value, object_zv);
				}
			}
			break;
		case IS_ARRAY:
			{
				zval* entry{nullptr};
				MYSQLX_HASH_FOREACH_VAL(Z_ARRVAL(orderby_expr[i]), entry) {
					const util::string_view orderby_expr_str{ Z_STRVAL_P(entry), Z_STRLEN_P(entry) };
					if (Z_TYPE_P(entry) != IS_STRING) {
						RAISE_EXCEPTION(err_msg_wrong_param_1);
						DBG_VOID_RETURN;
					}
					if (FAIL == xmysqlnd_crud_table_update__add_orderby(data_object.crud_op, orderby_expr_str)) {
						RAISE_EXCEPTION(err_msg_add_orderby_fail);
						DBG_VOID_RETURN;
					}
				} ZEND_HASH_FOREACH_END();
				ZVAL_COPY(return_value, object_zv);
			}
			break;
		default:
			RAISE_EXCEPTION(err_msg_wrong_param_3);
			break;
		}
	}
	DBG_VOID_RETURN;
}

MYSQL_XDEVAPI_PHP_METHOD(mysqlx_table__update, limit)
{
	DBG_ENTER("mysqlx_table__update::limit");

	zval* object_zv{nullptr};
	zend_long rows;
	if (FAILURE == util::zend::parse_method_parameters(execute_data, getThis(), "Ol",
												&object_zv, mysqlx_table__update_class_entry,
												&rows))
	{
		DBG_VOID_RETURN;
	}

	if (rows < 0) {
		RAISE_EXCEPTION(err_msg_wrong_param_2);
		DBG_VOID_RETURN;
	}

	auto& data_object{ util::fetch_data_object<st_mysqlx_table__update>(object_zv) };

	RETVAL_FALSE;

	if (data_object.crud_op) {
		if (PASS == xmysqlnd_crud_table_update__set_limit(data_object.crud_op, rows)) {
			ZVAL_COPY(return_value, object_zv);
		}
	}

	DBG_VOID_RETURN;
}

MYSQL_XDEVAPI_PHP_METHOD(mysqlx_table__update, bind)
{
	DBG_ENTER("mysqlx_table__update::bind");

	zval* object_zv{nullptr};
	HashTable * bind_variables;
	if (FAILURE == util::zend::parse_method_parameters(execute_data, getThis(), "Oh",
												&object_zv, mysqlx_table__update_class_entry,
												&bind_variables))
	{
		DBG_VOID_RETURN;
	}

	auto& data_object{ util::fetch_data_object<st_mysqlx_table__update>(object_zv) };

	RETVAL_FALSE;

	if (data_object.crud_op) {
		zend_string * key;
		zval* val{nullptr};
		zend_bool op_success{TRUE};
		MYSQLX_HASH_FOREACH_STR_KEY_VAL(bind_variables, key, val) {
			if (key) {
				const util::string_view variable{ ZSTR_VAL(key), ZSTR_LEN(key) };
				if (FAIL == xmysqlnd_crud_table_update__bind_value(data_object.crud_op, variable, val)) {
					RAISE_EXCEPTION(err_msg_bind_fail);
					op_success = FALSE;
					break;
				}
			}
		} ZEND_HASH_FOREACH_END();
		if( op_success ) {
			ZVAL_COPY(return_value, object_zv);
		}
	}
	DBG_VOID_RETURN;
}

MYSQL_XDEVAPI_PHP_METHOD(mysqlx_table__update, execute)
{
	DBG_ENTER("mysqlx_table__update::execute");

	zval* object_zv{nullptr};
	if (FAILURE == util::zend::parse_method_parameters(execute_data, getThis(), "O",
												&object_zv, mysqlx_table__update_class_entry))
	{
		DBG_VOID_RETURN;
	}

	auto& data_object{ util::fetch_data_object<st_mysqlx_table__update>(object_zv) };

	RETVAL_FALSE;

	DBG_INF_FMT("crud_op=%p table=%p", data_object.crud_op, data_object.table);
	if (data_object.crud_op && data_object.table) {
		if (FALSE == xmysqlnd_crud_table_update__is_initialized(data_object.crud_op)) {
			RAISE_EXCEPTION(err_msg_update_fail);
		} else {
			xmysqlnd_stmt * stmt = data_object.table->update(data_object.crud_op);
			if (stmt) {
				zval stmt_zv;
				ZVAL_UNDEF(&stmt_zv);
				mysqlx_new_stmt(&stmt_zv, stmt);
				if (Z_TYPE(stmt_zv) == IS_NULL) {
					xmysqlnd_stmt_free(stmt, nullptr, nullptr);
				}
				if (Z_TYPE(stmt_zv) == IS_OBJECT) {
					zval zv;
					ZVAL_UNDEF(&zv);
					zend_long flags{0};
					mysqlx_statement_execute_read_response(Z_MYSQLX_P(&stmt_zv), flags, MYSQLX_RESULT, &zv);

					ZVAL_COPY(return_value, &zv);
					zval_dtor(&zv);
				}
				zval_ptr_dtor(&stmt_zv);
			}
		}
	}

	DBG_VOID_RETURN;
}

static const zend_function_entry mysqlx_table__update_methods[] = {
	PHP_ME(mysqlx_table__update, __construct,	nullptr,											ZEND_ACC_PRIVATE)

	PHP_ME(mysqlx_table__update, set,		arginfo_mysqlx_table__update__set,		ZEND_ACC_PUBLIC)
	PHP_ME(mysqlx_table__update, where,	arginfo_mysqlx_table__update__where,	ZEND_ACC_PUBLIC)
	PHP_ME(mysqlx_table__update, orderby,	arginfo_mysqlx_table__update__orderby,	ZEND_ACC_PUBLIC)
	PHP_ME(mysqlx_table__update, limit,	arginfo_mysqlx_table__update__limit,	ZEND_ACC_PUBLIC)
	PHP_ME(mysqlx_table__update, bind,		arginfo_mysqlx_table__update__bind,	ZEND_ACC_PUBLIC)

	PHP_ME(mysqlx_table__update, execute,	arginfo_mysqlx_table__update__execute,	ZEND_ACC_PUBLIC)

	{nullptr, nullptr, nullptr}
};

#if 0
static zval *
mysqlx_table__update_property__name(const st_mysqlx_object* obj, zval* return_value)
{
	const st_mysqlx_table__update* object = (const st_mysqlx_table__update* ) (obj->ptr);
	DBG_ENTER("mysqlx_table__update_property__name");
	if (object->table && !object->table->get_name().empty()) {
		ZVAL_STRINGL(return_value, object->table->get_name().data(), object->table->get_name().length());
	} else {
		/*
		  This means EG(uninitialized_value). If we return just return_value, this is an UNDEF-ed value
		  and ISSET will say 'true' while for EG(unin) it is false.
		  In short:
		  return nullptr; -> isset()===false, value is nullptr
		  return return_value; (without doing ZVAL_XXX)-> isset()===true, value is nullptr
		*/
		return_value = nullptr;
	}
	DBG_RETURN(return_value);
}

#endif

static zend_object_handlers mysqlx_object_table__update_handlers;
static HashTable mysqlx_table__update_properties;

const st_mysqlx_property_entry mysqlx_table__update_property_entries[] =
{
#if 0
	{std::string_view("name"), mysqlx_table__update_property__name,	nullptr},
#endif
	{std::string_view{}, nullptr, nullptr}
};

static void
mysqlx_table__update_free_storage(zend_object * object)
{
	util::free_object<st_mysqlx_table__update>(object);
}

static zend_object *
php_mysqlx_table__update_object_allocator(zend_class_entry* class_type)
{
	DBG_ENTER("php_mysqlx_table__update_object_allocator");
	st_mysqlx_object* mysqlx_object = util::alloc_object<st_mysqlx_table__update>(
		class_type,
		&mysqlx_object_table__update_handlers,
		&mysqlx_table__update_properties);
	DBG_RETURN(&mysqlx_object->zo);
}

void
mysqlx_register_table__update_class(UNUSED_INIT_FUNC_ARGS, zend_object_handlers* mysqlx_std_object_handlers)
{
	MYSQL_XDEVAPI_REGISTER_CLASS(
		mysqlx_table__update_class_entry,
		"TableUpdate",
		mysqlx_std_object_handlers,
		mysqlx_object_table__update_handlers,
		php_mysqlx_table__update_object_allocator,
		mysqlx_table__update_free_storage,
		mysqlx_table__update_methods,
		mysqlx_table__update_properties,
		mysqlx_table__update_property_entries,
		mysqlx_executable_interface_entry);

#if 0
	/* The following is needed for the Reflection API */
	zend_declare_property_null(mysqlx_table__update_class_entry, "name",	sizeof("name") - 1,	ZEND_ACC_PUBLIC);
#endif
}

void
mysqlx_unregister_table__update_class(UNUSED_SHUTDOWN_FUNC_ARGS)
{
	zend_hash_destroy(&mysqlx_table__update_properties);
}

void
mysqlx_new_table__update(zval* return_value, xmysqlnd_table* table)
{
	DBG_ENTER("mysqlx_new_table__update");
	st_mysqlx_table__update& data_object{
		util::init_object<st_mysqlx_table__update>(mysqlx_table__update_class_entry, return_value) };
	data_object.table = table->get_reference();
	data_object.crud_op = xmysqlnd_crud_table_update__create(
		data_object.table->get_schema()->get_name(),
		data_object.table->get_name());
	DBG_VOID_RETURN;
}

} // namespace devapi

} // namespace mysqlx
