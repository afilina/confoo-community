# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|

  config.env.enable

  config.vm.box = "scotch/box"
  config.vm.network "private_network", ip: "192.168.33.20"
  config.vm.synced_folder "./", "/var/www/community.confoo",
    :type => "nfs"

  config.vm.provider "virtualbox" do |vb|
      vb.name = "community.confoo"
  end
end
