use strict;
package FuzzyOcr::Misc;

use base 'Exporter';
our @EXPORT_OK = qw(max removedirs removedir save_execute);

use lib qw(..);
use FuzzyOcr::Config qw(set_pid unset_pid get_config get_timeout kill_pid);
use FuzzyOcr::Logging qw(debuglog errorlog warnlog infolog);
use Time::HiRes qw( gettimeofday tv_interval );
use POSIX qw(WIFEXITED WIFSIGNALED WIFSTOPPED WEXITSTATUS WTERMSIG WSTOPSIG);

# Provide some misc helper functions

sub max {
    unless ( defined( $_[0] ) and defined( $_[1] ) ) { return 0 }
    unless ( defined( $_[0] ) ) { return $_[1] }
    unless ( defined( $_[1] ) ) { return $_[0] }
    if     ( $_[0] < $_[1] )    { return $_[1] }
    else                        { return $_[0] }
}

sub removedirs {
    my @dirs = @_;
    foreach my $dir (@dirs) {
        removedir($dir);
    }
}

sub removedir {
    my $dir = $_[0];
    return unless -d $dir;
    opendir D, $dir;
    my @files = readdir D;
    closedir D;
    foreach my $f (@files) {
        next if $f eq '.';
        next if $f eq '..';
        my $ff = Mail::SpamAssassin::Util::untaint_file_path("$dir/$f");
        unless (unlink $ff) {
            errorlog("Cannot remove: $ff");
        }
    }
    debuglog("Remove DIR: $dir");
    unless(rmdir $dir) {
        errorlog("Cannot remove DIR: $dir");
    }
}

# map process termination status number to a string, and append optional
# user error mesage, returning the resulting string
sub exit_status_str($;$) {
  my($stat,$err) = @_; my($str);
  if (WIFEXITED($stat)) {
    $str = sprintf("exit %d", WEXITSTATUS($stat));
  } elsif (WIFSTOPPED($stat)) {
    $str = sprintf("stopped, signal %d", WSTOPSIG($stat));
  } else {
    $str = sprintf("DIED on signal %d (%04x)", WTERMSIG($stat),$stat);
  }
  $str .= ', '.$err  if defined $err && $err != 0;
  $str;
}

# POSIX::open a file or dup an existing fd (Perl open syntax), with a
# requirement that it gets opened on a prescribed file descriptor $fd_target;
# this subroutine is usually called from a forked process prior to exec
sub open_on_specific_fd($$) {
  my($fd_target,$fname) = @_;
  my($flags) = 0; my($mode) = 0640;
  $fname =~ s/^< *//;
  $fname =~ s/^>> *// and $flags |= POSIX::O_CREAT|POSIX::O_WRONLY|POSIX::O_APPEND;
  $fname =~ s/^> *//  and $flags |= POSIX::O_CREAT|POSIX::O_WRONLY;
  POSIX::close($fd_target);  # ignore error, we may have just closed a log
  my($fd_got) = POSIX::open($fname,$flags,$mode);
  defined $fd_got or die "Can't open $fname, flags=$flags: $!";
  $fd_got = 0 + $fd_got;     # turn into numeric, avoid: "0 but true"
  if ($fd_got != $fd_target) {  # dup, ensuring we get a specified descriptor
    # POSIX mandates we got the lowest fd available (but some kernels have
    # bugs), let's be explicit that we require a specified file descriptor
    defined POSIX::dup2($fd_got,$fd_target)
      or die "Can't dup2 from $fd_got to $fd_target: $!";
    if ($fd_got > 2) {  # let's get rid of the original fd, unless 0,1,2
      my($err); defined POSIX::close($fd_got) or $err = $!;
      $err = defined $err ? ": $err" : '';
    }
  }
  $fd_got;
}

sub save_execute {
    my $conf = get_config();
    my $t = get_timeout();
    my ($cmd, $stdin, $stdout, $stderr, $return_stdout) = @_;
    my ($pgm,@args) = split(' ',$cmd);
    $stdout = '>/dev/null' unless $stdout;
    $stderr = '>/dev/null' unless $stderr;
    my $retcode;
    my $begin = [gettimeofday];
    if ($conf->{'focr_global_timeout'}) {
        my $pid = fork();
        if (not defined $pid) {
            errorlog("Can't fork to execute external programs! Aborting");
            return -1;
        } elsif (not $pid) {
          eval {  # must not use die in forked process, or we end up with
                  # two running daemons!
            debuglog("Exec  : $cmd");
            debuglog("Stdin : $stdin")  if (defined $stdin);
            debuglog("Stdout: $stdout") if (defined $stdout);
            debuglog("Stderr: $stderr") if (defined $stderr);
            # there is no guarantee that Perl file handles STDIN, STDOUT
            # and STDERR are on file descriptors 0, 1, 2.  Let's make sure
            # the exec'd program receives the right files on file descr 0,1,2
            if (defined $stdin) {
                open_on_specific_fd(0, $stdin);
            }
            if (defined $stdout) {
                open_on_specific_fd(1, $stdout);
            }
            if (defined $stderr) {
                open_on_specific_fd(2, $stderr);
            }
            exec {$pgm} ($pgm,@args);
            die "failed to exec $cmd: $!";
          };
          # couldn't open file descriptors or exec failed
          chomp($@); my($msg) = "save_execute: $@\n";
          # try to get some attention, log and stderr may be closed
          POSIX::write(2,$msg,length($msg));  print STDERR $msg;
          POSIX::_exit(8);  # must avoid END and destructor processing!
        } else {
            set_pid($pid); wait(); $retcode = $?;
            debuglog(sprintf("Elapsed [%s]: %.6f sec. (%s: %s)",
                $pid, tv_interval($begin, [gettimeofday]),
                $pgm, exit_status_str($retcode)));
            unset_pid();
            if ($return_stdout and $stdout !~ m,/dev/null,i) {
                $stdout =~ tr/>|</   /;
                open(INFILE, "<$stdout");
                my @stdout_data = <INFILE>;
                close(INFILE);
                return($retcode, @stdout_data);
            }
            return $retcode;
        }
    } else {
        my @stdout_data;
        my $pid;
        $t->run_and_catch(sub {
            $pid = fork();
            if (not defined $pid) {
                errorlog("Can't fork to execute external programs! Aborting");
                return -1;
            } elsif (not $pid) {
              eval {  # must not use die in forked process, or we end up with
                      # two running daemons!
                debuglog("Exec  : $cmd");
                debuglog("Stdin : $stdin") if (defined $stdin);
                debuglog("Stdout: $stdout") if (defined $stdout);
                debuglog("Stderr: $stderr") if (defined $stderr);
                if (defined $stdin) {
                    open_on_specific_fd(0, $stdin);
                }
                if (defined $stdout) {
                    open_on_specific_fd(1, $stdout);
                }
                if (defined $stderr) {
                    open_on_specific_fd(2, $stderr);
                }
                exec {$pgm} ($pgm,@args);
                die "failed to exec $cmd: $!";
              };
              # couldn't open file descriptors or exec failed
              chomp($@); my($msg) = "save_execute: $@\n";
              # try to get some attention, log and stderr may be closed
              POSIX::write(2,$msg,length($msg));  print STDERR $msg;
              POSIX::_exit(8);  # must avoid END and destructor processing!
            } else {
                set_pid($pid); wait(); $retcode = $?;
                debuglog(sprintf("Elapsed [%s]: %.6f sec. (%s: %s)",
                    $pid, tv_interval($begin, [gettimeofday]),
                    $pgm, exit_status_str($retcode)));
                unset_pid();
                if ($return_stdout and $stdout !~ m,/dev/null,i) {
                    $stdout =~ tr/>|</   /;
                    open(INFILE, "<$stdout");
                    @stdout_data = <INFILE>;
                    close(INFILE);
                }
            }
        });
        if ($t->timed_out()) {
            errorlog("Command \"$cmd\" timed out after $conf->{focr_timeout} seconds.");
            errorlog("Consider decreasing your load and/or increasing the timeout.");
            errorlog("Killing possibly running pid...");
            my ($ret, $pid) = kill_pid();
            if ($ret > 0) {
                infolog("Successfully killed PID $pid");
            } elsif ($ret < 0) {
                warnlog("No processes left... this shouldn't happen...");
            } else {
                warnlog("Failed to kill PID $pid, possibly stale process");
            }
            return -1;
        } else {
            if ($return_stdout) {
                return($retcode, @stdout_data);
            } else {
                return $retcode;
            }
        }
    }
}

1;
