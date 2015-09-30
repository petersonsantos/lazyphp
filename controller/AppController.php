<?php
/**
 * Classe AppController
 * 
 * @author Miguel
 * @package \controller
 */

class AppController extends Controller {

    /**
     * Esta função é executada sempre antes da execução da funçao do
     * Controller especificado.
     * 
     * Implemente aqui regras globais que podem valer para todos os Controllers
     */
    public function beforeRun() {
        # evitando Cross-site Scripting
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
        //$_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
        
        # preservando as ordenações
        if ($this->getParam('orderBy'))
            Session::set(CONTROLLER . ACTION . APPKEY . '.orderBy', $this->getParam('orderBy'));
        $or = Session::get(CONTROLLER . ACTION . APPKEY . '.orderBy');
        if (!empty($or) && !($this->getParam('orderBy'))) {
            $this->setParam('orderBy',Session::get(CONTROLLER . ACTION . APPKEY . '.orderBy'));
        }        
    }
}

