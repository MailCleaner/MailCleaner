#! /bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
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
#   This script will install pyenv, python 3.7.7 and MailCleaner library
#
VARDIR="/var/mailcleaner"
cd $VARDIR


if [ -f $VARDIR/log/mailcleaner/install_pyenv.log ]
then
 rm $VARDIR/log/mailcleaner/install_pyenv.log
fi

FREE_SPACE=$(df -k /var/mailcleaner | tail -1 |awk '{print $4}')

if [ $FREE_SPACE -lt 600000 ]
then 
  echo "[Errno 1]: Not enough disk space" >> $VARDIR/log/mailcleaner/install_pyenv.log
  exit 
fi


DOWNLOADSERVER="mailcleanerdl.alinto.net"
curl --insecure http://$DOWNLOADSERVER/downloads/openssl-1.1.1g.tar.gz -o openssl-1.1.1g.tar.gz 2>&1 >/dev/null
SHA=$(sha256sum openssl-1.1.1g.tar.gz | cut -d ' ' -f 1)
if [[ "$SHA" != "ddb04774f1e32f0c49751e21b67216ac87852ceb056b75209af2443400636d46" ]]; then
    echo "Download failed or did not match SHA256SUM"
    exit
fi

if [ -f "openssl-1.1.1g.tar.gz" ]
then 
  tar xvf openssl-1.1.1g.tar.gz
  cd openssl-1.1.1g   && ./config --prefix=$HOME/lib/openssl --openssldir=$HOME/lib/openssl no-ssl2   && make   && make install && cd ..  && rm -rf openssl-1.1.1g openssl-1.1.1g.tar.gz

  git clone https://github.com/pyenv/pyenv.git .pyenv
  export PYENV_ROOT="$VARDIR/.pyenv"
  export PATH="$PYENV_ROOT/bin:$PATH"
  eval "$(pyenv init --path)"
  LD_LIBRARY_PATH="${HOME}/lib/openssl/lib" LDFLAGS="-L${HOME}/lib/openssl/lib -Wl,-rpath,${HOME}/lib/openssl/lib" CFLAGS="-I$HOME/lib/openssl/include" SSH="$HOME/lib/openssl" pyenv install 3.7.7 -s
  pyenv local 3.7.7

  pip install mailcleaner-library --trusted-host repository.mailcleaner.net --index https://repository.mailcleaner.net/python/ --extra-index https://pypi.org/simple/

  SSL_VERSION=$(python -c "import ssl; print(ssl.OPENSSL_VERSION)")
  if [[ "$SSL_VERSION" != "OpenSSL 1.1.1g  21 Apr 2020" ]]
  then
    echo "[Errno 3]: Can't import SSL" >> $VARDIR/log/mailcleaner/install_pyenv.log
    echo $SSL_VERSION >> $VARDIR/log/mailcleaner/install_pyenv.log
    exit
  fi

  IMPORT_MC_LIB=$(python -c "import mailcleaner") 
  if [ $? -eq 1 ]
  then
    echo "[Errno 4]: Can't import mailcleaner" >> $VARDIR/log/mailcleaner/install_pyenv.log
    exit
  fi
else
  echo "[Errno 2]: Can't download openssl exiting..." >> $VARDIR/log/mailcleaner/install_pyenv.log
  exit
fi
echo "[Errno 0]: Everything went fine..." >> $VARDIR/log/mailcleaner/install_pyenv.log
