<!--
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2018 Florian Billebault <florian.billebault@gmail.com>
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
#   Update page of MailCleaner Configurator
#
-->
<?php
if ('updater.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');
?>
<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
<p>The product should have been updated now:</p>
<?php
  if (file_exists("/var/mailcleaner/run/configurator/updater4mc-ran")) {
     echo "<span class='text-success'>MailCleaner has been updated correctly ! You can now click on the \"next\" arrow.</span>";
     touch('/var/mailcleaner/run/configurator/updater');
  } elseif(file_exists("/var/mailcleaner/run/configurator/updater4mc-running")) {
    echo "<span class='text-danger'>Your MailCleaner is being updated, please be patient...</span>";
  } else {
    echo "<span class='text-danger'>MailCleaner has not been updated yet, please verify network access and connection, then restart MailCleaner.</span>";
  }
?>
