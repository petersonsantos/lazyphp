<h1><?= __('Bem vindo ao Instalador') ?></h1>
<div class="jumbotron">
    <p>O instalador do <em>lazyphp</em> permite a geração automática dos models, controllers e views a partir da estrutura de sua base de dados, facilitando o início do desenvolvimento de seu sistema.</p>
    <p><strong>Antes de instalar, leia estas recomendações:</strong></p>
    <ul>
        <li>Verifique atentamente o arquivo \config.php;</li>
        <li>Cada tabela do seu BD deve ter uma chave primária única e auto-incremental;</li>
        <li>Verifique as chaves estrangeiras; Eventuais mapeamentos serão gerados a partir delas;</li>
        <li>Embora não seja obrigatório, recomendo que o nome das tabelas esteja no singular;</li>
        <li>Verifique as permissões de escrita nos diretórios;</li>
        <li>Apague o arquivo <em>\controller\InstallController.php</em> após a instalação.</li>
    </ul>

</div>
<div class="panel panel-default">
    <?php if ($ok) { ?>
        <div class="panel-heading">
            <h3 class="panel-title">Escolha os arquivos que deseja instalar</h3>
        </div>
        <form method='post' role="form">
            <table class='table table-striped'>
                <?php
                echo '<tr>' . "\t\t\n";
                echo "<th>&nbsp;</th>";
                echo "<th>&nbsp;</th>";
                echo "<th colspan='2'><input class='btn btn-default btn-sm' type='button' value='Desmarcar todos' onclick='uncheck();'></th>";
                echo '</tr>' . "\n";
                foreach ($tables as $t) {
                    echo '<tr>' . "\t\t\n";
                    echo "<th>$t->name</th>";
                    echo '<td width="10%"><div class="checkbox"><label><input type="checkbox" name="model' . $t->name . '" checked> <small>Model</small></label></div></td>' . "\t\t\n";
                    echo '<td width="10%"><div class="checkbox"><label><input type="checkbox" name="controller' . $t->name . '" checked> <small>Controller</small></label></div></td>' . "\t\t\n";
                    echo '<td width="10%"><div class="checkbox"><label><input type="checkbox" name="view' . $t->name . '" checked> <small>View</small></label></div></td>' . "\t\t\n";
                    echo '</tr>' . "\n";
                }
                ?>
                <tr>
                    <th>Menu de Navegação</th>
                    <td colspan="3">
                        <div class="checkbox"><label><input type="checkbox" name="menu" checked> <small>(Re)Instalar menu</small></label></div>
                    </td>  
                </tr>
                <tr>
                    <th>Sobrescrever Models, View e Controllers caso existam?</th>            
                    <td colspan="3">
                        <div class="checkbox"><label><input type="checkbox" name="sobrescrever"> <small>sobrescrever?</small></label></div>
                    </td>  
                </tr>
            </table>

            <div class="panel-footer">
                <input type="submit" value="<?= __('Instalar'); ?>"  class="btn btn-primary pull-right">
                <br class="clearfix"><br class="clearfix">
            </div>
        </form>
    <?php } ?>
</div>
<script>
    function uncheck() {
        var formularios = document.getElementsByTagName("input");
        for (var i = 0, j = 0; i < formularios.length; i++)
        {
            if (formularios[i].type == "checkbox") {
                j++;
                if (j > 0)
                    formularios[i].checked = false;
            }
        }
    }
</script>
