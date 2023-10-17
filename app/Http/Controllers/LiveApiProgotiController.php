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

class LiveApiProgotiController extends Controller
{
  public function ProgotiSync($db, $dberp, $apikey, $key, $token, $BranchCode, $ProjectCode, $LastSyncTime, $Appid, $Pin, $AppVersionCode, $AppversionName, $CurrentTimes)
  {
    $DataSetArray = array();
    $config = $this->ConfigurationSync($db, $Pin, $BranchCode, $Appid, $CurrentTimes, $ProjectCode, $token);
    $DataSetArray['Configurationdata'] = $config;
    $bank_info = $this->Bank_List($db, $Pin, $BranchCode, $Appid, $CurrentTimes, $ProjectCode, $token);
    $DataSetArray['BankList'] = $bank_info;
    //dd("Test");
    $memberlistdata = $this->MemberLists($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid, $key);
    $DataSetArray['erpmemberlist'] = $memberlistdata;
    // dd("Tes");
    $servey = $this->getSurveys($db, $BranchCode, $ProjectCode, $Pin, $Appid);
    $DataSetArray['surveydata'] = $servey;
    $admissiondata =  $this->AdmissionDataSync($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
    $DataSetArray['admissiondata'] = $admissiondata;
    $loandata = $this->LoanDataSync($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
    //dd($loandata);
    $DataSetArray['loandata'] = $loandata;
    $loandata = $this->Loan_Other_DataSync($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
    $bank_info = $loandata[0];
    $borrower_office_info = $loandata[1];
    $borrower_passport_visa_details = $loandata[2];
    $business_info = $loandata[3];
    $co_borrower_details = $loandata[4];
    $expenses_info = $loandata[5];
    $guarantor_details1 = $loandata[6];
    $housing_business_details = $loandata[7];
    $income_info = $loandata[8];
    $information_abroad_resident = $loandata[9];
    $information_remittances = $loandata[10];
    $personal_asset_info = $loandata[11];
    $common_checklist = $loandata[12];
    $checklist_nirvorota = $loandata[13];
    $checklist_remitance = $loandata[14];
    $checklist_trade_agri = $loandata[15];
    $otherloanInfo = $loandata[16];

    $DataSetArray['bank_info'] = $bank_info;
    $DataSetArray['borrower_office_info'] = $borrower_office_info;
    $DataSetArray['borrower_passport_visa_details'] = $borrower_passport_visa_details;
    $DataSetArray['business_info'] = $business_info;
    $DataSetArray['co_borrower_details'] = $co_borrower_details;
    $DataSetArray['expenses_info'] = $expenses_info;
    $DataSetArray['guarantor_details'] = $guarantor_details1;
    //$DataSetArray['housing_business_details'] = $housing_business_details;
    $DataSetArray['income_info'] = $income_info;
    $DataSetArray['information_abroad_resident'] = $information_abroad_resident;
    $DataSetArray['information_remittances'] = $information_remittances;
    $DataSetArray['personal_asset_info'] = $personal_asset_info;
    $DataSetArray['document_checklist'] = $common_checklist;
    $DataSetArray['checklist_nirvorota'] = $checklist_nirvorota;
    $DataSetArray['checklist_remitance'] = $checklist_remitance;
    $DataSetArray['checklist_trade_agri'] = $checklist_trade_agri;
    $DataSetArray['otherLoanInfo'] = $otherloanInfo;



    $arrayFile = array("status" => "success", "time" => $CurrentTimes, "message" => "", "data" => $DataSetArray);
    $jsonFile = json_encode($arrayFile);

    $this->ZipFileCreate($db, $Pin, $BranchCode, $Appid, $CurrentTimes, $ProjectCode, $jsonFile);
  }
  public function AdmissionDataSync($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
    $admissiondataary = array();
    $admissiondata = DB::table($db . '.admissions')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->Where('assignedpo', $Pin)->where("update_at", ">=", $LastSyncTime)->orderBy('id', 'desc')->get();
    if (!empty($admissiondata)) {
      foreach ($admissiondata as $data) {
        $MainIdTypeId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->MainIdTypeId)->first();
        $NomineeNidType = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->NomineeNidType)->first();
        $OtherIdTypeId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->OtherIdTypeId)->first();
        $SpouseCardType = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->SpouseCardType)->first();
        $EducationId = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'educationId')->where('data_id', $data->EducationId)->first();
        $MaritalStatusIds = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'maritalStatusId')->where('data_id', $data->MaritalStatusId)->first();
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
        //dd($status);
        $presentUpazilaId = DB::table($db . '.office_mapping')->select('thana_name')->where('thana_id', $data->presentUpazilaId)->where('district_id', $data->PresentDistrictId)->first();
        $parmanentUpazilaId = DB::table($db . '.office_mapping')->select('thana_name')->where('thana_id', $data->parmanentUpazilaId)->where('district_id', $data->PermanentDistrictId)->first();
        $PresentDistrictId = DB::table($db . '.office_mapping')->select('district_name')->where('district_id', $data->PresentDistrictId)->first();
        $PermanentDistrictId = DB::table($db . '.office_mapping')->select('district_name')->where('district_id', $data->PermanentDistrictId)->first();
        $mstatus = $MaritalStatusIds->data_name ?? null;
        //$MaritalStatusIdsName = $MaritalStatusIds->date_name ?? null;
        $MainIdTypeId = $MainIdTypeId->data_name ?? null;
        $EducationId = $EducationId->data_name ?? null;
        $WalletOwner = $WalletOwner->data_name ?? null;
        $RelationshipId = $RelationshipId->data_name ?? null;
        $Occupation = $Occupation->data_name ?? null;
        $genderId = $genderId->data_name ?? null;
        $PrimaryEarner = $PrimaryEarner->data_name ?? null;
        $MemberCateogryId = $MemberCateogryId->categoryname ?? null;
        $role_name = $role_name->designation ?? null;
        $recieverrole_name = $recieverrole_name->designation ?? null;
        $status = $status->status_name ?? null;
        //echo $status;
        $NomineeNidType = $NomineeNidType->data_name ?? null;
        $SpuseOccupationId = $SpuseOccupationId->data_name ?? null;
        $SpouseCardType = $SpouseCardType->data_name ?? null;
        $OtherIdTypeId = $OtherIdTypeId->data_name ?? null;
        $presentUpazilaId = $presentUpazilaId->thana_name ?? null;
        $parmanentUpazilaId = $parmanentUpazilaId->thana_name ?? null;
        $PresentDistrictId = $PresentDistrictId->district_name ?? null;
        $PermanentDistrictId = $PermanentDistrictId->district_name ?? null;
        $comment = $dochistory->comment ?? null;
        $ErpStatusId = null;
        $ErpRejectionReason = null;
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
        //dd($mstatus);
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
          "MaritalStatus" => $mstatus,
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
          "created_at" => date('Y-m-d', strtotime($data->created_at)),
          "updated_at" => date('Y-m-d', strtotime($data->updated_at)),
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
          "Comment" => $comment ?? null,
          "ErpStatus" => $ErpStatus,
          "ErpStatusId" => $ErpStatusId,
          "ErpRejectionReason" => $ErpRejectionReason,
          "Flag" => $data->Flag,
          "refByDropdown" => $data->ref_by_dropdown ?? null,
          'otherReferee' => $data->other_referee ?? null
        );
        $admissiondataary[] = $arrayData;
      }
    } else {
      $admissiondataary = $admissiondata;
    }
    return $admissiondataary;
    // $getDataAdmission = array();
    // $getDataAdmission = DB::Table($db . '.admissions')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('projectcode', $ProjectCode)->where('update_at', '>=', $LastSyncTime)->get();
    // return $getDataAdmission;
  }
  public function Loan_Other_DataSync($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
    $getLoanData = array();
    $LoanRca  = array();
    $bank_info = array();
    $borrower_office_info = array();
    $borrower_passport_visa_details = array();
    $business_info = array();
    $co_borrower_details = array();
    $expenses_info = array();
    $guarantor_details1 = array();
    $housing_business_details = array();
    $income_info = array();
    $information_abroad_resident = array();
    $information_remittances = array();
    $personal_asset_info = array();
    $common_checklist = array();
    $checklist_nirvorota = array();
    $checklist_remitance = array();
    $checklist_trade_agri = array();
    $otherloanInfo = array();
    //echo $BranchCode . "/" . $Pin . "/" . $ProjectCode . "/" . $LastSyncTime;
    //$getLoanData = DB::Table($db . '.loans')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('projectcode', $ProjectCode)->where('update_at', '>=', $LastSyncTime)->get();
    // $LoanRca = DB::select(DB::raw("select * from $db.rca where loan_id in (SELECT id FROM $db.loans where branchcode='$BranchCode' and projectcode='$ProjectCode' and assignedpo='$Pin' and update_at >='$LastSyncTime')")); //DB::Table($db . '.loans')->select('id')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('projectcode', $ProjectCode)->where('update_at', '>=', $LastSyncTime)->get();
    //$LoanRca = DB::Table($db . '.rca')->whereIn('loan_id', $getLoanid)->get();
    //dd($LoanRca);
    $bank_info = DB::Table($db . '.bank_info')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $borrower_office_info = DB::Table($db . '.borrower_office_info')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $borrower_passport_visa_details = DB::Table($db . '.borrower_passport_visa_details')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $business_info = DB::Table($db . '.business_info')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $co_borrower_details = DB::Table($db . '.co_borrower_details')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $expenses_info = DB::Table($db . '.expenses_info')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $guarantor_details1 = DB::Table($db . '.guarantor_details1')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $housing_business_details = DB::Table($db . '.housing_business_details')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $income_info = DB::Table($db . '.income_info')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $information_abroad_resident = DB::Table($db . '.information_abroad_resident')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $information_remittances = DB::Table($db . '.information_remittances')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $personal_asset_info = DB::Table($db . '.personal_asset_info')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $common_checklist = DB::Table($db . '.common_checklist')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $checklist_nirvorota = DB::Table($db . '.checklist_nirvorota')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $checklist_remitance = DB::Table($db . '.checklist_remitance')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $checklist_trade_agri = DB::Table($db . '.checklist_trade_agri')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    $otherloanInfo = DB::Table($db . '.other_loan_information')->where('branchcode', $BranchCode)->where('assignedpo', $Pin)->where('updated_at', '>=', $LastSyncTime)->get();
    return array($bank_info, $borrower_office_info, $borrower_passport_visa_details, $business_info, $co_borrower_details, $expenses_info, $guarantor_details1, $housing_business_details, $income_info, $information_abroad_resident, $information_remittances, $personal_asset_info, $common_checklist, $checklist_nirvorota, $checklist_remitance, $checklist_trade_agri, $otherloanInfo);
  }
  public function TokenCheck()
  {
    //$token = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2NjI1MjM3MTIsImV4cCI6MTY2MjUyNzMxMiwic3ViIjoiUXNvZnQiLCJpc3MiOiJlcnAuYnJhYy5uZXQiLCJqdGkiOiIxXzQzd2M0MWhlbjdjd2cwc2c0czA0NGMwc2NjOHdjazRvIiwiYXVkIjoiMzcuMTExLjIxNS41In0.Peab_VgG3sBaALINiTMFO9E0He-re_d3hXxz8uP0Bj6JqxiBEqq5v3c9cjQ61YyhjTW2gVowmdqfz0ok31PKfX99_3ThYUe6AZj40LqUdvPLS-_UTimjvqaDs2X7j6tA23xpPSn1cnoS9MsfhZ2Iu_vt2Vwbi3V1RLeVZCh1YEM';
    //$token = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2NTQ1NzkwNTgsImV4cCI6MTY1NDU4MjY1OCwic3ViIjoiZGNzLWFwaS10ZXN0IiwiaXNzIjoiYnJhY2FwaXRlc3RpbmcuYnJhYy5uZXQiLCJqdGkiOiJJZWcxTjVXMnFoM2hGMHFTOVpoMndxNmVleDJEQjkzNSIsImF1ZCI6IjEwLjE0MC4wLjEwNSJ9.WCJdKEdZnVDH8nj_zm6W_uYFSqdj7f9O36hu1dUVTJ3gpf-xMbEhwZCk-xYlEUx-ltTN0rYW3cEAXlt_iSuUULIs6RnARl_g43QrsQte7zZOmBhRpQcjVMDvMjD1YP1yqDc3jqvv-oTBvvk_mNGw4u9Ghxpe3diWbNSnkpZOw2s';
    //return $token;
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
      //$clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
      // $clientsecret = '4H2QJ89kYQBStaCuY73h';
      //$clienturl = 'https://bracapitesting.brac.net/oauth/v2/token?grant_type=client_credentials'; //test
      $clienturl = 'https://erp.brac.net/oauth/v2/token?grant_type=client_credentials'; // live
      $header = array( //live 
        'x-client-id:1_43wc41hen7cwg0sg4s044c0scc8wck4o',
        'x-client-secret:654spemp5qckcg4g448044kco4k0g8wwo0440osgwosggwg4'
      );
      /*$header = array( //test
        'x-client-id:Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935',
        'x-client-secret:4H2QJ89kYQBStaCuY73h'
      );*/

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $clienturl);
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
    $db = 'dcs_progoti';
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
  public function ZipFileCreate($db, $Pin, $BranchCode, $Appid, $CurrentTimes, $ProjectCode, $jsonFile)
  {
    $baseurl = '/var/www/html/json/dcs/';
    // echo $baseurl;
    $mainFile = $baseurl . $Pin . 'dcs.zip';
    //echo $mainFile;
    if (is_file($mainFile)) {
      if (!unlink($mainFile)) {
        //   echo ("Error deleting $mainFile");
      }
    }
    $writeFile = $baseurl . $Pin . 'dcsresults.json';
    if (!is_file($writeFile)) {
    }
    $fp = fopen($baseurl . $Pin  . 'dcsresults.json', 'w');
    fwrite($fp, $jsonFile);
    fclose($fp);
    $zip = new ZipArchive;
    if ($zip->open($mainFile, ZipArchive::CREATE) === TRUE) {
      // Add files to the zip file
      $zip->addFile($writeFile, $Pin  . 'dcsresults.json');
      //$zip->addFile('/var/www/html/json/'.$PIN.'transtrail.json',$PIN.'transtrail.json');
      //$zip->addFile('test.pdf', 'demo_folder1/test.pdf');

      // All files are added, so close the zip file.
      $zip->close();
    }
    $message = array("status" => "DCS", "time" => $CurrentTimes, "message" => "Please Download File!!");
    $json2 = json_encode($message);
    Log::info("message-" . $json2);
    echo $json2;
    die;
  }
  public function ConfigurationSync($db, $Pin, $BranchCode, $Appid, $CurrentTimes, $ProjectCode, $token)
  {
    $auth_array = [];
    $branchcode = (int)$BranchCode;
    $projectcode = (int)$ProjectCode;
    $cellingData = '';
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($branchcode != null and $projectcode != null) {
        $Process = DB::Table($db . '.processes')->select('id', 'process')->get();
        $FormConfig = DB::Table($db . '.form_configs')->where('projectcode', $ProjectCode)->get();
        $PayloadData = DB::Table($db . '.payload_data')->where('status', 1)->get();
        $OfficeMapping = DB::Table($db . '.office_mapping')->where('status', 1)->get();
        $ProductDetail = DB::Table($db . '.product_details')->get();
        if ($Appid == 'bmsmerp') {
          $cellingData =  DB::Table($db . '.celing_configs')->where('projectcode', $projectcode)->get();
        }

        //dd($cellingData);
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
        // echo $ProjectCode;
        $auth = DB::Table($db . '.auths')->where('projectcode', $ProjectCode)->where('roleId', '0')->where('prerequisiteprocessid', '!=', '0')->get();
        //dd($auth);
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
          "cellingConfig" => $cellingData,
        );
        return $result;
      } else {
        $message = "Branchcode Or ProjectCode Not Found!";
        $this->CUSTMSG($message);
      }
    } else {
      $message = "Api Key Not Found!";
      $this->CUSTMSG($message);
    }
  }
  public function getSurveys($db, $BranchCode, $ProjectCode, $Pin, $Appid)
  {
    if ($Appid == 'bmsmerp') {
      $surveydata = DB::table($db . '.surveys')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->orderBy('id', 'desc')->get();
    } else {
      $surveydata = DB::table($db . '.surveys')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->where('assignedpo', $Pin)->orderBy('id', 'desc')->get();
    }
    return $surveydata;
  }
  public function CUSTMSG($message)
  {
    echo json_encode(array("status" => "CUSTMSG", "message" => $message));
    die;
  }
  public function MemberLists($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid, $key)
  {
    //dd("Huda");
    $LastSyncTime = '2000-01-01%2012:00:00';
    $serverul = $this->ServerURL();
    $url = $serverul[0];
    $url2 = $serverul[1];
    $memberlist = array();
    if ($Appid == 'bmsmerp') {
      //dd("t");
      $polist = DB::Table($dberp . '.polist')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->where('cono', '!=', $Pin)->where('status', 1)->get();
      //dd($polist);
      if (!$polist->isEmpty()) {

        foreach ($polist as $row) {
          $cono = $row->cono;
          $url4 = $url . "MemberList?BranchCode=$BranchCode&CONo=$cono&ProjectCode=$ProjectCode&UpdatedAt=$LastSyncTime&key=$key&Status=1";
          // echo $url4;
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
          $decode = json_decode($output_colsed);
          $data =  $decode->data;
          if (!empty($data)) {
            $memberlist[] = $data;
          }


          //echo $output_colsed;
        }
      }
      // dd($memberlist);
      return $memberlist;
    } else {
      $url4 = $url . "MemberList?BranchCode=$BranchCode&CONo=$Pin&ProjectCode=$ProjectCode&UpdatedAt=$LastSyncTime&key=$key&Status=2";
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
      $decode = json_decode($output_colsed);
      $data = $decode->data;
      return $data;
    }
  }
  public function PoList($dberp, $BranchCode, $ProjectCode, $Pin)
  {
    $polist = DB::Table($dberp . '.polist')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->where('cono', '!=', $Pin)->where('status', 1)->get();
    if ($polist->isEmpty()) {
      $this->CUSTMSG("Data Not Found This Branch!");
    }
    return $polist;
  }
  public function Bank_List($db, $Pin, $BranchCode, $Appid, $CurrentTimes, $ProjectCode, $token)
  {
    $banklist = DB::table($db . '.bank_name')->get();
    if ($banklist->isEmpty()) {
      $bank = [];
    } else {
      $bank = $banklist;
    }
    return $bank;
  }
  public function Visa_Check(Request $request)
  {
    $serverul = $this->ServerURL();
    $url = $serverul[0];
    $url2 = $serverul[1];
    $checkauth = "https://trendx.brac.net/dcs/api/auth/login";
    $post = [
      'userpin' => '3405',
      'password' => 'brac2022',
    ];
    $visano = Request::get('visano');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $checkauth);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $getdata = json_decode($output);
    $getstatus = $getdata->success;
    //dd($getstatus);
    if ($getstatus == true) {
      $token = $getdata->token;
      if (!empty($token)) {
        $headerss = array(
          "Content-Type: application/json",
          "Authorization: Bearer " . $token
        );
        $CheckVisa = "https://trendx.brac.net/dcs/api/survey/checkVisaNo?visano=$visano";
        //echo $CheckVisa;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $CheckVisa);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerss);
        $outputdataset = curl_exec($ch);
        curl_close($ch);
        return $outputdataset;
      } else {
        $statuss = array("status" => "CUSTMSG", "message" => "Token Not Found From BITS Server!");
        $json = json_encode($statuss);
        echo $json;
        die;
      }
    } else {
      $statuss = array("status" => "CUSTMSG", "message" => $getstatus);
      $json = json_encode($statuss);
      echo $json;
      die;
    }
  }
  public function LoanDataSync($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid)
  {
    //$this->Loan_GetDataForErpStatusCheck($db, $dberp, $BranchCode, $ProjectCode, $Pin, $LastSyncTime, $Appid);
    $dataset = array();
    $loandata = DB::table($db . '.loans')->where('branchcode', $BranchCode)->where('projectcode', $ProjectCode)->where('assignedpo', $Pin)->where('update_at', '>=', $LastSyncTime)->orderBy('id', 'desc')->get();
    //dd($loandata);
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
        $loan_product_name = DB::table($db . '.product_project_member_category')->select('productname', 'productcode')->where('productid', $data->loan_product)->first();

        $grntorRlationClients = $grntorRlationClient->data_name ?? null;
        $investSectors = $investSector->sectorname ?? null;
        $subSectorIds = $subSectorId->subsectorname ?? null;
        $frequencyIds = $frequencyId->frequency ?? null;
        $schemes = $scheme->schemename ?? null;
        $role_names = $role_name->designation ?? null;
        $recieverrole_names = $recieverrole_name->designation ?? null;
        $memberTypeIds = $memberTypeId->categoryname ?? null;
        $loan_product_names = $loan_product_name->productname ?? null;
        $loan_product_code = $loan_product_name->productcode ?? null;
        if ($data->insurn_gender != null) {
          $InsurnGender = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'genderId')->where('data_id', $data->insurn_gender)->first();
          $insurnGender = $InsurnGender->data_name;
        } else {
          $insurnGender = null;
        }
        //dd($insurnGender);
        if ($data->insurn_relation != null) {
          $InsurnRelation = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'relationshipId')->where('data_id', $data->insurn_relation)->first();
          $insurnRelation = $InsurnRelation->data_name;
        } else {
          $insurnRelation = null;
        }
        //dd($insurnRelation);
        if ($data->insurn_mainIDType != null) {
          $insurnMainID = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'cardTypeId')->where('data_id', $data->insurn_mainIDType)->first();
          $insurnMainIDType = $insurnMainID->data_name;
        } else {
          $insurnMainIDType = null;
        }
        // dd($insurnMainIDType);
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
        //Log::info("Token" . $token);
        /* $member = Http::get($url . 'MemberList', [
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
        }*/

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
          "loan_product_id" => $data->loan_product,
          //previous loan_product
          "loan_product_name" => $loan_product_names,
          "loan_product_code" => $loan_product_code,
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
          "Comment" => $dochistory->comment ?? null,
          "ErpStatus" => $ErpStatus,
          "ErpStatusId" => $ErpStatusId,
          "ErpRejectionReason" => $ErpRejectionReason,
          "orgmemno" => $data->orgmemno,
          "microInsurance" => $data->microinsurance,
          "approval_amount" => $data->approval_amount,
          "insurn_insuranceIssueDate" => $data->insurn_issuedate
        );
        //dd("te");
        // $data['loan']=$loanArrayData;
        $rca = DB::table($db . '.rca')->where('loan_id', $data->id)->first();
        if (empty($rca)) {
          //$arrayData['rca'] = [];
        } else {
          $PrimaryEarner = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'primaryEarner')->where('data_id', $rca->primary_earner)->first();
          $bmPrimaryEarner = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'primaryEarner')->where('data_id', $rca->bm_primary_earner)->first();
          if ($bmPrimaryEarner) {
            $bmPrimaryEarnerIs = $bmPrimaryEarner->data_name;
          } else {
            $bmPrimaryEarnerIs = null;
          }
          //  dd($PrimaryEarnername);
          $arrayData['rca'] = array(
            "id" => $rca->id,
            "loan_id" => $rca->loan_id,
            "primary_earner" => $PrimaryEarner->data_name ?? null,
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
            "monthlyincome_spouse_child" => $rca->monthlyincome_spouse_child,
            "po_seasonal_income" => $rca->po_seasonal_income,
            "bm_seasonal_income" => $rca->bm_seasonal_income,
            "po_incomeformfixedassets" => $rca->po_incomeformfixedassets,
            "bm_incomeformfixedassets" => $rca->bm_incomeformfixedassets,
            "po_imcomeformsavings" => $rca->po_imcomeformsavings,
            "bm_imcomeformsavings" => $rca->bm_imcomeformsavings,
            "po_houseconstructioncost" => $rca->po_houseconstructioncost,
            "bm_houseconstructioncost" => $rca->bm_houseconstructioncost,
            "po_expendingonmarriage" => $rca->po_expendingonmarriage,
            "bm_expendingonmarriage" => $rca->bm_expendingonmarriage,
            "po_operation_childBirth" => $rca->po_operation_childBirth,
            "bm_operation_childBirth" => $rca->bm_operation_childBirth,
            "po_foreigntravel" => $rca->po_foreigntravel,
            "bm_foreigntravel" => $rca->bm_foreigntravel
          );
        }

        // $arrayData['clientInfo'] = $member;
        $dataset[] = $arrayData;
      }
    }
    return $dataset;
  }
}
