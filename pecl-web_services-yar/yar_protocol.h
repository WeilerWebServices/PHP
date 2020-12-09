/*
  +----------------------------------------------------------------------+
  | Yar - Light, concurrent RPC framework                                |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2011 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:  Xinchen Hui   <laruence@php.net>                            |
  |          Zhenyu  Zhang <zhangzhenyu@php.net>                         |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifndef PHP_YAR_PROTOCOL_H
#define PHP_YAR_PROTOCOL_H

#define YAR_PROTOCOL_MAGIC_NUM  0x80DFEC60

#ifdef PHP_WIN32
#pragma pack(push)
#pragma pack(1)
#endif
typedef struct _yar_header {
    unsigned int   id;
    unsigned short version;
    unsigned int   magic_num;
    unsigned int   reserved;
    unsigned char  provider[32];
    unsigned char  token[32];
    unsigned int   body_len; 
}
#ifndef PHP_WIN32
__attribute__ ((packed))
#endif
yar_header_t;
#ifdef PHP_WIN32
#pragma pack(pop)
#endif

yar_header_t * php_yar_protocol_parse(char **payload, size_t *payload_len, char **err_msg TSRMLS_DC);
void php_yar_protocol_render(yar_header_t *header, uint id, char *provider, char *token, uint body_len, uint reserved TSRMLS_DC);

#endif	/* PHP_YAR_PROTOCOL_H */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */