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
#   Last page with advices of MailCleaner "Configurator" wizard
#
-->
<?php
if ('ending.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');
?>
	<h2 class="text-center">Step: <?php echo $validStep['title'] ?>
	<br/>
	<small>Configuration done !</small>
	</h2>
	<p>After leaving this configurator, you should :
	<ol>
	<li>Check your MailCleaner network configuration</li>
	<li>Disable Configuration interface once you are sure you can access your MailCleaner</li>
	<li>Read the documentation</li>
	<li>Consult the KnowledgeBase</li>
	<li>[Optionnal] Setup some MailCleaner slaves to setup a Cluster</li>
	<li>[Optionnal] Open a ticket on Commercial Support page or ask help of community on MailCleaner Community Forum</li>
	<li>Drink a beer and watch spam to be catch in Tracing !</li>
	</ol>
	</p>
        <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']."?step=".$_GET['step']; ?>" method="post">
	  <div class="form-group">
	    <div class="col-md-12">
	      <button type="submit" name="submit_<?php echo $_GET['step'] ?>" value="Submit" class="btn btn-default"><span style="color:red">8. [Mandatory]</span> Close and allow access to MailCleaner Web Interface ! (Please wait reload after clicking)</button>
	    </div>
	  </div>
          <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
            <?php
	    if (isset($_POST['submit_ending'])) {
	      echo "<script type='text/javascript'>";
          if (!empty($_SESSION['burl'])) {
              echo "window.location.href = 'https://".$_SESSION['burl']."/admin/'";
          } else {
              echo "window.location.href = 'https://".$_SERVER['SERVER_NAME']."/admin/'";
          }
	      echo "</script>";
	    }
           ?>
           </div>
	   <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
            <?php
	    if (isset($_POST['submit_ending'])) {
	      
	    }
           ?>
           </div>
         </div>
         </div>
	</form>
