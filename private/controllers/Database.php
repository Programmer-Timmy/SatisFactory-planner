<?php

class Database
{
    private $connection;
    private $host;
    private $user;
    private $password;
    private $database;
    function __construct()
    {
        global $database;
        $this->host = $database['host'];
        $this->user = $database['user'];
        $this->password = $database['password'];
        $this->database = $database['database'];

        self::connect($this->host, $this->user, $this->password, $this->database);
    }

    /**
     * @param $host
     * @param $user
     * @param $password
     * @param $database
     * @return void
     */
    function connect($host, $user, $password, $database)
    {
        $this->connection = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    }

    /**
     * @param $sql
     * @return mixed
     */
    function prepare($sql)
    {
        return $this->connection->prepare($sql);
    }

    function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }


    /**
     * @param string $table
     * @param array $columns
     * @param array $values
     * @return void
     */
    public static function insert(string $table, array $columns, array $values, $connection = (new Database))
    {
        $sql = "INSERT INTO $table (";
        foreach ($columns as $column) {
            $sql .= "$column, ";
        }
        $sql = substr($sql, 0, -2);
        $sql .= ") VALUES (";
        foreach ($values as $value) {
            $sql .= "?,";
        }
        $sql = substr($sql, 0, -1);
        $sql .= ")";
        $sql = htmlspecialchars($sql);
        $stmt = $connection->prepare($sql);
        try {
            $stmt->execute($values);
            // Get the last inserted ID
            $lastInsertId = $connection->lastInsertId();
            return $lastInsertId;
        } catch (Exception $e) {
            // Construct error message including SQL query and values
            $errorMessage = "Error executing SQL query: " . $stmt->queryString . ". Values: " . json_encode($values) . ". Exception: " . $e->getMessage();

            // Handle the exception, such as logging or displaying an error message
            // Then you can rethrow the exception if needed
            throw new ErrorException($errorMessage);
        }
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $join
     * @param array $where
     * @param string $orderBy
     * @return mixed
     */
    public static function getAll(string $table, array $columns = ['*'],$join = [], array $where = [], string $orderBy = '')
    {
        $sql = "SELECT ";
        foreach ($columns as $column) {
            $sql .= "$column,";
        }
        $sql = substr($sql, 0, -1);
        $sql .= " FROM $table";
        foreach ($join as $joinTable => $joinOn) {
            $sql .= " JOIN $joinTable ON $joinOn";
        }
        if (!empty($where)) {
            $sql .= " WHERE ";
            foreach ($where as $column => $value) {
                $sql .= "$column = ? AND ";
            }
            $sql = substr($sql, 0, -5);
        }
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        $stmt = (new Database)->prepare($sql);
        $stmt->execute(array_values($where));
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @param string $table
     * @param array $columns
     * @param $join
     * @param array $where
     * @param string $orderBy
     * @return mixed
     */
    public static function get(string $table, array $columns = ['*'],$join = [], array $where = [], string $orderBy = '')
    {
        $sql = "SELECT ";
        foreach ($columns as $column) {
            $sql .= "$column,";
        }
        $sql = substr($sql, 0, -1);
        $sql .= " FROM $table";
        foreach ($join as $joinTable => $joinOn) {
            $sql .= " JOIN $joinTable ON $joinOn";
        }
        if (!empty($where)) {
            $sql .= " WHERE ";
            foreach ($where as $column => $value) {
                $sql .= "$column = ? AND ";
            }
            $sql = substr($sql, 0, -5);
        }
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        $stmt = (new Database)->prepare($sql);
        $stmt->execute(array_values($where));
        return $stmt->fetchobject();
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $values
     * @param array $where
     * @return void
     */
    public static function update(string $table, array $columns, array $values, array $where)
    {
        $sql = "UPDATE $table SET ";
        foreach ($columns as $column) {
            $sql .= "$column = ?,";
        }
        $sql = substr($sql, 0, -1);
        $sql .= " WHERE ";
        foreach ($where as $column => $value) {
            $sql .= "$column = ? AND ";
        }
        $sql = substr($sql, 0, -5);
        $sql = htmlspecialchars($sql);
        $stmt = (new Database)->prepare($sql);
        $stmt->execute(array_merge($values, array_values($where)));
    }

    /**
     * @param string $table
     * @param array $where
     * @return void
     */
    public static function delete(string $table, array $where)
    {
        $sql = "DELETE FROM $table WHERE ";
        foreach ($where as $column => $value) {
            $sql .= "$column = ? AND ";
        }
        $sql = substr($sql, 0, -5);
        $sql = htmlspecialchars($sql);
        $stmt = (new Database)->prepare($sql);
        $stmt->execute(array_values($where));
    }

    /**
     * @param string $query
     * @param array $values
     * @return mixed
     */
    public static function query(string $query, array $values = [])
    {
        $stmt = (new Database)->prepare($query);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}