<?php
class Msg {
    /**
     * Mostra uma mensagem na próxima renderização de uma view
     * 
     * @param String $msg
     * @param int $tipo {1,2,3}  1=Success, 2=Warning, 3=Error
     */
    function __construct($msg, $tipo = 1) {
        if ($tipo == 1)
            $_SESSION['frameworkMsg' . APPKEY][] = '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><span class="glyphicon glyphicon-ok"></span> ' . $msg . '</div>';
        elseif ($tipo == 2)
            $_SESSION['frameworkMsg' . APPKEY][] = '<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><span class="glyphicon glyphicon-warning-sign"></span> ' . $msg . '</div>';
        else
            $_SESSION['frameworkMsg' . APPKEY][] = '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><span class="glyphicon glyphicon-warning-sign"></span> ' . $msg . '</div>';
    }
    
    /**
     * Busca as mensagens do sistema.<br>
     * <b>deve ser utilizado apenas no template.</b><br>
     * 
     * Exemplo: <?php echo $this->getMsg();?>
     * 
     * 
     * @return String mensagem
     */
    public static function getMsg() {
        if (isset($_SESSION['frameworkMsg' . APPKEY])) {
            $msgarr = $_SESSION['frameworkMsg' . APPKEY];
            unset($_SESSION['frameworkMsg' . APPKEY]);
            $msg = '';
            foreach ($msgarr as $value) {
                $msg .= $value;
            }
            return $msg;
        }
        return null;
    }

}