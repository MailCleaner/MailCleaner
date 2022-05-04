<!--
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Florian Billebault <florian.billebault@gmail.com>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 3 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program. If not, see <http://www.gnu.org/licenses/>.
#
#
#   Index page of MailCleaner "Configurator" wizard
#
-->

<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
      body {
      }
  
    </style>
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/main.css">

    <script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>

    <style>

      body {
      background-color: #FCFCFC;
      }

      header {
      /* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#741864+0,7db9e8+100 */
      background: #741864; /* Old browsers */
      background: -moz-linear-gradient(-45deg,  #741864 0%, #7db9e8 100%); /* FF3.6-15 */
      background: -webkit-linear-gradient(-45deg,  #741864 0%,#7db9e8 100%); /* Chrome10-25,Safari5.1-6 */
      background: linear-gradient(135deg,  #741864 0%,#7db9e8 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
      filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#741864', endColorstr='#7db9e8',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */

      border-style: solid;
      border-top: none;
      border-left: none;
      border-right: none;
      border-bottom: 4em solid black;
      }

      h1 small {
      color: white;
      }
      
      .progress {
      position: relative;
      }

      .progress-bar {
      background-image: none;
      /* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#741864+0,7db9e8+100 */
      background: #741864; /* Old browsers */
      background: -moz-linear-gradient(-45deg,  #741864 0%, #7db9e8 100%); /* FF3.6-15 */
      background: -webkit-linear-gradient(-45deg,  #741864 0%,#7db9e8 100%); /* Chrome10-25,Safari5.1-6 */
      background: linear-gradient(135deg,  #741864 0%,#7db9e8 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
      filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#741864', endColorstr='#7db9e8',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
      
      }
      
      .progress span {
      position: absolute;
      display: block;
      width: 100%;
      font-weight: bold;
      color: white;
      }

      .customnav a {
      color: grey;
      text-decoration: none;
      }

      .customnav a:hover {
      color: #C7130D;
      }

      footer {
      position: absolute;
      width: 100%;
      bottom: 0;
      height: 6em;
      padding: 10px 30px 10px 30px;
      }
      
    </style>
  </head>
  <body class="container-fluid">
    <?php
      if (session_status() == PHP_SESSION_NONE) {
       session_start();
       }

       $pagePath = '../includes/configurator/';
       $infoPage = array('welcome' => array('page' => 'welcome.inc', 'prev' => '', 'next' => 'updater', 'sesstep' => 'welcome', 'title' => 'Welcome', 'progress' => '0'),
    'updater' => array('page' => 'updater.inc', 'prev' => 'welcome', 'next' => 'identify', 'sesstep' => 'updater', 'title' => 'MailCleaner Update', 'progress' => '7'),
  'identify' => array('page' => 'identify.inc', 'prev' => 'updater', 'next' => 'adminpass', 'sesstep' => 'identify', 'title' => 'Configurator Identification', 'progress' => '14'),
  'adminpass' => array('page' => 'adminpass.inc', 'prev' => 'identify', 'next' => 'rootpass', 'sesstep' => 'adminpass', 'title' => 'Change Admin (Web) Password', 'progress' => '28'),
  'rootpass' => array('page' => 'rootpass.inc', 'prev' => 'adminpass', 'next' => 'dbpass', 'sesstep' => 'rootpass', 'title' => 'Change Root Password', 'progress' => '42'),
  'dbpass' => array('page' => 'dbpass.inc', 'prev' => 'rootpass', 'next' => 'hostid', 'sesstep' => 'dbpass', 'title' => 'Change Database Password', 'progress' => '56'),
  'hostid' => array('page' => 'hostid.inc', 'prev' => 'dbpass', 'next' => 'baseurl', 'sesstep' => 'hostid', 'title' => 'Change Host ID', 'progress' => '70'),
  'baseurl' => array('page' => 'baseurl.inc', 'prev' => 'hostid', 'next' => 'ending', 'sesstep' => 'baseurl', 'title' => 'Change Base URL', 'progress' => '84'),
  'ending' => array('page' => 'ending.inc', 'prev' => 'baseurl', 'next' => '', 'sesstep' => 'ending', 'title' => 'Some tips before you go !', 'progress' => '100')
  );
  
  $validStep = $infoPage['welcome'];
  if (isset($_GET['step'])) {
    if (array_key_exists($_GET['step'], $infoPage)) {
      $validStep = $infoPage[$_GET['step']];
    }
  }

  if (isset($_GET['step']) && $_GET['step'] != 'welcome' && $_GET['step'] != 'updater' && $_GET['step'] != 'identify') {
    if ($_SESSION['identok'] != "True") {
      $validStep = $infoPage['identify'];
      $_GET['step'] = 'identify';
    }
  }

  if (!empty($validStep['prev'])) {
    if (!file_exists('/var/mailcleaner/run/configurator/'.$validStep['prev'])) {
      $validStep = $infoPage['welcome'];
      $_GET['step'] = 'welcome';
      echo "<script>alert('Please valid each step the first time !');</script>";
    }
  }
  
  ?>
    <header class="text-center row">
      <h1><img src="img/header_config_assistant.png" title="MailCleaner logo" alt="MailCleaner logo"/>
      </h1>
      <!--[if lt IE 8]>
          <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
          <![endif]-->
    </header>
    <div class="row mainrow">
      <div class="customnav col-md-2 text-center">
	<?php if (!empty($validStep['prev'])) { ?>
	<a href="index.php?step=<?php echo $validStep['prev'] ?>">
	  <span style="font-size: 10em;" class="glyphicon glyphicon-backward"></span><br/>
	  <span style="font-size: 2em;">Previous</span>
	</a>
	<?php } ?>
      </div>
      <div class="col-md-8">
	<div class="jumbotron">  
	  <?php require($pagePath . $validStep['page']); ?>
	</div>
	<div class="progress" style="height: 3vh">
	  <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $validStep['progress'] ?>" aria-valuemin="0" aria-valuemax=100 style="min-width: 2em; width: <?php echo $validStep['progress'] ?>%">
	    <span class="text-center"><?php echo $validStep['progress'] ?>%</span>
	  </div>
	</div>
      </div><!-- End of Main column-->
      <div class="customnav col-md-2 text-center">
	<?php if (!empty($validStep['next'])) { ?>
	<a href="index.php?step=<?php echo $validStep['next'] ?>">
	  <span style="font-size: 10em;" class="glyphicon glyphicon-forward"></span><br/>
	  <span style="font-size: 2em;">Next</span>
	</a>
	<?php } ?>
      </div>
    </div><!-- End of Main row -->
    <footer class="row footer text-right">
      <hr>
      <p>&copy; MailCleaner</p>
    </footer>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>

      <script src="js/vendor/bootstrap.min.js"></script>

      <script src="js/plugins.js"></script>
      <script src="js/main.js"></script>
  </body>
</html>
