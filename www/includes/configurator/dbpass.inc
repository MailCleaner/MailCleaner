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
#   DB password change page of MailCleaner "Configurator" wizard
#
-->
<?php
if ('dbpass.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');
?>
	<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
	<p>This password will be set for mysql root and mailcleaner account.</p>
	<p class="text-danger">Please change this password only before setting a cluster. Otherwise, unset your cluster and reset it after this change.</p>
        <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']."?step=".$_GET['step']; ?>" method="post">
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="oldpassdb">Old Password :</label>
	    <div class="col-md-4"><input type="password" id="autofill_<?php echo $_GET['step'] ?>" class="form-control col-md-6" name="oldpassdb" placeholder="Password"></div>
        <button type="submit" name="autofill_<?php echo $_GET['step'] ?>" value="autofill" class="btn btn-default">Click here for default password</button>
	  </div>
      <div class="form-group">
	    <label class="col-md-5 control-label" for="newpassdb">New Password :</label>
	    <div class="col-md-4"><input type="password" class="form-control col-md-6" name="newpassdb" placeholder="Password"></div>
	  </div>
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="confnewpassdb">Re-type New Password :</label>
	    <div class="col-md-4"><input type="password" class="form-control col-md-6" name="confnewpassdb" placeholder="Password"></div>
	  </div>
	  <div class="form-group">
	    <div class="col-md-offset-5 col-md-4">
	      <button type="submit" name="submit_<?php echo $_GET['step'] ?>" value="Submit" class="btn btn-default">Submit</button>
	    </div>
	  </div>
          <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
            <?php
        if (isset($_POST['autofill_dbpass'])) {
          echo '<script type="text/javascript">document.getElementById("autofill_dbpass").value="'.'MCPassw0rd'.'";</script>';
         }
         if (isset($_POST['submit_dbpass'])) {
  	      if (!empty($_POST['oldpassdb']) && !empty($_POST['newpassdb']) && !empty($_POST['confnewpassdb'])) {
	        if ($_POST['newpassdb'] == $_POST['confnewpassdb']) {
		require_once('db_functions.php');
		$master = getDb('', 'root', $_POST['oldpassdb']);
                if($master) {
		      $users = array("'mailcleaner'@'%'", "'root'@'127.0.0.1'", "'root'@'::1'", "'root'@'mailcleaner'", "'root'@'localhost'");
		     
		      $sql = "SET PASSWORD FOR ".$users[0]." = PASSWORD(:newpassdb)";
		      $sth = $master->prepare($sql);
		      $retdb3 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
		      $sql = "SET PASSWORD FOR ".$users[0]." = PASSWORD(:newpassdb)"; $slave = getDb('', 'root', $_POST['oldpassdb'], false);
                      $sth = $slave->prepare($sql);
                      $retdb4 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
		      exec("sudo /usr/mailcleaner/scripts/configuration/set_pass_in_db.sh ".$_POST['newpassdb'], $outputdbconf, $retdbconf);
		      $sql = "SET PASSWORD FOR ".$users[1]." = PASSWORD(:newpassdb)"; $master = getDb('', 'root', $_POST['oldpassdb']);
                      $sth = $master->prepare($sql);
                      $retdb5 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
		      $sql = "SET PASSWORD FOR ".$users[2]." = PASSWORD(:newpassdb)";
                      $sth = $master->prepare($sql);
                      $retdb6 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
		      $sql = "SET PASSWORD FOR ".$users[3]." = PASSWORD(:newpassdb)";
                      $sth = $master->prepare($sql);
                      $retdb7 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
		      $sql = "SET PASSWORD FOR ".$users[1]." = PASSWORD(:newpassdb)"; $slave = getDb('', 'root', $_POST['oldpassdb'], false);
                      $sth = $slave->prepare($sql);
                      $retdb8 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
		      $sql = "SET PASSWORD FOR ".$users[2]." = PASSWORD(:newpassdb)";
                      $sth = $slave->prepare($sql);
                      $retdb9 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
                      $sql = "SET PASSWORD FOR ".$users[3]." = PASSWORD(:newpassdb)";
                      $sth = $slave->prepare($sql);
                      $retdb10 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
		      $sth = $slave->prepare("STOP SLAVE; CHANGE MASTER TO MASTER_PASSWORD=:masterpass; START SLAVE;");
                      $retdbreplic = $sth->execute(array(':masterpass' => $_POST['newpassdb'])) == true ? 0 : 1;		      
		      $actual_hostid = shell_exec("grep HOSTID /etc/mailcleaner.conf |cut -d' ' -f3"); $master = getDb('', 'root', $_POST['oldpassdb']);
                      $sth = $master->prepare("UPDATE mc_config.master SET password=:newpassdb");
                      $retdbmaster = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
                      $sth = $master->prepare("UPDATE mc_config.slave SET password=:newpassdb where id=:hostid");
                      $retdbslave = $sth->execute(array(':newpassdb' => $_POST['newpassdb'], ':hostid' => $actual_hostid)) == true ? 0 : 1;	
                      $sql = "SET PASSWORD FOR ".$users[4]." = PASSWORD(:newpassdb)";
                      $sth = $master->prepare($sql);
		      $retdb1 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;
                      $sql = "SET PASSWORD FOR ".$users[4]." = PASSWORD(:newpassdb)"; $slave = getDb('', 'root', $_POST['oldpassdb'], false);
                      $sth = $slave->prepare($sql);
		      $retdb2 = $sth->execute(array(':newpassdb' => $_POST['newpassdb'])) == true ? 0 : 1;

                      touch('/var/mailcleaner/run/configurator/dbpass');
                } else {
                      $retdb1 = $retdbconf = $retdbreplic = $retdbmaster = $retdbslave = 1;
		      for($i = 1; $i <= 10; $i++)
			  ${'retdb' . $i} = 1;
                }

                ($retdb1 == 0) ? $retdb1 = "<span class='text-success'>Master instance Database root@localhost password changed</span>" : $retdb1 = "<span class='text-danger'>Failed to change Master instance Database root@localhost password</span>";
                ($retdb2 == 0) ? $retdb2 = "<span class='text-success'>Slave instance Database root@localhost password changed</span>" : $retdb2 = "<span class='text-danger'>Failed to change Slave instance Database root@localhost password</span>";
                ($retdb3 == 0) ? $retdb3 = "<span class='text-success'>Master instance Database mailcleaner@% password changed</span>" : $retdb3 = "<span class='text-danger'>Failed to change Master instance Database mailcleaner@% password</span>";
                ($retdb4 == 0) ? $retdb4 = "<span class='text-success'>Slave instance Database mailcleaner@% password changed</span>" : $retdb4 = "<span class='text-danger'>Failed to change Slave instance Database mailcleaner@% password</span>";
                ($retdb5 == 0) ? $retdb5 = "<span class='text-success'>Master instance Database root@127.0.0.1 password changed</span>" : $retdb5 = "<span class='text-danger'>Failed to change Master instance Database root@127.0.0.1 password</span>";
                ($retdb6 == 0) ? $retdb6 = "<span class='text-success'>Master instance Database root@::1 password changed</span>" : $retdb6 = "<span class='text-danger'>Failed to change Master instance Database root@::1 password</span>";
                ($retdb7 == 0) ? $retdb7 = "<span class='text-success'>Master instance Database root@mailcleaner password changed</span>" : $retdb7 = "<span class='text-danger'>Failed to change Master instance Database root@mailcleaner password</span>";
                ($retdb8 == 0) ? $retdb8 = "<span class='text-success'>Slave instance Database root@127.0.0.1 password changed</span>" : $retdb8 = "<span class='text-danger'>Failed to change Slave instance Database root@127.0.0.1 password</span>";
                ($retdb9 == 0) ? $retdb9 = "<span class='text-success'>Slave instance Database root@::1 password changed</span>" : $retdb9 = "<span class='text-danger'>Failed to change Slave instance Database root@::1 password</span>";
                ($retdb10 == 0) ? $retdb10 = "<span class='text-success'>Slave instance Database root@mailcleaner password changed</span>" : $retdb10 = "<span class='text-danger'>Failed to change Slave instance Database root@mailcleaner password</span>";
                ($retdbconf == 0) ? $retdbconf = "<span class='text-success'>Master and Slave instance Database passwords changed in configuration</span>" : $retdbconf = "<span class='text-danger'>Failed to change Master and Slave instance Database password in conf</span>";
                ($retdbreplic == 0) ? $retdbreplic = "<span class='text-success'>Mysql Replication password changed</span>" : $retdbreplic = "<span class='text-danger'>Failed to change Mysql Replication password</span>";
                ($retdbmaster == 0) ? $retdbmaster = "<span class='text-success'>Mysql password changed in table master</span>" : $retdbmaster = "<span class='text-danger'>Failed to change Mysql password in table master</span>";
                ($retdbslave == 0) ? $retdbslave = "<span class='text-success'>Mysql password changed in table slave</span>" : $retdbslave = "<span class='text-danger'>Failed to change Mysql password in table slave</span>";
                echo $retdb1;
                echo "<br/>";
                echo $retdb2;
         		echo "<br/>";
		        echo $retdb3;
                echo "<br/>";
                echo $retdb4;
                echo "<br/>";
                echo $retdb5;
                echo "<br/>";
                echo $retdb6;
                echo "<br/>";
                echo $retdb7;
                echo "<br/>";
                echo $retdb8;
                echo "<br/>";
                echo $retdb9;
                echo "<br/>";
                echo $retdb10;
                echo "<br/>";
                echo $retdbconf;
                echo "<br/>";
                echo $retdbreplic;
                echo "<br/>";
                echo $retdbmaster;
                echo "<br/>";
                echo $retdbslave;
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
