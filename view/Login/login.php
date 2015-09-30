<h1 class="text-center">Login</h1>
<div class="row" style="max-width: 350px; margin: auto">
    <form class="form" method="post">
        <div class="form-group">
            <input type="text" name="login" class="form-control" placeholder="Login" autofocus="">
        </div>
        <div class="form-group">
            <input type="password" name="password" class="form-control" placeholder="Senha">
        </div>
        <div class="form-group">
            <button class="btn btn-primary btn-block">Acessar</button>
            <span class="pull-right">
                <?php echo $this->Html->getLink('Registrar',$user, 'add');?>
                </span>
            <span> <?php echo $this->Html->getLink('Esqueceu sua senha?','Login', 'send');?></span>
        </div>
    </form>
</div>