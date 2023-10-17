<?php

namespace App\Http\Controllers;

use view;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use DB;
use App\Http\Requests;

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 500);

use ZipArchive;
use Log;

header('Content-Type: text/html; charset=utf-8');
class POListController extends Controller
{
  public function Get_PO_From_SyncCloud(Request $request)
  {
    $db = config('database.db');
    $url = "http://scm.brac.net/sc/GetProgotiActivePO";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $documentoutput = curl_exec($ch);
    curl_close($ch);

    $polistdecode = json_decode($documentoutput);
    if (!empty($polistdecode)) {
      foreach ($polistdecode as $row) {
        $insert = DB::Table($db . '.polist')->insert(['cono' => $row->cono, 'coname' => $row->coname, 'sessionno' => $row->sessionno, 'opendate' => $row->opendate, 'openingbal' => $row->openingbal, 'password' => $row->password, 'emethod' => $row->emethod, 'cashinhand' => $row->cashinhand, 'enteredby' => $row->enteredby, 'deviceid' => $row->deviceid, 'status' => $row->status, 'branchcode' => $row->branchcode, 'branchname' => $row->branchname, 'projectcode' => $row->projectcode, 'desig' => $row->desig, 'lastposynctime' => $row->lastposynctime, 'sl_no' => $row->sl_no, 'clearstatus' => $row->clearstatus, 'abm' => $row->abm, 'mobileno' => $row->mobileno, 'sls' => $row->sls, 'checklogin' => $row->checklogin, 'imei' => $row->imei, 'qsoftid' => $row->qsoftid, 'trxsl' => $row->trxsl, 'admindeviceid' => $row->admindeviceid, 'upgdeviceid' => $row->upgdeviceid]);
      }
    }
  }
  public function POLIST_ActiveInactive(Request $request)
  {
    $db = config('database.db');
    $jsondata = $request->get("json");
    $activepo = json_decode($jsondata);
    foreach ($activepo as $row) {
      $insert = DB::Table($db . '.polist')->insert(['cono' => $row->cono, 'coname' => $row->coname, 'sessionno' => $row->sessionno, 'opendate' => $row->opendate, 'openingbal' => $row->openingbal, 'password' => $row->password, 'emethod' => $row->emethod, 'cashinhand' => $row->cashinhand, 'enteredby' => $row->enteredby, 'deviceid' => $row->deviceid, 'status' => $row->status, 'branchcode' => $row->branchcode, 'branchname' => $row->branchname, 'projectcode' => $row->projectcode, 'desig' => $row->desig, 'lastposynctime' => $row->lastposynctime, 'sl_no' => $row->sl_no, 'clearstatus' => $row->clearstatus, 'abm' => $row->abm, 'mobileno' => $row->mobileno, 'sls' => $row->sls, 'checklogin' => $row->checklogin, 'imei' => $row->imei, 'qsoftid' => $row->qsoftid, 'trxsl' => $row->trxsl, 'admindeviceid' => $row->admindeviceid, 'upgdeviceid' => $row->upgdeviceid]);
    }
  }
}
