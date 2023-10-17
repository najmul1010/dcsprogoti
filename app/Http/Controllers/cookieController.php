<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use view;
use DateTime;
use Illuminate\Support\Facades\Input;
use DB;

date_default_timezone_set('Asia/Dhaka');

ini_set('memory_limit', '3072M');
ini_set('max_execution_time', 1800);

use ZipArchive;
use Log;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use File;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\LiveApiProgotiController;
use Cookie;

header('Content-Type: application/json; charset=utf-8');

class cookieController extends Controller
{
  public function setcookie(Request $req)
  {
    if (Session::has('data')) {
      echo Session::get('data');
    }
    $name = "Test152";
    Session::put('data', $name);
    $value = Session::get('data');
    echo $value;
    Session::forget('data');
  }
}
