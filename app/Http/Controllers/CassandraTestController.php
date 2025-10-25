<?php

namespace App\Http\Controllers;

use App\Services\CassandraApiService;

class CassandraTestController extends Controller
{
    public function index(CassandraApiService $api)
    {
        $rows = $api->getProducts();
        return response()->json($rows);
    }
}
