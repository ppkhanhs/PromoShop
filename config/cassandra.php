<?php

return [
    'host' => env('CASSANDRA_HOST', '127.0.0.1'),
    'port' => env('CASSANDRA_PORT', 9042),
    'keyspace' => env('CASSANDRA_KEYSPACE', 'ql_khuyenmai'),
];
