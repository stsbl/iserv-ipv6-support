use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

for my $case (['chklan6cfg', 'LAN', '2001:db8::/64'], ['chkregdns6cfg', 'ExternalIPs', '2001:db8::1'])
{
  my ($script, $key, $address) = @$case;
  my $dir = tempdir(CLEANUP => 1);
  make_path("$dir/IServ", "$dir/Regexp", "$dir/bin");
  sub write_file {
    my ($file, $content) = @_;
    open my $fh, '>', $file or die $!;
    print $fh $content;
    close $fh;
  }
  write_file("$dir/IServ/Conf.pm", "package IServ::Conf; use Exporter 'import'; our \@EXPORT = qw(\$conf); our \$conf = { UpdateIPv6LAN => 1, UpdateIPv6Regdns => 1, $key => ['$address'] }; 1;\n");
  write_file("$dir/Regexp/IPv6.pm", 'package Regexp::IPv6; our $IPv6_re = qr/[0-9a-f:]*:[0-9a-f:]+/; 1;');
  write_file("$dir/bin/netquery6", "#!/bin/sh\nexit 0\n");
  write_file("$dir/bin/store_cfg_array", "#!/bin/sh\nprintf '%s' \"\$3\" > '$dir/result'\n");
  write_file("$dir/bin/newconf", "#!/bin/sh\nexit 0\n");
  chmod 0755, glob "$dir/bin/*";
  open my $fh, '<', "sbin/$script" or die $!;
  my $source = do { local $/; <$fh> };
  write_file("$dir/bin/ipv6_defif", "#!/bin/sh\necho eth0\n");
  $source =~ s{/usr/lib/iserv/ipv6_defif}{$dir/bin/ipv6_defif}g;
  $source =~ s{/usr/lib/iserv/newconf}{$dir/bin/newconf}g;
  write_file("$dir/$script", $source);
  local $ENV{PERL5LIB} = $dir;
  local $ENV{PATH} = "$dir/bin:$ENV{PATH}";
  local $ENV{IPV6CHDETECTD_RUNNING} = 1;
  is(system('perl', '-CSDAL', "$dir/$script", '-r'), 0, "$script removes the last old IPv6 entry");
  open my $result, '<', "$dir/result" or die "missing persisted result: $!";
  is(do { local $/; <$result> }, '()', "$key is persisted as an empty array without an empty-string entry");
  # Reacquisition after the persisted empty state must not become a dead end.
  write_file("$dir/IServ/Conf.pm", "package IServ::Conf; use Exporter 'import'; our \@EXPORT = qw(\$conf); our \$conf = { UpdateIPv6LAN => 1, UpdateIPv6Regdns => 1, $key => [] }; 1;\n");
  my $new = $key eq 'LAN' ? '2001:db8:1::/64' : '2001:db8::2';
  write_file("$dir/bin/netquery6", "#!/bin/sh\necho '$new'\n");
  is(system('perl', '-CSDAL', "$dir/$script", '-r'), 0, "$script handles reacquisition after last-address loss");
  open my $reacquired, '<', "$dir/result" or die $!;
  is(do { local $/; <$reacquired> }, '("' . $new . '")', "$key is populated again after reacquisition");

  unlink "$dir/result";
  write_file("$dir/IServ/Conf.pm", "package IServ::Conf; use Exporter 'import'; our \@EXPORT = qw(\$conf); our \$conf = { UpdateIPv6LAN => 1, UpdateIPv6Regdns => 1, $key => ['$address'] }; 1;\n");
  write_file("$dir/bin/netquery6", "#!/bin/sh\nexit 2\n");
  isnt(system('perl', '-CSDAL', "$dir/$script", '-r'), 0, "$script reports an actual query failure");
  ok(!-e "$dir/result", "$script does not persist a query failure as an empty address list");

}
done_testing;
