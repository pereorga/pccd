#!/bin/sh

set -eu

# Remove default DocumentRoot directive.
sed -i '/^DocumentRoot/d' /etc/apache2/httpd.conf

# Enable required modules.
sed -i 's/#LoadModule\ deflate_module/LoadModule\ deflate_module/' /etc/apache2/httpd.conf
sed -i 's/#LoadModule\ headers_module/LoadModule\ headers_module/' /etc/apache2/httpd.conf
sed -i 's/#LoadModule\ rewrite_module/LoadModule\ rewrite_module/' /etc/apache2/httpd.conf

# Disable unnecessary modules.
sed -i 's/LoadModule access_compat_module/#LoadModule access_compat_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule alias_module/#LoadModule alias_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule auth_basic_module/#LoadModule auth_basic_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule authn_core_module/#LoadModule authn_core_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule authn_file_module/#LoadModule authn_file_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule authz_groupfile_module/#LoadModule authz_groupfile_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule authz_host_module/#LoadModule authz_host_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule authz_user_module/#LoadModule authz_user_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule autoindex_module/#LoadModule autoindex_module/' /etc/apache2/httpd.conf
rm -f /etc/apache2/conf.d/languages.conf
sed -i 's/LoadModule negotiation_module/#LoadModule negotiation_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule reqtimeout_module/#LoadModule reqtimeout_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule setenvif_module/#LoadModule setenvif_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule status_module/#LoadModule status_module/' /etc/apache2/httpd.conf
sed -i 's/LoadModule version_module/#LoadModule version_module/' /etc/apache2/httpd.conf

# Add additional types.
echo 'image/avif avif' >> /etc/apache2/mime.types

echo 'Running Apache...'
httpd -D FOREGROUND
