package FuzzyOcr::Preprocessor;

sub new {
    my ($class, $label, $command, $args) = @_;

    bless {
        "label"     => $label,
        "command"   => $command,
        "args"      => $args
    }, $class;
}

sub run {
    my ($self, $input) = @_;
    my $tmpdir = FuzzyOcr::Config::get_tmpdir();
    my $label = $self->{label};
    my $output = "$tmpdir/prep.$label.out";
    my $stderr = ">$tmpdir/prep.$label.err";

    my $stdin = undef;
    my $stdout = undef;
    my $args = $self->{args};
    my $rcmd = $self->{command};

    if (defined $args) {
        $rcmd .= ' ' . $args;
    }

    # Does the processor expect input from file or from stdin?
    if(defined $args and $args =~ /\$input/) {
        $rcmd =~ s/\$input/$input/;
    } else {
        $stdin = "<$input";
    }

    # Does it output to file or to stdout?
    if(defined $args and $args =~ /\$output/) {
        $rcmd =~ s/\$output/$output/;
    } else {
        $stdout = ">$output";
    }

    # Run processor
    my $retcode = FuzzyOcr::Misc::save_execute($rcmd, $stdin, $stdout, $stderr);

    # Return code
    return $retcode;
}

1;
