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
if ('welcome.inc' == basename($_SERVER['SCRIPT_FILENAME']))
  die ('Try again later.');

shell_exec("mkdir /var/mailcleaner/run/configurator");
?>
<h2 class="text-center">Step: <?php echo $validStep['title'] ?></h2>
<p>Welcome in this configuration wizard.</p>
    <p>Please take some time to read each step as some password (Database) should not be changed after setup of a proper cluster.</p>
