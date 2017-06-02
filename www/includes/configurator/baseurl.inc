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
#   Base Url page change of MailCleaner "Configurator" wizard
#
-->
<?php
if ('baseurl.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');
?>
	<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
	<p>Set the MailCleaner Base URL. This is the URL users and administrators will use to access MailCleaner. This should be the associated address in your DNS server too.</p>
        <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']."?step=".$_GET['step']; ?>" method="post">
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="burl">Base URL :</label>
	    <div class="col-md-4"><input type="text" class="form-control" name="burl" placeholder="mailcleaner.mycompany.com"></div>
	  </div>
	  <div class="form-group">
	    <div class="col-md-offset-5 col-md-4">
	      <button type="submit" name="submit_<?php echo $_GET['step'] ?>" value="Submit" class="btn btn-default">Submit</button>
	    </div>
	  </div>
          <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
            <?php
	    if (isset($_POST['submit_baseurl'])) {
  	      if (!empty($_POST['burl'])) {
		  exec("echo \"UPDATE httpd_config SET servername='".$_POST['burl']."';\" |/usr/mailcleaner/bin/mc_mysql -m mc_config", $outputburl, $retburl);
     		  touch('/var/mailcleaner/run/configurator/baseurl');
          $_SESSION['burl'] = $_POST['burl'];
		  ($retburl == 0) ? $retburl = "<span class='text-success'>MailCleaner Base URL changed</span>" : $retburl = "<span class='text-danger'>Failed to change MailCleaner Base URL</span>";
		  echo $retburl;	          
	      } else {
	        echo "<span class='text-danger'>No field should stay empty !</span>";
	      }
	    }
           ?>
           </div>
         </div>
	</form>
