yum install gcc php-devel php-pear libssh2 libssh2-devel make
pecl install -f ssh2
echo extension=ssh2.so > /etc/php.d/ssh2.ini
service httpd restart
php -m | grep ssh2