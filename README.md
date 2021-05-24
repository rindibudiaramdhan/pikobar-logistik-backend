[![Maintainability](https://api.codeclimate.com/v1/badges/4cc69dc0dac733eb9d36/maintainability)](https://codeclimate.com/github/rindibudiaramdhan/pikobar-logistik-backend/maintainability)

[![Test Coverage](https://api.codeclimate.com/v1/badges/4cc69dc0dac733eb9d36/test_coverage)](https://codeclimate.com/github/rindibudiaramdhan/pikobar-logistik-backend/test_coverage)

## PIKOBAR LOGISTIK API
Build with laravel 5.7 + MySql

## Installation

```sh
$ git clone git@github.com:jabardigitalservice/pikobar-logistik-api.git
$ cd pikobar-logistik-api
$ cp .env.example .env
$ docker-compose up -d
$ docker exec -it api_logistik bash
$ php composer.phar install
$ php artisan key:generate
$ php artisan jwt:secret
$ php artisan migrate
```


## Coding Style
- Naming conventions :
https://github.com/alexeymezenin/laravel-best-practices#follow-laravel-naming-conventions
- PSR standards :
https://www.php-fig.org/psr/psr-2/

## License
Pikobar Logistik API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
