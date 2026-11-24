# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below.
Vagrant.configure("2") do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://app.vagrantup.com/boxes/search
  config.vm.box = "bento/ubuntu-26.04"
  
  # Set a long boot timeout to allow for slow VM startups (during ssh timeout).
  config.vm.boot_timeout = 600

  # Set a long SSH connection timeout to allow for slow VM startups.
  # the initial boot rides the line for timing out
  config.ssh.connect_timeout = 300

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  # Example for VirtualBox:
  #
  config.vm.provider "virtualbox" do |vb|
    # Display the VirtualBox GUI when booting the machine
    vb.gui = true

    # Customize the amount of resources on the VM:
    vb.cpus = 4
    vb.memory = "6144"

    # Disable vagrant-vbguest for this VM
    if Vagrant.has_plugin?("vagrant-vbguest")
      config.vbguest.auto_update = false
    end

  end

  # Network configuration to forward ports.
  config.vm.network :forwarded_port, guest: 80, host: 4567, auto_correct: true
  config.vm.network "private_network", ip: "172.30.41.66"

  config.vm.synced_folder ".", "/vagrant", :owner => 'vagrant'

  # Enable provisioning with a shell script. Additional provisioners such as
  # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
  # documentation for more information about their specific syntax and use.
  config.vm.provision "shell", inline: <<-SHELL

    # Install package dependencies.
    export DEBIAN_FRONTEND=noninteractive
    apt-get update
    apt-get install -y --no-install-recommends git zip unzip apache2 lsof dos2unix \
    libapache2-mod-php php-pear php php-curl php-dev php-gd php-intl php-json php-ldap php-mbstring php-mysql php-soap php-xml
    apt autoremove --purge -y
    
    # Install composer.
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    mv composer.phar /usr/local/bin/composer

    # Check out and set up VuFind.
    mkdir -p /vufindlocal/cache/cli /vufindlocal/config/vufind
    chown -R vagrant:vagrant /vufindlocal
    su - vagrant -c 'cd /vagrant && composer install && php install.php --non-interactive --overridedir=/vufindlocal'
    ln -s /vufindlocal/httpd-vufind.conf /etc/apache2/conf-enabled/vufind.conf
    a2enmod rewrite

    # Copy custom Vagrant Apache configuration after installation
    sed \
      -e 's#^RedirectMatch 301 \^/vufind/register#RedirectMatch 301 ^/register#' \
      -e 's#^AliasMatch \^/vufind/themes#AliasMatch ^/themes#' \
      -e 's#^AliasMatch \^/vufind/cache#AliasMatch ^/cache#' \
      -e 's#^Alias /vufind /vagrant/public#DocumentRoot /vagrant/public/#' \
      -e 's#^<Location /vufind>#<Location />#' \
      /vagrant/local/httpd-vufind-vagrant.conf > /vufindlocal/httpd-vufind.conf

    # Enable VuFind Apache configuration
    ln -sfn /vufindlocal/httpd-vufind.conf /etc/apache2/conf-enabled/vufind.conf
    a2dissite 000-default
    a2enmod rewrite

    # Restart Apache
    if [[ -d /run/systemd/system ]]; then
      systemctl restart apache2
    else
      service apache2 restart
    fi

    # Set up cache and config permissions.
    chown -R www-data:www-data /vufindlocal/cache /vufindlocal/config/vufind
    chmod 777 /vufindlocal/cache/cli

    # Set up profile for command line.
    echo export VUFIND_HOME=/vagrant > /etc/profile.d/vufind.sh
    echo export VUFIND_LOCAL_DIR=/vufindlocal >> /etc/profile.d/vufind.sh
  SHELL
end
