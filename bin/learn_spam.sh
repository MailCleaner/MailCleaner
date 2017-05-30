#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#
#   This script will learn a message file or directory as a spam
#
#   Usage:
#           learn_spam.sh message_file/directory


SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/var/mailcleaner
fi

if [ "$1" = "" ]; then
  echo "usage: ./learn_spam.sh message_file|message_dir"
  exit 1
fi

if [ -f $1 ] || [ -d $1 ]; then
  sa-learn --spam -p $SRCDIR/etc/mailscanner/spam.assassin.prefs.conf $1
else
  echo "file/directory $1 is not useable"
  exit 1
fi

exit 0
