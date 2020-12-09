/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2007 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Antony Dovgal <tony2001@php.net>                             |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_tdb.h"

#include <fcntl.h>
#include <tdb.h>

static int le_tdb;
#define le_tdb_name "Trivial DB context"

#ifdef COMPILE_DL_TDB
ZEND_GET_MODULE(tdb)
#endif

typedef struct _php_tdb_context_t {
	int id;
	TDB_CONTEXT *tdb;
} php_tdb_context_t;

#define PHP_FETCH_TDB_RESOURCE(tdb_zval, ctx) \
	ZEND_FETCH_RESOURCE(ctx, php_tdb_context_t*, &tdb_zval, -1, le_tdb_name, le_tdb); \
	/* this is pure paranoia */ \
	if (!ctx->tdb) { \
		RETURN_FALSE; \
	}

static void php_tdb_list_dtor(zend_rsrc_list_entry *rsrc TSRMLS_DC) /* {{{ */
{
	php_tdb_context_t *ctx = (php_tdb_context_t *)rsrc->ptr;

	if (ctx->tdb) {
		tdb_close(ctx->tdb);
		ctx->tdb = NULL;
	}
	efree(ctx);
}
/* }}} */

static inline void php_tdb_errmsg(php_tdb_context_t *ctx TSRMLS_DC) /* {{{ */
{
	if (ctx && ctx->tdb && tdb_error(ctx->tdb)) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "%s", tdb_errorstr(ctx->tdb));
	}
}
/* }}} */

#define PHP_TDB_CONSTANT(name) \
	REGISTER_LONG_CONSTANT( #name, name, CONST_CS | CONST_PERSISTENT)

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(tdb)
{
	le_tdb = zend_register_list_destructors_ex(php_tdb_list_dtor, NULL, le_tdb_name, module_number);

	PHP_TDB_CONSTANT(TDB_REPLACE);
	PHP_TDB_CONSTANT(TDB_INSERT);
	PHP_TDB_CONSTANT(TDB_MODIFY);

	PHP_TDB_CONSTANT(TDB_CLEAR_IF_FIRST);
	PHP_TDB_CONSTANT(TDB_INTERNAL);
	PHP_TDB_CONSTANT(TDB_NOLOCK);
	PHP_TDB_CONSTANT(TDB_NOMMAP);
	PHP_TDB_CONSTANT(TDB_NOSYNC);
	PHP_TDB_CONSTANT(TDB_SEQNUM);

	PHP_TDB_CONSTANT(O_CREAT);
	PHP_TDB_CONSTANT(O_APPEND);
	PHP_TDB_CONSTANT(O_EXCL);
	PHP_TDB_CONSTANT(O_SYNC);
	PHP_TDB_CONSTANT(O_TRUNC);
	PHP_TDB_CONSTANT(O_RDONLY);
	PHP_TDB_CONSTANT(O_RDWR);

	PHP_TDB_CONSTANT(S_IRWXU);
	PHP_TDB_CONSTANT(S_IRUSR);
	PHP_TDB_CONSTANT(S_IWUSR);
	PHP_TDB_CONSTANT(S_IXUSR);
	PHP_TDB_CONSTANT(S_IRWXG);
	PHP_TDB_CONSTANT(S_IRGRP);
	PHP_TDB_CONSTANT(S_IWGRP);
	PHP_TDB_CONSTANT(S_IXGRP);
	PHP_TDB_CONSTANT(S_IRWXO);
	PHP_TDB_CONSTANT(S_IROTH);
	PHP_TDB_CONSTANT(S_IWOTH);
	PHP_TDB_CONSTANT(S_IXOTH);

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(tdb)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(tdb)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "Trivial DB support", "enabled");
	php_info_print_table_row(2, "Extension version", PHP_TDB_VERSION);
	php_info_print_table_row(2, "Revision", "$Revision$");
	php_info_print_table_end();
}
/* }}} */

/* {{{ proto resource tdb_open(string file [, int hash_size [, int tdb_flags [, int open_flags [, int mode ]]]]) 
 Open or create the database and return DB resource.
*/
static PHP_FUNCTION(tdb_open)
{
	php_tdb_context_t *ctx;
	TDB_CONTEXT *tdb;
	char *file;
	int file_len;
	long hash_size = 0;
	long tdb_flags = 0; 
	long open_flags = O_CREAT|O_RDWR;
	long mode = S_IRUSR|S_IWUSR;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|llll", &file, &file_len, &hash_size, &tdb_flags, &open_flags, &mode) == FAILURE) {
		return;
	}

#if PHP_MAJOR_VERSION < 6
	if (file_len && PG(safe_mode) && (!php_checkuid(file, NULL, CHECKUID_CHECK_FILE_AND_DIR))) {
		RETURN_FALSE;
	}
#endif

	if (php_check_open_basedir(file TSRMLS_CC)) {
		RETURN_FALSE;
	}

	if ((int)hash_size < 0) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "The integer value of hash_size cannot be less than zero");
		RETURN_FALSE;
	}
	
	tdb = tdb_open(file, (int)hash_size, (int)tdb_flags, (int)open_flags, (mode_t)mode);

	if (!tdb) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "%s", strerror(errno));
		RETURN_FALSE;
	}

	ctx = emalloc(sizeof(php_tdb_context_t));
	ctx->tdb = tdb;

	ctx->id = ZEND_REGISTER_RESOURCE(return_value, ctx, le_tdb);
}
/* }}} */

/* {{{ proto bool tdb_close(resource tdb) 
 Close & free the TDB resource
*/
static PHP_FUNCTION(tdb_close)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	int res;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &tdb) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	res = tdb_close(ctx->tdb);
	ctx->tdb = NULL;
	zend_list_delete(ctx->id);

	if (res) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "%s", strerror(errno));
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto string tdb_error(resource tdb) 
 Return the error status of the TDB context or false if no error occured
*/
static PHP_FUNCTION(tdb_error)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	enum TDB_ERROR err;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &tdb) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	err = tdb_error(ctx->tdb);

	/* don't return "Success" error */
	if (err) {
		RETURN_STRING((char *)tdb_errorstr(ctx->tdb), 1);
	}
	RETURN_FALSE;
}
/* }}} */

static void php_tdb_store(INTERNAL_FUNCTION_PARAMETERS, long flag) /* {{{ */
{
	php_tdb_context_t *ctx;
	zval *tdb;
	TDB_DATA tdb_key, tdb_data;
	char *key, *data;
	int key_len, data_len, res;

	if (!flag) {
		/* store */
		if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rssl", &tdb, &key, &key_len, &data, &data_len, &flag) == FAILURE) {
			return;
		}
		switch(flag) {
			case TDB_REPLACE:
			case TDB_INSERT:
			case TDB_MODIFY:
				break;
			default:
				php_error_docref(NULL TSRMLS_CC, E_WARNING, "Invalid operation mode specified: %ld", flag);
				RETURN_FALSE;
				break;
		}
	} else {
		/* update / insert / replace */
		if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rss", &tdb, &key, &key_len, &data, &data_len) == FAILURE) {
			return;
		}
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key.dptr = key;
	tdb_key.dsize = key_len;
	tdb_data.dptr = data;
	tdb_data.dsize = data_len;

	res = tdb_store(ctx->tdb, tdb_key, tdb_data, (int)flag);

	if (res) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool tdb_store(resource tdb, string key, string data, int flag) 
 Store the data in the database using the specified key
*/
static PHP_FUNCTION(tdb_store)
{
	php_tdb_store(INTERNAL_FUNCTION_PARAM_PASSTHRU, 0);
}
/* }}} */

/* {{{ proto bool tdb_replace(resource tdb, string key, string data) 
 Replaces or inserts the data in the database using the specified key.
 This works even if such record doesn't exist.
*/
static PHP_FUNCTION(tdb_replace)
{
	php_tdb_store(INTERNAL_FUNCTION_PARAM_PASSTHRU, TDB_REPLACE);
}
/* }}} */

/* {{{ proto bool tdb_insert(resource tdb, string key, string data) 
 Inserts the data in the database using the specified key.
 Insert will fail if such record already exists.
*/
static PHP_FUNCTION(tdb_insert)
{
	php_tdb_store(INTERNAL_FUNCTION_PARAM_PASSTHRU, TDB_INSERT);
}
/* }}} */

/* {{{ proto bool tdb_update(resource tdb, string key, string data) 
 Modifies the data in the database using the specified key.
 This operation will fail if such record doesn't exist.
*/
static PHP_FUNCTION(tdb_update)
{
	php_tdb_store(INTERNAL_FUNCTION_PARAM_PASSTHRU, TDB_MODIFY);
}
/* }}} */

/* {{{ proto bool tdb_append(resource tdb, string key, string data) 
 Appends the data to the record or creates new record with the data.
*/
static PHP_FUNCTION(tdb_append)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	TDB_DATA tdb_key, tdb_data;
	char *key, *data;
	int key_len, data_len, res;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rss", &tdb, &key, &key_len, &data, &data_len) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key.dptr = key;
	tdb_key.dsize = key_len;
	tdb_data.dptr = data;
	tdb_data.dsize = data_len;

	res = tdb_append(ctx->tdb, tdb_key, tdb_data);

	if (res) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto string tdb_fetch(resource tdb, string key) 
 Fetch the data from the database.
*/
static PHP_FUNCTION(tdb_fetch)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	TDB_DATA tdb_key, tdb_data;
	char *key;
	int key_len;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs", &tdb, &key, &key_len) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key.dptr = key;
	tdb_key.dsize = key_len;

	tdb_data = tdb_fetch(ctx->tdb, tdb_key);

	if (tdb_data.dptr == NULL) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETVAL_STRINGL(tdb_data.dptr, tdb_data.dsize, 1);
	free(tdb_data.dptr);
}
/* }}} */

/* {{{ proto bool tdb_delete(resource tdb, string key) 
 Delete record from the database
*/
static PHP_FUNCTION(tdb_delete)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	TDB_DATA tdb_key;
	char *key;
	int key_len, res;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs", &tdb, &key, &key_len) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key.dptr = key;
	tdb_key.dsize = key_len;

	res = tdb_delete(ctx->tdb, tdb_key);

	if (res) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool tdb_exists(resource tdb, string key) 
 Return true if such record exists and false otherwise
*/
static PHP_FUNCTION(tdb_exists)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	TDB_DATA tdb_key;
	char *key;
	int key_len, res;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs", &tdb, &key, &key_len) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key.dptr = key;
	tdb_key.dsize = key_len;

	res = tdb_exists(ctx->tdb, tdb_key);

	if (res) { /* found */
		RETURN_TRUE;
	}
	RETURN_FALSE;
}
/* }}} */

/* {{{ proto bool tdb_lock(resource tdb [, bool read_lock]) 
 Lock the database
*/
static PHP_FUNCTION(tdb_lock)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	int res;
	zend_bool read_lock = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r|b", &tdb, &read_lock) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	if (!read_lock) {
		res = tdb_lockall(ctx->tdb);
	} else {
		res = tdb_lockall_read(ctx->tdb);
	}

	if (res) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool tdb_unlock(resource tdb [, bool read_lock]) 
 Unlock the database
*/
static PHP_FUNCTION(tdb_unlock)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	int res;
	zend_bool read_lock = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r|b", &tdb, &read_lock) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	if (!read_lock) {
		res = tdb_unlockall(ctx->tdb);
	} else {
		res = tdb_unlockall_read(ctx->tdb);
	}

	if (res) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool tdb_chainlock(resource tdb, string key [, bool read_lock]) 
 Lock one hash chain
*/
static PHP_FUNCTION(tdb_chainlock)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	int res;
	zend_bool read_lock = 0;
	char *key;
	int key_len;
	TDB_DATA tdb_key;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs|b", &tdb, &key, &key_len, &read_lock) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key.dptr = key;
	tdb_key.dsize = key_len;

	if (!read_lock) {
		res = tdb_chainlock(ctx->tdb, tdb_key);
	} else {
		res = tdb_chainlock_read(ctx->tdb, tdb_key);
	}

	if (res) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool tdb_chainunlock(resource tdb, string key [, bool read_lock]) 
 Unlock one hash chain
*/
static PHP_FUNCTION(tdb_chainunlock)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	int res;
	zend_bool read_lock = 0;
	char *key;
	int key_len;
	TDB_DATA tdb_key;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs|b", &tdb, &key, &key_len, &read_lock) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key.dptr = key;
	tdb_key.dsize = key_len;

	if (!read_lock) {
		res = tdb_chainunlock(ctx->tdb, tdb_key);
	} else {
		res = tdb_chainunlock_read(ctx->tdb, tdb_key);
	}

	if (res) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETURN_TRUE;
}
/* }}} */

/* {{{ PHP_TDB_1_PARAM_FUNCTION(func) */
#define PHP_TDB_1_PARAM_FUNCTION(func)															\
	php_tdb_context_t *ctx;																		\
	zval *tdb;																					\
	int res;																					\
																								\
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &tdb) == FAILURE) {				\
		return;																					\
	}																							\
																								\
	PHP_FETCH_TDB_RESOURCE(tdb, ctx);															\
																								\
	res = func(ctx->tdb);																		\
																								\
	if (res) {																					\
		php_tdb_errmsg(ctx TSRMLS_CC);															\
		RETURN_FALSE;																			\
	}																							\
	RETURN_TRUE;																				

/* }}} */

/* {{{ proto bool tdb_transaction_start(resource tdb) 
 Start transaction 
*/
static PHP_FUNCTION(tdb_transaction_start)
{
	PHP_TDB_1_PARAM_FUNCTION(tdb_transaction_start);
}
/* }}} */

/* {{{ proto bool tdb_transaction_commit(resource tdb) 
 Commit transaction
*/
static PHP_FUNCTION(tdb_transaction_commit)
{
	PHP_TDB_1_PARAM_FUNCTION(tdb_transaction_commit);
}
/* }}} */

/* {{{ proto bool tdb_transaction_cancel(resource tdb) 
 Cancel transaction 
*/
static PHP_FUNCTION(tdb_transaction_cancel)
{
	PHP_TDB_1_PARAM_FUNCTION(tdb_transaction_cancel);
}
/* }}} */

/* {{{ proto bool tdb_transaction_recover(resource tdb) 
 Recover transaction 
*/
static PHP_FUNCTION(tdb_transaction_recover)
{
	PHP_TDB_1_PARAM_FUNCTION(tdb_transaction_recover);
}
/* }}} */

/* {{{ proto string tdb_first_key(resource tdb) 
 Return key of the first record in the db
*/
static PHP_FUNCTION(tdb_first_key)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	TDB_DATA tdb_key;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &tdb) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	tdb_key = tdb_firstkey(ctx->tdb);

	if (!tdb_key.dptr) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETVAL_STRINGL(tdb_key.dptr, tdb_key.dsize, 1);
	free(tdb_key.dptr);
}
/* }}} */

/* {{{ proto string tdb_next_key(resource tdb, string key) 
 Return key of the next entry in the db
*/
static PHP_FUNCTION(tdb_next_key)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	char *key;
	int key_len;
	TDB_DATA old_key, tdb_key;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs", &tdb, &key, &key_len) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	old_key.dptr = key;
	old_key.dsize = key_len;

	tdb_key = tdb_nextkey(ctx->tdb, old_key);

	if (!tdb_key.dptr) {
		php_tdb_errmsg(ctx TSRMLS_CC);
		RETURN_FALSE;
	}
	RETVAL_STRINGL(tdb_key.dptr, tdb_key.dsize, 1);
	free(tdb_key.dptr);
}
/* }}} */

/* {{{ proto bool tdb_set_max_dead(resource tdb, long dead) 
 Set the maximum number of dead records per hash chain
*/
static PHP_FUNCTION(tdb_set_max_dead)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	long dead;
	int dead_int;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rl", &tdb, &dead) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	dead_int = (int)dead;
	if (dead_int < 0) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "Maximum number of dead records cannot be less than zero");
		RETURN_FALSE;
	}

	tdb_set_max_dead(ctx->tdb, dead_int);
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto long tdb_get_seqnum(resource tdb)
 Get the TDB sequence number.
*/
static PHP_FUNCTION(tdb_get_seqnum)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	int res;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &tdb) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	res = tdb_get_seqnum(ctx->tdb);
	RETURN_LONG(res);
}
/* }}} */

/* {{{ proto long tdb_get_flags(resource tdb)
 Get the flags used when the db was created.
*/
static PHP_FUNCTION(tdb_get_flags)
{
	php_tdb_context_t *ctx;
	zval *tdb;
	int res;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &tdb) == FAILURE) {
		return;
	}

	PHP_FETCH_TDB_RESOURCE(tdb, ctx);

	res = tdb_get_flags(ctx->tdb);
	RETURN_LONG(res);
}
/* }}} */

/* {{{ tdb_functions[]
 */
zend_function_entry tdb_functions[] = {
	PHP_FE(tdb_open,	NULL)
	PHP_FE(tdb_close,	NULL)
	PHP_FE(tdb_error,	NULL)
	PHP_FE(tdb_store,	NULL)
	PHP_FE(tdb_replace,	NULL)
	PHP_FE(tdb_insert,	NULL)
	PHP_FE(tdb_update,	NULL)
	PHP_FE(tdb_append,	NULL)
	PHP_FE(tdb_fetch,	NULL)
	PHP_FE(tdb_delete,	NULL)
	PHP_FE(tdb_exists,	NULL)
	PHP_FE(tdb_lock,	NULL)
	PHP_FE(tdb_unlock,	NULL)
	PHP_FE(tdb_chainlock,	NULL)
	PHP_FE(tdb_chainunlock,	NULL)
	PHP_FE(tdb_transaction_start,	NULL)
	PHP_FE(tdb_transaction_commit,	NULL)
	PHP_FE(tdb_transaction_cancel,	NULL)
	PHP_FE(tdb_transaction_recover,	NULL)
	PHP_FE(tdb_first_key,	NULL)
	PHP_FE(tdb_next_key,	NULL)
	PHP_FE(tdb_set_max_dead,	NULL)
	PHP_FE(tdb_get_seqnum,	NULL)
	PHP_FE(tdb_get_flags,	NULL)
	{NULL, NULL, NULL}
};
/* }}} */

/* {{{ tdb_module_entry
 */
zend_module_entry tdb_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"tdb",
	tdb_functions,
	PHP_MINIT(tdb),
	PHP_MSHUTDOWN(tdb),
	NULL,
	NULL,
	PHP_MINFO(tdb),
#if ZEND_MODULE_API_NO >= 20010901
	PHP_TDB_VERSION,
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
