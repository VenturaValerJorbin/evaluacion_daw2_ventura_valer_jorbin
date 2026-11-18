<?php

class Model extends Conexion
{
    // Tabla del modelo; si no se define, se deduce del nombre de la clase (ProductosModel -> productos)
    protected $table;
    protected $primaryKey = "id";

    // Estado del constructor de consultas; se reinicia despues de cada get()
    protected $select = "*";
    protected $joins = [];
    protected $whereBuilder = [];
    protected $bindings = [];
    protected $orderBy = [];
    protected $limit = "";

    // Columnas por tabla ya consultadas, para no repetir SHOW COLUMNS en la misma peticion
    private static $columnCache = [];

    // La tabla tambien puede indicarse al instanciar, sin crear una clase: new Model("categorias")
    public function __construct($table = null)
    {
        parent::__construct();
        if ($table !== null) {
            $this->table = $table;
        }
        if (empty($this->table)) {
            $name = preg_replace("/Model$/", "", static::class);
            $this->table = strtolower(preg_replace("/(?<!^)[A-Z]/", "_$0", $name));
        }
    }

    // Valida un nombre de tabla o columna (admite alias.columna) y lo encierra entre comillas invertidas
    // Los nombres no se pueden enlazar como parametros, por eso se restringen a letras, numeros y guion bajo
    protected function ident($name)
    {
        $name = trim($name);
        if (!preg_match("/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/", $name)) {
            throw new InvalidArgumentException("Identificador no valido: {$name}");
        }
        return "`" . str_replace(".", "`.`", $name) . "`";
    }

    // Descarta los datos que no corresponden a columnas reales de la tabla (por ejemplo, campos extra del formulario)
    // En update tambien se excluye la clave primaria
    protected function onlyFillable($data, $isInsert)
    {
        if (!isset(self::$columnCache[$this->table])) {
            $rows = $this->connect()->query("SHOW COLUMNS FROM " . $this->ident($this->table))->fetchAll();
            self::$columnCache[$this->table] = array_column($rows, "Field");
        }
        $data = array_intersect_key($data, array_flip(self::$columnCache[$this->table]));
        if (!$isInsert) {
            unset($data[$this->primaryKey]);
        }
        return $data;
    }

    // Ejecuta una consulta preparada; el tipo PDO de cada parametro se deduce de su tipo en PHP
    // (los enteros se envian como numero, de modo que columnas bit o tinyint se comparan correctamente)
    protected function query($sql, $params = [])
    {
        $stmt = $this->connect()->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $value = (int)$value;
            }
            if ($value === null) {
                $type = PDO::PARAM_NULL;
            } elseif (is_int($value)) {
                $type = PDO::PARAM_INT;
            } else {
                $type = PDO::PARAM_STR;
            }
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    // Todos los registros de la tabla
    public function all()
    {
        return $this->get();
    }

    // Un registro por su clave primaria, o null si no existe
    public function find($id)
    {
        return $this->where($this->primaryKey, $id)->first();
    }

    // Inserta un registro usando solo las columnas validas del arreglo; devuelve la clave primaria generada
    public function insert($data)
    {
        $data = $this->onlyFillable($data, true);
        if (empty($data)) {
            throw new InvalidArgumentException("No hay datos validos para insertar");
        }
        $columns = [];
        $holders = [];
        $params = [];
        foreach ($data as $key => $value) {
            $columns[] = $this->ident($key);
            $holders[] = ":c_{$key}";
            $params[":c_{$key}"] = $value;
        }
        $sql = "INSERT INTO " . $this->ident($this->table) . " (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $holders) . ")";
        $this->query($sql, $params);
        return $data[$this->primaryKey] ?? $this->connect()->lastInsertId();
    }

    // Actualiza las columnas indicadas del registro; devuelve false si no hay columnas validas que actualizar
    public function update($id, $data)
    {
        $data = $this->onlyFillable($data, false);
        if (empty($data)) {
            return false;
        }
        $sets = [];
        $params = [":pk_value" => $id];
        foreach ($data as $key => $value) {
            $sets[] = $this->ident($key) . " = :c_{$key}";
            $params[":c_{$key}"] = $value;
        }
        $sql = "UPDATE " . $this->ident($this->table) . " SET " . implode(", ", $sets) . " WHERE " . $this->ident($this->primaryKey) . " = :pk_value";
        $this->query($sql, $params);
        return true;
    }

    // Elimina el registro de la tabla
    public function delete($id)
    {
        $sql = "DELETE FROM " . $this->ident($this->table) . " WHERE " . $this->ident($this->primaryKey) . " = :pk_value";
        $this->query($sql, [":pk_value" => $id]);
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BUILDER
    |--------------------------------------------------------------------------
    */
    // Columnas a consultar; recibe SQL escrito por el programador, nunca datos del usuario
    public function select($fields)
    {
        $this->select = is_array($fields) ? implode(", ", $fields) : $fields;
        return $this;
    }

    // Agrega un JOIN: el tipo y la tabla se validan, la condicion ON es SQL escrito por el programador
    public function join($table, $on, $type = "INNER")
    {
        $type = strtoupper($type);
        if (!in_array($type, ["INNER", "LEFT", "RIGHT"], true)) {
            throw new InvalidArgumentException("Tipo de join no valido: {$type}");
        }
        if (!preg_match("/^[A-Za-z_][A-Za-z0-9_]*( (AS )?[A-Za-z_][A-Za-z0-9_]*)?$/i", trim($table))) {
            throw new InvalidArgumentException("Tabla no valida: {$table}");
        }
        $this->joins[] = "{$type} JOIN " . trim($table) . " ON {$on}";
        return $this;
    }

    // Formas aceptadas: where(columna, valor), where(columna, operador, valor), un arreglo columna => valor
    // o un texto SQL completo. Los valores se enlazan como parametros, no se concatenan en la consulta
    public function where($condition, $operator = null, $value = null)
    {
        $args = func_num_args();
        if (is_array($condition)) {
            foreach ($condition as $key => $item) {
                if (is_int($key)) {
                    $this->whereBuilder[] = $item;
                } else {
                    $this->addCondition($key, "=", $item);
                }
            }
            return $this;
        }
        if ($args === 1) {
            $this->whereBuilder[] = $condition;
            return $this;
        }
        if ($args === 2) {
            $value = $operator;
            $operator = "=";
        }
        $this->addCondition($condition, $operator, $value);
        return $this;
    }

    // Agrega una condicion con operador permitido y valor enlazado; comparar con null genera IS NULL / IS NOT NULL
    private function addCondition($column, $operator, $value)
    {
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, ["=", "<>", "!=", "<", ">", "<=", ">=", "LIKE", "NOT LIKE"], true)) {
            throw new InvalidArgumentException("Operador no valido: {$operator}");
        }
        $col = $this->ident($column);
        if ($value === null && $operator === "=") {
            $this->whereBuilder[] = "{$col} IS NULL";
            return;
        }
        if ($value === null && ($operator === "<>" || $operator === "!=")) {
            $this->whereBuilder[] = "{$col} IS NOT NULL";
            return;
        }
        $holder = ":w" . count($this->bindings);
        $this->whereBuilder[] = "{$col} {$operator} {$holder}";
        $this->bindings[$holder] = $value;
    }

    public function orderBy($field, $direction = "ASC")
    {
        $direction = strtoupper($direction) === "DESC" ? "DESC" : "ASC";
        $this->orderBy[] = $this->ident($field) . " {$direction}";
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $this->limit = "LIMIT " . (int)$limit . ($offset !== null ? " OFFSET " . (int)$offset : "");
        return $this;
    }

    // Arma y ejecuta el SELECT con lo acumulado en el constructor
    // El estado se reinicia siempre al terminar, incluso si la consulta falla
    public function get()
    {
        try {
            $sql = "SELECT {$this->select} FROM " . $this->ident($this->table);
            if (!empty($this->joins)) {
                $sql .= " " . implode(" ", $this->joins);
            }
            if (!empty($this->whereBuilder)) {
                $sql .= " WHERE " . implode(" AND ", $this->whereBuilder);
            }
            if (!empty($this->orderBy)) {
                $sql .= " ORDER BY " . implode(", ", $this->orderBy);
            }
            if ($this->limit !== "") {
                $sql .= " " . $this->limit;
            }
            return $this->query($sql, $this->bindings)->fetchAll();
        } finally {
            $this->resetQuery();
        }
    }

    // Primer resultado de la consulta, o null
    public function first()
    {
        $this->limit(1);
        $result = $this->get();
        return $result[0] ?? null;
    }

    private function resetQuery()
    {
        $this->select = "*";
        $this->joins = [];
        $this->whereBuilder = [];
        $this->bindings = [];
        $this->orderBy = [];
        $this->limit = "";
    }
}
