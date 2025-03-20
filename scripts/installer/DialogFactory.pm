#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2023 John Mertz <git@john.me.tz>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

package DialogFactory;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;

our @ISA = qw(Exporter);
our @EXPORT = qw(get getSimpleDialog getListDialog getYesNoDialog);
our $VERSION = 1.0;

sub get($model)
{
    my $this = {
         model => $model
    };
    bless $this, "DialogFactory";
    return $this;
}

sub getSimpleDialog($this)
{
    if ($this->{model} eq 'InLine') {
        require model::InLine::SimpleDialog;
        return model::InLine::SimpleDialog::get();
    }
}

sub getPasswordDialog($this)
{
    if ($this->{model} eq 'InLine') {
        require model::InLine::PasswordDialog;
        return model::InLine::PasswordDialog::get();
    }
}


sub getListDialog($this)
{
    if ($this->{model} eq 'InLine') {
        require model::InLine::ListDialog;
        return model::InLine::ListDialog::get();
    }
}


sub getYesNoDialog($this)
{
    if ($this->{model} eq 'InLine') {
        require model::InLine::YesNoDialog;
        return model::InLine::YesNoDialog::get();
    }
}

1;

