<?php

function mdie($message=null)
{
    if (!$message)
        die(sprintf("Error during query: %s\n", mysql_error()));
    else
        die(sprintf("%s: %s\n", $message, mysql_error()));
}

function mysql_query_params($query, $parameters=array(), $database=false)
{
    // Escape parameters as required & build parameters for callback function
    foreach ($parameters as $k=>$v)
    {
        if (is_int($v))
            $parameters[$k] = $v;
        else
            if (NULL === $v)
                $parameters[$k] = 'NULL';
            else
                "'" . mysql_real_escape_string($v) . "'";
    }
    $mysql_query_params__parameters = $parameters;

    // Callback for preg_replace_callback
    $cb = function($at) use ($parameters)
            {
                return $parameters[ $at[1]-1 ];
            };

    // Call using mysql_query
    if (false === $database)
        return mysql_query(
            preg_replace_callback('/\$([0-9]+)/', $cb, $query)
        );
    else
        return mysql_query(
            preg_replace_callback('/\$([0-9]+)/', $cb, $query),
            $database);
}


class DBModel
{
    public $primary_key = '';
    public $conn;
    public $table;
    public $columns = array();

    public function delete($id)
    {
        $query = sprintf("DELETE FROM %s WHERE %s = $1",
            $this->table,
            $this->primary_key
        );
        $result = mysql_query_params($query, array($id), $this->conn);
        if (!$result)
            mdie("Could not delete record");
        $worked = mysql_affected_rows($this->conn) == 1;
        mysql_free_result($result);
        return $worked;
    }

    public function insert($datahash=array())
    {
        $realkeys = array();
        $realvals = array();
        foreach($datahash as $key => $val)
        {
            if ($key == $this->primary_key or !in_array($key, $this->columns))
                continue;
            array_push($realkeys, $key);
            if (is_numeric($val))
                array_push($realvals, $val);
            else
                array_push($realvals, "'" . mysql_real_escape_string($val) . "'");
        }
        $cols = implode(",", $realkeys);
        $vals = implode(",", $realvals);
        $query = sprintf('INSERT INTO %s (%s) VALUES (%s);', $this->table,
            $cols, $vals);

        mysql_query($query, $this->conn);
        $newid = mysql_insert_id($this->conn);
        if (!$newid)
            throw new Exception("Record was not inserted");
        // need to get id here
        return $this->get($newid);
    }

    public function find($data=array(), $page = -1, $per_page = 20)
    {
        $sets = array();
        foreach ($data as $key => $val)
        {
            if (is_numeric($val))
                array_push($sets, sprintf("`%s` = %s", $key, $val));
            else
                array_push($sets, sprintf("`%s` = '%s'", $key,
                    mysql_real_escape_string($val)));
        }
        $clause = implode(" AND ", $sets);

        // Calculate the limit/offset. Intended for pagination.
        if ($page == -1)
            $limitoffset = '';
        else
            $limitoffset = sprintf("LIMIT %s OFFSET %s", $per_page,
                $per_page * ($page - 1));

        $query = sprintf("SELECT * FROM %s WHERE %s %s", $this->table, $clause,
            $limitoffset);
        $result = mysql_query($query, $this->conn);
        if (!$result)
            mdie("Could not search for instances");
        $insts = array();
        while ($row = mysql_fetch_assoc($result))
            array_push($insts, new DBInstance(&$this, $row));
        return $insts;
    }


    public function get($id)
    {
        $query = sprintf('SELECT * FROM %s WHERE %s = $1',
            $this->table,
            $this->primary_key
        );
        $result = mysql_query_params($query, array($id), $this->conn);
        if (!$result)
            mdie("Could not get instance");
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        return new DBInstance(&$this, $row);
    }

    public function getall()
    {
        $query = sprintf('SELECT * FROM %s', $this->table);
        $result = mysql_query($query, $this->conn);
        if (!$result)
            mdie("Could not retrieve all instances");

        $instances = array();
        while ($row = mysql_fetch_assoc($result)) {
            array_push($instances, new DBInstance(&$this, $row));
        }
        mysql_free_result($result);
        return $instances;
    }

    public function __construct(
        $table,
        $conn = null,
        $database = 'mysql',
        $user = 'root',
        $password = '',
        $server = 'localhost:3306'
        )
    {
        $this->table = $table;
        if (!$conn)
        {
            $this->server = $server;
            $this->username = $user;
            $this->password = $password;
            $this->database = $database;
            $this->conn = mysql_connect(
                $this->server,
                $this->username,
                $this->password
            ) or mdie("Could not connect");
        } else {
            $this->conn = $conn;
            $this->database = $database;
        }

        if (!(mysql_select_db($this->database, $this->conn)))
            mdie("Could not select db");

        // Next, query information_schema.tables for our primary key.
        // Use this for saving/deleting model instances
        // Note: Multicolumn keys are not supported

        $query = 'SELECT column_key = \'PRI\' as is_key, column_name '
            . 'FROM INFORMATION_SCHEMA.COLUMNS '
            . 'WHERE table_name = \'%s\' ';
        $result = mysql_query(sprintf($query, $this->table), $this->conn)
            or mdie("Could not get primary key");

        if (!$result)
            die(mysql_error());

        while ($row = mysql_fetch_assoc($result))
        {
            if ($row['is_key'] && $this->primary_key === '')
                $this->primary_key = $row['column_name'];
            array_push($this->columns, $row['column_name']);
        }
        mysql_free_result($result);
    }
}

class DBInstance
{
    private $columns;
    public $model;

    public function __get($varname)
    {
        if (array_key_exists($varname, $this->columns))
            return $this->columns[$varname];
        return NULL;
    }

    public function __set($varname, $varval)
    {
        if (array_key_exists($varname, $this->columns))
            $this->columns[$varname] = $varval;
    }

    public function pk()
    {
        return $this->model->primary_key;
    }

    public function save()
    {
        $sets = array();
        $pk = $this->model->primary_key;
        foreach ($this->columns as $key => $val)
        {
            // No! You can't change the primary key.
            if ($key == $pk)
                continue;
            if (is_numeric($val))
                array_push($sets, sprintf("`%s` = %s", $key, $val));
            else
                array_push($sets, sprintf("`%s` = '%s'", $key,
                    mysql_real_escape_string($val)));
        }
        $clause = implode(",\n", $sets);
        $query = sprintf("UPDATE %s SET %s WHERE %s = $1",
            $this->model->table,
            $clause,
            $pk
        );
        mysql_query_params($query, array($this->{$pk}), $this->model->conn);
        $count = mysql_affected_rows($this->model->conn);
        return ($count > 0);
    }

    public function delete()
    {
        $this->model->delete($this->{$this->pk()});
    }

    public function get_set()
    {
        return $this->columns;
    }

    public function __construct($model, $datahash = Array())
    {
        $this->model = $model;
        $this->columns = $datahash;
    }

}
