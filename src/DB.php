<?php

final class DB
{
    const TABLES = [
        'user' => 'cb_users',
        'group' => 'cb_groups',
        'task' => DATA_TABLE.'47',
        'company' => DATA_TABLE.'42',
        'employee' => DATA_TABLE.'46',
    ];

    public static function query(string $sql, array $parameters = [])
    {
        self::processSql($sql, $parameters);
        //echo nl2br($sql).'<hr>';
        $result = sql_query($sql);
        $resultArray = [];

        while ($row = sql_fetch_assoc($result)) {
            $resultArray[] = $row;
        }

        return $resultArray;
    }

    /**
     * @return array|null
     */
    public static function row(string $sql, array $parameters = [])
    {
        $resultArray = self::query($sql, $parameters);

        if (isset($resultArray[0])) {
            return $resultArray[0];
        }

        return null;
    }

    public static function value($sql, array $parameters = [])
    {
        foreach (self::query($sql, $parameters) as $row) {
            foreach ($row as $value) {
                return $value;
            }
        }

        return null;
    }

    public static function column($sql)
    {
        $column = [];
        foreach (self::query($sql) as $row) {
            foreach ($row as $val) {
                $column[] = $val;
                break;
            }
        }

        return $column;
    }

    private static function processSql(string &$sql, array $parameters)
    {
        self::setTables($sql);
        self::setParameters($sql, $parameters);

        if (preg_match('#[{}]#', $sql)) {
            throw new \LogicException(sprintf('"%s" query is not valid', $sql));
        }
    }

    private static function setTables(string &$sql)
    {
        foreach (self::TABLES as $alias => $table) {
            $sql = str_replace(sprintf('{table.%s}', $alias), $table, $sql);
        }
    }

    private static function setParameters(string &$sql, array $parameters)
    {
        foreach ($parameters as $key => $parameter) {
            $sql = str_replace(sprintf('{%s}', $key), self::processParameter($parameter), $sql);
        }
    }

    private static function processParameter($parameter): string
    {
        $processParameter = function ($parameter): string {
            if (null === $parameter) {
                return 'NULL';
            }

            return "'$parameter'";
        };

        if (is_array($parameter)) {
            return implode(', ', array_map($processParameter, $parameter));
        }

        return $processParameter($parameter);
    }
}
