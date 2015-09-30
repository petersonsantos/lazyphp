<?php
date_default_timezone_set("America/Campo_Grande");
##################################
# CONFIGURAÇÕES DO SGBD 
##################################

# servidor do SGBD:
Config::set('db_host', 'localhost');
# usuario do SGBD:
Config::set('db_user', 'root');
# senha do SGBD:
Config::set('db_password', '');
# nome do banco de dados:
Config::set('db_name', '');

# MODO DE DEPURAÇÃO
# desenvolvimento: true
# produção: false
Config::set('debug', true);

# TEMPLATE PADRÃO
# Ex: para o diretório /template/default:
# Config::set('template', 'default');
Config::set('template', 'default');


# Chave da aplicação, para controle de sessões e criptografia
# Utilize uma cadeia alfanumérica aleatória única
Config::set('key', 'bF3mMstKyG96AhBfGjfubQRR7W5Mrpnwarzq7GWufVyfZwrzttmAnP75wqvHGSzX');

# SALT - Utilizada na criptografia
# Utiliza uma chave alfa-numéria complexa de no mínimo 16 dígitos
Config::set('salt', 'TjcqT8jgR8H6v6vhJhBcdZmCvnZs4Ghs9JcvW48gCqUTtVEkcK5hYLpsw6As8AbU');

Config::set('lang', 'pt_br');
Config::set('rewriteURL', true);
Config::set('indexController', 'Index');
Config::set('indexAction', 'index');
Config::set('criptedGetParamns', array());