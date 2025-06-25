<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController; // Ini yang penting

class Controller extends BaseController // Memastikan extend BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    // Metode middleware() disediakan oleh Illuminate\Routing\Controller
    // Jadi, jika App\Http\Controllers\Controller Anda extend BaseController, seharusnya tidak ada masalah.
}
