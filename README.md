[![CI Status](https://github.com/vufind-org/vufind/actions/workflows/ci.yaml/badge.svg?branch=dev)](https://github.com/vufind-org/vufind/actions/workflows/ci.yaml)
VuFind®
=======

Introduction
------------
VuFind® is an open source discovery environment for searching a collection of
records.  To learn more, visit https://vufind.org.


Installation
------------
See our [online installation documentation](https://vufind.org/wiki/installation) for step-by-step instructions for installing from packaged releases to popular platforms.

VuFind®'s [packaged releases](https://vufind.org/vufind/downloads.html) have all dependencies included. If you are installing directly from a Git checkout, you will need to load these dependencies manually using the [Composer](https://getcomposer.org) tool by running `composer install` from the VuFind home directory.


Documentation and Support
-------------------------
The VuFind® community maintains a detailed [wiki](https://vufind.org/wiki) containing information on using and customizing the software. The VuFind® website also lists [sources of community and commercial support](https://vufind.org/vufind/support.html).


Contributing
------------
See our [developers handbook](https://vufind.org/wiki/development) for more information.

## Running VuFind Locally using Vagrant

1. Start a terminal with administrator privileges (right click + run as administrator)
2. Run `$ vagrant up` and wait for VM to build and provision
3. Once the VM is built and provisioned, run `$ vagrant ssh`
4. Navigate to the VuFind install directory: `$ cd /vagrant`
5. Run the following commands:
   1. `$ dos2unix solr.sh`
   2. `$ dos2unix import-marc.sh`
   3. `$ dos2unix import-marc-auth.sh`
- Start the SOLR instance: `$ ./solr.sh start`
- Navigate to http://127.0.0.1:4567/vufind/

### Update Windows Host File

- Using a text editor with administrative rights, open `C:\Windows\System32\drivers\etc\hosts`
- Add the following line: `172.30.41.66  local.catalog.freelibrary.org`
  - If there is already a line for `local.catalog.freelibrary.org`, remove it
- Save file
- Navigate to http://local.catalog.freelibrary.org:4567/vufind/

### Startup Errors

#### Could not rename '/vagrant/downloads/solr-9.8.1.tgz.partial' to '/vagrant/downloads/solr-9.8.1.tgz'

- `$ vagrant ssh`
- `$ mv /vagrant/downloads/solr-9.8.1.tgz.partial /vagrant/downloads/solr-9.8.1.tgz`
- `$ exit`
- `$ vagrant reload --provision`

## Moving files to Ubuntu VM with WinSCP

- Download [WinSCP](https://winscp.net/eng/index.php) or a similar application (putty, etc)
- Click on **New Site** and enter these settings:
  - **File Protocol**: SFTP
  - **Hostname**: 172.30.41.66
  - **Port**: 22
  - **Username**: vagrant
  - **Password**: vagrant
- Click Save
- Set **Site name:** to something that makes sense (ie "Local Catalog VM")
- Click on the new entry in WinSCP and then click **Login**
