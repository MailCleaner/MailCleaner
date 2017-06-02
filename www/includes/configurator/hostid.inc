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
#   HostID change page of MailCleaner "Configurator" wizard
#
-->
<?php
if ('hostid.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');
?>
	<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
	<p>Set a unique host ID. This ID will be needed when you use MailCleaner in Cluster.</p>
        <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']."?step=".$_GET['step']; ?>" method="post">
	  <div class="form-group">
	    <label class="col-md-5 control-label" for="hid">Host ID :</label>
	    <div class="col-md-4"><input type="number" min="1" max="1000" class="form-control" name="hid" placeholder="Between 1 and 1000. Must be unique."></div>
	  </div>
	  <div class="form-group">
	    <div class="col-md-offset-5 col-md-4">
	      <button type="submit" name="submit_<?php echo $_GET['step'] ?>" value="Submit" class="btn btn-default">Submit</button>
	    </div>
	  </div>
          <div class="form-group">
            <div class="col-md-offset-5 col-md-4">
            <?php
	    if (isset($_POST['submit_hostid'])) {
  	      if (!empty($_POST['hid'])) {
	        if (preg_match('/^\d+$/', $_POST['hid'])) {
		  exec("sudo /usr/mailcleaner/bin/change_hostid.sh ".$_POST['hid']." -f", $outputhid, $rethid);
       		  touch('/var/mailcleaner/run/configurator/hostid');
($rethid == 0) ? $rethid = "<span class='text-success'>MailCleaner ID changed</span>" : $rethid = "<span class='text-danger'>Failed to change MailCleaner ID</span>";
                  echo $rethid;
	        } else {
	          echo "<span class='text-danger'>Not a valid value.</span>";
	        }
	      } else {
	        echo "<span class='text-danger'>No field should stay empty !</span>";
	      }
	    }
           ?>
           </div>
         </div>
	</form>
