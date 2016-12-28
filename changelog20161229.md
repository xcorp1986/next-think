* remove APP_USE_NAMESPACE from src/Conf/convention.php
* move Think\Think::start() from src/Bootstrap.php to index.php(the application main entrance)
* add magic function __clone & __wakeup in class \Think\Think
* remove function load() in src/Common/function.php
* remove class Think\Session\Driver\Db
* remove class Think\Session\Driver\Mysqli
* remove variables like Think.xxx in template parser
* remove \Behavior\CheckActionRouteBehavior
* remove \Behavior\RobotCheckBehavior
* remove \Behavior\BrowserCheckBehavior
* remove \Behavior\AgentCheckBehavior