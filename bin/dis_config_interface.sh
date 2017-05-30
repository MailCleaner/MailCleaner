#!/bin/bash
#
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
#   Usage:
#           dis_config_interface.sh true|false

if [ "$1" == "true" ]; then
    mv /etc/network/interfaces.d/configif.conf /etc/network/interfaces.d/configif.conf.disabled 2>/dev/null
else
    mv /etc/network/interfaces.d/configif.conf.disabled /etc/network/interfaces.d/configif.conf 2>/dev/null
fi
exit 0
