# [Choice] PHP version: 8-apache-bullseye, 8.0-apache-bullseye, 7-apache-bullseye, 7.4-apache-bullseye, 7.3-apache-bullseye, 8-apache-buster, 8.0-apache-buster, 7-apache-buster, 7.4-apache-buster, 7.3-apache-buster
FROM mcr.microsoft.com/vscode/devcontainers/php:0-7.4-apache-bullseye AS web

# Copy library scripts to execute
COPY library-scripts/*.sh library-scripts/*.env /tmp/library-scripts/

ENV NODE_VERSION = lts/*

# [Option] Install zsh
ARG INSTALL_ZSH="true"
# [Option] Upgrade OS packages to their latest versions
ARG UPGRADE_PACKAGES="true"
# Install needed packages and setup non-root user. Use a separate RUN statement to add your own dependencies.
ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=$USER_UID
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive && bash /tmp/library-scripts/common-debian.sh "${INSTALL_ZSH}" "${USERNAME}" "${USER_UID}" "${USER_GID}" "${UPGRADE_PACKAGES}" "true" "true"  && apt-get -y install --no-install-recommends lynx && usermod -aG www-data ${USERNAME} && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install xdebug
#RUN yes | pecl install xdebug \
#&& echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
#&& echo "xdebug.mode = debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
#&& echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
#&& echo "xdebug.client_port = 9000" >> /usr/local/etc/php/conf.d/xdebug.ini \
#&& rm -rf /tmp/pear

# Install composer2
#RUN curl -sSL https://getcomposer.org/installer | php \
#&& chmod +x composer.phar \
#&& mv composer.phar /usr/local/bin/composer

COPY --from=composer:1 /usr/bin/composer /usr/local/bin/composer

# [Choice] Node.js version: none, lts/*, 16, 14, 12, 10
ARG NODE_VERSION="none"
ENV NVM_DIR=/usr/local/share/nvm
ENV NVM_SYMLINK_CURRENT=true PATH=${NVM_DIR}/current/bin:${PATH}
RUN bash /tmp/library-scripts/node-debian.sh "${NVM_DIR}" "${NODE_VERSION}" "${USERNAME}" && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Remove library scripts for final image
RUN rm -rf /tmp/library-scripts

# [Optional] Uncomment this section to install additional packages.
# [Choice] Node.js version: none, lts/*, 16, 14, 12, 10
ARG NODE_VERSION="none"
RUN if [ "${NODE_VERSION}" != "none" ]; then su vscode -c "umask 0002 && . /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION} 2>&1"; fi
RUN  echo 'deb [trusted=yes] https://repo.symfony.com/apt/ /' | sudo tee /etc/apt/sources.list.d/symfony-cli.list
RUN apt-get update && apt-get install -y symfony-cli libfreetype6-dev libjpeg62-turbo-dev libpng-dev git libz-dev libzip-dev zip libmemcached-dev libmemcached11 libmemcachedutil2 build-essential memcached python && docker-php-ext-install zip && docker-php-ext-install -j$(nproc) iconv && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ && docker-php-ext-install -j$(nproc) gd && docker-php-ext-install -j$(nproc) mysqli  && docker-php-ext-install -j$(nproc) pdo_mysql && docker-php-ext-install -j$(nproc) opcache && pecl install memcached && echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini


# Install Postgre PDO
RUN apt-get install -y libpq-dev \
  && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
  && docker-php-ext-install pdo pdo_pgsql pgsql

RUN { \
  echo 'opcache.memory_consumption=128'; \
  echo 'opcache.interned_strings_buffer=8'; \
  echo 'opcache.max_accelerated_files=4000'; \
  echo 'opcache.revalidate_freq=2'; \
  echo 'opcache.fast_shutdown=1'; \
  } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# [Optional] Uncomment this section to install additional OS packages.
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get -y install --no-install-recommends <your-package-list-here>

# [Optional] Uncomment this line to install global node packages.
# RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

# Install Pulumi
# RUN if [ "$PULUMI_VERSION" = "latest" ]; then \
#   curl -fsSL https://get.pulumi.com/ | bash; \
#   else \
#   curl -fsSL https://get.pulumi.com/ | bash -s -- --version $(echo $PULUMI_VERSION | cut -c 2-); \
#   fi && \
#   mv ~/.pulumi/bin/* /usr/bin

COPY sites-available/*.conf /etc/apache2/sites-available/
RUN a2enmod headers && a2enmod rewrite
#RUN sudo chmod a+x /workspaces/back_office/tour-operator && sudo rm -rf /var/www/html && sudo ln -s /workspaces/back_office/tour-operator /var/www/html && apache2ctl start
#RUN composer install
#https://github.com/microsoft/vscode-dev-containers/tree/v0.195.0/containers/php
