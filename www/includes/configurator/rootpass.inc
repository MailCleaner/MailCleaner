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
#   Root password change page of MailCleaner "Configurator" wizard
#
-->
<?php
if ('rootpass.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');
?>
	<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
	<p>This password will be set for root account.</p>
        <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']."?step=".$_GET['step']; ?>" method="post">
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="newpass">New Password :</label>
	    <div class="col-md-4"><input type="password" class="form-control col-md-6" name="newpass" placeholder="Password"></div>
	  </div>
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="confnewpass">Re-type New Password :</label>
	    <div class="col-md-4"><input type="password" class="form-control col-md-6" name="confnewpass" placeholder="Password"></div>
	  </div>
	  <div class="form-group">
	    <div class="col-md-offset-5 col-md-4">
	      <button type="submit" name="submit_<?php echo $_GET['step'] ?>" value="Submit" class="btn btn-default">Submit</button>
	    </div>
	  </div>
          <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
            <?php
	    if (isset($_POST['submit_rootpass'])) {
  	      if (!empty($_POST['newpass']) && !empty($_POST['confnewpass'])) {
	        if ($_POST['newpass'] == $_POST['confnewpass']) {
		  $cmdrp = "sudo /usr/mailcleaner/bin/setpassword root ".$_POST['newpass'];
		  $cmdrp = escapeshellcmd($cmdrp);
		  $cmdrp = str_replace('!', '\!', $cmdrp);
	          exec($cmdrp, $outputrp, $retrp);
		  touch('/var/mailcleaner/run/configurator/rootpass');
		  ($retrp == 0) ? $retrp = "<span class='text-success'>Root password changed</span>" : $retrp = "<span class='text-danger'>Failed to change Root password</span>";
		  echo $retrp;
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
