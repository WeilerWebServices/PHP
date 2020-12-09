<?php
  switch($_GET['test']) {
    case 'post':
      var_dump(filter_var_array($_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
      break;
    case 'getpost':
      var_dump(filter_var_array($_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
      var_dump(filter_var_array($_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
      break;
    case 'referer':
      echo htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8');
      break;
    case 'useragent':
      echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
      break;
    default:
      echo "Hello World!\n";
      echo "Hello World!";
      break;
  }
?>
