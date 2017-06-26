<?php


namespace Think\Db\Driver;

use Think\Db\Driver;

/**
 * Sqlite数据库驱动
 */
class Sqlite extends Driver
{

    /**
     * 解析pdo连接的dsn信息
     * @access public
     *
     * @param array $config 连接信息
     *
     * @return string
     */
    protected function parseDsn($config)
    {
        return 'sqlite:'.$config['database'];
    }

    /**
     * 取得数据表的字段信息
     * @access public
     *
     * @param $tableName
     *
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        $result = $this->query('PRAGMA table_info( '.$tableName.' )');
        $info = [];
        if ($result) {
            foreach ($result as $val) {
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => (bool)($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['dey']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                ];
            }
        }

        return $info;
    }

    /**
     * 取得数据库的表信息
     * @access public
     *
     * @param string $dbName
     *
     * @return array
     */
    public function getTables($dbName = '')
    {
        $result = $this->query(
            "SELECT name FROM sqlite_master WHERE type='table' "
            ."UNION ALL SELECT name FROM sqlite_temp_master "
            ."WHERE type='table' ORDER BY name"
        );
        $info = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * SQL指令安全过滤
     * @access public
     *
     * @param string $str SQL指令
     *
     * @return string
     */
    public function escapeString($str)
    {
        return str_ireplace("'", "''", $str);
    }

    /**
     * limit
     * @access public
     *
     * @param mixed $limit
     *
     * @return string
     */
    public function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
            } else {
                $limitStr .= ' LIMIT '.$limit[0].' ';
            }
        }

        return $limitStr;
    }
}
