<?php

namespace App\Http\Controllers;

use App\Helpers\Document;
use App\Helpers\Responser;

abstract class Controller
{
    use Document, Responser;
}
