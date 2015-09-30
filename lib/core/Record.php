<?php

class Record {

    private $recordEnabled = true;

    /**
     * Instancia um novo objeto do Modelo ou busca uma instancia 
     * da base de dados a partir da chave primária (id);
     * 
     * <b>Exemplo:</b><br>     * 
     * $m = new Modelo(5);
     * 
     * Retorna um objeto que representa o Modelo com id = 5
     * na base de dados;
     * 
     * @param mixed $id
     */
    public function __construct($id = NULL) {
        if (!is_null($id)) {
            if (!$this->load((int) $id))
                throw new Exception(__('Não foi possível localizar %s %d', array($this::TABLE, $id)));
        }
    }

    public function __call($name, $arguments) {
        if (DEBUG_MODE)
            new DebugMsg(__("Record: Método %s não encontrado no modelo %s.", array($name, get_called_class())));
    }

    public static function __callStatic($name, $arguments) {
        $pos = strpos($name, 'getListBy');
        if ($pos !== false) {
            $class = get_called_class();
            $table = $class::TABLE;
            $att = str_replace('getListBy', '', $name);
            $tabledesc = self::getTableDescription($table);
            foreach ($tabledesc as $field) {
                if (strtolower($field) == strtolower($att)) {
                    $c = new Criteria();
                    $c->setOrder($field);
                    return $class::getList($c);
                }
            }
        }
        $pos = strpos($name, 'findBy');
        if ($pos !== false) {
            $class = get_called_class();
            $table = $class::TABLE;
            $att = str_replace('findBy', '', $name);
            $tabledesc = self::getTableDescription($table);
            foreach ($tabledesc as $field) {
                if (strtolower($field) == strtolower($att)) {
                    $c = new Criteria();
                    if (count($arguments)) {
                        $c->addCondition($field, '=', $arguments[0]);
                    }
                    return $class::getList($c);
                }
            }
        }
    }

    public function __get($name) {
        /* if (DEBUG_MODE)
          new DebugMsg(__('O atributo %s não existe ou não foi declarado explicitamente no Modelo %s', array($name, get_called_class()))); */
    }

    public function __toString() {
        return $this->{$this::PK};
    }

    public function __unset($name) {
        if ($name == 'activerecord')
            $this->recordEnabled = false;
    }

    public function enableRecord() {
        $this->recordEnabled = true;
    }

    /**
     * Faz o mapeamento 1x1
     * Informe o nome do modelo e a chave estrangeiro que cria a relação
     * 
     * @param String $Model
     * @param String $FK
     * @return object $obj instância da classe passada por parâmetro $Model
     */
    protected function hasOne($Model, $FK) {
        $criteria = new Criteria();
        $criteria->addCondition($FK, '=', $this->{$this::PK});
        $objArr = $Model::getList($criteria);
        $obj = array_shift($objArr);
        if (is_null($obj))
            $obj = new $Model();
        return $obj;
    }

    /**
     * Realiza o mapeamento "possui muitos" (1 x N)
     * Informe o nome do Modelo que possui muitos e o nome do campo chave estrangeira 
     * que cria a relação
     * 
     * @param String $Model
     * @param String $FK
     * @param Criteria $criteria
     * @return object $obj instância da classe passada no parâmetro $Model
     */
    protected function hasMany($Model, $FK, $criteria = NULL) {
        $att = $Model . 's' . $criteria;
        if (!isset($this->$att) || !count($this->$att)) {
            if (is_null($criteria))
                $criteria = new Criteria();
            if (empty($this->{$this::PK})) {
                $criteria->addCondition($FK, 'IS', NULL);
            } else {
                $criteria->addCondition($FK, '=', $this->{$this::PK});
            }
            $this->$att = $Model::getList($criteria);
        }
        return $this->$att;
    }

    /**
     * Realiza o mapeamento "possui e pertence a muitos" (N x N)
     * 
     * @param String $MiddleModel - Modelo intermediário
     * @param String $sourceFK - chave estrangeira que aponta para o seu modelo
     * @param String $destinationFK - chave estrangeira que aponta para a tabela de destino
     * @param String $destinationModel - nome do Modelo da tabela de destino
     * @param Criteria $criteria
     * @return array $objs coleção de instância da classe passada por parâmetroModel $destinationModel
     */
    protected function hasNN($MiddleModel, $sourceFK, $destinationFK, $destinationModel, $criteria = NULL) {
        $db = new MysqlDB();
        $class = get_called_class();
        $sourceTable = $class::TABLE;
        $middleTable = $MiddleModel::TABLE;
        $destinationTable = $destinationModel::TABLE;

        $q = "SELECT $destinationTable.* FROM $sourceTable,$middleTable,$destinationTable";
        $q .= ' WHERE ' . $sourceTable . '.' . $class::PK . '=' . $middleTable . '.' . $sourceFK . ' AND ';
        $q .= $middleTable . '.' . $destinationFK . '=' . $destinationTable . '.' . $destinationModel::PK . ' AND ';
        $q .= $sourceTable . '.' . $class::PK . '=' . $this->{$this::PK};
        $criteriaConfig = $class::configure();
        if (empty($criteria) && empty($criteriaConfig)) {
            $db->query($q);
            return $db->getResults($destinationModel);
        }
        if (!empty($criteriaConfig)) {
            if (empty($criteria))
                $criteria = new Criteria();
            $criteria->merge($criteriaConfig);
        }
        $criteria->setTable($sourceTable);
        if ($criteria->getConditions()) {
            $conditions = array();
            $q .= ' AND ( ';
            foreach ($criteria->getConditions() as $c) {
                $label = $c[3];
                if (is_array($c))
                    $conditions[] = $c[0] . ' ' . $c[1] . ' :' . $label;
                else
                    $conditions[] = $c;
            }
            $q .= implode(' AND ', $conditions) . ' )';
            $q = str_replace('AND ) OR ( AND', ') OR (', $q);
            if ($criteria->getSqlConditions()) {
                $q .= ' AND ' . $criteria->getSqlConditions();
            }
        } elseif ($criteria->getSqlConditions()) {
            $q .= ' WHERE ' . $criteria->getSqlConditions();
        }

        if ($criteria->getOrder())
            $q .= ' ORDER BY ' . $criteria->getOrder();

        if ($criteria->getLimit())
            $q .= ' LIMIT ' . $criteria->getLimit();

        $db->query($q);
        $used = array();
        $i = 2;
        foreach ($criteria->getConditions() as $c) {
            if (!is_array($c))
                continue;
            $label = $c[3];
            while (array_search($label, $used)) {
                $label .= $i++;
            }
            $db->bind(':' . $label, $c[2]);
            $used[] = $label;
        }
        return $db->getResults($destinationModel);
    }

    /**
     * Realiza o mapeamento "pertence a" (N x 1)
     * Informe o nome do Modelo à quem pertence e o nome do campo chave estrangeira 
     * que cria a relação
     * 
     * @param string $Model
     * @param String $FK
     * @param Criteria $criteria
     * @return object $obj  instância da classe passada no parâmetro $Model
     */
    protected function belongsTo($Model, $FK) {
        $Model = '' . ucfirst($Model);
        $att = $Model . $FK;
        if (empty($this->$att)) {
            $this->$att = new $Model($this->$FK);
        }
        return $this->$att;
    }

    /**
     * Salva ou atualiza um registro.
     * 
     * <b>Exemplo de uso 1:</b>
     * 
     * $model = new Model();<br>
     * $model->foo = 'bar';<br>
     * $foo->save(); <br>// salva um novo registro na tabela Model<br>
     * 
     * <b>Exemplo de uso 2:</b>
     * 
     * $model = new Model(5);<br>
     * $model->foo = 'bar';<br>
     * $foo->save(); <br>// atualiza o registro com id = 5 na tabela Model<br>
     * 
     * <b>Exemplo de uso 3:</b>
     * 
     * $model = new Model();<br>
     * $foo->save($_POST); <br>// salva um novo registro na tabela Model 
     * com os dados recebido pelo formulário<br>
     * 
     * @param array $data Array associativo 'campo'=>'valor'
     * @return boolean
     * @throws Exception
     */
    public function save($data = NULL) {
        if (!$this->recordEnabled) {
            if (DEBUG_MODE) {
                new DebugMsg(__('Você não pode tentar salvar ou atualizar um modelo através de uma View.', ''), 2);
            }
            return;
        }
        $db = new MysqlDB();
        $pk = $this::PK;
        $table = $this::TABLE;
        $atts = array();
        $tabledesc = $this->getTableDescription($table);
        if (!is_null($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }

        if (empty($this->$pk)) {  // INSERT	
            foreach ($tabledesc as $field) {
                if (isset($this->$field))
                    if (trim($this->$field) == '')
                        $this->$field = NULL;
                $atts[$field] = $this->$field;
            }
            $q = "INSERT INTO $table (" . implode(',', array_keys($atts)) . ") VALUES (:" . implode(',:', array_keys($atts)) . ")";
            $db->query($q);
            foreach ($atts as $key => $value) {
                $db->bind(':' . $key, $value);
            }
            $result = $db->execute();
            $id = $db->lastInsertId();
            if (!$result)
                throw new Exception(__('Preencha todos os campo obrigatórios.'), 2);
            $this->$pk = $id;
            return $result;
        } else { // UPDATE
            if (DEBUG_MODE)
                new DebugMsg(__('atualizando %s...', $table));
            foreach ($tabledesc as $field) {
                if (isset($this->$field))
                    if (trim($this->$field) == '' || trim($this->$field) == NULL)
                        $this->$field = NULL;
                $atts[$field] = $this->$field;
                $fields[] = $field . '=:' . $field;
            }
            $q = "UPDATE $table SET " . implode(',', $fields);
            $q .= " WHERE $pk = :$pk";
            $db->query($q);
            foreach ($atts as $key => $value) {
                $db->bind(':' . $key, $value);
            }
            $result = $db->execute();
            if (!$result)
                throw new Exception(__('Preencha todos os campo obrigatórios.'), 2);
            return $result;
        }
    }

    protected function load($id) {
        $db = new MysqlDB();
        $pk = $this::PK;
        $table = $this::TABLE;
        $class = get_class($this);
        $criteriaConfig = $class::configure();
        if (empty($criteriaConfig))
            $criteriaConfig = new Criteria();
        $criteriaConfig->addCondition($pk, '=', $id);
        $data = $this->getFirst($criteriaConfig);
        if (empty($data))
            return false;
        foreach ($data as $key => $value) {
            $this->$key = $data->$key;
        }
        return true;
    }

    /**
     * deleta uma linha da tabela a partir de uma instancia do modelo
     * 
     * <b>Exemplo de uso:</b>
     * 
     * $model = new Model( 5 );<br>
     * $model->delete(); <br>
     * // apaga o registro 5 da tabela Model
     * 
     * @return boolean
     * @throws Exception
     */
    public function delete() {
        if (!$this->recordEnabled) {
            if (DEBUG_MODE)
                new DebugMsg(__('Você não pode tentar deletar um modelo através de uma View.'), 1);
            return;
        }
        $db = new MysqlDB();
        $pk = $this::PK;
        $table = $this::TABLE;
        $id = $this->$pk;
        $db->query("DELETE FROM $table WHERE $pk = :id");
        $db->bind(':id', $id);
        $result = $db->execute();
        if (!$result)
            throw new Exception(__('Não foi possível excluir o registro %d', $id), 3);
        return $result;
    }

    /**
     * Retorna uma coleção (array) de objetos de Model
     * 
     * <b>Exemplo de uso:</b>
     * 
     * $models = Model::getList(); 
     * 
     * @param Criteria $criteria
     * @return array de Objetos do modelo
     */
    public static function getList(Criteria $criteria = NULL) {
        $db = new MysqlDB();
        $class = get_called_class();
        $table = $class::TABLE;
        $criteriaConfig = $class::configure();
        if (empty($criteria) && empty($criteriaConfig)) {
            $q = "SELECT * FROM $table";
            $db->query($q);
            return $db->getResults($class);
        }
        if (!empty($criteriaConfig)) {
            if (empty($criteria))
                $criteria = new Criteria();
            $criteria->merge($criteriaConfig);
        }
        $criteria->setTable($table);
        $q = "SELECT * FROM " . implode(',', $criteria->getTables());
        if ($criteria->getConditions()) {
            $conditions = array();
            $q .= ' WHERE ( ';
            foreach ($criteria->getConditions() as $c) {
                $label = $c[3];
                if (is_array($c))
                    $conditions[] = $c[0] . ' ' . $c[1] . ' :' . $label;
                else
                    $conditions[] = $c;
            }
            $q .= implode(' AND ', $conditions) . ' )';
            $q = str_replace('AND ) OR ( AND', ') OR (', $q);
            if ($criteria->getSqlConditions()) {
                $q .= ' AND ' . $criteria->getSqlConditions();
            }
        } elseif ($criteria->getSqlConditions()) {
            $q .= ' WHERE ' . $criteria->getSqlConditions();
        }

        if ($criteria->getOrder())
            $q .= ' ORDER BY ' . $criteria->getOrder();

        if ($criteria->getLimit())
            $q .= ' LIMIT ' . $criteria->getLimit();

        $db->query($q);
        $used = array();
        $i = 2;
        foreach ($criteria->getConditions() as $c) {
            if (!is_array($c))
                continue;
            $label = $c[3];
            while (array_search($label, $used)) {
                $label .= $i++;
            }
            $db->bind(':' . $label, $c[2]);
            $used[] = $label;
        }
        return $db->getResults($class);
    }

    /**
     * Retorna a primeira ocorrência de Model da base de dados
     * 
     * @param Criteria $criteria
     * @return object 
     */
    public static function getFirst(Criteria $criteria = NULL) {
        $db = new MysqlDB();
        $class = get_called_class();
        $table = $class::TABLE;
        $q = "SELECT * FROM $table";

        $criteriaConfig = $class::configure();
        if (empty($criteria) && empty($criteriaConfig)) {
            $db->query($q);
            return $db->getRow($class);
        }
        if (!empty($criteriaConfig)) {
            if (empty($criteria))
                $criteria = new Criteria();
            $criteria->merge($criteriaConfig);
        }

        if ($criteria->getConditions()) {
            $conditions = array();
            foreach ($criteria->getConditions() as $c) {
                $conditions[] = $c[0] . ' ' . $c[1] . ' :' . $c[3];
            }
            $q .= ' WHERE ' . implode(' AND ', $conditions);
            if ($criteria->getSqlConditions()) {
                $q .= ' AND ' . $criteria->getSqlConditions();
            }
        } elseif ($criteria->getSqlConditions()) {
            $q .= ' WHERE ' . $criteria->getSqlConditions();
        }

        if ($criteria->getOrder())
            $q .= ' ORDER BY ' . $criteria->getOrder();

        $q .= ' LIMIT 1';

        $db->query($q);
        foreach ($criteria->getConditions() as $c) {
            $db->bind(':' . $c[3], $c[2]);
        }
        return $db->getRow($class);
    }

    /**
     * Retorna a quantidade de registro existentes
     * 
     * @param Criteria $criteria
     * @return int $n Número de linhas
     */
    public static function count(Criteria $criteria = NULL) {

        $db = new MysqlDB();
        $class = get_called_class();
        $table = $class::TABLE;
        $q = "SELECT count(*) as count FROM $table";
        $criteriaConfig = $class::configure();
        if (empty($criteria) && empty($criteriaConfig)) {
            $db->query($q);
            return $db->getRow()->count;
        }
        if (!empty($criteriaConfig)) {
            if (empty($criteria))
                $criteria = new Criteria();
            $criteria->merge($criteriaConfig);
        }
        if ($criteria->getConditions()) {
            $conditions = array();
            $used = array();
            $i = 2;
            $q .= ' WHERE (';
            foreach ($criteria->getConditions() as $c) {
                $label = $c[3];
                while (array_search($label, $used)) {
                    $label .= $i++;
                }
                if (is_array($c))
                    $conditions[] = $c[0] . ' ' . $c[1] . ' :' . $label;
                else
                    $conditions[] = $c;
                $used[] = $label;
            }
            $q .= implode(' AND ', $conditions) . ')';
            $q = str_replace('AND ) OR ( AND', ') OR (', $q);
            if ($criteria->getSqlConditions()) {
                $q .= ' AND ' . $criteria->getSqlConditions();
            }
        } elseif ($criteria->getSqlConditions()) {
            $q .= ' WHERE ' . $criteria->getSqlConditions();
        }

        if ($criteria->getOrder())
            $q .= ' ORDER BY ' . $criteria->getOrder();

        if ($criteria->getLimit())
            $q .= ' LIMIT ' . $criteria->getLimit();

        $db->query($q);
        foreach ($criteria->getConditions() as $c) {
            if (!is_array($c))
                continue;
            $db->bind(':' . $c[3], $c[2]);
        }
        return $db->getRow()->count;
    }

    private function getTableDescription($tablename) {
        $db = new MysqlDB();
        $db->query("DESCRIBE $tablename");
        $r = $db->getResults();
        $desc = array();
        foreach ($r as $rvalue) {
            $desc[] = $rvalue->Field;
        }
        return $desc;
    }

    public static function configure() {
        
    }

}

// fim da classe

