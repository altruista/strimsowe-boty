<?php

class Database
{
    static protected $instance;
    
    /**
     * @return Database
     */
    public static function getInstance()
    {
        if ( empty(self::$instance) )
        {
            self::$instance = new Database(include DIR_CONFIGS . "database.php");
        }
        return self::$instance;
    }
    
    protected $mysqli;
    
    public function __construct($config) 
    {        
        $this->mysqli = @new mysqli($config['host'], $config['user'], $config['password'], $config['database']);        
        
        if ( $this->mysqli->connect_errno )
        {
            Log::Add("Nie mogę połączyć się z bazą");
            die("Nie mogę połączyć się z bazą");
        }

        Log::Add('Połączono z baza danych');
    }
    
    private function _references(&$fields)
    {
        $refs = array();
        foreach($fields as &$field)
        {
            $refs[] = &$field;
        }
        return $refs;        
    }
    
    // w sumie to nie wiem po jaki chuj robilem preparowane zapytania mogłem sobie to darować ;x
    // chyba bałem się SQLi w linkach z wypoka :D
    private function _statement($query, $fields)
    {
        $types = str_repeat("s", count($fields));
        $stmt = $this->mysqli->prepare($query);
        if ( empty($stmt) ) {
            Log::Add("Nie moge utworzyć zapytania {$query}");
            exit;
        }
        call_user_func_array(array($stmt, 'bind_param'), array_merge(array($types), $this->_references($fields)));
        return $stmt;
    }
    
    private function _where($fields)
    {
        if ( empty($fields) ) 
            return " 1 ";
        
        $chunks = array();
        foreach($fields as $key => $value)
        {
            $chunks[] = "{$key} = ?";
        }
        
        return " (" . join(") AND (", $chunks) . ") ";
        
    }
    
    public function insert_ignore($table, $fields)
    {        
        $prep = array_fill(0, count($fields), '?');
        $query = "INSERT IGNORE INTO {$table} (".join(',',array_keys($fields)).") VALUES (".join(',',$prep).")";
                
        $stmt = $this->_statement($query, $fields);
        
        $stmt->execute();
    }
    
    public function update($table, $fields, $where = array(), $limit = false)
    {
        $query = "UPDATE {$table} SET ";
        $stmt_fields = $fields;
        
        $update_set = array();
        foreach($fields as $key => $value)
        {
            $update_set[] = "{$key} = ?";
        }
        
        $query .= join(', ', $update_set);
        
        $query .= " WHERE " . $this->_where($where);
        $stmt_fields = array_merge($stmt_fields, array_values($where));
        
        if ( $limit ) 
        {
            $query .= " LIMIT {$limit} ";
        }
        
        $stmt = $this->_statement($query, $stmt_fields);
        
        $stmt->execute();
    }
    
    public function get_rows($table, $where = array(), $order = false, $limit = false)
    {
        $query = "SELECT * FROM {$table} WHERE " . $this->_where($where);
        if ( $order )
        {
            $query .= " ORDER BY {$order} ";
        }
        if ( $limit )
        {
            $query .= " LIMIT {$limit} ";
        }
        
        $stmt = $this->_statement($query, $where);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ( ! $result->num_rows )
        {
            return FALSE;
        }
        
        $rows = array();
        while ($row = $result->fetch_object())
        {
            $rows[] = $row;
        }    
        return $rows;
    }
    
    public function get_row($table, $where, $order = false)
    {
        $rows = $this->get_rows($table, $where, $order, "1");
        return isset($rows[0]) ? $rows[0] : FALSE;
    }
    
    public function get_var($table, $var, $where = array())
    {
        $query = "SELECT {$var} FROM {$table} WHERE " . $this->_where($where);
        
        $stmt = $this->_statement($query, $where);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ( ! $result->num_rows )
        {
            return FALSE;
        }
        
        $row = $result->fetch_row();
        
        return $row[0];
    }
}