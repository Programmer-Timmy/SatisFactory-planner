<?php

class NewDatabase {
    public PDO $connection;
    private string $host;
    private string $user;
    private string $password;
    private string $database;

    /**
     * Database constructor.
     *
     * This constructor is used to connect to the database
     *
     * Example:
     * $database = new Database();
     * $database = new Database(host: 'YOUR_HOST', user: 'YOUR_USER', password: 'YOUR_PASSWORD', database: 'YOUR_DATABASE');
     *
     * This will connect to the database using the default values from the config file or the values you provided
     *
     * @param string|null $host the host of the database
     * @param string|null $user the user of the database
     * @param string|null $password the password of the database (is a sensitive parameter, so it will be hidden in the logs)
     * @param string|null $database the name of the database
     * @param bool $useSQLite whether to use SQLite or not. This is used for testing purposes
     *
     * @return void
     */
    function __construct(string|null $host = null, string|null $user = null, #[SensitiveParameter()] string|null $password = null, string|null $databaseName = null, $useSQLite = false) {
        global $database;
        $databaseSettings = $database;

        // If the host, user, password or database is not set, use the default values from the config file
        $this->host = $host ?? $databaseSettings['host'];
        $this->user = $user ?? $databaseSettings['user'];
        $this->password = $password ?? $databaseSettings['password'];
        $this->database = $databaseName ?? $databaseSettings['database'];

        if ($useSQLite) {
            $this->connection = new PDO('sqlite::memory:');
        } else {

            self::connect($this->host, $this->user, $this->password, $this->database);
        }
    }

    /**
     * This function is used to connect to the database
     *
     * Example:
     * $database->connect(host: 'YOUR_HOST', user: 'YOUR_USER', password: 'YOUR_PASSWORD', database: 'YOUR_DATABASE');
     *
     * Note: This function is called automatically when the class is instantiated. You don't need to call it manually.
     *
     * @param $host the host of the database
     * @param $user the user of the database
     * @param $password the password of the database (is a sensitive parameter, so it will be hidden in the logs)
     * @param $database the name of the database
     *
     * @return void
     */
    private function connect(string $host, string $user, #[SensitiveParameter()] string $password, string $database): void {
        $this->connection = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    }

    /**
     * This function is used to get the last inserted ID
     *
     * Example:
     * $lastInsertId = Database::lastInsertId();
     *
     * @return string|false the last inserted ID
     */
    public function lastInsertId(): string|false {
        return $this->connection->lastInsertId();
    }

    /**
     * This function is used to insert data into the database
     *
     * Example:
     * Database::insert(table: 'users', columns: ['name', 'email'], values: ['John Doe', 'Jhon.doe@gmail.com']);
     *
     * This will insert a new user into the users table with name John Doe and email
     *
     * @param string $table The table to insert the data into
     * @param array $columns Array of columns to insert the data into
     * @param array $values Array of values to insert into the columns
     *
     * @return void
     */
    public function insert(string $table, array $columns, array $values): void {
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

        $this->execute($sql, $values);
    }

    /**
     * This function is used to execute a query
     *
     * Example:
     * Database::execute("INSERT INTO users (name, email) VALUES (?, ?)", ['John Doe', 'John.doe@gmail.com']);
     *
     * @param string $sql
     * @param array $values
     *
     * @return PDOStatement the executed statement
     */
    private function execute(string $sql, array $values): PDOStatement {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($values);
            return $stmt;
        } catch (PDOException $e) {
            throw new ErrorException("Error: " . $e->getMessage() . "SQL: $sql");
        }
    }

    /**
     * This function is used to prepare a query
     *
     * Example:
     * $stmt = Database::prepare("SELECT * FROM users WHERE id = ?");
     *
     * This will prepare a query to get all users with id 1
     *
     * Note: This function is used internally. You don't need to call it manually.
     *
     * @param $sql the query to prepare
     *
     * @return PDOStatement|false the prepared statement
     */
    private function prepare(string $sql): PDOStatement|false {
        return $this->connection->prepare($sql);
    }

    /**
     * This function is used to get data from the database
     *
     * Examples:
     *  $database->getAll(table: 'users');
     *  This will return all users
     *
     *  $database->getAll(table: 'users', columns: ['name', 'email'], where: ['id' => 1]);
     *  This will return the name and email of the user with id 1
     *
     *  $database->getAll(table: 'users', columns: ['name', 'email'], where: ['id' => 1], orderBy: 'name DESC');
     *  This will return the name and email of the user with id 1 ordered by name in descending order
     *
     *  $database->getAll(table: 'users', columns: ['name', 'email'], join: ['user_roles' => 'users.role_id = user_roles.id'], where: ['users.id' => 1]);
     *  This will return the name and email of the user with id 1 with a join on user_roles table
     *
     * @param string $table The table to get the data from
     * @param array $columns Array of columns to get the data from
     * @param array $join Array of tables to join ['table' => 'on']
     * @param array $where Array of columns and values to filter the data
     * @param string $orderBy The column to order the data by
     * @param int $fetchStyle The fetch style to use
     *
     * @return object|false
     */
    public function getAll(string $table, array $columns = ['*'], array $join = [], array $where = [], string $orderBy = '', int $fetchStyle = PDO::FETCH_OBJ): array|false {
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

        $stmt = $this->execute($sql, array_values($where));
        return $stmt->fetchAll($fetchStyle);
    }

    /**
     * This function is used to get a single row from the database
     *
     * Examples:
     * $database->get(table: 'users');
     * This will return the first user
     *
     * $database->get(table: 'users', columns: ['name', 'email'], where: ['id' => 1]);
     * This will return the name and email of the user with id 1
     *
     * $database->get(table: 'users', columns: ['name', 'email'], where: ['id' => 1], orderBy: 'name DESC');
     * This will return the name and email of the user with id 1 ordered by name in descending order
     *
     * $database->get(table: 'users', columns: ['name', 'email'], join: ['user_roles' => 'users.role_id = user_roles.id'], where: ['users.id' => 1]);
     * This will return the name and email of the user with id 1 with a join on user_roles table
     *
     * @param string $table The table to get the data from
     * @param array $columns Array of columns to get the data from
     * @param array $join Array of tables to join ['table' => 'on']
     * @param array $where Array of columns and values to filter the data
     * @param string $orderBy The column to order the data by
     *
     * @return object|false
     */
    public function get(string $table, array $columns = ['*'], array $join = [], array $where = [], string $orderBy = ''): object|false {
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
        $stmt = $this->prepare($sql);
        $stmt->execute(array_values($where));
        return $stmt->fetchobject();
    }

    /**
     * This function is used to update data in the database
     *
     * Example:
     * $database->update(table: 'users', columns: ['name', 'email'], values: ['John Doe', 'John.doe@gmail.com'], where: ['id' => 1]);
     *
     * This will update the name and email of the user with id 1
     *
     * @param string $table The table to update the data in
     * @param array $columns Array of columns to update the data in
     * @param array $values Array of values to update in the columns
     * @param array $where Array of columns and values to filter the data
     *
     * @return void
     */
    public function update(string $table, array $columns, array $values, array $where) {
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
        $this->execute($sql, array_merge($values, array_values($where)));
    }

    /**
     * This function is used to delete data from the database
     *
     * Example:
     * $database->delete(table: 'users', where: ['id' => 1]);
     *
     * This will delete the user with id 1
     *
     * @param string $table The table to delete the data from
     * @param array $where Array of columns and values to filter the data
     * @return void
     */
    public function delete(string $table, array $where,): void {
        $sql = "DELETE FROM $table WHERE ";
        foreach ($where as $column => $value) {
            $sql .= "$column = ? AND ";
        }
        $sql = substr($sql, 0, -5);
        $sql = htmlspecialchars($sql);
        $this->execute($sql, array_values($where));

    }

    /**
     * This function is used to run custom queries
     *
     * Example:
     * $database->query("SELECT * FROM users WHERE id = ?", [1]);
     *
     * This will return all users with id 1
     *
     * Note: If you want to use more functions of pdo before or after the query, you can pass the database object in $database
     *
     * @param string $query The query to run
     * @param array $values The values to bind to the query
     *
     * @return array|false
     */
    public function query(string $query, array $values = []): array|false {
        $values = array_map('htmlspecialchars', $values);

        $stmt = $this->execute($query, $values);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * This function is used to begin a transaction
     * A transaction is a set of queries that are executed together by using Database::commit($database)
     * If one query fails, the entire transaction can be rolled back by using Database::rollBack($database)
     *
     * Example:
     * $database->beginTransaction();
     *
     * This will begin a transaction
     *
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }

    /**
     * This function is used to commit a transaction
     * A transaction is a set of queries that are executed together by using Database::commit($database)
     * If one query fails, the entire transaction can be rolled back by using Database::rollBack($database)
     *
     * Example:
     * $database->commit();
     *
     * This will commit the transaction
     *
     * @return void
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * This function is used to roll back a transaction
     * A transaction is a set of queries that are executed together by using Database::commit($database)
     * If one query fails, the entire transaction can be rolled back by using Database::rollBack($database)
     *
     * Example:
     * $database->rollBack();
     *
     * This will roll back the transaction
     *
     * @return void
     */
    public function rollBack() {
        $this->connection->rollBack();
    }
}