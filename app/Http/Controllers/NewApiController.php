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
//use App\Http\Controllers\TestingController_Version;
header('Content-Type: application/json; charset=utf-8');

class NewApiController extends Controller
{
  private $dberp = 'erptestingserver'; //erp test db
  private $db = 'dcs';        //dcs db name
  public function DcsSync(Request $request)
  {
    $apikey = '7f30f4491cb4435984616d1913e88389';
    $db = $this->db;
    $token = Request::header('apiKey');
    $BranchCode = Request::input('branchCode');
    $ProjectCode = Request::input('projectCode');
    $LastSyncTime = Request::input('lastSyncTime');
    $CurrentTime = Request::input('currentTime');
    $Appid = Request::header('appId');
    $Pin = Request::input('pin');
    $AppversionName = Request::header('appVersionName');
    $AppVersionCode = Request::header('appVersionCode');
    $req = Request::input('req');
    try {
      if (!empty($req)) {
        //json validation	
        if (!empty($req)) {
          $checkvalidation = $this->json_validator($req);
          if ($checkvalidation == false) {
            $jsonvalidation = array("status" => "CUSTMSG", "message" => "Json formate Invalid Please try again!!");
            $json3 = json_encode($jsonvalidation);
            echo $json3;
            die;
          } else {
            // Store 
            $DcsStore = json_decode($req);
            $Storestatus = $DcsStore->status;
            $data = $DcsStore->data;
            $AdmissionStore = $data[0]->data;
            $LoanStore = $data[1]->data;
            $SurveyStore = $data[2]->data;
            if (!empty($AdmissionStore)) {
              $this->AdmissionDataStore($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
            }
            if (!empty($LoanStore)) {
              $this->LoanDataStore($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
            }
            if (!empty($SurveyStore)) {
              $this->SurveyDataStore($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
            }
            // END Store
          }
        }
        // end json validation
      }
      if ($Appid == 'bmfpo') {
        $admissiondata  =  $this->AdmissionDataSync1($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
        $loandata = $this->LoanDataSync1($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
        $surveydata = $this->getSurveys($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
      } else if ($Appid == 'bmsmerp') {
        $admissiondata = $this->AdmissionDataSync1($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
      }
    } catch (Exception $e) {
    }
  }
  public function dcsInstallmentCalculator()
  {
    $json2 = json_encode(Request::all());
    // dd($json);
    Log::info("InstallMent Calculator-" . $json2);

    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    /* $access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/loan/installment-calculator';

    $headers = array(
      'Authorization: Bearer ' . $access_token,
      'Content-Type: application/json'
    );*/
    $serverurl = $this->ServerURL($db);
    $urlindex = $serverurl[0];
    $urlindex1 = $serverurl[1];
    if ($urlindex != '' or $urlindex1 != '') {
      $url = $urlindex;
      $url2 = $urlindex1;
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Api Url Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    $servertoken = $this->TokenCheck();
    if ($servertoken != '') {
      $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $servertoken
      );
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Token Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url2 . 'loan/installment-calculator',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $json2,
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    Log::info("InstallMent Calculator server Message-" . $response);
    // dd($response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      return $response;
    }
  }

  public function dcsInsurancePremiumCalculation()
  {
    $json = json_encode(Request::all());
    // dd(Request::toJson());

    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    /*$access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/loan/insurance-premium-calculator';

    $headers = array(
      'Authorization: Bearer ' . $access_token,
      'Content-Type: application/json'
    );*/
    $serverurl = $this->ServerURL($db);
    $urlindex = $serverurl[0];
    $urlindex1 = $serverurl[1];
    if ($urlindex != '' or $urlindex1 != '') {
      $url = $urlindex;
      $url2 = $urlindex1;
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Api Url Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    $servertoken = $this->TokenCheck();
    if ($servertoken != '') {
      $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $servertoken
      );
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Token Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url2 . 'loan/insurance-premium-calculator',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $json,
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    Log::info("Premium Calculation" . $response);
    // dd($response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      return $response;
    }
  }
  public function LastOneCloseLoanBehavior(Request $request)
  {
    $db = $this->db;
    $token = Request::input('token');
    $BranchCode = Request::get('BranchCode');
    $MemberId = Request::get('MemberId');
    $OrgNo = Request::get('OrgNo');
    $OrgMemNo = Request::get('OrgMemNo');
    $key = Request::get('key');
    // dd($MemberId, $OrgMemNo, $OrgNo);
    $serverurl = $this->ServerURL($db);
    $urlindex = $serverurl[0];
    $urlindex1 = $serverurl[1];
    if ($urlindex != '' or $urlindex1 != '') {
      $url = $urlindex;
      $url2 = $urlindex1;
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Api Url Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    $servertoken = $this->TokenCheck();
    if ($servertoken != '') {
      $headerss = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $servertoken
      );
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Token Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($OrgNo == null and $OrgMemNo == null and $MemberId != null) {
        $url = $url . "LastOneCloseLoanBehavior?BranchCode=$BranchCode&MemberId=$MemberId&key=$key";
      } elseif ($OrgNo != null and $OrgMemNo != null and $MemberId == null) {
        $url = $url . "LastOneCloseLoanBehavior?BranchCode=$BranchCode&OrgNo=$OrgNo&OrgMemNo=$OrgMemNo&key=$key";
      } else {
        $result = array("status" => "E", "message" => "Please choose MemberId or Orgmemno and OrgNo!");
        return json_encode($result);
      }
      // dd($url);
      $url = str_replace(" ", '%20', $url);
      $headers = array(
        'Accept: application/json',
      );

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, $headerss);
      $output_colsed = curl_exec($ch);
      curl_close($ch);

      return $output_colsed;
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }
  public function erpMemberList(Request $req)
  {
    $db = $this->db;
    $BranchCode = Request::get('BranchCode');
    $PIN = Request::get('PIN');
    $ProjectCode = Request::get('ProjectCode');
    $CONo = Request::get('CONo');
    $UpdatedAt = Request::get('UpdatedAt');
    $key = Request::get('key');
    $Status = Request::get('Status');
    $OrgNo = Request::get('OrgNo');
    $OrgMemNo = Request::get('OrgMemNo');
    $token = Request::input('token');

    $serverurl = $this->ServerURL($db);
    $urlindex = $serverurl[0];
    $urlindex1 = $serverurl[1];
    if ($urlindex != '' or $urlindex1 != '') {
      $url = $urlindex;
      $url2 = $urlindex1;
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Api Url Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }

    $servertoken = $this->TokenCheck();
    if ($servertoken != '') {
      $headerss = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $servertoken
      );
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => "Token Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      $url4 = $url . "MemberList?BranchCode=$BranchCode&CONo=$CONo&ProjectCode=$ProjectCode&UpdatedAt=$UpdatedAt&key=$key&Status=$Status&OrgNo=$OrgNo&OrgMemNo=$OrgMemNo";
      //dd($url4);
      $url4 = str_replace(" ", '%20', $url4);
      $headers = array(
        'Accept: application/json',
      );

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url4);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $output_colsed = curl_exec($ch);
      curl_close($ch);
      //dd($output_colsed)
      return $output_colsed;
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }
  public function AdmissionDataStore($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
  }
  public function LoanDataStore($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
  }
  public function SurveyDataStore($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
  }
  public function json_validator($data = NULL)
  {

    if (!empty($data)) {

      @json_decode($data);
      return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
  }
  public function AdmissionDataSync1($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {

    $admissiondata = DB::table($db . '.admissions')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->Where('assignedpo', $Pin)->orderBy('id', 'desc')->get();
    if (!empty($admissiondata)) {
      foreach ($admissiondata as $data) {
        $MainIdTypeId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->MainIdTypeId)->first();
        $NomineeNidType = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->NomineeNidType)->first();
        $OtherIdTypeId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->OtherIdTypeId)->first();
        $SpouseCardType = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->SpouseCardType)->first();
        $EducationId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'educationId')->where('data_id', $data->EducationId)->first();
        $MaritalStatusId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'maritalStatusId')->where('data_id', $data->MaritalStatusId)->first();
        $SpuseOccupationId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'occupationId')->where('data_id', $data->SpuseOccupationId)->first();
        $RelationshipId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'relationshipId')->where('data_id', $data->RelationshipId)->first();
        $Occupation = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'occupationId')->where('data_id', $data->Occupation)->first();
        $genderId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'genderId')->where('data_id', $data->GenderId)->first();
        $PrimaryEarner = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'primaryEarner')->where('data_id', $data->PrimaryEarner)->first();
        $MemberCateogryId = DB::table($db . '.projectwise_member_category')->select('categoryname')->where('categoryid', $data->MemberCateogryId)->first();
        $WalletOwner = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'primaryEarner')->where('data_id', $data->WalletOwner)->first();
        $role_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $data->projectcode)->where('position', $data->roleid)->first();
        $recieverrole_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $data->projectcode)->where('position', $data->reciverrole)->first();
        $dochistory = DB::table($db . '.document_history')->select('comment')->where('id', $data->dochistory_id)->first();
        $status = DB::table($db . '.status')->select('status_name')->where('status_id', $data->status)->first();
        $presentUpazilaId = DB::table($db . '.office_mapping')->select('thana_name')->where('thana_id', $data->presentUpazilaId)->where('district_id', $data->PresentDistrictId)->first();
        $parmanentUpazilaId = DB::table($db . '.office_mapping')->select('thana_name')->where('thana_id', $data->parmanentUpazilaId)->where('district_id', $data->PermanentDistrictId)->first();
        $PresentDistrictId = DB::table($db . '.office_mapping')->select('district_name')->where('district_id', $data->PresentDistrictId)->first();
        $PermanentDistrictId = DB::table($db . '.office_mapping')->select('district_name')->where('district_id', $data->PermanentDistrictId)->first();

        $MainIdTypeId = $MainIdTypeId->data_name ?? null;
        $EducationId = $EducationId->data_name ?? null;
        $WalletOwner = $WalletOwner->data_name ?? null;
        $MaritalStatusId = $MaritalStatusId->date_name ?? null;
        $RelationshipId = $RelationshipId->data_name ?? null;
        $Occupation = $Occupation->data_name ?? null;
        $genderId = $genderId->data_name ?? null;
        $PrimaryEarner = $PrimaryEarner->data_name ?? null;
        $MemberCateogryId = $MemberCateogryId->categoryname ?? null;
        $role_name = $role_name->designation ?? null;
        $recieverrole_name = $recieverrole_name->designation ?? null;
        $status = $status->status_name ?? null;
        $NomineeNidType = $NomineeNidType->data_name ?? null;
        $SpuseOccupationId = $SpuseOccupationId->data_name ?? null;
        $SpouseCardType = $SpouseCardType->data_name ?? null;
        $OtherIdTypeId = $OtherIdTypeId->data_name ?? null;
        $presentUpazilaId = $presentUpazilaId->thana_name ?? null;
        $parmanentUpazilaId = $parmanentUpazilaId->thana_name ?? null;
        $PresentDistrictId = $PresentDistrictId->district_name ?? null;
        $PermanentDistrictId = $PermanentDistrictId->district_name ?? null;
        $comment = $dochistory->comment ?? null;
        if ($data->IsBkash == '1') {
          $IsBkash = "Yes";
        } else {
          $IsBkash = "No";
        }
        if ($data->PassbookRequired == '1') {
          $PassbookRequired = "Yes";
        } else {
          $PassbookRequired = "No";
        }
        if ($data->IsSameAddress == '1') {
          $IsSameAddress = "Yes";
        } else {
          $IsSameAddress = "No";
        }
        if ($data->status == '2') {
          if ($data->ErpStatus == '1') {
            $ErpStatus = 'Pending';
          } else if ($data->ErpStatus == '2') {
            $ErpStatus = 'Approved';
          } else if ($data->ErpStatus == '3') {
            $ErpStatus = 'Rejected';
          } else {
            $ErpStatus = 'Pending';
            $ErpStatusId = null;
            $ErpRejectionReason = null;
          }
        } else {
          $ErpStatus = null;
          $ErpStatusId = null;
          $ErpRejectionReason = null;
        }
        $arrayData = array(
          "id" => $data->id,
          "IsRefferal" => $data->IsRefferal,
          "RefferedById" => $data->RefferedById,
          "MemberId" => $data->MemberId,
          "MemberCateogryId" => $data->MemberCateogryId,
          "MemberCateogry" => $MemberCateogryId,
          "ApplicantsName" => $data->ApplicantsName,
          "ApplicantSinglePic" => $data->ApplicantSinglePic,
          "MainIdType" => $MainIdTypeId,
          "MainIdTypeId" => $data->MainIdTypeId,
          "IdNo" => $data->IdNo,
          "OtherIdType" => $OtherIdTypeId,
          "OtherIdTypeId" => $data->OtherIdTypeId,
          "OtherIdNo" => $data->OtherIdNo,
          "ExpiryDate" => $data->ExpiryDate,
          "IssuingCountry" => $data->IssuingCountry,
          "DOB" => $data->DOB,
          "MotherName" => $data->MotherName,
          "FatherName" => $data->FatherName,
          "Education" => $EducationId,
          "EducationId" => $data->EducationId,
          "Phone" => $data->Phone,
          "PresentAddress" => $data->PresentAddress,
          "presentUpazilaId" => $data->presentUpazilaId,
          "presentUpazila" => $presentUpazilaId,
          "PermanentAddress" => $data->PermanentAddress,
          "parmanentUpazilaId" => $data->parmanentUpazilaId,
          "PresentDistrictId" => $data->PresentDistrictId,
          "PresentDistrictName" => $PresentDistrictId,
          // "PresentDistrict" => $PresentDistrictId,
          "PermanentDistrictId" => $data->PermanentDistrictId,
          "PermanentDistrictName" => $PermanentDistrictId,
          // "PermanentDistrict" => $PermanentDistrict,
          "parmanentUpazila" => $parmanentUpazilaId,
          "MaritalStatusId" => $data->MaritalStatusId,
          "MaritalStatus" => $MaritalStatusId,
          "SpouseName" => $data->SpouseName,
          "SpouseCardType" => $SpouseCardType,
          "SpouseCardTypeId" => $data->SpouseCardType,
          "SpouseNidOrBid" => $data->SpouseNidOrBid,
          "SposeDOB" => $data->SposeDOB,
          "SpuseOccupationId" => $data->SpuseOccupationId,
          "SpuseOccupation" => $SpuseOccupationId,
          "SpouseNidFront" => $data->SpouseNidFront,
          "SpouseNidBack" => $data->SpouseNidBack,
          "ReffererName" => $data->ReffererName,
          "ReffererPhone" => $data->ReffererPhone,
          "FamilyMemberNo" => $data->FamilyMemberNo,
          "NoOfChildren" => $data->NoOfChildren,
          "NomineeDOB" => $data->NomineeDOB,
          "RelationshipId" => $data->RelationshipId,
          "Relationship" => $RelationshipId,
          "ApplicantCpmbinedImg" => $data->ApplicantCpmbinedImg,
          "ReffererImg" => $data->ReffererImg,
          "ReffererIdImg" => $data->ReffererIdImg,
          "FrontSideOfIdImg" => $data->FrontSideOfIdImg,
          "BackSideOfIdimg" => $data->BackSideOfIdimg,
          "NomineeIdImg" => $data->NomineeIdImg,
          "DynamicFieldValue" => $data->DynamicFieldValue,
          "created_at" => $data->created_at,
          "updated_at" => $data->updated_at,
          "branchcode" => $data->branchcode,
          "projectcode" => $data->projectcode,
          "Occupation" => $Occupation,
          "OccupationId" => $data->Occupation,
          "IsBkash" => $IsBkash,
          "WalletNo" => $data->WalletNo,
          "WalletOwnerId" => $data->WalletOwner,
          "WalletOwner" => $WalletOwner,
          "NomineeName" => $data->NomineeName,
          "PrimaryEarner" => $PrimaryEarner,
          "PrimaryEarnerId" => $data->PrimaryEarner,
          "dochistory_id" => $data->dochistory_id,
          "roleid" => $data->roleid,
          "pin" => $data->pin,
          "action" => $data->action,
          "reciverrole" => $data->reciverrole,
          "status" => $status,
          "statusId" => $data->status,
          "orgno" => $data->orgno,
          "assignedpo" => $data->assignedpo,
          "NomineeNidNo" => $data->NomineeNidNo,
          "NomineeNidTypeId" => $data->NomineeNidType,
          "NomineeNidType" => $NomineeNidType,
          "NomineePhoneNumber" => $data->NomineePhoneNumber,
          "NomineeNidFront" => $data->NomineeNidFront,
          "NomineeNidBack" => $data->NomineeNidBack,
          "PassbookRequired" => $PassbookRequired,
          "IsSameAddress" => $IsSameAddress,
          "entollmentid" => $data->entollmentid,
          "GenderId" => $data->GenderId,
          "Gender" => $genderId,
          "SavingsProductId" => $data->SavingsProductId,
          "role_name" => $role_name,
          "reciverrole_name" => $recieverrole_name,
          "SurveyId" => $data->surveyid,
          "Comment" => $comment,
          "ErpStatus" => $ErpStatus,
          "ErpStatusId" => $ErpStatusId,
          "ErpRejectionReason" => $ErpRejectionReason,
          "Flag" => $data->Flag
        );
        $admissiondataary[] = $arrayData;
      }
    } else {
      $admissiondataary = [];
    }
    return $admissiondataary;
  }
  public function LoanDataSync1($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
    $loandata = DB::table($db . '.loans')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->where('assignedpo', $Pin)->orderBy('id', 'desc')->get();
    if (!empty($loandata)) {
      foreach ($loandata as $data) {
        $grntorRlationClient = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'relationshipId')->where('data_id', $data->grntor_rlationClient)->first();
        $investSector = DB::table($db . '.schemem_sector_subsector')->select('sectorname')->where('sectorid', $data->invest_sector)->first();
        $subSectorId = DB::table($db . '.schemem_sector_subsector')->select('subsectorname')->where('subsectorid', $data->subSectorId)->first();
        $frequencyId = DB::table($db . '.product_details')->select('frequency')->where('frequencyid', $data->frequencyId)->first();
        $scheme = DB::table($db . '.schemem_sector_subsector')->select('schemename')->where('schemeid', $data->scheme)->first();
        $role_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $ProjectCode)->where('position', $data->roleid)->first();
        $recieverrole_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $ProjectCode)->where('position', $data->reciverrole)->first();
        $memberTypeId = DB::table($db . '.projectwise_member_category')->select('categoryname')->where('categoryid', $data->memberTypeId)->first();
        $loan_product_name = DB::table($db . '.product_project_member_category')->select('productname')->where('productid', $data->loan_product)->first();

        $grntorRlationClients = $grntorRlationClient->data_name ?? null;
        $investSectors = $investSector->sectorname ?? null;
        $subSectorIds = $subSectorId->subsectorname ?? null;
        $frequencyIds = $frequencyId->frequency ?? null;
        $schemes = $scheme->schemename ?? null;
        $role_names = $role_name->designation ?? null;
        $recieverrole_names = $recieverrole_name->designation ?? null;
        $memberTypeIds = $memberTypeId->categoryname ?? null;
        $loan_product_names = $loan_product_name->productname ?? null;
        if ($data->insurn_gender != null) {
          $InsurnGender = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'genderId')->where('data_id', $data->insurn_gender)->first();
          $insurnGender = $InsurnGender->data_name;
        } else {
          $insurnGender = null;
        }

        if ($data->insurn_gender != null) {
          $InsurnRelation = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'relationshipId')->where('data_id', $data->insurn_relation)->first();
          $insurnRelation = $InsurnRelation->data_name;
        } else {
          $insurnRelation = null;
        }
        if ($data->insurn_mainIDType != null) {
          $insurnMainID = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->insurn_mainIDType)->first();
          $insurnMainIDType = $insurnMainID->data_name;
        } else {
          $insurnMainIDType = null;
        }
        $status = DB::table($db . '.status')->select('status_name')->where('status_id', $data->status)->first();

        $serverurl = $this->ServerURL($db);
        $urlindex = $serverurl[0];
        $urlindex1 = $serverurl[1];
        if ($urlindex != '' or $urlindex1 != '') {
          $url = $urlindex;
          $url2 = $urlindex1;
        } else {
          $statuss = array("status" => "CUSTMSG", "message" => "Api Url Not Found");
          $json = json_encode($statuss);
          echo $json;
          die;
        }
        $key = '5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae';
        $UpdatedAt = "2000-01-01 00:00:00";
        $token = $this->TokenCheck();
        if ($token != '') {
          $headers = array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $token
          );
        } else {
          $statuss = array("status" => "CUSTMSG", "message" => "Token Not Found");
          $json = json_encode($statuss);
          echo $json;
          die;
        }
        Log::info("Token" . $token);
        $member = Http::get($url . 'MemberList', [
          'BranchCode' => $data->branchcode,
          'CONo' => $data->assignedpo,
          'ProjectCode' => $data->projectcode,
          'UpdatedAt' => $UpdatedAt,
          'Status' => 1,
          'OrgNo' => $data->orgno,
          'OrgMemNo' => $data->orgmemno,
          'key' => $key
        ]);
        // dd($member);
        $member = $member->object();
        if ($member != null) {
          if ($member->data != null) {
            $member = $member->data[0];
          } else {
            $member = null;
          }
        } else {
          $member = null;
        }

        if ($data->status == '2') {
          $checkPostedLoan = DB::table($db . '.posted_loan')->where('loan_id', $data->loan_id)->first();
          if ($checkPostedLoan != null) {
            $ErpStatusId = $checkPostedLoan->loanproposalstatusid;
            if ($ErpStatusId == 1) {
              $ErpStatus = 'Pending';
            } elseif ($ErpStatusId == 2) {
              $ErpStatus = 'Approved';
            } elseif ($ErpStatusId == 3) {
              $ErpStatus = 'Rejected';
            } elseif ($ErpStatusId == 4) {
              $ErpStatus = 'Disbursed';
            }
            $ErpRejectionReason = $checkPostedLoan->rejectionreason;
          } else {
            $ErpStatus = 'Pending';
            $ErpStatusId = null;
            $ErpRejectionReason = null;
          }
        } else {
          $ErpStatus = null;
          $ErpStatusId = null;
          $ErpRejectionReason = null;
        }
        $dochistory = DB::table($db . '.document_history')->select('comment')->where('id', $data->dochistory_id)->first();


        if ($data->witness_knows == "1") {
          $witnesKnows = "Yes";
        } else {
          $witnesKnows = "No";
        }
        if ($data->insurn_type == "1") {
          $insurnType = "Single";
        } else {
          $insurnType = "Double";
        }
        if ($data->insurn_option == "1") {
          $insurnOption = "Existing";
        } elseif ($data->insurn_option == "2") {
          $insurnOption = "New";
        } else {
          $insurnOption = null;
        }
        if ($data->houseowner_knows == "1") {
          $houseownerKnows = "Yes";
        } else {
          $houseownerKnows = "No";
        }

        $time = date('Y-m-d', strtotime($data->time));

        $arrayData['loan'] = array(
          "id" => $data->id,
          "orgno" => $data->orgno,
          "branchcode" => $data->branchcode,
          "projectcode" => $data->projectcode,
          "loan_product" => $data->loan_product,
          "loan_product_name" => $loan_product_names,
          "loan_duration" => $data->loan_duration,
          "invest_sector_id" => $data->invest_sector,
          "invest_sector" => $investSectors,
          "scheme_id" => $data->scheme,
          "scheme" => $schemes,
          "propos_amt" => $data->propos_amt,
          "instal_amt" => $data->instal_amt,
          "bracloan_family" => $data->bracloan_family,
          "vo_leader" => $data->vo_leader,
          "recommender" => $data->recommender,
          "grntor_name" => $data->grntor_name,
          "grntor_phone" => $data->grntor_phone,
          "grntor_rlationClient" => $grntorRlationClients,
          "grntor_rlationClientId" => $data->grntor_rlationClient,
          "grntor_nid" => $data->grntor_nid,
          "witness_knows" => $witnesKnows,
          "residence_type" => $data->residence_type,
          "residence_duration" => $data->residence_duration,
          "houseowner_knows" => $houseownerKnows,
          "reltive_presAddress" => $data->reltive_presAddress,
          "reltive_name" => $data->reltive_name,
          "reltive_phone" => $data->reltive_phone,
          "insurn_type" => $insurnType,
          "insurn_type_id" => $data->insurn_type,
          "insurn_option" => $insurnOption,
          "insurn_option_id" => $data->insurn_option,
          "insurn_spouseName" => $data->insurn_spouseName,
          "insurn_spouseNid" => $data->insurn_spouseNid,
          "insurn_spouseDob" => $data->insurn_spouseDob,
          "insurn_gender" => $insurnGender,
          "insurn_gender_id" => $data->insurn_gender,
          "insurn_relation" => $insurnRelation,
          "insurn_relation_id" => $data->insurn_relation,
          "insurn_name" => $data->insurn_name,
          "insurn_dob" => $data->insurn_dob,
          "insurn_mainID" => $data->insurn_mainID,
          "grantor_nidfront_photo" => $data->grantor_nidfront_photo,
          "grantor_nidback_photo" => $data->grantor_nidback_photo,
          "grantor_photo" => $data->grantor_photo,
          "DynamicFieldValue" => $data->DynamicFieldValue,
          "time" => $time,
          "dochistory_id" => $data->dochistory_id,
          "roleid" => $data->roleid,
          "pin" => $data->pin,
          "reciverrole" => $data->reciverrole,
          "status" => $status->status_name,
          "statusId" => $data->status,
          "action" => $data->action,
          "assignedpo" => $data->assignedpo,

          "bm_repay_loan" => $data->bm_repay_loan,
          "bm_conduct_activity" => $data->bm_conduct_activity,
          "bm_action_required" => $data->bm_action_required,
          "bm_rca_rating" => $data->bm_rca_rating,

          "bm_noofChild" => $data->bm_noofChild,
          "bm_earningMember" => $data->bm_earningMember,
          "bm_duration" => $data->bm_duration,
          "bm_hometown" => $data->bm_hometown,
          "bm_landloard" => $data->bm_landloard,
          "bm_recomand" => $data->bm_recomand,
          "bm_occupation" => $data->bm_occupation,
          "bm_aware" => $data->bm_aware,
          "bm_grantor" => $data->bm_grantor,
          "bm_socialAcecptRating" => $data->bm_socialAcecptRating,
          "bm_grantorRating" => $data->bm_grantorRating,
          "bm_clienthouse" => $data->bm_clienthouse,
          "bm_remarks" => $data->bm_remarks,

          "loan_id" => $data->loan_id,
          "mem_id" => $data->mem_id,
          "erp_mem_id" => $data->erp_mem_id,
          "memberTypeId" => $data->memberTypeId,
          "memberType" => $memberTypeIds,
          "frequencyId" => $data->frequencyId,
          "frequency" => $frequencyIds,
          "subSectorId" => $data->subSectorId,
          "subSector" => $subSectorIds,
          "insurn_mainIDTypeId" => $data->insurn_mainIDType,
          "insurn_mainIDType" => $insurnMainIDType,
          "insurn_id_expire" => $data->insurn_id_expire,
          "insurn_placeofissue" => $data->insurn_placeofissue,
          "ErpHttpStatus" => $data->ErpHttpStatus,
          "ErpErrorMessage" => $data->ErpErrorMessage,
          "ErpErrors" => $data->ErpErrors,
          "erp_loan_id" => $data->erp_loan_id,
          "role_name" => $role_names,
          "reciverrole_name" => $recieverrole_names,
          "SurveyId" => $data->surveyid,
          "amount_inword" => $data->amount_inword,
          "loan_purpose" => $data->loan_purpose,
          "loan_user" => $data->loan_user,
          "loan_type" => $data->loan_type,
          "brac_loancount" => $data->brac_loancount,
          "Comment" => $dochistory->comment,
          "ErpStatus" => $ErpStatus,
          "ErpStatusId" => $ErpStatusId,
          "ErpRejectionReason" => $ErpRejectionReason,
          "orgmemno" => $data->orgmemno
        );
        // $data['loan']=$loanArrayData;
        $rca = DB::table($db . '.rca')->where('loan_id', $data->id)->first();
        $PrimaryEarner = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'primaryEarner')->where('data_id', $rca->primary_earner)->first();
        $bmPrimaryEarner = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'primaryEarner')->where('data_id', $rca->bm_primary_earner)->first();
        if ($bmPrimaryEarner) {
          $bmPrimaryEarnerIs = $bmPrimaryEarner->data_name;
        } else {
          $bmPrimaryEarnerIs = null;
        }
        $arrayData['rca'] = array(
          "id" => $rca->id,
          "loan_id" => $rca->loan_id,
          "primary_earner" => $PrimaryEarner->data_name,
          "monthlyincome_main" => $rca->monthlyincome_main,
          "monthlyincome_other" => $rca->monthlyincome_other,
          "house_rent" => $rca->house_rent,
          "food" => $rca->food,
          "education" => $rca->education,
          "medical" => $rca->medical,
          "festive" => $rca->festive,
          "utility" => $rca->utility,
          "saving" => $rca->saving,
          "other" => $rca->other,
          "monthly_instal" => $rca->monthly_instal,
          "debt" => $rca->debt,
          "monthly_cash" => $rca->monthly_cash,
          "instal_proposloan" => $rca->instal_proposloan,
          "time" => $rca->time,
          "DynamicFieldValue" => $rca->DynamicFieldValue,
          "bm_primary_earner" => $bmPrimaryEarnerIs,
          "bm_monthlyincome_main" => $rca->bm_monthlyincome_main,
          "bm_monthlyincome_other" => $rca->bm_monthlyincome_other,
          "bm_house_rent" => $rca->bm_house_rent,
          "bm_food" => $rca->bm_food,
          "bm_education" => $rca->bm_education,
          "bm_medical" => $rca->bm_medical,
          "bm_festive" => $rca->bm_festive,
          "bm_utility" => $rca->bm_utility,
          "bm_saving" => $rca->bm_saving,
          "bm_other" => $rca->bm_other,
          "bm_monthly_instal" => $rca->bm_monthly_instal,
          "bm_debt" => $rca->bm_debt,
          "bm_monthly_cash" => $rca->bm_monthly_cash,
          "bm_instal_proposloan" => $rca->bm_instal_proposloan,
          "bm_monthlyincome_spouse_child" => $rca->bm_monthlyincome_spouse_child,
          "monthlyincome_spouse_child" => $rca->monthlyincome_spouse_child
        );
        $arrayData['clientInfo'] = $member;
        $dataset[] = $arrayData;
      }
    } else {
      $dataset = [];
    }
    return $dataset;
  }
  public function getSurveys($db, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
    $db = $this->db;
    if ($BranchCode != null and $Pin == null) {
      $surveydata = DB::table($db . '.surveys')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->where('assignedpo', $Pin)->orderBy('id', 'desc')->get();
    } elseif ($BranchCode != null and $Pin != null) {
      $surveydata = DB::table($db . '.surveys')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->where('assignedpo', $Pin)->orderBy('id', 'desc')->get();
    }
    return $surveydata;
  }
  public function ConfigurationSync(Request $request)
  {
    $db = $this->db;
    $token = Request::header('apiKey');
    $branchcode = Request::input('branchCode');
    $ProjectCode = Request::input('projectCode');
    $LastSyncTime = Request::input('lastSyncTime');
    $CurrentTime = Request::input('currentTime');
    $Appid = Request::header('appId');
    $Pin = Request::input('pin');
    $AppversionName = Request::header('appVersionName');
    $AppVersionCode = Request::header('appVersionCode');
    $auth_array = [];
    $branchcode = (int)$branchcode;
    $projectcode = (int)$ProjectCode;
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($branchcode != null and $ProjectCode != null) {
        $Process = DB::Table($db . '.processes')->select('id', 'process')->get();
        $FormConfig = DB::Table($db . '.form_configs')->where('projectcode', $projectcode)->get();
        $PayloadData = DB::Table($db . '.payload_data')->where('status', 1)->get();
        $OfficeMapping = DB::Table($db . '.office_mapping')->where('status', 1)->get();
        $ProductDetail = DB::Table($db . '.product_details')->get();
        $ProjectwiseMemberCategory = DB::Table($db . '.projectwise_member_category')->where('projectcode', $projectcode)->get();
        $ProductProjectMemberCategory = DB::Table($db . '.product_project_member_category')->where('projectcode', $projectcode)->where(
          function ($query) use ($branchcode) {
            return $query
              ->where('branchcode', $branchcode)->orWhere('branchcode', '*');
          }
        )->get();
        $InsuranceProducts = DB::Table($db . '.insurance_products')->where('project_code', $projectcode)->where(
          function ($query) use ($branchcode) {
            return $query
              ->where('branchcode', $branchcode)->orWhere('branchcode', 'All Office');
          }
        )->get();
        $SchememSectorSubsector = DB::Table($db . '.schemem_sector_subsector')->where(
          function ($query) use ($branchcode) {
            return $query
              ->where('branchcode', $branchcode)->orWhere('branchcode', '*');
          }
        )->where('projectcode', $projectcode)->get();
        $auth = DB::Table($db . '.auths')->where('projectcode', $ProjectCode)->where('roleId', '0')->whereNotNull('prerequisiteprocessid')->get();

        if (!$auth->isEmpty()) {
          foreach ($auth as $row) {
            $processname = DB::Table($db . '.processes')->select('process')->where('id', $row->processId)->first();
            $prerequisiteprocessname = DB::Table($db . '.processes')->select('process')->where('id', $row->prerequisiteprocessid)->first();

            $array['processid'] = $row->processId;
            $array['processname'] = $processname->process;
            $array['prerequisiteprocessid'] = $row->prerequisiteprocessid;
            $array['prerequisiteprocessname'] = $prerequisiteprocessname->process;
            $auth_array[] = $array;
          }
        }

        $result = array(
          "status" => "S",
          "message" => "",
          "Process" => $Process,
          "FormConfig" => $FormConfig,
          "PayloadData" => $PayloadData,
          "OfficeMapping" => $OfficeMapping,
          "ProductDetail" => $ProductDetail,
          "ProjectwiseMemberCategory" => $ProjectwiseMemberCategory,
          "ProductProjectMemberCategory" => $ProductProjectMemberCategory,
          "SchememSectorSubsector" => $SchememSectorSubsector,
          "AuthConfig" => $auth_array,
          "InsuranceProducts" => $InsuranceProducts,
        );
        return json_encode($result);
      } else {
        $message = "Branchcode Or ProjectCode Not Found!";
        $this->CUSTMSG($message);
      }
    } else {
      $message = "Api Key Not Found!";
      $this->CUSTMSG($message);
    }
  }
  public function CUSTMSG($message)
  {
    echo json_encode(array("status" => "CUSTMSG", "message" => $message));
    die;
  }
  public function TokenCheck()
  {
    //session_start();
    $token = '';
    if (isset($_SESSION["expirtime"])) {
      $time = $_SESSION["expirtime"];
    } else {
      $time = 0;
    }
    if (isset($_SESSION["expirdate"])) {
      $date = $_SESSION["expirdate"];
    } else {
      $date = '2000-01-01';
    }
    $chour = date('h');
    $cdate = date('Y-m-d');
    $totalh = $chour - $time;
    if ($cdate != $date) {
      $totalh = 1;
    }
    if ($totalh > 0) {
      $tokestart =  date('h');
      $_SESSION["expirtime"] = $tokestart;
      $_SESSION["expirdate"] = date('Y-m-d');

      $header = array(
        'x-client-id:1_43wc41hen7cwg0sg4s044c0scc8wck4o',
        'x-client-secret:654spemp5qckcg4g448044kco4k0g8wwo0440osgwosggwg4'
      );
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://erp.brac.net/oauth/v2/token?grant_type=client_credentials");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_POSTFIELDS, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $auth_output = curl_exec($ch);
      //dd($auth_output);
      Log::info("Access Token" . $auth_output);
      $auth = json_decode($auth_output);
      $accesstoken = $auth->access_token;
      $_SESSION["access_token"] = $accesstoken;
    }
    if (isset($_SESSION["access_token"])) {
      $token = $_SESSION["access_token"];
      //echo $token;
    }
    return $token;
    //Log::info('bksh json Check -' . $PIN . "-" . $walletno . "-" . $qsoftids . "-" . $auth_output . "-" . $token);
  }
  public function ServerURL()
  {
    $db = 'dcs';
    $url = '';
    $url2 = '';
    $serverurl = DB::Table($db . '.server_url')->where('server_status', 3)->where('status', 1)->first();
    //dd($serverurl);
    if (empty($serverurl)) {
      $statuss = array("status" => "CUSTMSG", "message" => "Server Api Not Found");
      $json = json_encode($statuss);
      echo $json;
      die;
    } else {
      //dd($serverurl->url);
      $url = $serverurl->url;
      $url2 = $serverurl->url2;
      $servermessage = $serverurl->maintenance_message;
      $serverstatus = $serverurl->maintenance_status;
      if ($serverstatus == '1') {
        $statuss = array("status" => "CUSTMSG", "message" => $servermessage);
        $json = json_encode($statuss);
        echo $json;
        die;
      }
    }
    $urlaray = array($url, $url2);
    return $urlaray;
  }
  public function LogCreate($branchcode, $projectcode, $pin, $type, $message)
  {
    $db = $this->db;
    $insertquery = DB::Table($db . '.logs')->insert(['branchcode' => $branchcode, "projectcode" => $projectcode, "pin" => $pin, "type" => $type, "message" => $message]);
  }
  public function DocumentManager(Request $request)
  {
    $db = $this->db;
    $baseUrl = url('');
    $projectcode = Request::input('projectcode');
    $doc_type = Request::input('doc_type');
    $doc_id = Request::input('doc_id');
    $entollmentid = Request::input('entollmentid');
    $pin = Request::input('pin');
    $roleid = Request::input('role');
    $branchcode = Request::input('branchcode');
    $action = Request::input('action');
    $comment = Request::input('comment');
    // dd("Huda");
    //get proccessid by doc type request
    if ($doc_type == 'admission') {
      $processid = DB::table($db . '.processes')->select('id')->where('process', 'member admission')->first();
      $processid = $processid->id;
    } elseif ($doc_type == 'loan') {
      $processid = DB::table($db . '.processes')->select('id')->where('process', 'loan application')->first();
      $processid = $processid->id;
    }

    //get doc_id by enrollment id
    if ($doc_id == '' and $entollmentid != '') {
      if ($doc_type == 'admission') {
        $doc = DB::table($db . '.admissions')->select('id')->where('entollmentid', $entollmentid)->first();
        $doc_id = $doc->id;
      } elseif ($doc_type == 'loan') {
        $doc = DB::table($db . '.loans')->select('id')->where('loan_id', $entollmentid)->first();
        $doc_id = $doc->id;
      }
    }
    // dd($doc_id);
    //get enrollment id by doc id
    if ($doc_id != '' and $entollmentid == '') {
      if ($doc_type == 'admission') {
        $doc = DB::table($db . '.admissions')->select('entollmentid')->where('id', $doc_id)->first();
        $entollmentid = $doc->entollmentid;
      } elseif ($doc_type == 'loan') {
        $doc = DB::table($db . '.loans')->select('loan_id')->where('id', $doc_id)->first();
        $entollmentid = $doc->loan_id;
      }
    }
    //dd($doc_id);
    //find action id for the action
    $actionAry = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $actionAry->id;
    // dd($actionid);
    //check for parameter
    if ($projectcode != '' and $doc_type != '' and $doc_id != '' and $pin != '' and $roleid != '' and $branchcode != '') {
      $check_doc_history = DB::table($db . '.document_history')->where('projectcode', $projectcode)->where('doc_type', $doc_type)->where('doc_id', $doc_id)->get();
      $status = 1;
      if ($action == 'Request' or $action == 'Modify') {
        $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'action' => $actionid, 'projectcode' => $projectcode, 'roleid' => $roleid, 'reciverrole' => 1]);
        if ($doc_type == 'admission') {
          DB::table($db . '.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $roleid, 'pin' => $pin, 'reciverrole' => 1, 'status' => $status]);
        } elseif ($doc_type == 'loan') {
          DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $roleid, 'pin' => $pin, 'reciverrole' => 1, 'status' => $status]);
        }
        Log::channel('daily')->info('Po :' . $pin . ' send member admission to bm for approval');

        $result = array("status" => "S", "message" => "Document history saved");
        echo json_encode($result);
      } else {
        if ($doc_type == 'admission') {
          $document = DB::table($db . '.admissions')->where('id', $doc_id)->first();
        } elseif ($doc_type == 'loan') {
          $document = DB::table($db . '.loans')->where('id', $doc_id)->first();
        }
        if ($roleid != $document->reciverrole) {
          $result = array("status" => "E", "message" => "Domument already has been proccesed.");
          return json_encode($result);
        }
        $reciverrole = $document->reciverrole;
        $branchcode = $document->branchcode;
        $docpin = $document->pin;
        // dd($docpin);
        //authrizetion check
        $checkAuth = $this->roleAuthrizatioCheck($reciverrole, $processid, $projectcode);
        if ($checkAuth) {
          $findHierarchyRole = $this->findHierarchyRole($reciverrole, $projectcode);
          $nextrole = $findHierarchyRole[0];
          //dd($nextrole);
          $nextroledesig = $findHierarchyRole[1];
          //dd($nextroledesig);
          $findPreviousRole = $this->findPreviousRole($reciverrole, $projectcode);

          $Previousrole = $findPreviousRole[0];
          $Previousroledesig = $findPreviousRole[1];
          // dd($action);
          if ($action != '') {
            if ($action == 'Recommend') {
              $checkApprove = $this->actionForRecommend($nextrole, $nextroledesig, $action, $reciverrole, $pin, $processid, $doc_type, $doc_id, $projectcode, $comment);

              if ($checkApprove) {
                Log::channel('daily')->info($reciverrole . $doc_type . '  to ' . $nextroledesig . '(' . $nextrole . ') for approval');
              }
            }
            if ($action == 'Sendback') {
              $checkApprove = $this->actionForSendback($Previousrole, $Previousroledesig, $action, $reciverrole, $pin, $processid, $doc_type, $doc_id, $projectcode, $comment);

              if ($checkApprove) {
                Log::channel('daily')->info($reciverrole . $doc_type . ' to ' . $nextroledesig . '(' . $nextrole . ') for sendback');
              }
            }
            if ($action == 'Reject') {
              $checkApprove = $this->actionForReject($Previousrole, $Previousroledesig, $action, $reciverrole, $pin, $processid, $doc_type, $doc_id, $projectcode, $comment);

              if ($checkApprove) {
                Log::channel('daily')->info($reciverrole . $doc_type . ' to ' . $nextroledesig . '(' . $nextrole . ') for Reject');
              }
            }
            if ($action == 'Approve') {
              $checkErpResponse = $this->documentErpPosting($doc_id, $doc_type);
              if ($checkErpResponse[0] != '200') {
                $result = array("status" => "E", "httpstatus" => $checkErpResponse[0], "errors" => $checkErpResponse[1]);
                return json_encode($result);
                die;
              } else {
                //$checkErpResponse = 'OK';
                Log::channel('daily')->info("Check Approve Log" . $nextrole . "/" . $nextroledesig . "/" . $action . "/" . $reciverrole . "/" . $pin . "/" . $processid . "/" . $doc_type . "/" . $doc_id . '/' . $projectcode);
                // dd("Huda");
                Log::channel('daily')->info('Erp Response : ' . json_encode($checkErpResponse));


                $checkApprove = $this->actionForApprove($nextrole, $nextroledesig, $action, $reciverrole, $pin, $processid, $doc_type, $doc_id, $projectcode);
                // dd($checkApprove);
                $message = "Use actionForApprove-" . $doc_id . "-" . $status;
                $this->LogCreate($branchcode, $projectcode, $pin, $doc_type, $message);
                Log::channel('daily')->info("Check Approve" . $checkApprove);
                if ($checkApprove) {
                  Log::channel('daily')->info($reciverrole . ' Approve ' . $doc_type);
                  $result = array("status" => "S", "message" => 'Approve ' . $doc_type);
                  return json_encode($result);
                } else {
                  Log::channel('daily')->info("Check Not Approve" . $checkApprove);
                }
              }

              //return erp errors
              /* if ($checkErpResponse != "OK") {
                $result = array("status" => "E", "errors" => $checkErpResponse);
                return json_encode($result);
              }*/
            }

            //send notification
            $notification_url = $baseUrl . "/NotificatioManager?projectcode=$projectcode&doc_type=$doc_type&pin=$docpin&role=$roleid&branchcode=$branchcode&entollmentid=$entollmentid&action=$action";

            Log::channel('daily')->info('notification_url : ' . $notification_url);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $notification_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $notificationoutput = curl_exec($ch);
            curl_close($ch);
            Log::channel('daily')->info('notification_response : ' . $notificationoutput);
            //end notification

            $result = array("status" => "S", "entollmentid" => "$entollmentid");

            Log::channel('daily')->info('tab_response : ' . json_encode($result));

            return json_encode($result);
          } else {
            $result = array("status" => "E", "message" => "Action Required");
            return json_encode($result);
          }
        } else {
          $result = array("status" => "E", "message" => "User Not Authrize!");
          return json_encode($result);
        }
      }
    } else {
      $result = array("status" => "E", "message" => "parameter missing!");
      return json_encode($result);
    }
  }
  //start Document manager functions
  public function roleAuthrizatioCheck($roleId, $processId, $projectcode)
  {
    $db = $this->db;
    $isAuthurize = DB::table($db . '.auths')->select('isAuthorized')->where('roleId', $roleId)->where('processId', $processId)->where('projectcode', $projectcode)->first();

    return $isAuthurize->isAuthorized;
  }

  public function findHierarchyRole($role, $projectcode)
  {
    $db = $this->db;
    // $position=DB::table($db.'.role_hierarchies')->select('position')->where('role', $role)->where('projectcode', $projectcode)->first();
    // $position=$position->position;
    // $position=$position+1;
    // $nextrole=DB::table($db.'.role_hierarchies')->where('position', $position)->where('projectcode', $projectcode)->first();
    $role = $role + 1;
    $nextrole = DB::table($db . '.role_hierarchies')->where('position', $role)->where('projectcode', $projectcode)->first();
    return array($nextrole->position, $nextrole->designation);
  }

  public function findPreviousRole($role, $projectcode)
  {
    $db = $this->db;
    // $position=DB::table($db.'.role_hierarchies')->select('position')->where('role', $role)->where('projectcode', $projectcode)->first();
    // $position=$position->position;
    // $position=$position-1;
    // $nextrole=DB::table($db.'.role_hierarchies')->where('position', $position)->where('projectcode', $projectcode)->first();
    $role = $role - 1;
    $nextrole = DB::table($db . '.role_hierarchies')->where('position', $role)->where('projectcode', $projectcode)->first();
    return array($nextrole->position, $nextrole->designation);
  }

  public function actionForRecommend($nextrole, $nextroledesig, $action, $role, $pin, $processid, $doc_type, $doc_id, $projectcode, $comment)
  {
    $db = $this->db;
    $status = 1;
    $currentDatetime = date("Y-m-d h:i:s");
    $action = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $action->id;
    $actioncounter = DB::table($db . '.document_history')->where('doc_id', $doc_id)->max('action_counter');
    $actioncounter = $actioncounter + 1;
    $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'projectcode' => $projectcode, 'action' => $actionid, 'roleid' => $role, 'reciverrole' => $nextrole, 'action_counter' => $actioncounter, 'comment' => $comment]);
    DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime]);
    // if($doc_type=='admission'){
    // 	DB::table($db.'.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id,'roleid'=>$role,'pin'=>$pin,'action'=>$actionid,'reciverrole'=>$nextrole,'status'=>$status]);
    // }elseif($doc_type=='loan'){
    // 	DB::table($db.'.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id,'roleid'=>$role,'pin'=>$pin,'action'=>$actionid,'reciverrole'=>$nextrole,'status'=>$status]);
    // }

    return true;
  }

  public function actionForSendback($nextrole, $nextroledesig, $action, $role, $pin, $processid, $doc_type, $doc_id, $projectcode, $comment)
  {
    $db = $this->db;
    $status = 1;
    $currentDatetime = date("Y-m-d h:i:s");
    $action = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $action->id;
    $actioncounter = DB::table($db . '.document_history')->where('doc_id', $doc_id)->max('action_counter');
    $actioncounter = $actioncounter + 1;
    $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'projectcode' => $projectcode, 'action' => $actionid, 'roleid' => $role, 'reciverrole' => $nextrole, 'action_counter' => $actioncounter, 'comment' => $comment]);
    if ($doc_type == 'admission') {
      DB::table($db . '.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime]);
    } elseif ($doc_type == 'loan') {
      DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime]);
    }

    return true;
  }

  public function actionForReject($nextrole, $nextroledesig, $action, $role, $pin, $processid, $doc_type, $doc_id, $projectcode, $comment)
  {
    $db = $this->db;
    $status = '3';
    $currentDatetime = date("Y-m-d h:i:s");
    $action = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $action->id;
    $actioncounter = DB::table($db . '.document_history')->where('doc_id', $doc_id)->max('action_counter');
    $actioncounter = $actioncounter + 1;
    $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'projectcode' => $projectcode, 'action' => $actionid, 'roleid' => $role, 'reciverrole' => $nextrole, 'action_counter' => $actioncounter, 'comment' => $comment]);
    if ($doc_type == 'admission') {
      DB::table($db . '.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime]);
    } elseif ($doc_type == 'loan') {
      DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'status' => $status, 'updated_at' => $currentDatetime]);
    }

    return true;
  }

  public function actionForApprove($nextrole, $nextroledesig, $action, $role, $pin, $processid, $doc_type, $doc_id, $projectcode)
  {
    $db = $this->db;
    $branchcode = '0000';
    $status = '2';
    $erpstatus = 1;
    $currentDatetime = date("Y-m-d h:i:s");
    $action = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $action->id;
    //dd($actionid);
    $actioncounter = DB::table($db . '.document_history')->where('doc_id', $doc_id)->max('action_counter');
    // dd($actioncounter);
    $actioncounter = $actioncounter + 1;
    // dd($actioncounter);
    $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'projectcode' => $projectcode, 'action' => $actionid, 'roleid' => $role, 'action_counter' => $actioncounter]);
    if ($doc_type == 'admission') {
      DB::table($db . '.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'status' => $status, 'ErpStatus' => $erpstatus, 'updated_at' => $currentDatetime]);
      $message = "Admission Approve-" . $doc_id . "-" . $status;
      $this->LogCreate($branchcode, $projectcode, $pin, $doc_type, $message);
    } elseif ($doc_type == 'loan') {
      DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'status' => $status, 'ErpStatus' => $erpstatus, 'updated_at' => $currentDatetime]);
      $message = "Loan Approve-" . $doc_id . "-" . $status;
      $this->LogCreate($branchcode, $projectcode, $pin, $doc_type, $message);
    }
    return true;
    $message = "Not Access-" . $doc_id . "-" . $status;
    $this->LogCreate($branchcode, $projectcode, $pin, $doc_type, $message);
  }
  //end document manager functions
  //start notification Manager
  public function NotificatioManager(Request $request)
  {
    $db = $this->db;
    $projectcode = Request::input('projectcode');
    $doc_type = Request::input('doc_type');
    $doc_id = Request::input('doc_id');
    $entollmentid = Request::input('entollmentid');
    $pin = Request::input('pin');
    $roleid = Request::input('role');
    $branchcode = Request::input('branchcode');
    $action = Request::input('action');
    // $comment = Request::input('comment');

    //get doc_id by enrollment id
    if ($doc_id == '' and $entollmentid != '') {
      if ($doc_type == 'admission') {
        $doc = DB::table($db . '.admissions')->select('id')->where('entollmentid', $entollmentid)->first();
        $doc_id = $doc->id;
      } elseif ($doc_type == 'loan') {
        $doc = DB::table($db . '.loans')->select('id')->where('loan_id', $entollmentid)->first();
        $doc_id = $doc->id;
      }
    }

    if ($doc_type == 'admission') {
      $processid = DB::table($db . '.processes')->select('id')->where('process', 'member admission')->first();
      $processid = $processid->id;
    } elseif ($doc_type == 'loan') {
      $processid = DB::table($db . '.processes')->select('id')->where('process', 'loan application')->first();
      $processid = $processid->id;
    }

    //find designation
    $roleDesignationQuery = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $roleid)->first();
    $roleDesignation = $roleDesignationQuery->designation;

    $actionary = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $actionary->id;

    $notification = DB::table($db . '.notifications')->where('actionid', $actionid)->where('projectid', $projectcode)->where('roleid', $roleid)->where('status', 1)->first();
    if ($notification->inApp) {
      $reciverrole = $notification->recieverlist;
      $msgcontent = $notification->msgcontent;

      $reciverroleary = explode(',', $reciverrole);

      if (count($reciverroleary) == 1) {
        //find designation
        $reciverRoleDesignationQuery = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $reciverrole)->first();
        $reciverroleDesignation = $reciverRoleDesignationQuery->designation;

        $inAppReturn = $this->inAppAction($roleid, $roleDesignation, $reciverrole, $reciverroleDesignation, $msgcontent, $projectcode, $pin, $processid, $doc_type, $doc_id, $entollmentid, $actionid, $action, $branchcode);
        // return $inAppReturn;
      } else {
        foreach ($reciverroleary as $reciverrole) {
          //find designation
          $reciverRoleDesignationQuery = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $reciverrole)->first();
          $reciverroleDesignation = $reciverRoleDesignationQuery->designation;

          $inAppReturn = $this->inAppAction($roleid, $roleDesignation, $reciverrole, $reciverroleDesignation, $msgcontent, $projectcode, $pin, $processid, $doc_type, $doc_id, $entollmentid, $actionid, $action, $branchcode);
        }
      }

      if ($inAppReturn) {
        Log::channel('daily')->info('In App notification suucessful');
      }
    } else if ($notification->sms) {
    } else if ($notification->email) {
    }
    $result = array("status" => "S", "message" => "Notification created successfully");
    echo json_encode($result);
  }

  public function inAppAction($role, $roleDesignation, $reciverrole, $reciverroleDesignation, $msgcontent, $projectcode, $pin, $processid, $doc_type, $doc_id, $entollmentid, $actionid, $action, $branchcode)
  {
    $db = $this->db;
    $dberp = $this->dberp;
    $baseUrl = url('');
    $trendxurl = 'http://trendx.brac.net/api/';
    $reciverpin = '';
    $associateid = 0;
    $brcode = $branchcode;
    $branchcode = (int)$branchcode; //for remover inital zero 
    $tendxbmpin = 'b' . $branchcode;

    if ($projectcode == '015') {
      $program_id = 1;
    } elseif ($projectcode == '060') {
      $program_id = 5;
    }

    // dd($reciverroleDesignation);

    if ($reciverroleDesignation == 'BM') {
      $findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
        ->Where(function ($query) {
          // $query->where('desig','Branch Manager')->orWhere('desig','Assistant Branch Manager');
          $query->where('desig', 'Branch Manager');
        })->first();
      if ($findpin != null) {
        $reciverpin = $findpin->cono;
        //$deviceid = $findpin->deviceid;
      } else {
        $findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
          ->Where(function ($query) {
            $query->Where('desig', 'Assistant Branch Manager');
            // $query->where('desig','Branch Manager');
          })->first();
        if ($findpin != null) {
          $reciverpin = $findpin->cono;
          //$deviceid = $findpin->deviceid;
        }
      }
    } else if ($reciverroleDesignation == 'PO') {
      $findpin = DB::table($db . '.document_history')->select('pin')->where('doc_type', $doc_type)->where('doc_id', $doc_id)->where('projectcode', $projectcode)->where('action_counter', 1)->first();

      if ($findpin != null) {
        $reciverpin = $findpin->pin;
        $getDeviceid = DB::table($dberp . '.polist')->where('cono', $reciverpin)->where('projectcode', $projectcode)->where('status', 1)->where('branchcode', $brcode)->get();
        if ($getDeviceid != null) {
          //$deviceid = $getDeviceid->deviceid;
        }
      }
      // $reciverpin='186251';
    }

    $associate = DB::table('public.branch')->select('area_id', 'region_id', 'division_id')->where('branch_id', $branchcode)->where('program_id', $program_id)->first();
    // dd($associate);
    if ($reciverroleDesignation == 'AM') {
      $associateid = $associate->area_id;
    } else if ($reciverroleDesignation == 'RM') {
      $associateid = $associate->region_id;
    } else if ($reciverroleDesignation == 'DM') {
      $associateid = $associate->division_id;
    }

    if ($doc_type == 'admission') {
      $docreff = $baseUrl . '/operation/admission-approval/' . $doc_id;
    } elseif ($doc_type == 'loan') {
      $docreff = $baseUrl . '/operation/loan-approval/' . $doc_id;
    }


    if ($reciverroleDesignation == 'PO' or $reciverroleDesignation == 'BM') {
      DB::Table($db . '.message_ques')->insert(['pin' => $reciverpin, 'message' => $msgcontent, 'docreff' => $docreff, 'doctype' => $doc_type]);

      $test = $this->sendAppNotification($entollmentid, $doc_type, $reciverpin, $msgcontent, $action);
      // dd($test);
    } else {
      DB::Table($db . '.message_ques')->insert(['message' => $msgcontent, 'docreff' => $docreff, 'doctype' => $doc_type, 'roleid' => $reciverrole, 'associateid' => $associateid, 'programid' => $program_id]);
    }

    return true;

    // else if ($reciverroleDesignation == 'AM') {
    // 	//find associate id
    // 	$findassciateid = DB::table('public.branch')->select('area_id')->where('branch_id', $branchcode)->where('program_id', $program_id)->groupBy('area_id')->first();
    // 	$associated_id = $findassciateid->area_id;

    // 	$findpin = DB::table($db . '.user')->select('user_pin')->where('status_id', 1)->where('associated_id', $associated_id)->where('role_id', $reciverrole)->where('program_id', $program_id)->first();
    // 	if ($findpin != null) {
    // 		$reciverpin = $findpin->user_pin;
    // 	}
    // 	if ($projectcode == '015') {
    // 		$reciverpin = 'a123';
    // 	} elseif ($projectcode == '060') {
    // 		$reciverpin = 'b123';
    // 	}
    // } else if ($reciverroleDesignation == 'RM') {

    // 	//find associate id
    // 	$findassciateid = DB::table('public.branch')->select('region_id')->where('branch_id', $branchcode)->where('program_id', $program_id)->groupBy('region_id')->first();
    // 	$associated_id = $findassciateid->region_id;

    // 	$findpin = DB::table($db . '.user')->select('user_pin')->where('status_id', 1)->where('associated_id', $associated_id)->where('role_id', $reciverrole)->where('program_id', $program_id)->first();
    // 	if ($findpin != null) {
    // 		$reciverpin = $findpin->user_pin;
    // 	}
    // 	if ($projectcode == '015') {
    // 		$reciverpin = '50515';
    // 	} elseif ($projectcode == '060') {
    // 		$reciverpin = '40414';
    // 	}
    // } else if ($reciverroleDesignation == 'DM') {

    // 	//find associate id
    // 	$findassciateid = DB::table('public.branch')->select('division_id')->where('branch_id', $branchcode)->where('program_id', $program_id)->groupBy('division_id')->first();
    // 	$associated_id = $findassciateid->division_id;

    // 	// $findpin=DB::table($db.'.user')->select('user_pin')->where('status_id',1)->where('branchcode',$branchcode)->where('designation','Divisional Manager')->first();
    // 	$findpin = DB::table($db . '.user')->select('user_pin')->where('status_id', 1)->where('associated_id', $associated_id)->where('role_id', $reciverrole)->where('program_id', $program_id)->first();
    // 	if ($findpin != null) {
    // 		$reciverpin = $findpin->user_pin;
    // 	}
    // 	if ($projectcode == '015') {
    // 		$reciverpin = '112233';
    // 	} elseif ($projectcode == '060') {
    // 		$reciverpin = '445566';
    // 	}
    // } 

    //trendx api integration for am,rm,dm
    // $trendx = Http::get($trendxurl . 'branch', [
    // 	'user_pin' => $tendxbmpin,
    // 	'role_id' => 1,
    // 	'module_id' => 10
    // ]);

    // $trendxAry = $trendx->object();

    // if (!empty($trendxAry)) {
    // 	$bm_id = $trendxAry[0]->bm_id;
    // 	$am_id = $trendxAry[0]->am_id;
    // 	$rm_id = $trendxAry[0]->rm_id;
    // 	$div_id = $trendxAry[0]->div_id;

    // 	if ($reciverroleDesignation == 'AM') {
    // 		$reciverpin = $am_id;
    // 	} else if ($reciverroleDesignation == 'RM') {
    // 		$reciverpin = $rm_id;
    // 	} else if ($reciverroleDesignation == 'DM') {
    // 		$reciverpin = $div_id;
    // 	}
    // } else {
    // 	return false;
    // }
    //end trendx api integration for am,rm,dm

  }
  //end notification manager

  //push notification
  public function sendAppNotification($doc_id, $doc_type, $reciverpin, $msgcontent, $action)
  {
    $res = array();
    $res['doc_id'] = $doc_id;
    $res['doc_type'] = $doc_type;
    $res['pin'] = $reciverpin;
    $res['message'] = $msgcontent;
    $res['command'] = "dataReceived";
    $res['action'] = $action;
    //$res['deviceid'] = $deviceid;
    $res['timestamp'] = date('Y-m-d H:i:s');
    $data['data'] = $res;
    $topic = $this->Topic . "" . $reciverpin;  //$reciverpin;
    $test = $this->sendToTopic($topic, $data);
    // dd($test);
    Log::channel('daily')->info('topic: ' . $topic . ',meg: ' . json_encode($data));
    Log::channel('daily')->info('firease response: ' . $test);
    return $test;
  }
  public function sendToTopic($to, $message)
  {
    $fields = array(
      'to' => '/topics/' . $to,
      'data' => $message,
    );
    return $this->sendPushNotification($fields);
  }

  public function sendPushNotification($fields)
  {
    //define('FIREBASE_API_KEY', 'AAAAAehTCwo:APA91bHE2R70FRVrx_WsEbEnal_AGn8MtyFhfxyyv51bh_9xm85eANaV8OoBPdeA0QUVl9umLY-gfILnAFu6GLSMeB6zTHY2v5aUbo2iXzkX6nnaRD1lqTAPjOCVvZwHZ9MP7wyDUere');
    //var_dump($fields);
    // Set POST variables
    // $FIREBASE_API_KEY = 'AAAAgArpCfk:APA91bEE8TjJgYZvvvh8JycZrmQNhsyVnCP6PTFCeHfeCUZItPnYowcPgScHfTJMO9RRT6RreQyF1OX55UJAGsSzRgMoF9mG_KIQvANzuwlYLuxpCrVFKQ7X-lz2h0h_sClza8w3kk0w';
    $FIREBASE_API_KEY = 'AAAAn7dnUEs:APA91bHWNtWzZrkMOPMvSKPVpgKbIYFRoZlP5k2CbRZzaHlpHXq-B8cfeQUsdi7GqbAg-gDDCN1YK9gbcuuPZmN4IK0IEF6PZVfxu1HHK0vX9IzgTfdY-xQt989E8csMSVNO4lx5Bze-';
    $url = 'https://fcm.googleapis.com/fcm/send';

    $headers = array(
      'Authorization: key=' . $FIREBASE_API_KEY,
      'Content-Type: application/json'
    );
    // Open connection
    $ch = curl_init();

    // Set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Disabling SSL Certificate support temporarly
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    // Execute post
    $result = curl_exec($ch);
    if ($result === FALSE) {
      die('Curl failed: ' . curl_error($ch));
    }

    // Close connection
    curl_close($ch);
    //echo $result;
    return $result;
  }
  //end push notification
  public function HitIP(Request $request)
  {

    $curl = curl_init();
    $token = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2NzU5MTY3MjksImV4cCI6MTY3NTkyMDMyOSwic3ViIjoiUXNvZnQiLCJpc3MiOiJlcnAuYnJhYy5uZXQiLCJqdGkiOiIxXzQzd2M0MWhlbjdjd2cwc2c0czA0NGMwc2NjOHdjazRvIiwiYXVkIjoiMTAzLjQzLjkzLjEzMCJ9.e81OMAyu6baCz5XuuA1yETBe_7MwaHiFm4J0nKgZ7msnYEf1HsjsoiHWyS7rWWSYKcvjvYax8kuvBB3TSVfnDHIMcqLtZkN0OONuWrZp_AdClaArepwLmG4hButD_EQ3-OGiDbDG8ex4cAEmyFgjxCaZtUeYOrm0C9s1ioJ5YhM';
    $headerss = array(
      "Content-Type: application/json",
      "Authorization: Bearer " . $token
    );

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://erp.brac.net/node/scapir/MemberList?BranchCode=0605&CONo=00096392&ProjectCode=060&UpdatedAt=2000-01-01%2012:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
    ));
    $notificationoutput = curl_exec($curl);
    echo $notificationoutput;
    /*$url = "https://erp.brac.net/api/dcs/v1/branch-wise-projects-insurance-products-mappings";
    // $url = "https://erp.brac.net/oauth/v2/token?grant_type=client_credentials";
    // $url = "http://erp.brac.net/scapiw/StoreSPSCollectionInfo";
    //$url = "https://bracapitesting.brac.net/dcs/v1/";
    // $url = "https://bracapitesting.brac.net/scapir/VOList?BranchCode=1948&ProjectCode=015&CONo=00186154&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae";
    //$url = "http://bracapitesting.brac.net/scapiw/StoreSPSCollectionInfo";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $notificationoutput = curl_exec($ch);
    echo $notificationoutput;
    curl_close($ch);*/
  }
}
