Exec {
    path => [ '/bin/', '/sbin/' , '/usr/bin/', '/usr/sbin/' ]
}

package { ['nmap', 'supervisor', 'php5', 'php5-cli', 'php5-sqlite'] :
  ensure => latest,
}

$interface = "eth1"
$ciddr = inline_template('<%= scope["::netmask_eth1"].split(".").map { |e| e.to_i.to_s(2).rjust(8, "0") }.join.count("1").to_s %>')
$network = "$network_eth1/$ciddr"

file { 'config.yml':
  path => "/vagrant/config/app/config.yml",
  content => template("/vagrant/dev/puppet/files/config.yml.erb"),
}

file { 'scanner.conf':
  source => "/vagrant/dev/puppet/files/scanner.conf",
  path => "/etc/supervisor/conf.d/scanner.conf",
  notify => Service['supervisor'],
  require => Exec['create-db'],
}

file { 'web.conf':
  source => "/vagrant/dev/puppet/files/web.conf",
  path => "/etc/supervisor/conf.d/web.conf",
  notify => Service['supervisor'],
  require => Exec['create-db'],
}

service { 'supervisor':
  ensure => 'running'
}

exec { 'create-db':
  command => 'php /vagrant/vendor/bin/doctrine orm:schema-tool:create',
  cwd => '/vagrant',
  unless => 'test -e /vagrant/device.db',
  require => [ Package['php5-cli'], File['config.yml'] ]
}