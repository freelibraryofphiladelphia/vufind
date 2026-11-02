# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below.
Vagrant.configure("2") do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://app.vagrantup.com/boxes/search
  config.vm.box = "bento/ubuntu-24.04"
  #config.vm.box.version = "202510.26.0"
  config.vbguest.iso_path = "c:/program files/oracle/virtualbox/VBoxGuestAdditions.iso"
  config.vbguest.auto_update = false

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  # Example for VirtualBox:
  #
  config.vm.provider "virtualbox" do |vb|
    # Display the VirtualBox GUI when booting the machine
    # vb.gui = true

    # Customize the amount of resources on the VM:
    vb.cpus = 4
    vb.memory = "6144"
  end

  # Network configuration to forward ports.
  config.vm.network :forwarded_port, guest: 80, host: 4567
  config.vm.network :forwarded_port, guest: 8983, host: 4568
  config.vm.synced_folder ".", "/vagrant", :owner => 'vagrant', disabled: false

  # Provisioning: install.sh contents, inlined. Quoted heredoc so bash
  # receives the script byte-for-byte (no Ruby escape or interpolation).
  config.vm.provision "shell", privileged: true, inline: <<-'SHELL'
    #!/bin/bash
    export DEBIAN_FRONTEND=noninteractive
    sudo apt-get update

    # MariaDB has no debconf root-password presets; root password is set below.

    # Install package dependencies.
#    apt-get install -y build-essential git zip unzip apache2 default-jdk-headless mysql-server lsof \
#    libapache2-mod-php php-pear php php-curl php-dev php-gd php-intl php-json php-ldap php-mbstring php-mysql php-soap php-xml

    apt-get install -y -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold git zip unzip apache2 default-jdk-headless mariadb-server lsof libapache2-mod-php php-pear php php-curl \
    php-dev php-gd php-intl php-json php-ldap php-mbstring php-mysql php-soap php-xml

    # Dev-only root password over TCP (MariaDB defaults root to unix_socket auth).
    # Same approach as the repo's CI workflow.
    systemctl start mariadb
    mariadb -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('root'); FLUSH PRIVILEGES;"

    # Install composer (skipped if already present).
    # Build in /tmp, not the /vagrant shared folder, which ignores file modes.
    if [ ! -x /usr/local/bin/composer ]; then
        cd /tmp
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php -r "if (hash_file('SHA384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); } echo PHP_EOL;"
        php composer-setup.php
        php -r "unlink('composer-setup.php');"
        mv composer.phar /usr/local/bin/composer
        chmod 755 /usr/local/bin/composer
        cd /
    fi

    # Check out and set up VuFind.
    mkdir -p /vufindlocal/cache/cli /vufindlocal/config/vufind
    chown -R vagrant:vagrant /vufindlocal
    su - vagrant -c 'cd /vagrant && composer install && php install.php --non-interactive --overridedir=/vufindlocal'
    # install.php writes /vufindlocal/import/import.properties. Make sure the Solr
    # URL points at this VM (never a Docker hostname, which the local/ override used).
    if [ -f /vufindlocal/import/import.properties ]; then
        sed -i 's|^solr.hosturl[[:space:]]*=.*|solr.hosturl = http://localhost:8983/solr/biblio/update|' /vufindlocal/import/import.properties
    fi
    ln -sfn /vufindlocal/httpd-vufind.conf /etc/apache2/conf-enabled/vufind.conf
    a2enmod rewrite

    # Restart Apache (and MariaDB to be sure it's running).
    if [[ -d /run/systemd/system ]]; then
      systemctl restart apache2
      systemctl restart mariadb
    else
      service apache2 restart
      service mariadb restart
    fi

    # Set up cache and config permissions.
    chown -R www-data:www-data /vufindlocal/cache /vufindlocal/config/vufind
    chmod 777 /vufindlocal/cache/cli

    # Set up profile for command line.
    echo export VUFIND_HOME=/vagrant > /etc/profile.d/vufind.sh
    echo export VUFIND_LOCAL_DIR=/vufindlocal >> /etc/profile.d/vufind.sh

    # Solr schema: the id field needs docValues for sorting on Solr 9.8
    # (see README-SOLR-DOCVALUES.md). Only matches the stock line, so re-runs are no-ops.
    sed -i 's|<field name="id" type="string" indexed="true" stored="true"/>|<field name="id" type="string" indexed="true" stored="true" docValues="true"/>|' /vagrant/solr/vufind/biblio/conf/schema.xml

    # --- VUFIND SOLR SERVICE START ---
    # Stop any manually-started Solr (tolerated if not running).
    su - vagrant -c 'VUFIND_HOME=/vagrant SOLR_PID_DIR=/vufindlocal /vagrant/solr.sh stop' >/dev/null 2>&1 || true

    # Install a CRLF-safe copy of solr.sh (the synced folder may carry CRLF endings).
    tr -d '\r' < /vagrant/solr.sh > /usr/local/bin/vufind-solr.sh
    chmod 755 /usr/local/bin/vufind-solr.sh

    # Solr as a systemd service: runs in the foreground as the vagrant user and
    # binds all interfaces so the host port-forward 4568 -> guest 8983 works.
    cat > /etc/systemd/system/vufind-solr.service <<'UNIT'
[Unit]
Description=VuFind Solr (Jetty)
RequiresMountsFor=/vagrant
After=network.target mariadb.service

[Service]
Type=simple
User=vagrant
Group=vagrant
Environment=VUFIND_HOME=/vagrant
Environment=SOLR_HOME=/vagrant/solr/vufind
Environment=SOLR_PID_DIR=/vufindlocal
Environment=SOLR_PORT=8983
Environment=SOLR_JETTY_HOST=0.0.0.0
Environment=SOLR_ADDITIONAL_START_OPTIONS=-f
Environment=SOLR_ULIMIT_CHECKS=false
LimitNOFILE=65000
TasksMax=65000
ExecStart=/usr/local/bin/vufind-solr.sh start
Restart=on-failure
RestartSec=5
TimeoutStartSec=180

[Install]
WantedBy=multi-user.target
UNIT

    systemctl daemon-reload
    systemctl enable --now vufind-solr.service

    # Create the VuFind MySQL database/user if missing (vufind/vufind).
    if ! mysql -uroot -proot -N -e "SHOW DATABASES LIKE 'vufind'" 2>/dev/null | grep -q vufind; then
        su - vagrant -c 'cd /vagrant && php public/index.php install/database --dbHost=localhost:3306 --vufindHost=localhost --rootUser=root --rootPass=root vufind vufind vufind'
    fi
    # --- VUFIND SOLR SERVICE END ---

    # --- VUFIND SYMPHONY ILS + SAMPLE DATA START ---
    # ILS: use the Symphony driver against the public dev SymWS server.
    # Connection details live in local/config/vufind/Symphony.ini
    # (dev-symws.freelibrary.org:8080, clientID VuFind, WEBAPI credentials).
    if [ -f /vagrant/local/config/vufind/Symphony.ini ]; then
        cp /vagrant/local/config/vufind/Symphony.ini /vufindlocal/config/vufind/Symphony.ini
        chown www-data:www-data /vufindlocal/config/vufind/Symphony.ini 2>/dev/null || true
    fi
    # Guest config.ini. install.php (VuFind 11) does not generate config.ini,
    # so start from the stock file and apply the guest-specific settings,
    # scoped to their own sections.
    cp /vagrant/config/vufind/config.ini /vufindlocal/config/vufind/config.ini
    sed -i \
        -e '/^\[Site\]/,/^\[/ s|^url[[:space:]]*=.*|url = "http://localhost:4567/vufind"|' \
        -e '/^\[Catalog\]/,/^\[/ s|^driver[[:space:]]*=.*|driver = "Symphony"|' \
        -e '/^\[Database\]/,/^\[/ s|^database[[:space:]]*=.*|database = "mysql://vufind:vufind@localhost/vufind"|' \
        /vufindlocal/config/vufind/config.ini
    chown www-data:www-data /vufindlocal/config/vufind/config.ini 2>/dev/null || true

    # Import Symphony-safe sample data (numeric catkeys that resolve on the dev
    # SymWS server, giving real live item statuses) when the biblio core is empty.
    if [ -f /vagrant/symphony-sample.mrc ]; then
        for i in $(seq 1 60); do
            curl -s -m 5 "http://localhost:8983/solr/biblio/admin/ping" | grep -q "OK" && break
            sleep 2
        done
        N=$(curl -s "http://localhost:8983/solr/biblio/select?q=*:*&rows=0" | grep -o "\"numFound\":[0-9]*" | grep -o "[0-9]*")
        if [ -z "$N" ] || [ "$N" = "0" ]; then
            tr -d '\r' < /vagrant/import-marc.sh > /tmp/import-marc.sh
            chmod 644 /tmp/import-marc.sh
            su - vagrant -c 'cd /vagrant && bash /tmp/import-marc.sh /vagrant/symphony-sample.mrc' || true
        fi
    fi

    # Drop cached configs so ILS changes take effect on first request.
    rm -rf /vufindlocal/cache/configs

    # Installer leftovers: install.php backs up the files it regenerates on every run.
    # Remove them here; drop this function if you want to keep the backups.
    cleanup_installer_backups() {
        rm -f /vagrant/env.sh.bak.* /vagrant/env.bat.bak.*
        rm -f /vufindlocal/httpd-vufind.conf.bak.* /vufindlocal/import/*.bak.*
    }
    cleanup_installer_backups
    # --- VUFIND SYMPHONY ILS + SAMPLE DATA END ---
    systemctl restart apache2

    echo "VuFind setup complete. Browse to http://localhost:4567/vufind/install -- there's one button to click there to 'fix security' and complete the setup."
  SHELL



end
