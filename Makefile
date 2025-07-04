# Makefile for Symfony project
# This Makefile is used to automate common tasks in a Symfony project.

.PHONY: install

deploy:
	ssh o2switch 'cd repositories/setOn && git pull origin main && make install'

# Install dependencies and prepare the project for production
install: vendor/autoload.php
	php bin/console doctrine:migrations:migrate -n
	php bin/console importmap:install
	php bin/console asset-map:compile
	composer dump-env prod
	php bin/console cache:clear --no-warmup

# Ensure the vendor directory and autoload file are created
vendor/autoload.php: composer.lock composer.json
	composer install --no-dev --optimize-autoloader
	touch vendor/autoload.php