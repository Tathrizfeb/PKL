FROM shinsenter/phpfpm-nginx:latest

WORKDIR /var/www/html

COPY . /var/www/html

# Optional: beri permission ke folder exports
RUN mkdir -p /var/www/html/exports && chmod -R 777 /var/www/html/exports

# Expose port nginx
EXPOSE 80