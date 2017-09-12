# Text Local API
Repository for the Textlocal PHP Class demo as provided via: http://api.txtlocal.com/docs/phpclass

composer require scottybo/textlocal


This package comes with a Facade, providing an easy way to call the class
// config/app.php
'aliases' => [
    ...
    'TextLocal' => Illuminate\Support\Facades\TextLocal::class,
];

The config file must be published with this command:

php artisan vendor:publish --provider="App\TextLocalApi\TextLocalServiceProvider" --tag="config"

It will be published in config/textlocal.php