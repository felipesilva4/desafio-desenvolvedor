<?php

return [
    'uri' => env('MONGO_URI', 'mongodb://mongo:27017'),
    'database' => env('MONGO_DATABASE', 'file_imports'),
    'collection' => env('MONGO_COLLECTION', 'cadastro_instrumentos'),
];
