php app/console doctrine:database:drop -e dev --force
php app/console doctrine:database:create -e dev
php app/console doctrine:migrations:migrate -e dev --no-interaction
php app/console doctrine:fixtures:load -e dev --append