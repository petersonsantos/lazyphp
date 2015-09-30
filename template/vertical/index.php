<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">
        <?php $this->getHeaders(); ?>
        <link rel="stylesheet" type="text/css" href="<?php echo SITE_PATH ?>/template/vertical/css/vertical.css">    
		<link rel="icon" type="image/png" href="<?php echo SITE_PATH ?>/template/gear.png">
        <script src="<?php echo SITE_PATH ?>/template/vertical/menu.js"></script>
    </head>

    <body>

        <div id="wrapper">


            <div class="row">
                <!-- Sidebar -->
                <div id="mostrar_menu">
                   <span class="glyphicon glyphicon-list"></span> Menu
                </div>
                <div id="sidebar-wrapper" class="col-xs-4 col-md-3 well"> 
                    <ul class="nav nav-pills nav-stacked">
                        <?php include 'template/menu.php' ?>
                    </ul>
                </div>
                <!-- Page content -->
                <div id="page-content-wrapper" class="col-xs-8 col-sm-6 col-md-9">
                        <?php $this->getContents(); ?>
                </div>
            </div>

            <!-- Generic Modal -->
            <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="Modal" aria-hidden="true">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <div class="modal-dialog">        
                    <div class="modal-content">
                        <div style="text-align:center"><img src="<?php echo SITE_PATH; ?>/template/default/images/loading.gif" alt="LazyPHP"></div>
                    </div>
                </div>
            </div>
            <footer class="text-center">            
                <a href="http://lazyphp.com.br" target="_blank"><img src="http://lazyphp.com.br/template/lazy/images/lazyphp.png" alt="LazyPHP"></a>
            </footer>
        </div>

        <!-- Custom JavaScript for the Menu Toggle -->
        <script>
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("active");
            });
        </script>
    </body>

</html>
