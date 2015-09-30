<?php

class InstallController extends AppController {

    private $dbschema = NULL;

    public function __construct() {
        exit;
    }

    public function index() {
        $ok = true;
        if (!$this->checkDB()) {
            $ok = false;
        }
        if (!is_writable('model')) {
            new Msg('O diretório<strong> /model </strong>não tem permissão de escrita', 3);
            $ok = false;
        }
        if (!is_writable('controller')) {
            new Msg('O diretório<strong> /controller </strong>não tem permissão de escrita', 3);
            $ok = false;
        }
        if (!is_writable('view')) {
            new Msg('O diretório<strong> /view </strong>não tem permissão de escrita', 3);
            $ok = false;
        }
        if (!is_writable('template')) {
            new Msg('O diretório<strong> /template </strong>não tem permissão de escrita', 3);
            $ok = false;
        }
        if ($ok) {
            $this->set('tables', $this->getTables());
            $this->set('ok', TRUE);
        } else {
            $this->set('tables', array());
            $this->set('ok', FALSE);
        }
    }

    public function post_index() {
        $tables = $this->getTables();
        $this->set('tables', $tables);
        $overwrite = FALSE;
        if (isset($_POST['sobrescrever']))
            $overwrite = TRUE;
        foreach ($_POST as $key => $value) {
            foreach ($tables as $t) {
                if ('model' . $t->name == $key) {
                    $this->installM($t->name, $overwrite);
                }
                if ('controller' . $t->name == $key) {
                    $this->installC($t->name, $overwrite);
                }
                if ('view' . $t->name == $key) {
                    $this->installV($t->name, $overwrite);
                }
            }
            if ($key == 'menu')
                $this->installMenu();
        }
        $this->go('Install', 'index');
    }

    private function installM($table, $overwrite = false) {
        if (file_exists('model/' . ucfirst($table) . '.php') && !$overwrite) {
            new Msg(__('Model %s.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }

        $used = array('1');
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);

        $handle = fopen("model/" . ucfirst($table) . ".php", 'w');
        if (!$handle) {
            new Msg(__('Não foi possível criar o model %s. Verifique as permissões do diretório', ucfirst($table)), 3);
            return;
        }
        fwrite($handle, "<?php\n");
        //fwrite($handle, "namespace model;\n\n");
        fwrite($handle, "final class " . ucfirst($table) . " extends Record{ \n");
        fwrite($handle, $this->nlt(1) . 'const TABLE = \'' . $table . '\';');
        fwrite($handle, $this->nlt(1) . 'const PK = \'');
        $pk = '';
        foreach ($tableschema as $field) {
            if ($field->Key == 'PRI') {
                fwrite($handle, $field->Field);
                break;
            }
        }
        fwrite($handle, '\';');

        # atributos do modelo
        fwrite($handle, $this->nlt(1));
        foreach ($tableschema as $field) {
            fwrite($handle, $this->nlt(1) . 'public $' . $field->Field . ';');
        }

        fwrite($handle, $this->nlt(1));
        fwrite($handle, $this->nlt(1) . '/**');
        fwrite($handle, $this->nlt(1) . '* Configurações e filtros globais do modelo');
        fwrite($handle, $this->nlt(1) . '* @return Criteria $criteria');
        fwrite($handle, $this->nlt(1) . '*/');
        fwrite($handle, $this->nlt(1) . 'public static function configure(){');
        fwrite($handle, $this->nlt(2) . '# $criteria = new Criteria();');
        fwrite($handle, $this->nlt(2) . '# return $criteria;');
        fwrite($handle, $this->nlt(1) . '}');
        foreach ($dbschema as $v) {
            if ($v->table == $table) {
                $mname = 'get' . ucfirst(($v->reftable));
                $cused = 2;
                while (array_search($mname, $used))
                    $mname .= $cused;
                $used[] = $mname;
                fwrite($handle, $this->nlt(1));
                fwrite($handle, $this->nlt(1) . '/**');
                fwrite($handle, $this->nlt(1) . '* ' . ucfirst($table) . ' pertence a ' . ucfirst($v->reftable));
                fwrite($handle, $this->nlt(1) . '* @return ' . ucfirst($v->reftable) . ' $' . ucfirst($v->reftable));
                fwrite($handle, $this->nlt(1) . '*/');
                fwrite($handle, $this->nlt(1) . 'function ' . $mname . '() {');
                fwrite($handle, $this->nlt(2) . 'return $this->belongsTo(\'' . ucfirst($v->reftable) . '\',\'' . $v->fk . '\');');
                fwrite($handle, $this->nlt(1) . "}");
            }
            if ($v->reftable == $table) {
                $mname = 'get' . $this->getPlural(ucfirst(($v->table)));
                $cused = 2;
                while (array_search($mname, $used))
                    $mname .= $cused;
                $used[] = $mname;
                fwrite($handle, $this->nlt(1));
                fwrite($handle, $this->nlt(1) . '/**');
                fwrite($handle, $this->nlt(1) . '* ' . ucfirst($table) . ' possui ' . $this->getPlural(ucfirst($v->table)));
                fwrite($handle, $this->nlt(1) . '* @return ' . $this->getPlural(ucfirst($v->table)) . '[] $array');
                fwrite($handle, $this->nlt(1) . '*/');
                fwrite($handle, $this->nlt(1) . 'function ' . $mname . '($criteria=NULL) {');
                fwrite($handle, $this->nlt(2) . 'return $this->hasMany(\'' . ucfirst(($v->table)) . '\',\'' . $v->fk . '\',$criteria);');
                fwrite($handle, $this->nlt(1) . "}");
            }
        }
        fwrite($handle, $this->nlt(0) . "}");
        fclose($handle);
    }

    private function installC($table, $overwrite = false) {
        if (file_exists('controller/' . ucfirst($table) . 'Controller.php') && !$overwrite) {
            new Msg(__('Controller %sController.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);
        $priField = $tableschema[0]->Field;
        foreach ($tableschema as $f) {
            if ($f->Type == 'PRI') {
                $priField = $f->Field;
                break;
            }
        }
        $handle = fopen("controller/" . ucfirst($table) . "Controller.php", 'w');
        if (!$handle) {
            new Msg(__('Não foi possível criar o controller %s. Verifique as permissões do diretório', ucfirst($table)), 3);
            return;
        }
        fwrite($handle, "<?php\n");
        fwrite($handle, "final class " . ucfirst($table) . "Controller extends AppController{ \n");

        fwrite($handle, $this->nlt(1) . '# página inicial do módulo ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . "function index(){");
        fwrite($handle, $this->nlt(2) . '$this->setTitle(\'' . ucfirst($table) . '\');');
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# lista de ' . $this->getPlural(ucfirst($table)));
        fwrite($handle, $this->nlt(1) . '# renderiza a visão /view/' . ucfirst($table) . '/all.php');
        fwrite($handle, $this->nlt(1) . 'function all(){');
        fwrite($handle, $this->nlt(2) . '$this->setTitle(\'' . $this->getPlural(ucfirst($table)) . '\');');
        fwrite($handle, $this->nlt(2) . '$p = new Paginate(\'' . ucfirst($table) . '\', 10);');
        fwrite($handle, $this->nlt(2) . '$this->set(\'search\', NULL);');
        fwrite($handle, $this->nlt(2) . '$c = new Criteria();');
        fwrite($handle, $this->nlt(2) . 'if (isset($_GET[\'search\'])) {');
        foreach ($tableschema as $field) {
            if ($field->Key == 'PRI') {
                $f = $field->Field;
                break;
            }
        }
        foreach ($tableschema as $field) {
            if (strstr($field->Type, 'char')) {
                $f = $field->Field;
                break;
            }
        }
        fwrite($handle, $this->nlt(3) . '$c->addCondition(\'' . $f . '\', \'LIKE\', \'%\' . $_GET[\'search\'] . \'%\');');
        fwrite($handle, $this->nlt(3) . '$this->set(\'search\', $this->getParam(\'search\'));');
        fwrite($handle, $this->nlt(2) . '}');
        fwrite($handle, $this->nlt(2) . 'if ($this->getParam(\'orderBy\')) {');
        fwrite($handle, $this->nlt(3) . '$c->setOrder($this->getParam(\'orderBy\'));');
        fwrite($handle, $this->nlt(2) . '}');
        fwrite($handle, $this->nlt(2) . '$this->set(\'' . $this->getPlural(ucfirst($table)) . '\', $p->getPage($c));');
        fwrite($handle, $this->nlt(2) . '$this->set(\'nav\', $p->getNav());');
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# visualiza um(a) ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . '# renderiza a visão /view/' . ucfirst($table) . '/view.php');
        fwrite($handle, $this->nlt(1) . 'function view(){');
        fwrite($handle, $this->nlt(2) . 'try {');
        fwrite($handle, $this->nlt(3) . '$' . ucfirst($table) . ' = new ' . ucfirst($table) . '( (int)$this->getParam(\'id\') );');
        fwrite($handle, $this->nlt(3) . '$this->set(\'' . ucfirst($table) . '\', $' . ucfirst($table) . ');');
        fwrite($handle, $this->nlt(3) . '$this->setTitle(\'' . ucfirst($table) . ' \'.$' . ucfirst($table) . ');');
        fwrite($handle, $this->nlt(2) . '} catch (Exception $e) {');
        fwrite($handle, $this->nlt(3) . 'new Msg($e->getMessage(), 2);');
        fwrite($handle, $this->nlt(3) . '$this->go(\'' . ucfirst($table) . '\', \'all\');');
        fwrite($handle, $this->nlt(2) . '}');
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# formulário de cadastro de ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . '# renderiza a visão /view/' . ucfirst($table) . '/add.php');
        fwrite($handle, $this->nlt(1) . 'function add(){');
        fwrite($handle, $this->nlt(2) . '$this->setTitle(\'Adicionar ' . (ucfirst($table)) . '\');');
        fwrite($handle, $this->nlt(2) . '$this->set(\'' . ucfirst($table) . '\', new ' . ucfirst($table) . ');');
        foreach ($dbschema as $v) {
            if ($v->table == $table) {
                fwrite($handle, $this->nlt(2) . '$this->set(\'' . $this->getPlural(ucfirst(($v->reftable))) . '\',  ' . ucfirst(($v->reftable)) . '::getList());');
            }
        }
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# recebe os dados enviados via post do cadastro de ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . '# (true)redireciona ou (false) renderiza a visão /view/' . ucfirst($table) . '/add.php');
        fwrite($handle, $this->nlt(1) . 'function post_add(){');
        fwrite($handle, $this->nlt(2) . '$this->setTitle(\'Adicionar ' . (ucfirst($table)) . '\');');
        fwrite($handle, $this->nlt(2) . '$' . ucfirst($table) . ' = new ' . ucfirst($table) . '();');
        fwrite($handle, $this->nlt(2) . '$this->set(\'' . ucfirst($table) . '\', $' . ucfirst($table) . ');');
        fwrite($handle, $this->nlt(2) . 'try {');
        fwrite($handle, $this->nlt(3) . '$' . ucfirst($table) . '->save($_POST);');
        fwrite($handle, $this->nlt(3) . 'new Msg(__(\'' . ucfirst($table) . ' cadastrado com sucesso\'));');
        fwrite($handle, $this->nlt(3) . '$this->go(\'' . ucfirst($table) . '\', \'all\');');
        fwrite($handle, $this->nlt(2) . '} catch (Exception $e) {');
        fwrite($handle, $this->nlt(3) . 'new Msg($e->getMessage(),3);');
        fwrite($handle, $this->nlt(2) . '}');
        foreach ($dbschema as $v) {
            if ($v->table == $table) {
                fwrite($handle, $this->nlt(2) . '$this->set(\'' . $this->getPlural(ucfirst(($v->reftable))) . '\',  ' . ucfirst(($v->reftable)) . '::getList());');
            }
        }
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# formulário de edição de ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . '# renderiza a visão /view/' . ucfirst($table) . '/edit.php');
        fwrite($handle, $this->nlt(1) . 'function edit(){');
        fwrite($handle, $this->nlt(2) . '$this->setTitle(\'Editar ' . (ucfirst($table)) . '\');');
        fwrite($handle, $this->nlt(2) . 'try {');
        fwrite($handle, $this->nlt(3) . '$this->set(\'' . ucfirst($table) . '\', new ' . ucfirst($table) . '((int) $this->getParam(\'id\')));');
        foreach ($dbschema as $v) {
            if ($v->table == $table) {
                fwrite($handle, $this->nlt(3) . '$this->set(\'' . $this->getPlural(ucfirst(($v->reftable))) . '\',  ' . ucfirst(($v->reftable)) . '::getList());');
            }
        }
        fwrite($handle, $this->nlt(2) . '} catch (Exception $e) {');
        fwrite($handle, $this->nlt(3) . 'new Msg($e->getMessage(),3);');
        fwrite($handle, $this->nlt(3) . '$this->go(\'' . ucfirst($table) . '\', \'all\');');
        fwrite($handle, $this->nlt(2) . '}');
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# recebe os dados enviados via post da edição de ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . '# (true)redireciona ou (false) renderiza a visão /view/' . ucfirst($table) . '/edit.php');
        fwrite($handle, $this->nlt(1) . 'function post_edit(){');
        fwrite($handle, $this->nlt(2) . '$this->setTitle(\'Editar ' . (ucfirst($table)) . '\');');
        fwrite($handle, $this->nlt(2) . 'try {');
        fwrite($handle, $this->nlt(3) . '$' . ucfirst($table) . ' = new ' . ucfirst($table) . '((int) $_POST[\'' . $priField . '\']);');
        fwrite($handle, $this->nlt(3) . '$this->set(\'' . ucfirst($table) . '\', $' . ucfirst($table) . ');');
        fwrite($handle, $this->nlt(3) . '$' . ucfirst($table) . '->save($_POST);');
        fwrite($handle, $this->nlt(3) . 'new Msg(__(\'' . ucfirst($table) . ' atualizado com sucesso\'));');
        fwrite($handle, $this->nlt(3) . '$this->go(\'' . ucfirst($table) . '\', \'all\');');
        fwrite($handle, $this->nlt(2) . '} catch (Exception $e) {');
        fwrite($handle, $this->nlt(3) . 'new Msg(__(\'Não foi possível atualizar.\'), 2);');
        fwrite($handle, $this->nlt(2) . '}');
        foreach ($dbschema as $v) {
            if ($v->table == $table) {
                fwrite($handle, $this->nlt(2) . '$this->set(\'' . $this->getPlural(ucfirst(($v->reftable))) . '\',  ' . ucfirst(($v->reftable)) . '::getList());');
            }
        }
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# Confirma a exclusão ou não de um(a) ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . '# renderiza a /view/' . ucfirst($table) . '/delete.php');
        fwrite($handle, $this->nlt(1) . 'function delete(){');
        fwrite($handle, $this->nlt(2) . '$this->setTitle(\'Apagar ' . (ucfirst($table)) . '\');');
        fwrite($handle, $this->nlt(2) . 'try {');
        fwrite($handle, $this->nlt(3) . '$this->set(\'' . ucfirst($table) . '\', new ' . ucfirst($table) . '((int)$this->getParam(\'id\')));');
        fwrite($handle, $this->nlt(2) . '} catch (Exception $e) {');
        fwrite($handle, $this->nlt(3) . 'new Msg($e->getMessage(), 2);');
        fwrite($handle, $this->nlt(3) . '$this->go(\'' . ucfirst($table) . '\', \'all\');');
        fwrite($handle, $this->nlt(2) . '}');
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(1) . '# Recebe o id via post e exclui um(a) ' . ucfirst($table));
        fwrite($handle, $this->nlt(1) . '# redireciona para ' . ucfirst($table) . '/all');
        fwrite($handle, $this->nlt(1) . 'function post_delete(){');
        fwrite($handle, $this->nlt(2) . 'try {');
        fwrite($handle, $this->nlt(3) . '$' . ucfirst($table) . ' = new ' . ucfirst($table) . '((int) $_POST[\'id\']);');
        fwrite($handle, $this->nlt(3) . '$' . ucfirst($table) . '->delete();');
        fwrite($handle, $this->nlt(3) . 'new Msg(__(\'' . ucfirst($table) . ' apagado com sucesso\'), 1);');
        fwrite($handle, $this->nlt(2) . '} catch (Exception $e) {');
        fwrite($handle, $this->nlt(3) . 'new Msg($e->getMessage(),3);');
        fwrite($handle, $this->nlt(2) . '}');
        fwrite($handle, $this->nlt(2) . '$this->go(\'' . ucfirst($table) . '\', \'all\');');
        fwrite($handle, $this->nlt(1) . "}\n");

        fwrite($handle, $this->nlt(0) . "}");
        fclose($handle);
    }

    private function installV($table, $overwrite = false) {
        if (!is_dir('view/' . ucfirst($table)))
            mkdir('view/' . ucfirst($table));
        $this->installViewIndex($table, $overwrite);
        $this->installViewView($table, $overwrite);
        $this->installViewAll($table, $overwrite);
        $this->installViewAdd($table, $overwrite);
        $this->installViewEdit($table, $overwrite);
        $this->installViewDelete($table, $overwrite);
    }

    private function installViewIndex($table, $overwrite = false) {
        if (file_exists('view/' . ucfirst($table) . '/index.php') && !$overwrite) {
            new Msg(__('View %s index.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);

        $handle = fopen("view/" . ucfirst($table) . "/index.php", 'w');
        fwrite($handle, '<?php');
        fwrite($handle, $this->nlt(0) . '# Visão view/' . ucfirst($table) . '/index.php ');
        fwrite($handle, $this->nlt(0) . '/* @var $this ' . ucfirst($table) . 'Controller */');
        fwrite($handle, $this->nlt(0) . '?>');
        fwrite($handle, '<h1>' . ucfirst($table) . '</h1>');
        fclose($handle);
    }

    private function installViewView($table, $overwrite) {
        if (file_exists('view/' . ucfirst($table) . '/view.php') && !$overwrite) {
            new Msg(__('View %s view.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);

        $handle = fopen("view/" . ucfirst($table) . "/view.php", 'w');
        fwrite($handle, '<?php');
        fwrite($handle, $this->nlt(0) . '# Visão view/' . ucfirst($table) . '/view.php ');
        fwrite($handle, $this->nlt(0) . '/* @var $this ' . ucfirst($table) . 'Controller */');
        fwrite($handle, $this->nlt(0) . '/* @var $' . ucfirst($table) . ' ' . ucfirst($table) . ' */');
        fwrite($handle, $this->nlt(0) . '?>');
        fwrite($handle, $this->nlt(0) . '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
        fwrite($handle, $this->nlt(0) . '<h1>' . ucfirst($table) . '</h1>');
        foreach ($tableschema as $field) {
            foreach ($dbschema as $dbs) {
                if ($dbs->table == $table && $dbs->fk == $field->Field)
                    continue 2;
            }
            if ($field->Key == 'PRI')
                continue;
            fwrite($handle, $this->nlt(0) . '<p><strong>' . ucfirst(($field->Field)) . '</strong>: ');
            fwrite($handle, '<?php echo $' . ucfirst($table) . '->' . ($field->Field) . ';?></p>');
        }
        $used = array('1');
        foreach ($dbschema as $v) {
            if ($v->table == $table) {
                fwrite($handle, $this->nlt(0) . '<p>');
                fwrite($handle, $this->nlt(1) . '<strong>' . ucfirst(($v->reftable)) . '</strong>: ');
                $belongsSchema = $this->getTableSchema($v->reftable);
                $ba = $belongsSchema[0]->Field;
                foreach ($belongsSchema as $bf) {
                    if (strstr($bf->Type, 'char')) {
                        $ba = $bf->Field;
                        break;
                    }
                }
                $priRefField = $belongsSchema[0]->Field;
                foreach ($belongsSchema as $bf) {
                    if ($bf->Type == 'PRI') {
                        $priRefField = $bf->Field;
                        break;
                    }
                }
                $mname = 'get' . ucfirst(($v->reftable));
                $cused = 2;
                while (array_search($mname, $used))
                    $mname .= $cused;
                $used[] = $mname;
                fwrite($handle, $this->nlt(1) . '<?php');
                fwrite($handle, $this->nlt(1) . 'echo $this->Html->getLink($' . ucfirst($table) . '->' . $mname . '()->' . $ba . ', \'' . ucfirst(($v->reftable)) . '\', \'view\',');
                fwrite($handle, $this->nlt(1) . 'array(\'id\' => $' . ucfirst($table) . '->' . $mname . '()->' . $priRefField . '), // variaveis via GET opcionais');
                fwrite($handle, $this->nlt(1) . 'array(\'class\' => \'\')); // atributos HTML opcionais');
                fwrite($handle, $this->nlt(1) . '?>');
                fwrite($handle, $this->nlt(0) . '</p>');
            }
        }
        fclose($handle);
    }

    private function installViewAll($table, $overwrite = false) {
        if (file_exists('view/' . ucfirst($table) . '/all.php') && !$overwrite) {
            new Msg(__('View %s all.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);
        $stringField = $tableschema[0]->Field;
        $priField = $tableschema[0]->Field;
        foreach ($tableschema as $f) {
            if (strstr($f->Type, 'char')) {
                $stringField = $f->Field;
                break;
            }
        }
        foreach ($tableschema as $f) {
            if ($f->Type == 'PRI') {
                $priField = $f->Field;
                break;
            }
        }

        $handle = fopen("view/" . ucfirst($table) . "/all.php", 'w');
        fwrite($handle, '<?php');
        fwrite($handle, $this->nlt(0) . '# Visão view/' . ucfirst($table) . '/all.php ');
        fwrite($handle, $this->nlt(0) . '/* @var $this ' . ucfirst($table) . 'Controller */');
        fwrite($handle, $this->nlt(0) . '/* @var $' . $this->getPlural(ucfirst($table)) . ' ' . ucfirst($table) . '[] */');
        fwrite($handle, $this->nlt(0) . '?>');
        fwrite($handle, $this->nlt(0) . '<!-- titulo da pagina -->');
        fwrite($handle, $this->nlt(0) . '<div class="row">');
        fwrite($handle, $this->nlt(1) . '<h1>' . $this->getPlural(ucfirst($table)) . '</h1>');
        fwrite($handle, $this->nlt(0) . '</div>' . "\n");

        fwrite($handle, $this->nlt(0) . '<div class="row">');
        fwrite($handle, $this->nlt(1) . '<!-- botao de cadastro -->');
        fwrite($handle, $this->nlt(1) . '<div class="row text-right pull-right">');
        fwrite($handle, $this->nlt(2) . '<p><?php echo $this->Html->getLink(\'<span class="glyphicon glyphicon-plus-sign"></span> Cadastrar ' . ucfirst($table) . '\', \'' . ucfirst($table) . '\', \'add\', NULL, array(\'class\' => \'btn btn-primary\')); ?></p>');
        fwrite($handle, $this->nlt(1) . '</div>' . "\n");

        fwrite($handle, $this->nlt(1) . '<!-- formulario de pesquisa -->');
        fwrite($handle, $this->nlt(1) . '<div class="pull-left">');
        fwrite($handle, $this->nlt(2) . '<form class="form-inline" role="form" method="get" action="<?php echo $this->Html->getUrl(CONTROLLER,ACTION,array(\'orderBy\'=>$this->getParam(\'orderBy\')))?>">');
        fwrite($handle, $this->nlt(3) . '<input type="hidden" name="m" value="<?php echo CONTROLLER; ?>">');
        fwrite($handle, $this->nlt(3) . '<input type="hidden" name="p" value="<?php echo ACTION; ?>">');
        fwrite($handle, $this->nlt(3) . '<div class="form-group">');
        fwrite($handle, $this->nlt(4) . '<label class="sr-only" for="search">Pesquisar</label>');
        fwrite($handle, $this->nlt(4) . '<input value="<?php echo $search; ?>" type="search" class="form-control" name="search" id="search" placeholder="Pesquisar ' . $stringField . '">');
        fwrite($handle, $this->nlt(3) . '</div>');
        fwrite($handle, $this->nlt(3) . '<button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-search"></span></button>');
        fwrite($handle, $this->nlt(2) . '</form>');
        fwrite($handle, $this->nlt(1) . '</div>' . "\n");
        fwrite($handle, $this->nlt(0) . '</div>');

        fwrite($handle, $this->nlt(0) . '<!-- tabela de resultados -->');
        fwrite($handle, $this->nlt(0) . '<div class="row clearfix">  ');
        fwrite($handle, $this->nlt(1) . '<div class="table-responsive">');
        fwrite($handle, $this->nlt(2) . '<table class="table table-hover">');
        fwrite($handle, $this->nlt(3) . '<tr>');
        foreach ($tableschema as $f) {
            foreach ($dbschema as $dbs) {
                if ($dbs->table == $table && $dbs->fk == $f->Field)
                    continue 2;
            }
            if ($f->Key == 'PRI')
                continue;
            fwrite($handle, $this->nlt(4) . '<th>');
            fwrite($handle, $this->nlt(5) . '<a href=\'<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'all\', array(\'orderBy\' => \'' . $f->Field . '\', \'search\' => $search)); ?>\'>');
            fwrite($handle, $this->nlt(6) . $f->Field);
            fwrite($handle, $this->nlt(5) . '</a>');
            fwrite($handle, $this->nlt(4) . '</th>');
        }
        foreach ($dbschema as $dbs) {
            if ($dbs->table == $table) {
                $reftableschema = $this->getTableSchema($dbs->reftable);
                $strreftable = $reftableschema[0]->Field;
                foreach ($reftableschema as $fref) {
                    if (strstr($fref->Type, 'char')) {
                        $strreftable = $fref->Field;
                        break;
                    }
                }
                fwrite($handle, $this->nlt(4) . '<th>');
                fwrite($handle, $this->nlt(5) . '<a href=\'<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'all\', array(\'orderBy\' => \'' . $dbs->fk . '\', \'search\' => $search)); ?>\'>');
                fwrite($handle, $this->nlt(6) . $dbs->reftable);
                fwrite($handle, $this->nlt(5) . '</a>');
                fwrite($handle, $this->nlt(4) . '</th>');
            }
        }
        fwrite($handle, $this->nlt(4) . '<th>&nbsp;</th>');
        fwrite($handle, $this->nlt(4) . '<th>&nbsp;</th>');
        fwrite($handle, $this->nlt(3) . '</tr>');
        fwrite($handle, $this->nlt(3) . '<?php');
        fwrite($handle, $this->nlt(3) . 'foreach ($' . $this->getPlural(ucfirst($table)) . ' as $' . substr(strtolower($table), 0, 1) . ') {');
        fwrite($handle, $this->nlt(4) . 'echo \'<tr>\';');
        foreach ($tableschema as $f) {
            foreach ($dbschema as $dbs) {
                if ($dbs->table == $table && $dbs->fk == $f->Field)
                    continue 2;
            }
            if ($f->Key == 'PRI')
                continue;
            fwrite($handle, $this->nlt(4) . 'echo \'<td>\';');
            fwrite($handle, $this->nlt(4) . 'echo $this->Html->getLink($' . substr(strtolower($table), 0, 1) . '->' . $f->Field . ', \'' . ucfirst($table) . '\', \'view\',');
            fwrite($handle, $this->nlt(5) . 'array(\'id\' => $' . substr(strtolower($table), 0, 1) . '->' . $priField . '), // variaveis via GET opcionais');
            fwrite($handle, $this->nlt(5) . 'array(\'data-toggle\' => \'modal\')); // atributos HTML opcionais');
            fwrite($handle, $this->nlt(4) . 'echo \'</td>\';');
        }
        $used = array('1');
        foreach ($dbschema as $dbs) {
            if ($dbs->table == $table) {
                $reftableschema = $this->getTableSchema($dbs->reftable);
                $strreftable = $reftableschema[0]->Field;
                foreach ($reftableschema as $fref) {
                    if (strstr($fref->Type, 'char')) {
                        $strreftable = $fref->Field;
                        break;
                    }
                }
                $priRefField = $reftableschema[0]->Field;
                foreach ($reftableschema as $fref) {
                    if ($fref->Type == 'PRI') {
                        $priRefField = $fref->Field;
                        break;
                    }
                }
                $mname = 'get' . ucfirst(($dbs->reftable));
                $cused = 2;
                while (array_search($mname, $used))
                    $mname .= $cused;
                $used[] = $mname;
                fwrite($handle, $this->nlt(4) . 'echo \'<td>\';');
                fwrite($handle, $this->nlt(4) . 'echo $this->Html->getLink($' . substr(strtolower($table), 0, 1) . '->' . $mname . '()->' . $strreftable . ', \'' . ucfirst(($dbs->reftable)) . '\', \'view\',');
                fwrite($handle, $this->nlt(5) . 'array(\'id\' => $' . substr(strtolower($table), 0, 1) . '->' . $mname . '()->' . $priRefField . '), // variaveis via GET opcionais');
                fwrite($handle, $this->nlt(5) . 'array(\'data-toggle\' => \'modal\')); // atributos HTML opcionais');
                fwrite($handle, $this->nlt(4) . 'echo \'</td>\';');
            }
        }
        fwrite($handle, $this->nlt(4) . 'echo \'<td width="50">\';');
        fwrite($handle, $this->nlt(4) . 'echo $this->Html->getLink(\'<span class="glyphicon glyphicon-edit"></span> \', \'' . ucfirst($table) . '\', \'edit\', ');
        fwrite($handle, $this->nlt(5) . 'array(\'id\' => $' . substr(strtolower($table), 0, 1) . '->' . $priField . '), ');
        fwrite($handle, $this->nlt(5) . 'array(\'class\' => \'btn btn-warning btn-sm\'));');
        fwrite($handle, $this->nlt(4) . 'echo \'</td>\';');
        fwrite($handle, $this->nlt(4) . 'echo \'<td width="50">\';');
        fwrite($handle, $this->nlt(4) . 'echo $this->Html->getLink(\'<span class="glyphicon glyphicon-remove"></span> \', \'' . ucfirst($table) . '\', \'delete\', ');
        fwrite($handle, $this->nlt(5) . 'array(\'id\' => $' . substr(strtolower($table), 0, 1) . '->' . $priField . '), ');
        fwrite($handle, $this->nlt(5) . 'array(\'class\' => \'btn btn-danger btn-sm\',\'data-toggle\' => \'modal\'));');
        fwrite($handle, $this->nlt(4) . 'echo \'</td>\';');
        fwrite($handle, $this->nlt(4) . 'echo \'</tr>\';');
        fwrite($handle, $this->nlt(3) . '}');
        fwrite($handle, $this->nlt(3) . '?>');
        fwrite($handle, $this->nlt(2) . '</table>' . "\n");

        fwrite($handle, $this->nlt(2) . '<!-- menu de paginação -->');
        fwrite($handle, $this->nlt(2) . '<div style="text-align:center"><?php echo $nav; ?></div>');
        fwrite($handle, $this->nlt(1) . '</div>');
        fwrite($handle, $this->nlt(0) . '</div>' . "\n");
        fwrite($handle, $this->nlt(0) . '<script>');
        fwrite($handle, $this->nlt(1) . '/* faz a pesquisa com ajax */');
        fwrite($handle, $this->nlt(1) . '$(document).ready(function() {');
        fwrite($handle, $this->nlt(2) . '$(\'#search\').keyup(function() {');
        fwrite($handle, $this->nlt(3) . 'var r = true;');
        fwrite($handle, $this->nlt(3) . 'if (r) {');
        fwrite($handle, $this->nlt(4) . 'r = false;');
        fwrite($handle, $this->nlt(4) . '$("div.table-responsive").load(');
        fwrite($handle, $this->nlt(4) . '<?php');
        fwrite($handle, $this->nlt(4) . 'if (isset($_GET[\'orderBy\']))');
        fwrite($handle, $this->nlt(5) . 'echo \'"\' . $this->Html->getUrl(\'' . ucfirst($table) . '\', \'all\', array(\'orderBy\' => $_GET[\'orderBy\'])) . \'&search=" + encodeURIComponent($("#search").val()) + " .table-responsive"\';');
        fwrite($handle, $this->nlt(4) . 'else');
        fwrite($handle, $this->nlt(5) . 'echo \'"\' . $this->Html->getUrl(\'' . ucfirst($table) . '\', \'all\') . \'&search=" + encodeURIComponent($("#search").val()) + " .table-responsive"\';');
        fwrite($handle, $this->nlt(4) . '?>');
        fwrite($handle, $this->nlt(4) . ' , function() {');
        fwrite($handle, $this->nlt(5) . 'r = true;');
        fwrite($handle, $this->nlt(4) . '});');
        fwrite($handle, $this->nlt(3) . '}');
        fwrite($handle, $this->nlt(2) . '});');
        fwrite($handle, $this->nlt(1) . '});');
        fwrite($handle, $this->nlt(0) . '</script>');
        fclose($handle);
    }

    private function installViewAdd($table, $overwrite = false) {
        if (file_exists('view/' . ucfirst($table) . '/add.php') && !$overwrite) {
            new Msg(__('View %s add.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);

        $handle = fopen("view/" . ucfirst($table) . "/add.php", 'w');
        fwrite($handle, '<?php');
        fwrite($handle, $this->nlt(0) . '# Visão view/' . ucfirst($table) . '/add.php');
        fwrite($handle, $this->nlt(0) . '/* @var $this ' . ucfirst($table) . 'Controller */');
        fwrite($handle, $this->nlt(0) . '/* @var $' . (ucfirst($table)) . ' ' . ucfirst($table) . ' */');
        fwrite($handle, $this->nlt(0) . '?>');
        fwrite($handle, $this->nlt(0) . '<h1>Cadastrar ' . ucfirst($table) . '</h1>');
        fwrite($handle, $this->nlt(0) . '<form method="post" role="form" action="<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'add\') ?>">');
        fwrite($handle, $this->nlt(0) . '<div class="alert alert-info">Os campos marcados com <span class="small glyphicon glyphicon-asterisk"></span> são de preenchimento obrigatório.</div>');
        fwrite($handle, $this->nlt(1) . '<div class="well well-lg">');

        foreach ($tableschema as $f) {
            foreach ($dbschema as $dbs) {
                if ($dbs->table == $table && $dbs->fk == $f->Field)
                    continue 2;
            }
            if ($f->Key == 'PRI')
                continue;
            $req = '';
            if ($f->Null == 'NO')
                $req = ' required';
            fwrite($handle, $this->nlt(2) . '<div class="form-group">');
            if (!empty($req))
                fwrite($handle, $this->nlt(3) . '<label class="required" for="' . $f->Field . '">' . ucfirst($f->Field) . ' <span class="glyphicon glyphicon-asterisk"></span></label>');
            else
                fwrite($handle, $this->nlt(3) . '<label for="' . $f->Field . '">' . ucfirst($f->Field) . '</label>');
            if (strstr($f->Type, 'char')) {
                if ($f->Type == 'char(32)')
                    fwrite($handle, $this->nlt(3) . '<input type="password" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
                else
                    fwrite($handle, $this->nlt(3) . '<input type="text" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            }
            elseif (strstr($f->Type, 'int')) {
                fwrite($handle, $this->nlt(3) . '<input type="number" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif (strstr($f->Type, 'decimal')) {
                fwrite($handle, $this->nlt(3) . '<input type="number" step="0,01" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif ($f->Type == 'date') {
                fwrite($handle, $this->nlt(3) . '<input type="date" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif ($f->Type == 'datetime') {
                fwrite($handle, $this->nlt(3) . '<input type="datetime" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif ($f->Type == 'time') {
                fwrite($handle, $this->nlt(3) . '<input type="time" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif (strstr($f->Type, 'text')) {
                fwrite($handle, $this->nlt(3) . '<textarea name="' . $f->Field . '" id="' . $f->Field . '" class="form-control"' . $req . '><?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?></textarea>');
            }
            fwrite($handle, $this->nlt(2) . '</div>');
        }
        foreach ($dbschema as $dbs) {
            if ($dbs->table == $table) {
                $reftableschema = $this->getTableSchema($dbs->reftable);
                $strreftable = $reftableschema[0]->Field;
                foreach ($reftableschema as $fref) {
                    if (strstr($fref->Type, 'char')) {
                        $strreftable = $fref->Field;
                        break;
                    }
                }

                fwrite($handle, $this->nlt(2) . '<div class="form-group">');
                fwrite($handle, $this->nlt(3) . '<label for="' . $dbs->fk . '">' . ucfirst($dbs->reftable) . '</label>');
                fwrite($handle, $this->nlt(3) . '<select name="' . $dbs->fk . '" class="form-control" id="' . $dbs->fk . '">');
                fwrite($handle, $this->nlt(4) . '<?php');
                fwrite($handle, $this->nlt(4) . 'foreach ($' . $this->getPlural(ucfirst(($dbs->reftable))) . ' as $' . substr(strtolower($dbs->reftable), 0, 1) . ') {');
                fwrite($handle, $this->nlt(5) . 'if ($' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $dbs->refpk . ' == $' . ucfirst($table) . '->' . $dbs->fk . ')');
                fwrite($handle, $this->nlt(6) . 'echo \'<option selected value="\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $dbs->refpk . ' . \'">\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $strreftable . ' . \'</option>\';');
                fwrite($handle, $this->nlt(5) . 'else');
                fwrite($handle, $this->nlt(6) . 'echo \'<option value="\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $dbs->refpk . ' . \'">\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $strreftable . ' . \'</option>\';');
                fwrite($handle, $this->nlt(4) . '}');
                fwrite($handle, $this->nlt(4) . '?>');
                fwrite($handle, $this->nlt(3) . '</select>');
                fwrite($handle, $this->nlt(2) . '</div>');
            }
        }
        fwrite($handle, $this->nlt(1) . '</div>');
        fwrite($handle, $this->nlt(1) . '<div class="text-right">');
        fwrite($handle, $this->nlt(2) . '<a href="<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'all\') ?>" class="btn btn-default" data-dismiss="modal">Cancelar</a>');
        fwrite($handle, $this->nlt(2) . '<input type="submit" class="btn btn-primary" value="salvar">');
        fwrite($handle, $this->nlt(1) . '</div>');
        fwrite($handle, $this->nlt(0) . '</form>');
        fclose($handle);
    }

    private function installViewEdit($table, $overwrite = false) {
        if (file_exists('view/' . ucfirst($table) . '/edit.php') && !$overwrite) {
            new Msg(__('View %s edit.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);

        $priField = $tableschema[0]->Field;
        foreach ($tableschema as $f) {
            if ($f->Type == 'PRI') {
                $priField = $f->Field;
                break;
            }
        }
        $handle = fopen("view/" . ucfirst($table) . "/edit.php", 'w');
        fwrite($handle, '<?php');
        fwrite($handle, $this->nlt(0) . '# Visão view/' . ucfirst($table) . '/edit.php ');
        fwrite($handle, $this->nlt(0) . '/* @var $this ' . ucfirst($table) . 'Controller */');
        fwrite($handle, $this->nlt(0) . '/* @var $' . (ucfirst($table)) . ' ' . ucfirst($table) . ' */');
        fwrite($handle, $this->nlt(0) . '?>');
        fwrite($handle, $this->nlt(0) . '<h1>Editar ' . ucfirst($table) . '</h1>');
        fwrite($handle, $this->nlt(0) . '<form method="post" role="form" action="<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'edit\') ?>">');
        fwrite($handle, $this->nlt(0) . '<div class="alert alert-info">Os campos marcados com <span class="small glyphicon glyphicon-asterisk"></span> são de preenchimento obrigatório.</div>');
        fwrite($handle, $this->nlt(1) . '<div class="well well-lg">');
        foreach ($tableschema as $f) {
            foreach ($dbschema as $dbs) {
                if ($dbs->table == $table && $dbs->fk == $f->Field)
                    continue 2;
            }
            if ($f->Key == 'PRI')
                continue;
            $req = '';
            if ($f->Null == 'NO')
                $req = ' required';
            fwrite($handle, $this->nlt(2) . '<div class="form-group">');
            if (!empty($req))
                fwrite($handle, $this->nlt(3) . '<label class="required" for="' . $f->Field . '">' . ucfirst($f->Field) . ' <span class="glyphicon glyphicon-asterisk"></span></label>');
            else
                fwrite($handle, $this->nlt(3) . '<label for="' . $f->Field . '">' . ucfirst($f->Field) . '</label>');

            if (strstr($f->Type, 'char')) {
                if ($f->Type == 'char(32)')
                    fwrite($handle, $this->nlt(3) . '<input type="password" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
                else
                    fwrite($handle, $this->nlt(3) . '<input type="text" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif (strstr($f->Type, 'int')) {
                fwrite($handle, $this->nlt(3) . '<input type="number" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif (strstr($f->Type, 'decimal')) {
                fwrite($handle, $this->nlt(3) . '<input type="number" step="0,01" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif ($f->Type == 'date') {
                fwrite($handle, $this->nlt(3) . '<input type="date" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif ($f->Type == 'datetime') {
                fwrite($handle, $this->nlt(3) . '<input type="datetime" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif ($f->Type == 'time') {
                fwrite($handle, $this->nlt(3) . '<input type="time" name="' . $f->Field . '" id="' . $f->Field . '" class="form-control" value="<?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?>" placeholder="' . ucfirst($f->Field) . '"' . $req . '>');
            } elseif (strstr($f->Type, 'text')) {
                fwrite($handle, $this->nlt(3) . '<textarea name="' . $f->Field . '" id="' . $f->Field . '" class="form-control"' . $req . '><?php echo $' . ucfirst($table) . '->' . $f->Field . ' ?></textarea>');
            }
            fwrite($handle, $this->nlt(2) . '</div>');
        }
        foreach ($dbschema as $dbs) {
            if ($dbs->table == $table) {
                $reftableschema = $this->getTableSchema($dbs->reftable);
                $strreftable = $reftableschema[0]->Field;
                foreach ($reftableschema as $fref) {
                    if (strstr($fref->Type, 'char')) {
                        $strreftable = $fref->Field;
                        break;
                    }
                }

                fwrite($handle, $this->nlt(2) . '<div class="form-group">');
                fwrite($handle, $this->nlt(3) . '<label for="' . $dbs->fk . '">' . ucfirst($dbs->reftable) . '</label>');
                fwrite($handle, $this->nlt(3) . '<select name="' . $dbs->fk . '" class="form-control" id="' . $dbs->fk . '">');
                fwrite($handle, $this->nlt(4) . '<?php');
                fwrite($handle, $this->nlt(4) . 'foreach ($' . $this->getPlural(ucfirst(($dbs->reftable))) . ' as $' . substr(strtolower($dbs->reftable), 0, 1) . ') {');
                fwrite($handle, $this->nlt(5) . 'if ($' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $dbs->refpk . ' == $' . ucfirst($table) . '->' . $dbs->fk . ')');
                fwrite($handle, $this->nlt(6) . 'echo \'<option selected value="\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $dbs->refpk . ' . \'">\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $strreftable . ' . \'</option>\';');
                fwrite($handle, $this->nlt(5) . 'else');
                fwrite($handle, $this->nlt(6) . 'echo \'<option value="\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $dbs->refpk . ' . \'">\' . $' . substr(strtolower($dbs->reftable), 0, 1) . '->' . $strreftable . ' . \'</option>\';');
                fwrite($handle, $this->nlt(4) . '}');
                fwrite($handle, $this->nlt(4) . '?>');
                fwrite($handle, $this->nlt(3) . '</select>');
                fwrite($handle, $this->nlt(2) . '</div>');
            }
        }
        fwrite($handle, $this->nlt(1) . '</div>');
        fwrite($handle, $this->nlt(1) . '<input type="hidden" name="' . $priField . '" value="<?php echo $' . ucfirst($table) . '->' . $priField . ';?>">');
        fwrite($handle, $this->nlt(1) . '<div class="text-right">');
        fwrite($handle, $this->nlt(2) . '<a href="<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'all\') ?>" class="btn btn-default" data-dismiss="modal">Cancelar</a>');
        fwrite($handle, $this->nlt(2) . '<input type="submit" class="btn btn-primary" value="salvar">');
        fwrite($handle, $this->nlt(1) . '</div>');
        fwrite($handle, $this->nlt(0) . '</form>');
        fclose($handle);
    }

    private function installViewDelete($table, $overwrite = false) {
        if (file_exists('view/' . ucfirst($table) . '/delete.php') && !$overwrite) {
            new Msg(__('View %s delete.php ignorado. O arquivo já existe.', ucfirst($table)), 2);
            return;
        }
        $dbschema = $this->getDbSchema($table);
        $tableschema = $this->getTableSchema($table);

        $stringField = $tableschema[0]->Field;
        $priField = $tableschema[0]->Field;
        foreach ($tableschema as $f) {
            if (strstr($f->Type, 'char')) {
                $stringField = $f->Field;
                break;
            }
        }
        foreach ($tableschema as $f) {
            if ($f->Type == 'PRI') {
                $priField = $f->Field;
                break;
            }
        }

        $handle = fopen("view/" . ucfirst($table) . "/delete.php", 'w');
        fwrite($handle, '<?php');
        fwrite($handle, $this->nlt(0) . '# Visão view/' . ucfirst($table) . '/delete.php ');
        fwrite($handle, $this->nlt(0) . '/* @var $this ' . ucfirst($table) . 'Controller */');
        fwrite($handle, $this->nlt(0) . '/* @var $' . (ucfirst($table)) . ' ' . ucfirst($table) . ' */');
        fwrite($handle, $this->nlt(0) . '?>');
        fwrite($handle, $this->nlt(0) . '<form class="form" method="post" action="<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'delete\') ?>">');
        fwrite($handle, $this->nlt(1) . '<h1>Confirmação</h1>');
        fwrite($handle, $this->nlt(1) . '<div class="well well-lg">');
        fwrite($handle, $this->nlt(2) . '<p>Voce tem certeza que deseja excluir o ' . ucfirst($table) . ' <strong><?php echo $' . ucfirst($table) . '->' . $stringField . '; ?></strong>?</p>');
        fwrite($handle, $this->nlt(1) . '</div>');
        fwrite($handle, $this->nlt(1) . '<div class="text-right">');
        fwrite($handle, $this->nlt(2) . '<input type="hidden" name="id" value="<?php echo $' . ucfirst($table) . '->' . $priField . '; ?>">');
        fwrite($handle, $this->nlt(2) . '<a href="<?php echo $this->Html->getUrl(\'' . ucfirst($table) . '\', \'all\') ?>" class="btn btn-default" data-dismiss="modal">Cancelar</a>');
        fwrite($handle, $this->nlt(2) . '<input type="submit" class="btn btn-danger" value="Excluir">');
        fwrite($handle, $this->nlt(1) . '</div>');
        fwrite($handle, $this->nlt(0) . '</form>');
        fclose($handle);
    }

    private function installMenu() {
        $tables = $this->getTables();
        $handle = fopen("template/menu.php", 'w');
        //fwrite($handle, $this->nlt(0) . '<ul class="nav navbar-nav">');
        foreach ($tables as $t) {
            fwrite($handle, $this->nlt(1) . '<li>');
            fwrite($handle, $this->nlt(1) . '<?php echo $this->Html->getLink(\'' . $this->getPlural(ucfirst(($t->name))) . '\', \'' . ucfirst(($t->name)) . '\', \'all\'); ?>');
            fwrite($handle, $this->nlt(1) . '</li>');
        }
        //fwrite($handle, $this->nlt(0) . '</ul>');
        fclose($handle);
    }

    private function checkDB() {
        $tables = $this->getTables();
        $ok = true;
        foreach ($tables as $table) {
            $schema = $this->getTableSchema($table->name);
            $pris = 0;
            foreach ($schema as $t) {
                if ($t->Key == 'PRI') {
                    if ($t->Extra != 'auto_increment') {
                        new Msg('A chave primária da tabela <strong>' . $table->name . '</strong> deve ser AUTO-INCREMENT', 3);
                        $ok = false;
                    }
                    $pris++;
                    if ($pris != 1) {
                        new Msg('Cada tabela do seu banco deve ter uma chave primária auto-increment única.<br>Verifique a tabela <strong>' . $table->name . '</strong>', 3);
                        $ok = false;
                    }
                }
            }
            if ($pris == 0) {
                new Msg('A tabela <strong>' . $table->name . '</strong> não possui uma chave primária auto-increment.', 3);
                $ok = false;
            }
        }
        return $ok;
    }

    private function getTables() {
        return $this->query('SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE()');
    }

    private function getPlural($nome) {
        if (substr($nome, -1) == "s")
            return $nome;
        if (substr($nome, -1) == "r")
            return $nome . "es";
        if (substr($nome, -1) == "m")
            return substr($nome, 0, -1) . "ns";
        if (substr($nome, -1) == "l")
            return substr($nome, 0, -1) . "is";
        return $nome . "s";
    }

    private function getDbSchema() {
        if (is_null($this->dbschema)) {
            $data = $this->query('select database() as db');
            $db = $data[0]->db;
            $data = $this->query("SELECT table_name AS 'table',  column_name AS  'fk', 
            referenced_table_name AS 'reftable', referenced_column_name  AS 'refpk' 
            FROM information_schema.key_column_usage
            WHERE referenced_table_name IS NOT NULL 
            AND TABLE_SCHEMA='" . $db . "' ");
            $this->dbschema = $data;
        }
        return $this->dbschema;
    }

    private function getTableSchema($table) {
        return $this->query('describe ' . $table);
    }

    private function nlt($n) {
        $r = "\n";
        for ($i = 0; $i < $n; $i++)
            $r = $r . "    ";
        return $r;
    }

    private function t($n) {
        for ($i = 0; $i < $n; $i++)
            $r = $r . "    ";
        return $r;
    }

}
