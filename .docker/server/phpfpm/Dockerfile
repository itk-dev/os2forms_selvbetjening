FROM itkdev/php8.1-fpm:alpine

USER root

# https://github.com/axllent/mailpit#download-static-binary-windows-linux-and-mac
# https://github.com/axllent/mailpit/releases/tag/v1.6.17
RUN <<EOF
curl --location https://github.com/axllent/mailpit/releases/download/v1.6.17/mailpit-linux-amd64.tar.gz > mailpit-linux-amd64.tar.gz
tar xvf mailpit-linux-amd64.tar.gz --directory /usr/local/bin mailpit
rm mailpit-linux-amd64.tar.gz
EOF

USER deploy
