<?php
/**
 * classe Session
 * 
 * @author Miguel
 * @package \lib\util
 */
class Session {

    /**
     * Serializa e salva na sessão uma variável de qualquer tipo (obj, array, string,...)
     * 
     * @param String $varName
     * @param mixed $value
     */
    public static function set($varName, $value) {
        $_SESSION[$varName . APPKEY] = serialize($value);
    }

    /**
     * retorna o valor de uma sessão a partir do nome da variável.
     * 
     * @param String $varName
     * @return mixed value
     */
    public static function get($varName) {
        $obj = NULL;
        if (isset($_SESSION[$varName . APPKEY])) {
            $obj = unserialize($_SESSION[$varName . APPKEY]);
        }
        return $obj;
    }

}

?>
