<?php

/**
 * classe Html
 * 
 * @author Miguel
 * @package \lib\core
 */
class Html {

  /* 
     * Mesma coisa que o getLink, com a diferença que circunda a tag <a> com a tag <li>
     * Usada principalmente para o menu.
     * 
	 * @author  Healfull 
     * @param String $name
     * @param String $controller
     * @param String $action
     * @param array $urlParams Array associativo para especificar as variáveis opcionais enviadas via get
     * @param array $linkParams Array associativo para especificar os atributos HTML adicionais da tag <a>
     * @return string
     * 
     */
    public function getMenuLink($name, $controller, $action, Array $urlParams = NULL, Array $linkParams = NULL)         {
        return '<li>'.  $this->getLink($name, $controller, $action, $urlParams, $linkParams) .'</li>';
    }


    /**
     * Constrói um link (ancora) padrão lazyphp.
     * 
     * <b>Exemplo de uso 1:</b><br>
     * <?php echo $this->Html->getLink('Listar usuarios', 'Usuario', 'all');?>
     * 
     * <b>Retorna: </b><br>
     * &lt;a href=&quot;/Usuario/all&quot;&gt;Listar usuarios&lt;/a&gt;<br>
     * 
     * <b>Exemplo de uso 2:</b><br>
     * <?php echo $this->Html->getLink('Ver usuario', 'Usuario', 'all', array('id' => 1));?>
     * 
     * <b>Retorna: </b><br>
     * &lt;a href=&quot;/Usuario/all/?id=1&quot;&gt;Ver Usuario&lt;/a&gt;<br>
     * 
     * @param String $name
     * @param String $controller
     * @param String $action
     * @param array $urlParams Array associativo para especificar as variáveis opcionais enviadas via get
     * @param array $linkParams Array associativo para especificar os atributos HTML adicionais da tag <a>
     * @return string
     */
    public function getLink($name, $controller, $action, Array $urlParams = NULL, Array $linkParams = NULL) {

        $link = '<a href="';
        $url = SITE_PATH . '/?m=' . $controller . '&p=' . $action;
        if (Config::get('rewriteURL'))
            $url = SITE_PATH . '/' . $controller . '/' . $action . '/';
        $link .=$url;
        if (is_array($urlParams)) {
            $carr = (Config::get('criptedGetParamns'));
            if (is_array($carr))
                foreach ($carr as $param) {
                    foreach ($urlParams as $key => $value) {
                        if(is_int($key) && $param === ($key+1)){
                            $urlParams[$key] = Cript::cript($value);
                            continue;
                        }
                        elseif ($param === $key) {
                            $urlParams[$key] = Cript::cript($value);
                        }
                    }
                }
            if (Config::get('rewriteURL'))
                foreach ($urlParams as $key => $value) {
                    if (is_int($key))
                        $link .= $value . '/';
                    else
                        $link .= $key . ':' . $value . '/';
                    unset($urlParams[$key]);
                }
            foreach ($urlParams as $key => $value) {
                if (is_int($key))
                    $urlParams['arg' . ++$key] = $value;
            }
            if (Config::get('rewriteURL') && count($urlParams))
                $link .= '?';

            if (count($urlParams)) {
                $params = '&' . http_build_query($urlParams);
                $link .= $params;
            }
        }
        $link .= '"';
        if (is_array($linkParams))
            foreach ($linkParams as $key => $value) {
                $link .= ' ' . $key . '="' . $value . '"';
            }
        $link .= '>' . $name . '</a>';
        return str_replace('//', '/', $link);
    }

    /**
     * Constrói uma URL no padrão lazyphp.
     * 
     * <b>Exemplo de uso 1:</b><br>
     * <?php echo $this->Html->getUrl('Usuario', 'all');?>
     * 
     * <b>Retorna se rewriteURL estiver definido: </b><br>
     * /Usuario/all<br>
     * 
     * <b>Retorna se rewriteURL NÃO estiver definido: </b><br>
     * index.php?m=Usuario&p=all<br>
     * 
     * <b>Exemplo de uso 2:</b><br>
     * <?php echo $this->Html->getUrl('Usuario', 'all', array('id' => 1));?>
     * 
     * <b>Retorna se rewriteURL estiver definido: </b><br>
     * /Usuario/all/?id=1<br>
     * 
     * <b>Retorna se rewriteURL NÃO estiver definido: </b><br>
     * index.php?m=Usuario&p=all&id=1<br>
     * 
     * @param String $controller
     * @param String $action
     * @param array $urlParams
     * @return string
     */
    public function getUrl($controller, $action, Array $urlParams = NULL) {
        $link = '';
        $url = SITE_PATH . '/?m=' . $controller . '&p=' . $action;
        if (Config::get('rewriteURL'))
            $url = SITE_PATH . '/' . $controller . '/' . $action . '/';
        $link .=$url;
        if (is_array($urlParams)) {
            $carr = (Config::get('criptedGetParamns'));
            if (is_array($carr))
                foreach ($carr as $param) {
                    foreach ($urlParams as $key => $value) {
                         if(is_int($key) && $param === ($key+1)){
                            $urlParams[$key] = Cript::cript($value);
                            continue;
                        }
                        elseif ($param === $key) {
                            $urlParams[$key] = Cript::cript($value);
                        }
                    }
                }

            if (Config::get('rewriteURL'))
                foreach ($urlParams as $key => $value) {
                    if (is_int($key))
                        $link .= $value . '/';
                    else
                        $link .= $key . ':' . $value . '/';
                    unset($urlParams[$key]);
                }
            foreach ($urlParams as $key => $value) {
                if (is_int($key))
                    $urlParams['arg' . ++$key] = $value;
            }
            if (Config::get('rewriteURL') && count($urlParams))
                $link .= '?';
            if (count($urlParams)) {
                $params = '&' . http_build_query($urlParams);
                $link .= $params;
            }
        }        
        return str_replace('//', '/', $link);
    }

}

?>
