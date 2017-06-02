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
#   Admin password change page of MailCleaner "Configurator" wizard
#
-->
<?php
if ('adminpass.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');
?>
	<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
	<p>This password will be the admin account password used to access the MailCleaner admin web interface.</p>
        <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']."?step=".$_GET['step']; ?>" method="post">
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="newpassaw">New Password :</label>
	    <div class="col-md-4"><input type="password" class="form-control col-md-6" name="newpassaw" placeholder="Password"></div>
	  </div>
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="confnewpassaw">Re-type New Password :</label>
	    <div class="col-md-4"><input type="password" class="form-control col-md-6" name="confnewpassaw" placeholder="Password"></div>
	  </div>
	  <div class="form-group">
	    <div class="col-md-offset-5 col-md-4">
	      <button type="submit" name="submit_<?php echo $_GET['step'] ?>" value="Submit" class="btn btn-default">Submit</button>
	    </div>
	  </div>
          <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
<?php
         if (isset($_POST['submit_adminpass'])) {
             if (!empty($_POST['newpassaw']) && !empty($_POST['confnewpassaw'])) {
                if ($_POST['newpassaw'] == $_POST['confnewpassaw']) {
                    $saltaw = '$6$rounds=1000$'.dechex(rand(0,15)).dechex(rand(0,15)).'$';
                    $cryptedaw = crypt($_POST['newpassaw'], $saltaw);
		    $mymcpass = shell_exec("grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3");
                    $qryaw = "UPDATE administrator SET password=\"$cryptedaw\" WHERE username=\"admin\";";
		    $qryfull = "/opt/mysql5/bin/mysql --socket='/var/mailcleaner/run/mysql_master/mysqld.sock' -umailcleaner -p'".trim($mymcpass)."' -e '".$qryaw."' mc_config";
                    exec($qryfull, $outputaw, $retaw);
		    touch('/var/mailcleaner/run/configurator/adminpass');
                    ($retaw == 0) ? $retaw = "<span class='text-success'>Admin password changed</span>" : $retaw = "<span class='text-danger'>Failed to change Admin password</span>";
                echo $retaw;
	        } else {
	          echo "<span class='text-danger'>New Password and Re-type Password field have to be identical !</span>";
	        }
	      } else {
	        echo "<span class='text-danger'>No field should stay empty !</span>";
	      }
	    }
           ?>
           </div>
         </div>
	</form>
