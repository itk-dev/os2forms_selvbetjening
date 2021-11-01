# selvbetjening.aarhuskommune.dk

## Getting started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

* [Docker](https://www.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

### Installation

1. Clone the git repository
   ```sh
   git clone git@github.com:itk-dev/os2forms_selvbetjening selvbetjening
   ```

2. Enter the newly created project directory
   ```sh
   cd selvbetjening
   ```

3. Pull docker images and start docker containers
   ```sh
   docker-compose pull
   docker-compose up --detach

4. Install composer packages
   ```sh
   docker-compose exec phpfpm composer install
   ```

5. Install profile
   ```sh
   docker-compose exec phpfpm vendor/bin/drush site:install \
   os2forms_forloeb_profile --existing-config
   ```

6. Download and install external libraries
   ```sh
   docker-compose exec phpfpm vendor/bin/drush webform:libraries:download
   ```

You should now be able to browse to the application

```shell
open http://$(docker-compose port nginx 80)
```

### Configuration

Some modules included in this project needs additional configuration.
Take a look at the following modules on how to configure them:

* [OS2Forms CPR Lookup](https://github.com/itk-dev/os2forms_cpr_lookup)
* [OS2Forms CVR Lookup](https://github.com/itk-dev/os2forms_cvr_lookup)
* [OS2Forms Digital Post](https://github.com/itk-dev/os2forms_digital_post)
* [OS2Forms NemLogin OpenID Connect](https://github.com/itk-dev/os2forms_nemlogin_openid_connect)
