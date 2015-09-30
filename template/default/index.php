<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
		<link rel="icon" type="image/png" href="<?php echo SITE_PATH ?>/template/gear.png">
        <?php $this->getHeaders();?>
        <style>
            body {
                
            }
            .starter-template {
                padding: 0px 15px;
            }
        </style>
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>

        <div class="navbar navbar-inverse" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>
                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li><?php echo $this->Html->getLink(__('Início'), Config::get('indexController'), Config::get('indexAction')); ?></li>
                        
                    <?php include 'template/menu.php'?>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </div>

        <div class="container">

            <div class="starter-template">
                <?php
                $this->getContents();
                ?>
            </div>

        </div><!-- /.container -->
        
        <!-- Generic Modal -->
        <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="Modal" aria-hidden="true">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <div class="modal-dialog">        
                <div class="modal-content">
                    <div style="text-align:center"><img src="<?php echo SITE_PATH;?>/template/default/images/loading.gif" alt="LazyPHP"></div>
                </div>
            </div>
        </div>
        <footer class="text-center">            
            <a href="http://lazyphp.com.br" target="_blank"><img src="http://lazyphp.com.br/template/lazy/images/lazyphp.png" alt="LazyPHP"></a>
        </footer>
    </body>
</html>
