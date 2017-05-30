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
#   Identification page of MailCleaner "Configurator" wizard
#
-->
	<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
	<p>Please identify using web <strong>admin</strong> account.</p>
        <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']."?step=".$_GET['step']; ?>" method="post">
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="identpass">Actual Password :</label>
	    <div class="col-md-4"><input type="password" id="autofill_<?php echo $_GET['step'] ?>" class="form-control" name="identpass" placeholder="Password"></div>
         <button type="submit" name="autofill_<?php echo $_GET['step'] ?>" value="autofill" class="btn btn-default">Click here for default password</button>
	  </div>
	  <div class="form-group">
	    <div class="col-md-offset-5 col-md-4">
	      <button type="submit" name="submit_<?php echo $_GET['step'] ?>" value="Submit" class="btn btn-default">Submit</button>
	    </div>
	  </div>
          <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
            <?php
        if (isset($_POST['autofill_identify'])) {
          echo '<script type="text/javascript">document.getElementById("autofill_identify").value="'.'MCPassw0rd'.'";</script>';
         }
	    if (isset($_POST['submit_identify'])) {
  	      if (!empty($_POST['identpass'])) {
              $actualadminpass = trim(exec("echo \"SELECT password FROM administrator WHERE username = 'admin'\" |/usr/mailcleaner/bin/mc_mysql -m mc_config"));
              $salt = trim(exec("echo \"SELECT password FROM administrator WHERE username = 'admin'\" |/usr/mailcleaner/bin/mc_mysql -m mc_config |cut -d$ -f4"));
              $cryptedidentpass = crypt($_POST['identpass'],'$6$rounds=1000$'.$salt.'$');
	        if ($cryptedidentpass == $actualadminpass) {
	          echo "<span class='text-success'>Identification successful. Click on Next !</span>";
		  $_SESSION['identok'] = True;
		  touch('/var/mailcleaner/run/configurator/identify');
	        } else {
	          echo "<span class='text-danger'>Identification failed !</span>";
              $_SESSION['identok'] = False;
	        }
	      } else {
              echo "<span class='text-danger'>Identification failed !</span>";
    		  $_SESSION['identok'] = False;
	      }
	    }
           ?>
           </div>
         </div>
	</form>
