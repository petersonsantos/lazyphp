<?php

final class LoginController extends AppController {
    
    # Nome do Model que representa a tabela que guarda os dados dos usuários:
    private $model = 'Administrador';
    # Nome do campo da tabela que armazena o LOGIN:
    private $login = 'email';
    # Nome do campo da tabela que armazena a SENHA:
    private $password = 'senha';
    # Nome do campo da tabela que armazena o E-MAIL:
    private $email = 'email';

    function index() {
        $this->go('Login', 'login');
    }

    function login() {
        if (Session::get('user'))
            $this->go(Config::get('indexController'), Config::get('indexAction'));
        $this->setTitle('Login');
        $this->set('user', $this->model);
    }

    function post_login() {
        $this->setTitle('Login');
        $c = new Criteria();
        $c->addCondition($this->login, '=', $_POST['login']);
        $c->addCondition($this->password, '=', md5(Config::get('salt').$_POST['password']));
        $model = $this->model;
        $this->set('user', $this->model);
        $user = $model::getFirst($c);
        if ($user) {
            new Msg('Bem vindo ' . $_POST['login']);
            Session::set('user', $user);
            $this->go(Config::get('indexController'), Config::get('indexAction')); # Ao autenticar, redireciona para...
        } else {
            new Msg('Login ou senha incorretos. Por favor, tente novamente.', 3);
        }
    }

    function logout() {
        Session::set('user', NULL);
        $this->go('Login', 'login');
    }

    function send() {
        $this->setTitle('Recuperar senha');
    }

    function post_send() {
        $this->setTitle('Recuperar senha');
        $c = new Criteria();
        $c->addCondition($this->email, '=', $_POST['email']);
        $model = $this->model;
        $user = $model::getFirst($c);
        if ($user) {
            $d = new DateTime();
            $agora = $d->format('Ymdhi');
            # email:
            $headers = "From: nao-responder@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $subject = "Recuperação de senha em ".$_SERVER['HTTP_HOST'];
            $message = "Olá,<p>Alguém (provavelmente você) pediu para mudar a senha da sua conta em ";
            $message .= $_SERVER['HTTP_HOST'] . ".</p>";
            $message .= "<p>Para confirmar este pedido e cadastrar uma nova senha, vá ao seguinte endereço web: ";
            $message .= "<a href='" . $_SERVER['HTTP_HOST'] . "/" . SITE_PATH . "/index.php?m=Login&p=reset&recuperar=" . Cript::cript(Config::get('salt') . $user->{$model::PK}) . "&d=".urlencode(Cript::cript($agora))."'>Gerar uma nova senha</a></p>";
			$message .= '<p>Ou copie este endereço e cole no seu navegador: '.$_SERVER['HTTP_HOST'] . "/" . SITE_PATH . "/index.php?m=Login&p=reset&recuperar=" . Cript::cript(Config::get('salt') . $user->{$model::PK}) . "&d=".urlencode(Cript::cript($agora)).'</p>';
			
            mail($_POST['email'], $subject, $message, $headers);
            new Msg('Um e-mail foi enviado para ' . $_POST['email'] . ' com as instruções. <br>Caso não tenha recebido, verifique sua caixa de spam e tente novamente.');
            $this->go('Login', 'login');
        } else {
            new Msg('E-mail não cadastrado!', 3);
        }
    }

    function reset() {
        $id = (int)str_replace(Config::get('salt'), '', Cript::decript($_GET['recuperar']));
        try {
            $d = new DateTime();
            $agora = $d->format('Ymdhi');
			# int muito longo para o windows, não da para fazer typecast
            $data = str_replace(Config::get('salt'), '', urldecode(Cript::decript($_GET['d'])));
            #meia hora de validade do email
            if(($agora - $data) > 30){
                throw new Exception('<p>Este link expirou. Solicite novamente sua senha.</p>');
                $this->go('Login', 'login');
            }     
            $model = $this->model;
            $user = new $model($id);
            $senha = $this->gerarSenha();
            $user->{$this->password} = md5(Config::get('salt').$senha);
            $user->save();
            
            #envia email com a nova senha
            $headers = "From: nao-responder@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $subject = "Nova senha em ".$_SERVER['HTTP_HOST'];
            $message = "Olá de novo,<p>você pediu para mudar a senha da sua conta em ";
            $message .= $_SERVER['HTTP_HOST'] . ".</p>";
            $message .= "<p>Sua nova senha é:<strong>$senha</strong></p>";
            mail($user->{$this->email}, $subject, $message, $headers);
            
            new Msg('Uma nova senha foi gerada e enviada para o seu email!');
            $this->go('Login', 'login');
            
        } catch (Exception $exc) {
            new Msg($exc->getMessage(), 3);
             $this->go('Login', 'login');
        }
    }

    public function gerarSenha($length = 8) {
        $salt = "abcdefghijklmnpqrstuvwxyz123456789";
        $len = strlen($salt);
        $pass = '';
        mt_srand(10000000 * (double) microtime());
        for ($i = 0; $i < $length; $i++) {
            $pass .= $salt[mt_rand(0, $len - 1)];
        }
        return $pass;
    }

}