<?php

define("PATH", "");
define("DS", DIRECTORY_SEPARATOR);
define("ROOT", dirname(dirname(__FILE__)));
define("CORE_DIR", "core");
define("CORE_PATH", ROOT . DS . CORE_DIR);
define("CACHE_DIR", "cache");
define("CACHE_PATH", ROOT . DS . CACHE_DIR);
define("LOCALE_DIR", "locale");
define("LOCALE_PATH", CORE_PATH . DS . LOCALE_DIR);
define("THEMES_DIR", "themes");
define("THEMES_PATH", ROOT . DS . THEMES_DIR);
define("MODULES_DIR", "modules");
define("MODULES_PATH", ROOT . DS . MODULES_DIR);
define("MODEL_DIR", "model");
define("MODEL_PATH", CORE_PATH . DS . MODEL_DIR);
define("VIEW_DIR", "view");
define("VIEW_PATH", CORE_PATH . DS . VIEW_DIR);
define("CTRL_DIR", "controller");
define("CTRL_PATH", CORE_PATH . DS . CTRL_DIR);
define("DB_DIR", "db");
define("DB_PATH", CORE_PATH . DS . DB_DIR);
define("CONTRIB_DIR", "contrib");
define("CONTRIB_PATH", CORE_PATH . DS . CONTRIB_DIR);
define("HOME_DIR", "home");
define("HOME_PATH", ROOT . DS . HOME_DIR);

class Constants {
}