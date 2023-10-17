<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use view;
use DateTime;
use Illuminate\Support\Facades\Input;

date_default_timezone_set('Asia/Dhaka');

ini_set('memory_limit', '3072M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

use ZipArchive;
use Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use File;
use Illuminate\Support\Facades\Session;
//use App\Http\Controllers\TestingController_Version;
header('Content-Type: application/json; charset=utf-8');

class ApiController extends Controller
{
  private $dberp = 'erptestingserver'; //erp test db
  private $db = 'dcs_progoti';        //dcs db name
  private $Topic = 'progoti';
  public function index2()
  {
    echo "Test";
  }
  public function PoSync(Request $req)
  {
    $this->LaravelLog();
    //echo "Huda";
    // dd("H");
    $db = $this->db;
    $token = Request::input('token');
    $branchcode = Request::input('branchcode');
    $ProjectCode = Request::input('projectcode');
    $project_code = Request::input('projectcode');
    $auth_array = [];
    $branchcode = (int)$branchcode;
    $projectcode = (int)$ProjectCode;
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($branchcode != null and $projectcode != null) {
        $Process = DB::Table($db . '.processes')->select('id', 'process')->get();
        $FormConfig = DB::Table($db . '.form_configs')->where('projectcode', $project_code)->get();
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
        $result = array("status" => "E", "message" => "Invalid perameter!");
        return json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function OperationsDataSync(Request $req)
  {
    $token = Request::input('token');
    $branch_code = Request::input('branchcode');
    $branchcode = str_pad($branch_code, 4, "0", STR_PAD_LEFT);
    $pin = Request::input('pin');
    $project_code = Request::input('projectcode');
    $projectcode = str_pad($project_code, 3, "0", STR_PAD_LEFT);

    $this->GetErpPostedAdmissionData($branchcode); //erp dcs admission data sync 
    $this->GetErpPostedLoanData($branchcode); //erp dcs Loan data sync


    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($branchcode != null and $projectcode != null) {
        $SurveyDatas = $this->getSurveys($branchcode, $projectcode, $pin);
        $AdmissionDatas = $this->getAdmissions($branchcode, $projectcode, $pin);
        $LoanRcaDatas = $this->getLoanRcas($branchcode, $projectcode, $pin);

        $result = array(
          "status" => "S",
          "message" => "",
          "SurveyDatas" => $SurveyDatas,
          "AdmissionDatas" => $AdmissionDatas,
          "LoanRcaDatas" => $LoanRcaDatas,
        );
        return json_encode($result);
      } else {
        $result = array("status" => "E", "message" => "Invalid perameter!");
        return json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function getSurveys($branchcode, $projectcode, $pin)
  {
    $db = $this->db;
    if ($branchcode != null and $pin == null) {
      $surveydata = DB::table($db . '.surveys')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->orderBy('id', 'desc')->get();
    } elseif ($branchcode != null and $pin != null) {
      $surveydata = DB::table($db . '.surveys')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->orderBy('id', 'desc')->get();
    }
    return $surveydata;
  }

  public function getAdmissions($branchcode, $projectcode, $pin)
  {
    $this->LaravelLog();
    $db = $this->db;

    if ($pin == null) {
      // $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->orderBy('id', 'desc')->get();
      $admissionsDataWithoutPending = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'))->orderBy('id', 'desc');
      $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->Where('status', '1')->orderBy('id', 'desc')->unionAll($admissionsDataWithoutPending)->orderBy('id', 'desc')->get();
    } elseif ($pin != null) {
      $admissionsDataWithoutPending = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'))->orderBy('id', 'desc');
      $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->Where('status', '1')->orderBy('id', 'desc')->unionAll($admissionsDataWithoutPending)->orderBy('id', 'desc')->get();
      // dd($admissiondata, $branchcode, $projectcode, $pin);
      // $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('assignedpo', $pin)->orderBy('id', 'desc')->get();
      // $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('assignedpo', $pin)->orWhere('status', 1)->orWhere(function ($query) {
      // 	$query->where('updated_at', '<=', Carbon::now()->subMonth(2)->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now());
      // })->orderBy('id', 'desc')->get();

    }
    if ($admissiondata->isEmpty()) {
      return $admissiondata;
    } else {
      //dd($data->MaritalStatusId);
      foreach ($admissiondata as $data) {
        // dd($data->MaritalStatusId);
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
        $role_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $data->roleid)->first();
        $recieverrole_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $data->reciverrole)->first();
        $dochistory = DB::table($db . '.document_history')->select('comment')->where('id', $data->dochistory_id)->first();
        $status = DB::table($db . '.status')->select('status_name')->where('status_id', $data->status)->first();
        $presentUpazilaId = DB::table($db . '.office_mapping')->select('thana_name')->where('thana_id', $data->presentUpazilaId)->first();
        $parmanentUpazilaId = DB::table($db . '.office_mapping')->select('thana_name')->where('thana_id', $data->parmanentUpazilaId)->first();
        // $PresentDistrict = DB::table($db . '.office_mapping')->select('district_name')->where('district_id', $data->PresentDistrictId)->first();


        $WalletOwner = $WalletOwner->data_name ?? null;
        $NomineeNidType = $NomineeNidType->data_name ?? null;
        $SpuseOccupationId = $SpuseOccupationId->data_name ?? null;
        $SpouseCardType = $SpouseCardType->data_name ?? null;
        $OtherIdTypeId = $OtherIdTypeId->data_name ?? null;
        $presentUpazila = $presentUpazilaId->thana_name ?? null;
        $parmanentUpazilaId = $parmanentUpazilaId->thana_name ?? null;
        $MainIdTypeIds = $MainIdTypeId->data_name ?? null;
        $EducationIds = $EducationId->data_name ?? null;
        $MaritalStatusIds = $MaritalStatusId->data_name ?? null;
        $RelationshipIds = $RelationshipId->data_name ?? null;
        $Occupations = $Occupation->data_name ??  null;
        $genderIds = $genderId->data_name ?? null;
        $PrimaryEarners = $PrimaryEarner->data_name ?? null;
        $MemberCateogryIds = $MemberCateogryId->categoryname ?? null;
        $role_names = $role_name->designation ?? null;
        $recieverrole_names = $recieverrole_name->designation ?? null;
        $statuss = $status->status_name ?? null;

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
          $checkPostedAdmission = DB::table($db . '.posted_admission')->where('admission_id', $data->entollmentid)->first();
          if ($checkPostedAdmission != null) {
            $ErpStatusId = $checkPostedAdmission->statusid;
            if ($ErpStatusId == 1) {
              $ErpStatus = 'Pending';
            } elseif ($ErpStatusId == 2) {
              $ErpStatus = 'Approved';
            } elseif ($ErpStatusId == 3) {
              $ErpStatus = 'Rejected';
            }
            $ErpRejectionReason = $checkPostedAdmission->rejectionreason;
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
        $created_at = date('Y-m-d', strtotime($data->created_at));
        $updated_at = date('Y-m-d', strtotime($data->updated_at));

        $arrayData = array(
          "id" => $data->id,
          "IsRefferal" => $data->IsRefferal,
          "RefferedById" => $data->RefferedById,
          "MemberId" => $data->MemberId,
          "MemberCateogryId" => $data->MemberCateogryId,
          "MemberCateogry" => $MemberCateogryIds,
          "ApplicantsName" => $data->ApplicantsName,
          "ApplicantSinglePic" => $data->ApplicantSinglePic,
          "MainIdType" => $MainIdTypeIds,
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
          "Education" => $EducationIds,
          "EducationId" => $data->EducationId,
          "Phone" => $data->Phone,
          "PresentAddress" => $data->PresentAddress,
          "presentUpazilaId" => $data->presentUpazilaId,
          "presentUpazila" => $presentUpazila,
          "PermanentAddress" => $data->PermanentAddress,
          "parmanentUpazilaId" => $data->parmanentUpazilaId,
          "PresentDistrictId" => $data->PresentDistrictId,
          // "PresentDistrict" => $PresentDistrictId,
          "PermanentDistrictId" => $data->PermanentDistrictId,
          // "PermanentDistrict" => $PermanentDistrict,
          "parmanentUpazila" => $parmanentUpazilaId,
          "MaritalStatusId" => $data->MaritalStatusId,
          "MaritalStatus" => $MaritalStatusIds,
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
          "Relationship" => $RelationshipIds,
          "ApplicantCpmbinedImg" => $data->ApplicantCpmbinedImg,
          "ReffererImg" => $data->ReffererImg,
          "ReffererIdImg" => $data->ReffererIdImg,
          "FrontSideOfIdImg" => $data->FrontSideOfIdImg,
          "BackSideOfIdimg" => $data->BackSideOfIdimg,
          "NomineeIdImg" => $data->NomineeIdImg,
          "DynamicFieldValue" => $data->DynamicFieldValue,
          "created_at" => $created_at,
          "updated_at" => $updated_at,
          "branchcode" => $data->branchcode,
          "projectcode" => $data->projectcode,
          "Occupation" => $Occupations,
          "OccupationId" => $data->Occupation,
          "IsBkash" => $IsBkash,
          "WalletNo" => $data->WalletNo,
          "WalletOwnerId" => $data->WalletOwner,
          "WalletOwner" => $WalletOwner,
          "NomineeName" => $data->NomineeName,
          "PrimaryEarner" => $PrimaryEarners,
          "PrimaryEarnerId" => $data->PrimaryEarner,
          "dochistory_id" => $data->dochistory_id,
          "roleid" => $data->roleid,
          "pin" => $data->pin,
          "action" => $data->action,
          "reciverrole" => $data->reciverrole,
          "status" => $statuss,
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
          "Gender" => $genderIds,
          "SavingsProductId" => $data->SavingsProductId,
          "role_name" => $role_names,
          "reciverrole_name" => $recieverrole_names,
          "SurveyId" => $data->surveyid,
          "Comment" => $dochistory->comment,
          "ErpStatus" => $ErpStatus,
          "ErpStatusId" => $ErpStatusId,
          "ErpRejectionReason" => $ErpRejectionReason,
          "Flag" => $data->Flag,
          "NomineeIdExpiredate" => $data->NomineeIdExpiredate,
          "NomineeIdPlaceOfissue" => $data->NomineeIdPlaceOfissue,
        );
        $admissiondataary[] = $arrayData;
      }
    }
    return $admissiondataary;
  }

  public function getLoanRcas($branchcode, $projectcode, $pin)
  {
    $this->LaravelLog();
    $db = $this->db;
    $dberp = $this->dberp;
    $todate = date('Y-m-d');
    $fromdate = date('Y-m-d', strtotime($todate . ' - 10 days'));
    Log::info("fromdate" . $fromdate);
    if ($pin == null) {
      // $loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->orderBy('id', 'desc')->get();
      // $loansDataWithoutPending = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'))->orderBy('id', 'desc');
      // $loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->Where('status', '1')->orderBy('id', 'desc')->unionAll($loansDataWithoutPending)->orderBy('id', 'desc')->get();
      // $loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate' order by id desc"));
      $loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate'  order by id desc"));
    } elseif ($pin != null) {
      // $loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->orderBy('id', 'desc')->get();
      //$loansDataWithoutPending = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'))->orderBy('id', 'desc');
      //$loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->Where('status', '1')->orderBy('id', 'desc')->unionAll($loansDataWithoutPending)->orderBy('id', 'desc')->get();
      // $loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and assignedpo='$pin' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate' order by id desc"));
      $loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and assignedpo='$pin' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate' order by id desc"));
    }

    if (empty($loandata)) {
      return $loandata;
    } else {
      foreach ($loandata as $data) {
        $grntorRlationClient = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'relationshipId')->where('data_id', $data->grntor_rlationClient)->first();
        $investSector = DB::table($db . '.schemem_sector_subsector')->select('sectorname')->where('sectorid', $data->invest_sector)->first();
        $subSectorId = DB::table($db . '.schemem_sector_subsector')->select('subsectorname')->where('subsectorid', $data->subSectorId)->first();
        $frequencyId = DB::table($db . '.product_details')->select('frequency')->where('frequencyid', $data->frequencyId)->first();
        $scheme = DB::table($db . '.schemem_sector_subsector')->select('schemename')->where('schemeid', $data->scheme)->first();
        $role_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $data->roleid)->first();
        $recieverrole_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $data->reciverrole)->first();
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
        // if ($data->status == '2') {
        // 	$checkPostedLoan = DB::table($db . '.posted_loan')->where('loan_id', $data->loan_id)->first();
        // 	if ($checkPostedLoan != null) {
        // 		$ErpStatusId = $checkPostedLoan->loanproposalstatusid;
        // 		if ($ErpStatusId == 1) {
        // 			$ErpStatus = 'Pending';
        // 		} elseif ($ErpStatusId == 2) {
        // 			$ErpStatus = 'Approved';
        // 		} elseif ($ErpStatusId == 3) {
        // 			$ErpStatus = 'Rejected';
        // 		}
        // 		$ErpRejectionReason = $checkPostedLoan->rejectionreason;
        // 	}
        // } else {
        // 	$ErpStatus = null;
        // 	$ErpStatusId = null;
        // 	$ErpRejectionReason = null;
        // }


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
    }
    return $dataset;
  }

  public function Index(Request $req)
  {
    $baseUrl = url('');
    $projectCode = json_decode($projectCode);
    $projectCode = date('Y-m-d', strtotime($projectCode->asd)) ?? null;
    // $projectCode = $projectCode->asd;
    //echo "Huda";
    //dd("H");
    $db = $this->db;
    $projectCode = Request::input('projectcode');
    $Approver = Request::input('approver');
    $GrowthRate = Request::input('growthrate');
    $apikey = Request::input('apikey');
    $json = DB::Table($db . '.celing_configs')->where('projectcode', $projectCode)->where('approver', $Approver)->where('growth_rate', $GrowthRate)->get();
    if ($json->isEmpty()) {
      $result = array("status" => "E", "message" => "Data Not Found!");
      echo json_encode($result);
    } else {
      $result = array("status" => "S", "message" => "", "data" => $json);
      echo json_encode($result);
    }
  }

  public function Delete_All(Request $req)
  {
    $dbs = $this->db;
    //dd($eventid);
    DB::select(DB::raw("Delete from $dbs.admissions")); //DB::table('mnw_progoti.respondents')->where('eventid',$eventid)->delete();//"Delete from mnw_progoti.respondents";
    DB::select(DB::raw("Delete from $dbs.loans")); //DB::table('mnw_progoti.survey_data')->where('event_id',$eventid)->delete();//"Delete from mnw_progoti.survay_data";
    DB::select(DB::raw("Delete from $dbs.rca"));
    DB::select(DB::raw("Delete from $dbs.message_ques"));
    DB::select(DB::raw("Delete from $dbs.document_history"));
    echo "Delete Successfully";
  }

  public function erpVOList(Request $req)
  {
    $dberp = $this->dberp;
    $db = $this->db;
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
    $BranchCode = Request::get('BranchCode');
    $PIN = Request::get('PIN');
    $ProjectCode = Request::get('ProjectCode');
    $UpdatedAt = Request::get('UpdatedAt');
    $key = Request::get('key');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      $url4 = $url . "VOList?BranchCode=$BranchCode&PIN=$PIN&ProjectCode=$ProjectCode&UpdatedAt=$UpdatedAt&key=$key";
      // dd($url);
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

      return $output_colsed;
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function LastOneCloseLoanBehavior()
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
      Log::info("LastOneCloseInfo" . $url);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $output_colsed = curl_exec($ch);
      curl_close($ch);
      //dd("H");
      echo $output_colsed;
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

  public function erpSavingsInfo(Request $req)
  {
    $this->LaravelLog();
    $dberp = $this->dberp;
    $db = $this->db;
    $BranchCode = Request::get('BranchCode');
    $PIN = Request::get('PIN');
    $ProjectCode = Request::get('ProjectCode');
    $CONo = Request::get('CONo');
    $UpdatedAt = Request::get('UpdatedAt');
    $key = Request::get('key');
    $Status = Request::get('Status');
    $OrgNo = Request::get('OrgNo');
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
    $dataset = [];
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      $url3 = $url . "SavingsInfo?BranchCode=$BranchCode&CONo=$CONo&ProjectCode=$ProjectCode&UpdatedAt=$UpdatedAt&key=$key&Status=$Status";
      // dd($url);
      $url3 = str_replace(" ", '%20', $url3);
      $headers = array(
        'Accept: application/json',
      );

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url3);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $output_colsed = curl_exec($ch);
      curl_close($ch);

      $savingsInfo = json_decode($output_colsed);
      $data = $savingsInfo->data;

      if (!empty($data)) {
        foreach ($data as $row) {
          $posted_admission = DB::Table($db . '.admissions')->select('ApplicantCpmbinedImg')->where('MemberId', $row->OrgMemNo)->first();
          $array['OrgNo'] = $row->OrgNo;
          $array['OrgMemNo'] = $row->OrgMemNo;
          $array['ProjectCode'] = $row->ProjectCode;
          $array['BranchCode'] = $row->BranchCode;
          $array['MemberName'] = $row->MemberName;
          $array['MemberImage'] = $posted_admission->ApplicantCpmbinedImg ?? null;
          $array['SavBalan'] = $row->SavBalan;
          $array['SavPayable'] = $row->SavPayable;
          $array['CalcIntrAmt'] = $row->CalcIntrAmt;
          $array['TargetAmtSav'] = $row->TargetAmtSav;
          $array['ApplicationDate'] = $row->ApplicationDate;
          $array['NationalId'] = $row->NationalId;
          $array['FatherName'] = $row->FatherName;
          $array['MotherName'] = $row->MotherName;
          $array['SpouseName'] = $row->SpouseName;
          $array['ContactNo'] = $row->ContactNo;
          $array['BkashWalletNo'] = $row->BkashWalletNo;
          $array['AssignedPO'] = $row->AssignedPO;
          $array['UpdatedAt'] = $row->UpdatedAt;
          $dataset[] = $array;
        }
        $response = array("code" => 200, "data" => $dataset);
        return json_encode($response);
      } else {
        return $output_colsed;
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  //admission member erp posting
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
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    Log::info("Instalment" . $httpcode . "/" . $response);
    $data_array = json_decode($response);
    if ($httpcode != '200') {
      $instamentarray = array("status" => $httpcode, "projectCode" => '', "loanProductCode" => "", "noOfInstallment" => "", "proposalDurationInMonths" => "", "proposedLoanAmount" => 0, "frequencyId" => 0, 'loanInstallment' => 0);
      $response = json_encode($instamentarray);
    } else {
      $instamentarray = array("status" => $httpcode, "projectCode" => $data_array->projectCode, "loanProductCode" => $data_array->loanProductCode, "noOfInstallment" => $data_array->noOfInstallment, "proposalDurationInMonths" => $data_array->proposalDurationInMonths, "proposedLoanAmount" => $data_array->proposedLoanAmount, "frequencyId" => $data_array->frequencyId, "loanInstallment" => $data_array->loanInstallment);
      $response = json_encode($instamentarray);
    }

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

  public function dcsInsurancePremiumCalculation(Request $request)
  {
    $db = $this->db;
    $loanProductId = '';
    //$json = json_encode(Request::all());
    $projectCode = Request::get('projectCode');
    $LoanProduct = Request::get('loanProductCode');
    $policyType = Request::get('policyType');
    $proposalDurationInMonths = Request::get('proposalDurationInMonths');
    $proposedLoanAmount = Request::get('proposedLoanAmount');
    //$premiumAmount = Request::get('premiumAmount');
    $LoanProductCode1 = $this->Insurance_ProductId($db, $loanProductId, $projectCode, $LoanProduct);
    if ($LoanProductCode1 != '') {
      $insuranceProductId = $LoanProductCode1;
    } else {
      $insuranceProductId = null;
    }
    $arraydataset = array("projectCode" => $projectCode, "loanProductCode" => $LoanProduct, "policyType" => $policyType, "proposalDurationInMonths" => $proposalDurationInMonths, "proposedLoanAmount" => $proposedLoanAmount, "insuranceProductId" => $insuranceProductId);
    $json = json_encode($arraydataset);
    Log::info("Request Premium Calculation" . $json);
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
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    Log::info("Premium_Code" . $httpcode . "/" . $response);
    $err = curl_error($curl);

    curl_close($curl);
    $data_arrays = json_decode($response);
    if ($httpcode == '200') {
      if ($data_arrays == null) {
        $premiumarray = array("status" => $httpcode, "projectCode" => "", "loanProductCode" => "", "policyType" => "", "proposalDurationInMonths" => "", "proposedLoanAmount" => "", "insuranceProductId" => "", "premiumAmount" => "");
        $response = json_encode($premiumarray);
      } else {
        $premiumarray = array("status" => $httpcode, "projectCode" => $data_arrays->projectCode, "loanProductCode" => $data_arrays->loanProductCode, "policyType" => $data_arrays->policyType, "proposalDurationInMonths" => $data_arrays->proposalDurationInMonths, "proposedLoanAmount" => $data_arrays->proposedLoanAmount, "insuranceProductId" => $data_arrays->insuranceProductId, "premiumAmount" => $data_arrays->premiumAmount);
        $response = json_encode($premiumarray);
      }
    } else {
      $premiumarray = array("status" => $httpcode, "projectCode" => "", "loanProductCode" => "", "policyType" => "", "proposalDurationInMonths" => "", "proposedLoanAmount" => "", "insuranceProductId" => "", "premiumAmount" => "");
      $response = json_encode($premiumarray);
    }

    Log::info("Premium Calculation" . $response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      return $response;
    }
  }

  public function CelingConfig(Request $request)
  {
    //dd("H");
    $db = $this->db;
    $projectCode = Request::input('projectcode');
    $branchcode = Request::input('branchcode');
    $Approver = Request::input('approver');
    $apikey = Request::input('apikey');
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      $BranchGrowthType = DB::Table($db . '.project_wise_branch_growth_types')->where('project_code', (int)$projectCode)->where('office_code', $branchcode)->first();
      if ($BranchGrowthType != null) {
        $GrowthRate = $BranchGrowthType->branch_growth_type;
        $json = DB::Table($db . '.celing_configs')->where('projectcode', (int)$projectCode)->where('approver', $Approver)->where('growth_rate', $GrowthRate)->get();

        if ($json->isEmpty()) {
          $result = array("status" => "E", "message" => "Data Not Found!");
          echo json_encode($result);
        } else {
          $result = array("status" => "S", "message" => "", "data" => $json);
          echo json_encode($result);
        }
      } else {
        $result = array("status" => "E", "message" => "Branch Growth type Not Found!");
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function GetConfig(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $projectCode = Request::input('projectcode');
    $appid = Request::input('appid');
    $updatedat = Request::input('LastSynctime');
    //$new_timestamp=strtotime("-12 hour 30 minute", $source_timestamp);
    $apikey = Request::input('apikey');
    $formconfig = Request::input('formconfig');
    //$json = DB::Table($db.'.form_configs')->where('projectcode',$projectCode)->where('created_at','>=',$updatedat)->get();
    $json = DB::Table($db . '.form_configs')->where('projectcode', $projectCode)->get();
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($json->isEmpty()) {
        $result = array("status" => "E", "message" => "Data Not Found!");
        echo json_encode($result);
      } else {
        foreach ($json as $row) {
          $id = $row->id;
          $projectCode = $row->projectcode;
          $formid = $row->formID;
          $grouplabel = $row->groupLabel;
          $lebel =  $row->lebel;
          if (!empty($lebel)) {
            $lbl = json_decode($lebel);
            $enlbl = $lbl->english;
            $enlbn = $lbl->bangla;
          }

          $datatype = $row->dataType;
          $columntype = $row->columnType;
          $displayorder = $row->displayOrder;
          $status = $row->status;
          $groupno = $row->groupNo;
          $createdby = $row->createdby;
          $created_at = $row->created_at;
          $updated_at = $row->updated_at;
          $loanProduct = $row->loanProduct;
          // $getdatatye = DB::select(DB::raw("select * from $db.popup_models where label='$enlbl' and datatype='$datatype'"));
          // if (empty($getdatatye)) {
          // 	$values = '';
          // } else {
          // 	$values = $getdatatye[0]->values;
          // }
          $jsnarray[] = array(
            "id" => $id, "projectcode" => $projectCode, "formID" => $formid, "loanProduct" => $loanProduct, "groupLabel" => $grouplabel, "lebel" => $lebel, "values" => $row->values, "dataType" => $datatype, "columnType" => $columntype,
            "displayOrder" => $displayorder, "status" => $status, "groupNo" => $groupno, "createdby" => $createdby, "created_at" => $created_at, "updated_at" => $updated_at, 'captions' => $row->captions
          );
        }
        //echo json_encode($jsnarray);
        // $result = array("status" => "S", "message" => "", "data" => $jsnarray);
        $result = array("status" => "S", "message" => "", "data" => $json);
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }
  public function Auth(Request $request)
  {
    $db = $this->db;
    $projectCode = Request::input('projectcode');
    $appid = Request::input('appid');
    $processId = Request::input('processId');
    $apikey = Request::input('apikey');
    $json = DB::Table($db . '.auths')->where('projectcode', $projectCode)->where('processId', $processId)->get();
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($json->isEmpty()) {
        $result = array("status" => "E", "message" => "Data Not Found!");
        echo json_encode($result);
      } else {
        $result = array("status" => "S", "message" => "", "data" => $json);
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function NIDVerification(Request $req)
  {
    $db = $this->db;
    $appid = Request::input('appid');
    $apikey = Request::input('apikey');
    $nid =  Request::input('nid');
    $nidverificationcheck = DB::Table($db . '.nids')->where('nidno', $nid)->get();
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($nidverificationcheck->isEmpty()) {
        $result = array("status" => "E", "message" => "Data Not Found!");
        echo json_encode($result);
      } else {
        $result = array("status" => "S", "message" => "", "data" => $nidverificationcheck);
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function ImageUpload(Request $req)
  {
    $db = $this->db;
    $appid = Request::input('appid');
    $apikey = Request::input('apikey');
    $image = Request::input('file');
    $uploaddir = '/var/www/html/uploads/';
    $baseurl = 'http://35.194.177.21/uploads/';
    $time = date('Y-m-d h:i:s');
    $uploadfile = $uploaddir . $time . basename($_FILES['file']['name']);
    $responsefile = $baseurl . $time . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
      $result = array("status" => "S", "message" => "", "data" => $responsefile);
      echo json_encode($result);
    } else {
      $result = array("status" => "E", "message" => "Failed Upload");
      echo json_encode($result);
    }
  }

  public function SurveyStore(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    // $json = '{"token":"xxxxxxxxx","appid":"bmsm","data":[{"entollmentid":"12321","projectcode":"015","voCode":"123","branch_code":"321",
    // 	"client_name":"Rz Tutul","mainid_type":"Smart ID","mainid_number":"123456789","phone":"01726553589","status":"Potential","label":"High","fdate":"June 28, 2021","reffered_by":"tutul"}],"extra":[{"fieldName":"Location","fieldType":"input","fieldValue":"Mirpur-2"},{"fieldName":"City","fieldType":"input","fieldValue":"Dhaka"}]
    // 	}';
    $json = Request::input('json');
    Log::channel('daily')->info('Survey Data: ' . $json);
    $dataset = json_decode($json);
    // $token = $dataset->token;
    // $appid = $dataset->appid;
    $data = $dataset->data[0];
    $dynamicfieldvalue = $dataset->extra;
    $projectcode = $data->projectcode;
    $projectcode = str_pad($projectcode, 3, "0", STR_PAD_LEFT);
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      $entollmentid = $data->entollmentid;
      $branchcode = $data->branch_code;
      $branchcode = str_pad($branchcode, 4, "0", STR_PAD_LEFT); //
      $name = $data->client_name;
      //$mainidtypeid = $data->mainid_type;
      //$idno = $data->mainid_number;
      $phone = $data->phone;
      $status = $data->status;
      //$label = $data->label;
      $targetdate = $data->fdate;
      $targetdate = date_create($targetdate);
      $targetdate = date_format($targetdate, "Y-m-d");
      $assignedpo = $data->pin;
      //$refferdbyid = $data->reffered_by;
      $orgno = $data->vo_code;
      if ($dynamicfieldvalue == '') {
        $dynamicfieldvalue = null;
      }
      $survey_status = $data->survey_status;
      $houseName = $data->houseName;
      $roadNo = $data->roadNo;
      $union = $data->union;
      // $relation = $data->relation;
      $productType = $data->productType;
      // $fatherSpouseName = $data->fatherSpouseName;
      $expectedLoanAmount = $data->expectedLoanAmount;
      //$villageArea = $data->villageArea;
      $remarks = $data->remarks;
      $currentDate = $data->currentDate;

      //DB::Table($db . '.surveys')->insert(['entollmentid' => $entollmentid, 'name' => $name, 'mainidtypeid' => $mainidtypeid, 'idno' => $idno, 'phone' => $phone, 'status' => $status, 'label' => $label, 'targetdate' => $targetdate, 'refferdbyid' => $refferdbyid, 'dynamicfieldvalue' => $dynamicfieldvalue, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'orgno' => $orgno, 'survey_status' => $survey_status, 'housename' => $houseName, 'road_no' => $roadNo, 'union' => $union, 'relation' => $relation, 'producttype' => $productType, 'father_spouse_name' => $fatherSpouseName, 'expected_loan_amount' => $expectedLoanAmount, 'village_area' => $villageArea, 'remarks' => $remarks, 'survey_date' => $currentDate]);
      DB::Table($db . '.surveys')->insert(['entollmentid' => $entollmentid, 'name' => $name,  'phone' => $phone, 'status' => $status, 'targetdate' => $targetdate,  'dynamicfieldvalue' => $dynamicfieldvalue, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'orgno' => $orgno, 'survey_status' => $survey_status, 'housename' => $houseName, 'road_no' => $roadNo, 'union' => $union, 'producttype' => $productType, 'expected_loan_amount' => $expectedLoanAmount, 'remarks' => $remarks, 'survey_date' => $currentDate]);

      $result = array("status" => "S", "message" => "Data send to server");
      echo json_encode($result);
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
    // if($token=='xxxxxxxxx'){
    // if($projectcode=='015'){

    // }else{
    // 	$result = array("status"=>"E","message"=>"","Please check project");
    // 	echo json_encode($result);
    // }
    // }else{
    // 	$result = array("status"=>"E","message"=>"","Unauthorized Request");
    // 	echo json_encode($result);
    // }

  }
  public function AdmissionStore1(Request $request)
  {
  }
  public function AdmissionStore(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $baseUrl = url('');
    $json = Request::input('json');
    $currentTime = date('Y-m-d h:i:s');
    Log::channel('daily')->info('Admission Data: ' . $json);
    $dataset = json_decode($json);
    // $token = $dataset->token;
    $Flag = $dataset->flag;
    // $appid=$dataset->appid;
    $data = $dataset->data[0];
    $dynamicfieldvalue = $dataset->extra;
    $projectcode = $data->project_code;
    $projectcode = str_pad($projectcode, 3, "0", STR_PAD_LEFT);
    $branchcode = str_pad($data->branch_code, 4, "0", STR_PAD_LEFT); //
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {

      /*$checkAdmission = DB::Table($db . '.admissions')->where('orgno', $data->vo_code)->where('assignedpo', $data->pin)->where('MemberId', $data->erp_mem_id)->where('status', '!=', 2)->orWhere('ErpStatus', '!=', 2)->get();
      //dd($checkAdmission);
      $checkAdmissioncount = DB::Table($db . '.admissions')->where('branchcode',$branchcode)->where('projectcode',$productcode)->where('orgno', $data->vo_code)->where('assignedpo', $data->pin)->where('MemberId', $data->erp_mem_id)->where('status', '!=', 2)->orWhere('ErpStatus', '!=', 2)->count();
      if (!$checkAdmission->isEmpty()) {
        $ErpStatus = $checkAdmission[0]->ErpStatus;
        $status = $checkAdmission[0]->reciverrole;
        $flag = $checkAdmission[0]->Flag;
        if ($flag == '2') {
          if ($ErpStatus == '1' or $status != '0') {
            //$message = "প্রোফাইল আপডেটের অনুরোধ  $checkAdmissioncount টি পেন্ডিং রয়েছে। অনুগ্রহ করে পূর্ববর্তী অনুরোধটি প্রত্যাখ্যান করুন বা BM কে অনুমোদন করতে বলুন এবং এরপরে নতুন করে প্রোফাইল আপডেটের অনুরোধ করুন।";
            $message = "উক্ত সদস্যের $checkAdmissioncount টি প্রোফাইল আপডেট অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় প্রোফাইল আপডেটের জন্য অনুরোধ করুন।";
            $result = array("status" => "E", "message" => $message);
            echo json_encode($result);
            die;
          }
        }
      }*/
      if($projectcode=='060')
      {
        if ($Flag == '2') {
          //$checkAdmissioncount = DB::Table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('orgno', $data->vo_code)->where('assignedpo', $data->pin)->where('MemberId', $data->erp_mem_id)->where('entollmentid', $data->enroll_id)->where('status', '!=', 2)->orWhere('ErpStatus', '!=', 2)->count();
          $checkAdmissioncount = DB::select(DB::raw("select count(*) from $db.admissions where branchcode='$branchcode' and projectcode='$projectcode' and assignedpo='$data->pin' and erp_member_id='$data->erp_mem_id' and entollmentid != '$data->enroll_id' and (status not in('2','3') or erpstatus not in('2','3'))"));
          $checkAdmissioncount1 = $checkAdmissioncount[0]->count;
          if ($checkAdmissioncount1 > 0) {
            //$message = "উক্ত সদস্যের $checkAdmissioncount1 টি প্রোফাইল আপডেট অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় প্রোফাইল আপডেটের জন্য অনুরোধ করুন।";
            $message = "দুঃখিত! এই সদস্যের প্রোফাইল আপডেট পেন্ডিং আছে।\n•	অনুগ্রহ করে পূর্বের প্রোফাইল আপডেট রিকুয়েস্ট সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন/রিজেক্ট/সেন্ডব্যাক করতে হবে।\n•	প্রোফাইল আপডেট স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন/প্রোফাইল আপডেট রিকুয়েস্ট সাবমিট করুন। সংগ্রহকৃত সকল তথ্য ড্রাফটে সেভ হয়ে থাকবে।\n•	প্রয়োজনে সেন্ডব্যাককৃত রিকুয়েস্টে পুনরায় কাজ করুন, নতুন করে রিকুয়েস্ট করার প্রয়োজন নেই।\n•	শুধুমাত্র রিজেক্টকৃত রিকুয়েস্টের জন্য নতুন করে প্রোফাইল আপডেট রিকুয়েস্ট করুন।";
            $result = array("status" => "DEL", "message" => $message);
            echo json_encode($result);
            die;
          }
        }
      }
      else
      {
        if ($Flag == '2') {
          //$checkAdmissioncount = DB::Table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('orgno', $data->vo_code)->where('assignedpo', $data->pin)->where('MemberId', $data->erp_mem_id)->where('entollmentid', $data->enroll_id)->where('status', '!=', 2)->orWhere('ErpStatus', '!=', 2)->count();
          $checkAdmissioncount = DB::select(DB::raw("select count(*) from $db.admissions where branchcode='$branchcode' and projectcode='$projectcode' and orgno='$data->vo_code' and assignedpo='$data->pin' and erp_member_id='$data->erp_mem_id' and entollmentid != '$data->enroll_id' and (status not in('2','3') or erpstatus not in('2','3'))"));
          $checkAdmissioncount1 = $checkAdmissioncount[0]->count;
          if ($checkAdmissioncount1 > 0) {
            //$message = "উক্ত সদস্যের $checkAdmissioncount1 টি প্রোফাইল আপডেট অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় প্রোফাইল আপডেটের জন্য অনুরোধ করুন।";
            $message = "দুঃখিত! এই সদস্যের প্রোফাইল আপডেট পেন্ডিং আছে।\n•	অনুগ্রহ করে পূর্বের প্রোফাইল আপডেট রিকুয়েস্ট সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন/রিজেক্ট/সেন্ডব্যাক করতে হবে।\n•	প্রোফাইল আপডেট স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন/প্রোফাইল আপডেট রিকুয়েস্ট সাবমিট করুন। সংগ্রহকৃত সকল তথ্য ড্রাফটে সেভ হয়ে থাকবে।\n•	প্রয়োজনে সেন্ডব্যাককৃত রিকুয়েস্টে পুনরায় কাজ করুন, নতুন করে রিকুয়েস্ট করার প্রয়োজন নেই।\n•	শুধুমাত্র রিজেক্টকৃত রিকুয়েস্টের জন্য নতুন করে প্রোফাইল আপডেট রিকুয়েস্ট করুন।";
            $result = array("status" => "DEL", "message" => $message);
            echo json_encode($result);
            die;
          }
        }
      }
      
      //dd("Huda");
      $roleid = 0;
      $reciverrole = 1;
      $status = 1;
      $orgno = $data->vo_code; //
      $entollmentid = $data->enroll_id;
      //dd($entollmentid);
      $MemberId = $data->erp_mem_id;
      $pin = $data->pin;
      $assignedpo = $data->pin;
      $branchcode = $data->branch_code;
      $branchcode = str_pad($branchcode, 4, "0", STR_PAD_LEFT); //
      $IsRefferal = $data->is_ref;
      $RefferedById = $data->refby;
      $ReffererName = $data->refname;
      $ReffererPhone = $data->refphone;
      $MemberCateogryId = $data->mem_category;
      $ApplicantsName = $data->applicant_name;
      $MainIdTypeId = $data->mainid_type;
      $IdNo = $data->mainid_number;
      $OtherIdTypeId = $data->other_idtype;
      $OtherIdNo = $data->other_idnumber;
      if ($data->expiredate != 'null' or $data->expiredate != null) {
        $ExpiryDate = $data->expiredate;
      } else {
        $ExpiryDate = '';
      }

      $IssuingCountry = $data->place_ofissue;
      $DOB = $data->dob;
      $MotherName = $data->mother_name;
      $FatherName = $data->father_name;
      $EducationId = $data->education;
      $Occupation = $data->occupation;
      $Phone = $data->phone;
      $IsBkash = $data->isbkash;
      $WalletNo = $data->wallet_no;
      $WalletOwner = $data->wallet_owner;
      $PresentAddress = $data->present_adds;
      $presentUpazilaId = $data->present_upazila;
      $PresentDistrictId = $data->presentDistrictId;
      $PermanentAddress = $data->permanent_adds;
      $parmanentUpazilaId = $data->permanent_upazila;
      $MaritalStatusId = $data->matrial;
      $SpouseName = $data->spouse_name;
      $SpouseNidOrBid = $data->spouse_nid;
      $SposeDOB = $data->spouse_dob;
      $SpuseOccupationId = $data->spouse_occ;
      $FamilyMemberNo = $data->total_family_mem;
      $NoOfChildren = $data->total_child;
      $NomineeName = $data->nominee_name;
      $NomineeDOB = $data->nominee_dob;
      $RelationshipId = $data->relationship;
      $PrimaryEarner = $data->primary_earner;
      $ApplicantCpmbinedImg = $data->applicant_photo;
      $ReffererImg = $data->ref_photo;
      $ReffererIdImg = $data->refid_photo;
      $FrontSideOfIdImg = $data->nidfront_photo;
      $BackSideOfIdimg = $data->nidback_photo;
      $NomineeIdImg = $data->nominee_nid_photo;
      $SpuseIdImg = $data->spouse_nid_photo;
      $NomineeNidNo = $data->nominee_nid_no;
      $NomineeNidType = $data->nominee_nid_type;
      $NomineeNidFront = $data->nominee_nid_front;
      $NomineeNidBack = $data->nominee_nid_back;
      $SpouseNidFront = $data->spouse_nid_front;
      $SpouseNidBack = $data->spouse_nid_back;
      $PassbookRequired = $data->passbook_required;
      $GenderId = $data->genderid;
      $SavingsProductId = $data->savingsProductId;
      $NomineeIdExpiredate = $data->nominee_id_expiredate;
      $NomineeIdPlaceOfissue  = $data->nominee_id_place_ofissue;
      $NomineePhoneNumber = $data->nominee_phone_number;
      $SpouseCardType = $data->spouse_card_type;
      $SpouseIdExpiredate = $data->spouse_id_expiredate;
      $SpouseIdPlaceOfissue = $data->spouse_id_place_ofissue;
      $ApplicantSinglePic = $data->applicant_single_pic;
      $TargetAmount = $data->targetAmount;
      $PermanentDistrictId = $data->permanentDistrictId;
      $IsSameAddress = $data->is_same_addss;
      $surveyid = $data->surveyid;
      $otherReferee = $data->otherReferee;
      $refByDropdown = $data->refByDropdown;
      //dd($data->spouse_id_issuedate);
      if(isset($data->spouse_id_issuedate))
      {
        $spouseidissuedate = $data->spouse_id_issuedate;
      }
      else
      {
        $spouseidissuedate = null;
      }
     
      // $dynamicfieldvalue=json_encode($extra);

      if ($dynamicfieldvalue == '') {
        $dynamicfieldvalue = null;
      }
      $updatedTime = date('Y-m-d H:i:s');
      $checkData = DB::table($db . '.admissions')->where('entollmentid', $entollmentid)->first();
      //dd($checkData);
      if ($checkData == null) {
        $doc_id = DB::Table($db . '.admissions')->insertGetId(['IsRefferal' => $IsRefferal, 'RefferedById' => $RefferedById, 'ReffererName' => $ReffererName, 'ReffererPhone' => $ReffererPhone, 'MemberCateogryId' => $MemberCateogryId, 'ApplicantsName' => $ApplicantsName, 'MainIdTypeId' => $MainIdTypeId, 'IdNo' => $IdNo, 'OtherIdTypeId' => $OtherIdTypeId, 'OtherIdNo' => $OtherIdNo, 'ExpiryDate' => $ExpiryDate, 'IssuingCountry' => $IssuingCountry, 'DOB' => $DOB, 'MotherName' => $MotherName, 'FatherName' => $FatherName, 'EducationId' => $EducationId, 'Occupation' => $Occupation, 'Phone' => $Phone, 'IsBkash' => $IsBkash, 'WalletNo' => $WalletNo, 'WalletOwner' => $WalletOwner, 'PresentAddress' => $PresentAddress, 'presentUpazilaId' => $presentUpazilaId, 'PermanentAddress' => $PermanentAddress, 'parmanentUpazilaId' => $parmanentUpazilaId, 'MaritalStatusId' => $MaritalStatusId, 'SpouseName' => $SpouseName, 'SpouseNidOrBid' => $SpouseNidOrBid, 'SposeDOB' => $SposeDOB, 'SpuseOccupationId' => $SpuseOccupationId, 'FamilyMemberNo' => $FamilyMemberNo, 'NoOfChildren' => $NoOfChildren, 'NomineeName' => $NomineeName, 'NomineeDOB' => $NomineeDOB, 'RelationshipId' => $RelationshipId, 'PrimaryEarner' => $PrimaryEarner, 'ApplicantCpmbinedImg' => $ApplicantCpmbinedImg, 'ReffererImg' => $ReffererImg, 'ReffererIdImg' => $ReffererIdImg, 'FrontSideOfIdImg' => $FrontSideOfIdImg, 'BackSideOfIdimg' => $BackSideOfIdimg, 'NomineeIdImg' => $NomineeIdImg, 'SpuseIdImg' => $SpuseIdImg, 'DynamicFieldValue' => $dynamicfieldvalue, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'pin' => $pin, 'roleid' => $roleid, 'reciverrole' => $reciverrole, 'status' => $status, 'orgno' => $orgno, 'assignedpo' => $assignedpo, 'NomineeNidNo' => $NomineeNidNo, 'NomineeNidFront' => $NomineeNidFront, 'NomineeNidBack' => $NomineeNidBack, 'SpouseNidFront' => $SpouseNidFront, 'SpouseNidBack' => $SpouseNidBack, 'PassbookRequired' => $PassbookRequired, 'entollmentid' => $entollmentid, 'GenderId' => $GenderId, 'SavingsProductId' => $SavingsProductId, 'NomineeIdExpiredate' => $NomineeIdExpiredate, 'NomineeIdPlaceOfissue' => $NomineeIdPlaceOfissue, 'NomineePhoneNumber' => $NomineePhoneNumber, 'SpouseCardType' => $SpouseCardType, 'SpouseIdExpiredate' => $SpouseIdExpiredate, 'SpouseIdPlaceOfissue' => $SpouseIdPlaceOfissue, 'Flag' => $Flag, 'ApplicantSinglePic' => $ApplicantSinglePic, 'TargetAmount' => $TargetAmount, 'PermanentDistrictId' => $PermanentDistrictId, 'NomineeNidType' => $NomineeNidType, 'MemberId' => $MemberId, 'IsSameAddress' => $IsSameAddress, 'PresentDistrictId' => $PresentDistrictId, 'surveyid' => $surveyid, 'update_at' => $updatedTime, 'ref_by_dropdown' => $refByDropdown, 'other_referee' => $otherReferee, 'erp_member_id' => $MemberId,'spouseidissuedate'=>$spouseidissuedate]);
      } else {
        $doc_id = $checkData->id;
        DB::Table($db . '.admissions')->where('entollmentid', $entollmentid)->update(['IsRefferal' => $IsRefferal, 'RefferedById' => $RefferedById, 'ReffererName' => $ReffererName, 'ReffererPhone' => $ReffererPhone, 'MemberCateogryId' => $MemberCateogryId, 'ApplicantsName' => $ApplicantsName, 'MainIdTypeId' => $MainIdTypeId, 'IdNo' => $IdNo, 'OtherIdTypeId' => $OtherIdTypeId, 'OtherIdNo' => $OtherIdNo, 'ExpiryDate' => $ExpiryDate, 'IssuingCountry' => $IssuingCountry, 'DOB' => $DOB, 'MotherName' => $MotherName, 'FatherName' => $FatherName, 'EducationId' => $EducationId, 'Occupation' => $Occupation, 'Phone' => $Phone, 'IsBkash' => $IsBkash, 'WalletNo' => $WalletNo, 'WalletOwner' => $WalletOwner, 'PresentAddress' => $PresentAddress, 'presentUpazilaId' => $presentUpazilaId, 'PermanentAddress' => $PermanentAddress, 'parmanentUpazilaId' => $parmanentUpazilaId, 'MaritalStatusId' => $MaritalStatusId, 'SpouseName' => $SpouseName, 'SpouseNidOrBid' => $SpouseNidOrBid, 'SposeDOB' => $SposeDOB, 'SpuseOccupationId' => $SpuseOccupationId, 'FamilyMemberNo' => $FamilyMemberNo, 'NoOfChildren' => $NoOfChildren, 'NomineeName' => $NomineeName, 'NomineeDOB' => $NomineeDOB, 'RelationshipId' => $RelationshipId, 'PrimaryEarner' => $PrimaryEarner, 'ApplicantCpmbinedImg' => $ApplicantCpmbinedImg, 'ReffererImg' => $ReffererImg, 'ReffererIdImg' => $ReffererIdImg, 'FrontSideOfIdImg' => $FrontSideOfIdImg, 'BackSideOfIdimg' => $BackSideOfIdimg, 'NomineeIdImg' => $NomineeIdImg, 'SpuseIdImg' => $SpuseIdImg, 'DynamicFieldValue' => $dynamicfieldvalue, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'pin' => $pin, 'roleid' => $roleid, 'reciverrole' => $reciverrole, 'status' => $status, 'orgno' => $orgno, 'assignedpo' => $assignedpo, 'NomineeNidNo' => $NomineeNidNo, 'NomineeNidFront' => $NomineeNidFront, 'NomineeNidBack' => $NomineeNidBack, 'SpouseNidFront' => $SpouseNidFront, 'SpouseNidBack' => $SpouseNidBack, 'PassbookRequired' => $PassbookRequired, 'entollmentid' => $entollmentid, 'GenderId' => $GenderId, 'SavingsProductId' => $SavingsProductId, 'NomineeIdExpiredate' => $NomineeIdExpiredate, 'NomineeIdPlaceOfissue' => $NomineeIdPlaceOfissue, 'NomineePhoneNumber' => $NomineePhoneNumber, 'SpouseCardType' => $SpouseCardType, 'SpouseIdExpiredate' => $SpouseIdExpiredate, 'SpouseIdPlaceOfissue' => $SpouseIdPlaceOfissue, 'Flag' => $Flag, 'ApplicantSinglePic' => $ApplicantSinglePic, 'TargetAmount' => $TargetAmount, 'PermanentDistrictId' => $PermanentDistrictId, 'NomineeNidType' => $NomineeNidType, 'MemberId' => $MemberId, 'IsSameAddress' => $IsSameAddress, 'PresentDistrictId' => $PresentDistrictId, 'updated_at' => $currentTime, 'surveyid' => $surveyid, 'update_at' => $updatedTime, 'ref_by_dropdown' => $refByDropdown, 'other_referee' => $otherReferee, 'erp_member_id' => $MemberId,'spouseidissuedate'=>$spouseidissuedate]);
      }


      if ($Flag == 1) {
        $document_url = $baseUrl . "/DocumentManager?doc_id=$doc_id&projectcode=$projectcode&doc_type=admission&pin=$pin&role=0&branchcode=$branchcode&action=Request";
      } elseif ($Flag == 2) {
        $document_url = $baseUrl . "/DocumentManager?doc_id=$doc_id&projectcode=$projectcode&doc_type=admission&pin=$pin&role=0&branchcode=$branchcode&action=Modify";
      }

      Log::channel('daily')->info('Document_url : ' . $document_url);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $document_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $documentoutput = curl_exec($ch);
      curl_close($ch);
      //dd($documentoutput);
      $collectionfordocument = json_decode($documentoutput);

      Log::channel('daily')->info('document_url : ' . $document_url);
      Log::channel('daily')->info('document_response : ' . $documentoutput);

      $notification_url = $baseUrl . "/NotificatioManager?projectcode=$projectcode&doc_type=admission&pin=$pin&role=0&branchcode=$branchcode&entollmentid=$entollmentid&action=Request&assigndpo=$assignedpo";
      //echo $notification_url;
      Log::channel('daily')->info('notification_url : ' . $notification_url);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $notification_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $notificationoutput = curl_exec($ch);
      curl_close($ch);

      $collectionfornotification = json_decode($notificationoutput);

      Log::channel('daily')->info('notification_response : ' . $notificationoutput);
      // dd($collection);
      if ($collectionfornotification->status == 'S' and $collectionfordocument->status == 'S') {
        if ($projectcode == '060') {
          $document_url_Buffer = $baseUrl . "/DocumentManager?doc_id=$doc_id&projectcode=$projectcode&doc_type=admission&pin=$pin&role=1&branchcode=$branchcode&action=Approve";;
          Log::channel('daily')->info('Document_url Buffer: ' . $document_url_Buffer);
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $document_url_Buffer);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HEADER, false);
          $documentoutput = curl_exec($ch);
          // dd($documentoutput);
          curl_close($ch);
          Log::channel('daily')->info('document_response_from_Documenet_buffer : ' . $documentoutput);
          $collectionfordocument = json_decode($documentoutput);
          if (!empty($collectionfordocument)) {
            $getstatus = $collectionfordocument->status;
            if ($getstatus == 'E') {
              echo $documentoutput;
              die;
            }
          }
        }
        if ($Flag == '2') {
         //. $result = array("status" => "S", "message" => "অভিনন্দন! প্রফাইল আপডেটের সকল তথ্য সফলভাবে পাঠানো হয়েছে। আবেদনের অবস্থান জানতে নটিফিকেশন ও স্ট্যাটাস চেক করুন!");
          $result = array("status" => "S", "message" => "অভিনন্দন! প্রোফাইল আপডেটে রিকুয়েস্ট সফলভাবে পাঠানো হয়েছে।\n •	আবেদনের অবস্থান জানতে নোটিফিকেশন ও স্ট্যাটাস চেক করুন।\n •	প্রোফাইল আপডেট স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন সাবমিট করুন।");
          echo json_encode($result);
        } else {
          //$result = array("status" => "S", "message" => "অভিনন্দন! সদস্য ভর্তির সকল তথ্য সফলভাবে পাঠানো হয়েছে। আবেদনের অবস্থান জানতে নটিফিকেশন ও স্ট্যাটাস চেক করুন!");
          $result = array("status" => "S", "message" => "অভিনন্দন! সদস্য ভর্তির আবেদন সফলভাবে পাঠানো হয়েছে।\n •	আবেদনের অবস্থান জানতে নোটিফিকেশন ও স্ট্যাটাস চেক করুন।\n •	ভর্তি আবেদনের স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন সাবমিট করুন।");
          echo json_encode($result);
        }
      } else {
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
    // if($token=='xxxxxxxxx'){
    // 	if($projectcode=='015'){



    // 	}else{
    // 		$result = array("status"=>"E","message"=>"","Please check project");
    // 		echo json_encode($result);
    // 	}
    // }else{
    // 	$result = array("status"=>"E","message"=>"","Unauthorized Request");
    // 	echo json_encode($result);
    // }

  }

  public function BmAdmissionAssessment(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $json = Request::input('json');
    Log::channel('daily')->info('Bm Assessment Admission Data: ' . $json);
    $dataset = json_decode($json);
    $token = $dataset->token;
    // $appid=$dataset->appid;
    $data = $dataset->admission[0];
    $projectcode = $data->project_code;
    $projectcode = str_pad($projectcode, 3, "0", STR_PAD_LEFT);
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      $orgno = $data->vo_code;
      $pin = $data->pin;
      $branchcode = $data->branch_code;
      $branchcode = str_pad($branchcode, 4, "0", STR_PAD_LEFT); //
      $entollmentid = $data->mem_id;
      $MemberId = $data->erp_mem_id;
      $bm_behavior = $data->behavior;
      $bm_financial_status = $data->financial_status;
      $bm_client_house_image = $data->client_house_image;
      $bm_lat = $data->lat;
      $bm_lng = $data->lng;

      // dd($dynamicfieldvalue);
      $updatedAt = date('Y-m-d H:i:s');
      if ($entollmentid != null) {
        DB::Table($db . '.admissions')->where('entollmentid', $entollmentid)->update(['bm_behavior' => $bm_behavior, 'bm_financial_status' => $bm_financial_status, 'bm_client_house_image' => $bm_client_house_image, 'bm_lat' => $bm_lat, 'bm_lng' => $bm_lng, 'update_at' => $updatedAt]);
      } else {
        DB::Table($db . '.admissions')->where('MemberId', $MemberId)->update(['bm_behavior' => $bm_behavior, 'bm_financial_status' => $bm_financial_status, 'bm_client_house_image' => $bm_client_house_image, 'bm_lat' => $bm_lat, 'bm_lng' => $bm_lng, 'update_at' => $updatedAt]);
      }

      Log::channel('daily')->info('Bm Admission Assessment Successful.');
      $result = array("status" => "S", "message" => "Data saved");
      return json_encode($result);
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
    // dd($data);
    // if($token=='xxxxxxxxx'){
    // 	if($projectcode=='015'){



    // 	}else{
    // 		$result = array("status"=>"E","message"=>"","Please check project");
    // 		echo json_encode($result);
    // 	}
    // }else{
    // 	$result = array("status"=>"E","message"=>"","Unauthorized Request");
    // 	echo json_encode($result);
    // }

  }

  public function BmLoanAssessment(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    //$json = '{"token":"xxxxxxxxx","loan_checklist":[{"vo_code":"2029","loan_id":"494a31fb-aa50-4d84-a401-e9e6d0c525e2","branch_code":"1344","project_code":"015","pin":"00122372","mem_id":"35900701","erp_mem_id":null,"bm_repay_loan":null,"bm_conduct_activity":null,"bm_action_required":null,"bm_rca_rating":"3.0","bm_noofChild":"1","bm_earningMember":"5","bm_duration":"10","bm_hometown":"0","bm_landloard":"0","bm_recomand":"0","bm_occupation":"0","bm_aware":"0","bm_grantor":"0","bm_socialAcecptRating":7,"bm_grantorRating":7,"bm_clienthouse":"","bm_remarks":"test"}],"rca":[{"bm_monthlyincome_main":"2000","bm_monthlyincome_spouse_child":"","bm_monthlyincome_other":"","bm_house_rent":"1000","bm_food":"300","bm_education":"","bm_medical":"","bm_festive":"0","bm_utility":"0","bm_saving":"0","bm_other":"","bm_monthly_instal":"","bm_debt":"400","bm_monthly_cash":"700","bm_instal_proposloan":"0.0"}]}';
    $json = Request::input('json');
    Log::channel('daily')->info('Bm Loan Assessment Data: ' . $json);
    $dataset = json_decode($json);
    $token = $dataset->token;
    // $appid=$dataset->appid;
    $dataLoan = $dataset->loan_checklist[0];
    // dd($dataLoan);
    $dataRca = $dataset->rca[0];
    $projectcode = $dataLoan->project_code;
    $projectcode = str_pad($projectcode, 3, "0", STR_PAD_LEFT);
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      $orgno = $dataLoan->vo_code;
      $pin = $dataLoan->pin;
      $branchcode = $dataLoan->branch_code;
      $branchcode = str_pad($branchcode, 4, "0", STR_PAD_LEFT); //
      $mem_id = $dataLoan->mem_id;
      $MemberId = $dataLoan->erp_mem_id;
      $loan_id = $dataLoan->loan_id;
      //loan
      $bm_repay_loan = $dataLoan->bm_repay_loan;
      $bm_conduct_activity = $dataLoan->bm_conduct_activity;
      $bm_action_required = $dataLoan->bm_action_required;
      $bm_rca_rating = $dataLoan->bm_rca_rating;
      //loan new
      $bm_noofChild = $dataLoan->bm_noofChild;
      $bm_earningMember = $dataLoan->bm_earningMember;
      $bm_duration = $dataLoan->bm_duration;
      $bm_hometown = $dataLoan->bm_hometown;
      $bm_landloard = $dataLoan->bm_landloard;
      $bm_recomand = $dataLoan->bm_recomand;
      $bm_occupation = $dataLoan->bm_occupation;
      $bm_aware = $dataLoan->bm_aware;
      $bm_grantor = $dataLoan->bm_grantor;
      $bm_socialAcecptRating = $dataLoan->bm_socialAcecptRating;
      $bm_grantorRating = $dataLoan->bm_grantorRating;
      $bm_clienthouse = $dataLoan->bm_clienthouse;
      $bm_remarks = $dataLoan->bm_remarks;
      $approval_amount = $dataLoan->approval_amount;

      //rca
      $bm_monthlyincome_main = $dataRca->bm_monthlyincome_main;
      $bm_monthlyincome_spouse_child = $dataRca->bm_monthlyincome_spouse_child;
      $bm_monthlyincome_other = $dataRca->bm_monthlyincome_other;
      $bm_house_rent = $dataRca->bm_house_rent;
      $bm_food = $dataRca->bm_food;
      $bm_education = $dataRca->bm_education;
      $bm_medical = $dataRca->bm_medical;
      $bm_festive = $dataRca->bm_festive;
      $bm_utility = $dataRca->bm_utility;
      $bm_saving = $dataRca->bm_saving;
      $bm_other = $dataRca->bm_other;
      $bm_monthly_instal = $dataRca->bm_monthly_instal;
      $bm_debt = $dataRca->bm_debt;
      $bm_monthly_cash = $dataRca->bm_monthly_cash;
      $bm_instal_proposloan = $dataRca->bm_instal_proposloan;
      //$po_seasonal_income  = $dataRca->po_seasonal_income;
      $bm_seasonal_income  = $dataRca->bm_seasonal_income;
      //$po_incomeformfixedassets = $dataRca->po_incomeformfixedassets;
      $bm_incomeformfixedassets = $dataRca->bm_incomeformfixedassets;
      //$po_imcomeformsavings = $dataRca->po_imcomeformsavings;
      $bm_imcomeformsavings = $dataRca->bm_imcomeformsavings;
      // $po_houseconstructioncost = $dataRca->po_houseconstructioncost;
      $bm_houseconstructioncost = $dataRca->bm_houseconstructioncost;
      // $po_expendingonmarriage = $dataRca->po_expendingonmarriage;
      $bm_expendingonmarriage = $dataRca->bm_expendingonmarriage;
      //$po_operation_childBirth = $dataRca->po_operation_childBirth;
      $bm_operation_childBirth = $dataRca->bm_operation_childBirth;
      // $po_foreigntravel = $dataRca->po_foreigntravel;
      $bm_foreigntravel = $dataRca->bm_foreigntravel;

      // dd($dynamicfieldvalue);
      $updatedAt = date('Y-m-d H:i:s');
      if ($loan_id != null) {
        $loan_sl = Db::table($db . '.loans')->select('id')->where('loan_id', $loan_id)->first();
        //dd($loan_sl);
        $loan = $loan_sl->id;

        DB::Table($db . '.loans')->where('loan_id', $loan_id)->update(['bm_repay_loan' => $bm_repay_loan, 'bm_conduct_activity' => $bm_conduct_activity, 'bm_action_required' => $bm_action_required, 'bm_rca_rating' => $bm_rca_rating, 'bm_noofChild' => $bm_noofChild, 'bm_earningMember' => $bm_earningMember, 'bm_duration' => $bm_duration, 'bm_hometown' => $bm_hometown, 'bm_landloard' => $bm_landloard, 'bm_recomand' => $bm_recomand, 'bm_occupation' => $bm_occupation, 'bm_aware' => $bm_aware, 'bm_grantor' => $bm_grantor, 'bm_socialAcecptRating' => $bm_socialAcecptRating, 'bm_grantorRating' => $bm_grantorRating, 'bm_grantorRating' => $bm_grantorRating, 'bm_clienthouse' => $bm_clienthouse, 'bm_remarks' => $bm_remarks, 'approval_amount' => $approval_amount, 'update_at' => $updatedAt]);

        DB::Table($db . '.rca')->where('loan_id', $loan)->update([
          'bm_monthlyincome_main' => $bm_monthlyincome_main, 'bm_monthlyincome_spouse_child' => $bm_monthlyincome_spouse_child, 'bm_monthlyincome_other' => $bm_monthlyincome_other,
          'bm_house_rent' => $bm_house_rent, 'bm_food' => $bm_food, 'bm_education' => $bm_education, 'bm_medical' => $bm_medical, 'bm_festive' => $bm_festive, 'bm_utility' => $bm_utility, 'bm_saving' => $bm_saving, 'bm_other' => $bm_other, 'bm_monthly_instal' => $bm_monthly_instal, 'bm_debt' => $bm_debt, 'bm_monthly_cash' => $bm_monthly_cash, 'bm_instal_proposloan' => $bm_instal_proposloan, 'bm_seasonal_income' => $bm_seasonal_income,
          'bm_incomeformfixedassets' => $bm_incomeformfixedassets, 'bm_imcomeformsavings' => $bm_imcomeformsavings,
          'bm_houseconstructioncost' => $bm_houseconstructioncost,
          'bm_expendingonmarriage' => $bm_expendingonmarriage, 'bm_operation_childBirth' => $bm_operation_childBirth,
          'bm_foreigntravel' => $bm_foreigntravel
        ]);
      } else {
        $result = array("status" => "E", "message" => "loan id can not be empty");
        return json_encode($result);
      }

      Log::channel('daily')->info('Bm Loan Assessment Successful.- ' . $branchcode . "/" . $orgno);
      $result = array("status" => "S", "message" => "Data saved");
      return json_encode($result);
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
    // dd($dataRca);
    // if($token=='xxxxxxxxx'){
    // 	if($projectcode=='015'){



    // 	}else{
    // 		$result = array("status"=>"E","message"=>"","Please check project");
    // 		echo json_encode($result);
    // 	}
    // }else{
    // 	$result = array("status"=>"E","message"=>"","Unauthorized Request");
    // 	echo json_encode($result);
    // }

  }

  public function AllSurveyData(Request $request)
  {
    $db = $this->db;
    // $projectCode = Request::input('projectcode');
    // $appid = Request::input('appid');
    // $processId = Request::input('processId');
    // $apikey = Request::input('apikey');
    $json = DB::Table($db . '.surveys')->get();
    // dd($json);
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($json->isEmpty()) {
        $result = array("status" => "E", "message" => "Data Not Found!");
        echo json_encode($result);
      } else {
        $result = array("status" => "S", "message" => "", "data" => $json);
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function AllAdmissionData(Request $request)
  {
    $db = $this->db;
    // $projectCode = Request::input('projectcode');
    // $appid = Request::input('appid');
    // $processId = Request::input('processId');
    // $apikey = Request::input('apikey');
    $json = DB::Table($db . '.admissions')->get();
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($json->isEmpty()) {
        $result = array("status" => "E", "message" => "Data Not Found!");
        echo json_encode($result);
      } else {
        $result = array("status" => "S", "message" => "", "data" => $json);
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }
  public function LoanRcaDataStore1(Request $request)
  {
  }
  public function LoanRcaDataStore(Request $request)
  {
    $checkloancount1 = 0;
    $this->LaravelLog();
    $db = $this->db;
    $baseUrl = url('');
    $json = Request::input('json');
    Log::channel('daily')->info('Loan Rca Data: ' . $json);
    $dataset = json_decode($json);
    // $token = $dataset->token;
    $data = $dataset->loan[0];
    $dataRca = $dataset->rca[0];
    $projectcode = $data->project_code;
    $projectcode = str_pad($projectcode, 3, "0", STR_PAD_LEFT);
    $token = Request::input('token');
    $branchcode = str_pad($data->branch_code, 4, "0", STR_PAD_LEFT); //
    if ($token == '7f30f4491cb4435984616d1913e88389') {

      if ($projectcode == '015') {
        /*$checkloan = DB::Table($db . '.loans')->where('orgno', $data->vo_code)->where('assignedpo', $data->pin)->where('erp_mem_id', $data->erp_mem_id)->where('status', '!=', 2)->orWhere('ErpStatus', '!=', 2)->get();
        //dd($checkloan);
        $checkloancount = DB::Table($db . '.loans')->where('orgno', $data->vo_code)->where('assignedpo', $data->pin)->where('erp_mem_id', $data->erp_mem_id)->where('status', '!=', 2)->orWhere('ErpStatus', '!=', 2)->count();
        if (!$checkloan->isEmpty()) {
          $ErpStatus = $checkloan[0]->ErpStatus;
          $status = $checkloan[0]->reciverrole;
          //$flag = $checkloan[0]->Flag;
          if ($ErpStatus == '1' or $status != '0') {
            //$message = "প্রোফাইল আপডেটের অনুরোধ  $checkloancount টি পেন্ডিং রয়েছে। অনুগ্রহ করে পূর্ববর্তী অনুরোধটি প্রত্যাখ্যান করুন বা BM কে অনুমোদন করতে বলুন এবং এরপরে নতুন করে প্রোফাইল আপডেটের অনুরোধ করুন।";
            $message = "উক্ত সদস্যের $checkloancount টি লোন অ্যাপ্লিকেশন অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় লোন অ্যাপ্লিকেশন করুন।";
            $result = array("status" => "E", "message" => $message);
            echo json_encode($result);
            Log::info("Loan Duplicate" . json_encode($result));
            die;
          }
        }*/

        //$checkloancount = DB::Table($db . '.loans')->where('orgno', $data->vo_code)->where('assignedpo', $data->pin)->where('orgmemno', $data->orgmemno)->where('projectcode', $projectcode)->where('status', '!=', '2')->orWhere('erpstatus', '!=', 2)->orWhere('erpstatus', '!=', 4)->count()
        if($data->erp_mem_id =='')
        {
          $message = "দুঃখিত! এই সদস্যের ভর্তি আবেদন পেন্ডিং আছে।\n•	আবেদনটি সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন করতে হবে।\n•	ভর্তি আবেদনের স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন সাবমিট করুন। সংগ্রহকৃত সকল তথ্য ড্রাফটে সেভ হয়ে থাকবে।\n •	প্রয়োজনে সেন্ডব্যাককৃত ভর্তি আবেদনে পুনরায় কাজ করুন, নতুন করে ভর্তি আবেদন করার প্রয়োজন নেই।";
          $result = array("status" => "E", "message" => $message);
          echo json_encode($result);
          Log::info("Loan Duplicate" . json_encode($result));
          die;
        }
        $checkprofileupdate =DB::select(DB::raw("select count(*) from $db.admissions where orgno='$data->vo_code' and branchcode='$branchcode' and assignedpo='$data->pin' and projectcode='$projectcode' and erp_member_id='$data->erp_mem_id' and (status ='1' or erpstatus =1)"));
        $checkprofileupdatecount = $checkprofileupdate[0]->count;
        if($checkprofileupdatecount > 0)
        {
          $message = "দুঃখিত! এই সদস্যের প্রোফাইল আপডেট পেন্ডিং আছে।\n•	অনুগ্রহ করে পূর্বের প্রোফাইল আপডেট রিকুয়েস্ট সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন/রিজেক্ট/সেন্ডব্যাক করতে হবে।\n•	প্রোফাইল আপডেট স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন/প্রোফাইল আপডেট রিকুয়েস্ট সাবমিট করুন। সংগ্রহকৃত সকল তথ্য ড্রাফটে সেভ হয়ে থাকবে।\n•	প্রয়োজনে সেন্ডব্যাককৃত রিকুয়েস্টে পুনরায় কাজ করুন, নতুন করে রিকুয়েস্ট করার প্রয়োজন নেই।\n•	শুধুমাত্র রিজেক্টকৃত রিকুয়েস্টের জন্য নতুন করে প্রোফাইল আপডেট রিকুয়েস্ট করুন।";
          $result = array("status" => "E", "message" => $message);
          echo json_encode($result);
          Log::info("Loan Duplicate" . json_encode($result));
          die;
        }
        /*$checkloancount = DB::select(DB::raw("select count(*) from $db.loans where orgno='$data->vo_code' and branchcode='$branchcode' and assignedpo='$data->pin' and projectcode='$projectcode' and loan_id !='$data->loan_id' and orgmemno='$data->orgmemno' and (status not in('2','3') or erpstatus not in('3','4'))"));
        $checkloancount1 = $checkloancount[0]->count;
        if ($checkloancount1 > 0) {
          //$message = "উক্ত সদস্যের $checkloancount1 টি লোন অ্যাপ্লিকেশন অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় লোন অ্যাপ্লিকেশন করুন।";
          //$message = "দুঃখিত! এই সদস্যের $checkloancount1 টি লোন প্রপোজাল পেন্ডিং আছে। \n	অনুগ্রহ করে পূর্বের প্রপোজালটি সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন /রিজেক্ট/ সেন্ডব্যাক করতে হবে \n প্রয়োজনে সেন্ডব্যাককৃত প্রপোজালে পুনরায় কাজ করুন, নতুন করে প্রপোজাল করার প্রয়োজন নেই। শুধুমাত্র রিজেক্টকৃত প্রপোজালের জন্য নতুন করে প্রপোজাল তৈরি করুন। ";
          $message = "দুঃখিত! এই সদস্যের $checkloancount1 টি লোন প্রপোজাল পেন্ডিং আছে।\n•	অনুগ্রহ করে পূর্বের প্রপোজালটি সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন /রিজেক্ট/ সেন্ডব্যাক করতে হবে\n•	প্রয়োজনে সেন্ডব্যাককৃত প্রপোজালে পুনরায় কাজ করুন, নতুন করে প্রপোজাল করার প্রয়োজন নেই। শুধুমাত্র রিজেক্টকৃত প্রপোজালের জন্য নতুন করে প্রপোজাল তৈরি করুন।";
          $result = array("status" => "DEL", "message" => $message);
          echo json_encode($result);
          Log::info("Loan Duplicate" . json_encode($result));
          die;
        }*/
        $roleid = 0;
        $reciverrole = 1;
        $status = 1;
        $orgno = $data->vo_code; //
        $branchcode = $data->branch_code;
        $loanid = $data->loan_id;
        $pin = $data->pin;
        $assignedpo = $data->pin;
        $branchcode = str_pad($branchcode, 4, "0", STR_PAD_LEFT); //
        $mem_id = $data->mem_id;
        $loan_product = $data->loan_product;
        $loan_duration = $data->loan_duration;
        $invest_sector = $data->invest_sector;
        $scheme = $data->scheme;
        $propos_amt = $data->propos_amt;
        $instal_amt = $data->instal_amt;
        $bracloan_family = $data->bracloan_family;
        $vo_leader = $data->vo_leader;
        $recommender = $data->recommender;
        $grntor_name = $data->grntor_name;
        $grntor_phone = $data->grntor_phone;
        $grntor_rlationClient = $data->grntor_rlationClient;
        $grntor_nid = $data->grntor_nid;
        $witness_knows = $data->witness_knows;
        $residence_type = $data->residence_type;
        $residence_duration = $data->residence_duration;
        $houseowner_knows = $data->houseowner_knows ?? null;
        $reltive_presAddress = $data->reltive_presAddress;
        $reltive_name = $data->reltive_name;
        $reltive_phone = $data->reltive_phone;
        $insurn_type = $data->insurn_type;
        $insurn_option = $data->insurn_option;
        $insurn_spouseName = $data->insurn_spouseName;
        $insurn_spouseNid = $data->insurn_spouseNid;
        $insurn_spouseDob = $data->insurn_spouseDob;
        $insurn_gender = $data->insurn_gender;
        $insurn_relation = $data->insurn_relation;
        $insurn_name = $data->insurn_name;
        $insurn_dob = $data->insurn_dob;
        $insurn_mainID = $data->insurn_mainID;
        $grantor_nidfront_photo = $data->grantor_nidfront_photo;
        $grantor_nidback_photo = $data->grantor_nidback_photo;
        $grantor_photo = $data->grantor_photo;
        $erp_mem_id = $data->erp_mem_id;
        $memberTypeId = $data->memberTypeId;
        $subSectorId = $data->subSectorId;
        $frequencyId = $data->frequencyId;
        $insurn_mainIDType = $data->insurn_mainIDType;
        $insurn_id_expire = $data->insurn_id_expire;
        $insurn_placeofissue = $data->insurn_placeofissue;
        $dynamicfieldvalueLoan = $data->extra;
        $surveyid = $data->surveyid;
        $orgmemno = $data->orgmemno;
        $amount_inword = $data->amount_inword;
        $loan_purpose = $data->loan_purpose;
        $loan_user = $data->loan_user;
        $loan_type = $data->loan_type;
        $brac_loancount = $data->brac_loancount;
        $approval_amount = $data->propos_amt;
        if($invest_sector=='43' and $subSectorId=='24')
        {
          if($scheme=='299')
          {
            $scheme=='331';
          }
        }

        if ($dynamicfieldvalueLoan == '') {
          $dynamicfieldvalueLoan = null;
        }
        // $dynamicfieldvalueLoan=json_encode($loanjson);
        $checkData = DB::table($db . '.loans')->where('loan_id', $loanid)->first();
        // DB::beginTransaction();
        // try {
        $updatedate = date('Y-m-d H:i:s');
        if ($checkData == null) {
          $doc_id = DB::Table($db . '.loans')->insertGetId(['mem_id' => $mem_id, 'loan_product' => $loan_product, 'loan_duration' => $loan_duration, 'invest_sector' => $invest_sector, 'propos_amt' => $propos_amt, 'instal_amt' => $instal_amt, 'bracloan_family' => $bracloan_family, 'vo_leader' => $vo_leader, 'recommender' => $recommender, 'grntor_name' => $grntor_name, 'grntor_phone' => $grntor_phone, 'grntor_rlationClient' => $grntor_rlationClient, 'grntor_nid' => $grntor_nid, 'witness_knows' => $witness_knows, 'residence_type' => $residence_type, 'residence_duration' => $residence_duration, 'houseowner_knows' => $houseowner_knows, 'reltive_presAddress' => $reltive_presAddress, 'reltive_name' => $reltive_name, 'reltive_phone' => $reltive_phone, 'insurn_type' => $insurn_type, 'insurn_option' => $insurn_option, 'insurn_spouseName' => $insurn_spouseName, 'insurn_spouseNid' => $insurn_spouseNid, 'insurn_spouseDob' => $insurn_spouseDob, 'insurn_gender' => $insurn_gender, 'insurn_relation' => $insurn_relation, 'insurn_name' => $insurn_name, 'insurn_dob' => $insurn_dob, 'insurn_mainID' => $insurn_mainID, 'grantor_nidfront_photo' => $grantor_nidfront_photo, 'grantor_nidback_photo' => $grantor_nidback_photo, 'grantor_photo' => $grantor_photo, 'DynamicFieldValue' => $dynamicfieldvalueLoan, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'pin' => $pin, 'roleid' => $roleid, 'reciverrole' => $reciverrole, 'loan_id' => $loanid, 'assignedpo' => $assignedpo, 'orgno' => $orgno, 'erp_mem_id' => $erp_mem_id, 'scheme' => $scheme, "memberTypeId" => $memberTypeId, "subSectorId" => $subSectorId, "frequencyId" => $frequencyId, "insurn_mainIDType" => $insurn_mainIDType, "insurn_id_expire" => $insurn_id_expire, "insurn_placeofissue" => $insurn_placeofissue, 'surveyid' => $surveyid, 'amount_inword' => $amount_inword, 'loan_purpose' => $loan_purpose, 'loan_user' => $loan_user, 'loan_type' => $loan_type, 'brac_loancount' => $brac_loancount, 'orgmemno' => $orgmemno, 'update_at' => $updatedate, 'approval_amount' => $approval_amount]);
        } else {
          $doc_id = $checkData->id;
          $loan_id = $checkData->loan_id;
          DB::Table($db . '.loans')->where('loan_id', $loan_id)->update(['mem_id' => $mem_id, 'loan_product' => $loan_product, 'loan_duration' => $loan_duration, 'invest_sector' => $invest_sector, 'propos_amt' => $propos_amt, 'instal_amt' => $instal_amt, 'bracloan_family' => $bracloan_family, 'vo_leader' => $vo_leader, 'recommender' => $recommender, 'grntor_name' => $grntor_name, 'grntor_phone' => $grntor_phone, 'grntor_rlationClient' => $grntor_rlationClient, 'grntor_nid' => $grntor_nid, 'witness_knows' => $witness_knows, 'residence_type' => $residence_type, 'residence_duration' => $residence_duration, 'houseowner_knows' => $houseowner_knows, 'reltive_presAddress' => $reltive_presAddress, 'reltive_name' => $reltive_name, 'reltive_phone' => $reltive_phone, 'insurn_type' => $insurn_type, 'insurn_option' => $insurn_option, 'insurn_spouseName' => $insurn_spouseName, 'insurn_spouseNid' => $insurn_spouseNid, 'insurn_spouseDob' => $insurn_spouseDob, 'insurn_gender' => $insurn_gender, 'insurn_relation' => $insurn_relation, 'insurn_name' => $insurn_name, 'insurn_dob' => $insurn_dob, 'insurn_mainID' => $insurn_mainID, 'grantor_nidfront_photo' => $grantor_nidfront_photo, 'grantor_nidback_photo' => $grantor_nidback_photo, 'grantor_photo' => $grantor_photo, 'DynamicFieldValue' => $dynamicfieldvalueLoan, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'pin' => $pin, 'roleid' => $roleid, 'reciverrole' => $reciverrole, 'loan_id' => $loanid, 'assignedpo' => $assignedpo, 'orgno' => $orgno, 'erp_mem_id' => $erp_mem_id, 'scheme' => $scheme, "memberTypeId" => $memberTypeId, "subSectorId" => $subSectorId, "frequencyId" => $frequencyId, "insurn_mainIDType" => $insurn_mainIDType, "insurn_id_expire" => $insurn_id_expire, "insurn_placeofissue" => $insurn_placeofissue, 'surveyid' => $surveyid, 'amount_inword' => $amount_inword, 'loan_purpose' => $loan_purpose, 'loan_user' => $loan_user, 'loan_type' => $loan_type, 'brac_loancount' => $brac_loancount, 'orgmemno' => $orgmemno, 'update_at' => $updatedate, 'approval_amount' => $approval_amount]);
        }

        //Log::channel('daily')->info('Loan Rca Data check: ' . $checkData);

        
        if($dataRca->primary_earner == null)
        {
          $primary_earner = 1;
        }
        else
        {
          $primary_earner = $dataRca->primary_earner;
        }
        $monthlyincome_main = $dataRca->monthlyincome_main;
        $monthlyincome_other = $dataRca->monthlyincome_other;
        $house_rent = $dataRca->house_rent;
        $food = $dataRca->food;
        $education = $dataRca->education;
        $medical = $dataRca->medical;
        $festive = $dataRca->festive;
        $utility = $dataRca->utility;
        $saving = $dataRca->saving;
        $other = $dataRca->other;
        $monthly_instal = $dataRca->monthly_instal;
        $debt = $dataRca->debt;
        $monthly_cash = $dataRca->monthly_cash;
        $monthlyincome_spouse_child = $dataRca->monthlyincome_spouse_child;
        $instal_proposloan = $dataRca->instal_proposloan;
        $dynamicfieldvalueRca = $dataRca->extra;
        $po_seasonal_income  = $dataRca->po_seasonal_income;
        $po_incomeformfixedassets = $dataRca->po_incomeformfixedassets;
        $po_imcomeformsavings = $dataRca->po_imcomeformsavings;
        $po_houseconstructioncost = $dataRca->po_houseconstructioncost;
        $po_expendingonmarriage = $dataRca->po_expendingonmarriage;
        $po_operation_childBirth = $dataRca->po_operation_childBirth;
        $po_foreigntravel = $dataRca->po_foreigntravel;

        if ($dynamicfieldvalueRca == '') {
          $dynamicfieldvalueRca = null;
        }
        // $dynamicfieldvalueRca=json_encode($rcajson);
        // dd($checkData);
        if ($checkData == null) {
          DB::Table($db . '.rca')->insert([
            'loan_id' => $doc_id, 'primary_earner' => $primary_earner, 'monthlyincome_main' => $monthlyincome_main,
            'monthlyincome_other' => $monthlyincome_other, 'house_rent' => $house_rent, 'food' => $food, 'education' => $education,
            'medical' => $medical, 'festive' => $festive, 'utility' => $utility, 'saving' => $saving, 'other' => $other,
            'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan,
            'DynamicFieldValue' => $dynamicfieldvalueRca, 'monthlyincome_spouse_child' => $monthlyincome_spouse_child,
            'po_seasonal_income' => $po_seasonal_income, 'po_incomeformfixedassets' => $po_incomeformfixedassets,
            'po_imcomeformsavings' => $po_imcomeformsavings, 'po_houseconstructioncost' => $po_houseconstructioncost, 'po_expendingonmarriage' => $po_expendingonmarriage,
            'po_operation_childBirth' => $po_operation_childBirth,
            'po_foreigntravel' => $po_foreigntravel
          ]);
        } else {
          DB::Table($db . '.rca')->where('loan_id', $doc_id)->update([
            'loan_id' => $doc_id, 'primary_earner' => $primary_earner, 'monthlyincome_main' => $monthlyincome_main, 'monthlyincome_other' => $monthlyincome_other, 'house_rent' => $house_rent, 'food' => $food, 'education' => $education, 'medical' => $medical, 'festive' => $festive, 'utility' => $utility, 'saving' => $saving, 'other' => $other, 'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan, 'DynamicFieldValue' => $dynamicfieldvalueRca, 'monthlyincome_spouse_child' => $monthlyincome_spouse_child,
            'po_seasonal_income' => $po_seasonal_income, 'po_incomeformfixedassets' => $po_incomeformfixedassets,
            'po_imcomeformsavings' => $po_imcomeformsavings, 'po_houseconstructioncost' => $po_houseconstructioncost, 'po_expendingonmarriage' => $po_expendingonmarriage,
            'po_operation_childBirth' => $po_operation_childBirth,
            'po_foreigntravel' => $po_foreigntravel
          ]);
        }
      } // end 015 project
      else if ($projectcode == '060') {
        /*$checkloan = DB::Table($db . '.loans')->where('assignedpo', $data->pin)->where('erp_mem_id', $data->erp_mem_id)->where('projectcode', $projectcode)->where('ErpStatus', '!=', 2)->where('ErpStatus', '!=', 4)->get();
        //dd($checkloan);
        $checkloancount = DB::Table($db . '.loans')->where('assignedpo', $data->pin)->where('erp_mem_id', $data->erp_mem_id)->where('projectcode', $projectcode)->where('ErpStatus', '!=', 2)->count();
        if (!$checkloan->isEmpty()) {
          $ErpStatus = $checkloan[0]->ErpStatus;
          $status = $checkloan[0]->reciverrole;
          //$flag = $checkloan[0]->Flag;
          if ($ErpStatus == '1' or $status != '0') {
            //$message = "প্রোফাইল আপডেটের অনুরোধ  $checkloancount টি পেন্ডিং রয়েছে। অনুগ্রহ করে পূর্ববর্তী অনুরোধটি প্রত্যাখ্যান করুন বা BM কে অনুমোদন করতে বলুন এবং এরপরে নতুন করে প্রোফাইল আপডেটের অনুরোধ করুন।";
            $message = "উক্ত সদস্যের $checkloancount টি লোন অ্যাপ্লিকেশন অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় লোন অ্যাপ্লিকেশন করুন।";
            $result = array("status" => "E", "message" => $message);
            echo json_encode($result);
            Log::info("Loan Duplicate" . json_encode($result));
            die;
          }
        }*/
        //$this->ProgotiDataInsert($db, $projectcode, $data, $dataRca);
        // $checkloancount = DB::Table($db . '.loans')->where('assignedpo', $data->pin)->where('erp_mem_id', $data->erp_mem_id)->where('projectcode', $projectcode)->where('ErpStatus', '!=', 2)->where('ErpStatus', '!=', 4)->count();
        /*$checkloancount = DB::Table($db . '.loans')->where('assignedpo', $data->pin)->where('erp_mem_id', $data->erp_mem_id)->where('projectcode', $projectcode)->where('loan_id', '!=', $data->loan_id )->where('ErpStatus', '!=', 2)->where('ErpStatus', '!=', 4)->count();
        if ($checkloancount > 0) {
          $message = "উক্ত সদস্যের $checkloancount টি লোন অ্যাপ্লিকেশন অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় লোন অ্যাপ্লিকেশন করুন।";
          $result = array("status" => "E", "message" => $message);
          echo json_encode($result);
          Log::info("Loan Duplicate" . json_encode($result));
          die;
        }*/
        if($data->erp_mem_id =='')
        {
          $message = "দুঃখিত! এই সদস্যের ভর্তি আবেদন পেন্ডিং আছে।\n•	আবেদনটি সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন করতে হবে।\n•	ভর্তি আবেদনের স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন সাবমিট করুন। সংগ্রহকৃত সকল তথ্য ড্রাফটে সেভ হয়ে থাকবে।\n•	প্রয়োজনে সেন্ডব্যাককৃত ভর্তি আবেদনে পুনরায় কাজ করুন, নতুন করে ভর্তি আবেদন করার প্রয়োজন নেই।";
          $result = array("status" => "E", "message" => $message);
          echo json_encode($result);
          Log::info("Loan Duplicate" . json_encode($result));
          die;
        }
        $checkprofileupdate =DB::select(DB::raw("select count(*) from $db.admissions where branchcode='$branchcode' and assignedpo='$data->pin' and projectcode='$projectcode' and erp_member_id='$data->erp_mem_id' and (status !='2' or erpstatus =1)"));
        $checkprofileupdatecount = $checkprofileupdate[0]->count;
        if($checkprofileupdatecount > 0)
        {
          $message = "দুঃখিত! এই সদস্যের প্রোফাইল আপডেট পেন্ডিং আছে।\n•	অনুগ্রহ করে পূর্বের প্রোফাইল আপডেট রিকুয়েস্ট সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন/রিজেক্ট/সেন্ডব্যাক করতে হবে।\n•	প্রোফাইল আপডেট স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে ইন্টারনেট কানেকশন ঠিক রেখে লোন আবেদন/প্রোফাইল আপডেট রিকুয়েস্ট সাবমিট করুন। সংগ্রহকৃত সকল তথ্য ড্রাফটে সেভ হয়ে থাকবে।\n•	প্রয়োজনে সেন্ডব্যাককৃত রিকুয়েস্টে পুনরায় কাজ করুন, নতুন করে রিকুয়েস্ট করার প্রয়োজন নেই।\n•	শুধুমাত্র রিজেক্টকৃত রিকুয়েস্টের জন্য নতুন করে প্রোফাইল আপডেট রিকুয়েস্ট করুন।";
          $result = array("status" => "E", "message" => $message);
          echo json_encode($result);
          Log::info("Loan Duplicate" . json_encode($result));
          die;
        }
        /*$checkloancount = DB::select(DB::raw("select count(*) from $db.loans where branchcode='$branchcode' and assignedpo='$data->pin' and projectcode='$projectcode' and loan_id !='$data->loan_id' and erp_mem_id='$data->erp_mem_id' and (status not in('2','3') or erpstatus not in('3','4'))"));
        $checkloancount1 = $checkloancount[0]->count;
        if ($checkloancount1 > 0) {
          //$message = "উক্ত সদস্যের $checkloancount1 টি লোন অ্যাপ্লিকেশন অনুরোধ পেন্ডিং রয়েছে । অনুগ্রহ করে পূর্ববর্তী অনুরোধটি বিএম কে রিজেক্ট অথবা অনুমোদন করতে বলুন। তারপর যদি প্রয়োজন হয়, আপনি নতুন করে পুনরায় লোন অ্যাপ্লিকেশন করুন।";
          $message = "দুঃখিত! এই সদস্যের ১টি লোন প্রপোজাল পেন্ডিং আছে।\n•	অনুগ্রহ করে পূর্বের প্রপোজালটি সুপারভাইজার/ERP-এর মাধ্যমে অনুমোদন /রিজেক্ট/ সেন্ডব্যাক করতে হবে\n•	প্রয়োজনে সেন্ডব্যাককৃত প্রপোজালে পুনরায় কাজ করুন, নতুন করে প্রপোজাল করার প্রয়োজন নেই। শুধুমাত্র রিজেক্টকৃত প্রপোজালের জন্য নতুন করে প্রপোজাল তৈরি করুন।";
          $result = array("status" => "DEL", "message" => $message);
          echo json_encode($result);
          Log::info("Loan Duplicate" . json_encode($result));
          die;
        }*/
        $roleid = 0;
        $reciverrole = 1;
        $status = 1;
        $orgno = $data->vo_code ?? null; //
        $loanid = $data->loan_id;
        $branchcode = $data->branch_code;

        $pin = $data->pin;
        $assignedpo = $data->pin;
        $branchcode = str_pad($branchcode, 4, "0", STR_PAD_LEFT); //
        $mem_id = $data->mem_id;
        $orgmemno = $data->orgmemno;
        $erp_mem_id = $data->erp_mem_id;
        $memberTypeId = $data->memberTypeId;
        $loan_product = $data->loan_product;
        $loan_duration = $data->loan_duration;
        $invest_sector = $data->invest_sector;
        $subSectorId = $data->subSectorId;
        $frequencyId = $data->frequencyId;
        $scheme = $data->scheme;

        $propos_amt = $data->propos_amt;
        $instal_amt = $data->instal_amt;
        $bracloan_family = $data->bracloan_family;
        $surveyid = $data->surveyid;
        $dynamicfieldvalueLoan = $data->extra;
        $amount_inword = $data->amount_inword;
        $loan_purpose = $data->loan_purpose;
        $loan_user = $data->loan_user;
        $loan_type = $data->loan_type;

        $brac_loancount = $data->brac_loancount;
        $microInsurance = $data->microInsurance;
        $insurn_type = $data->insurn_type;
        $insurn_option = $data->insurn_option;
        $insurn_spouseName = $data->insurn_spouseName;
        $insurn_spouseNid = $data->insurn_spouseNid;
        $insurn_spouseDob = $data->insurn_spouseDob;
        $insurn_gender = $data->insurn_gender;
        $insurn_relation = $data->insurn_relation;
        $insurn_name = $data->insurn_name;
        $insurn_dob = $data->insurn_dob;
        $insurn_mainIDType = $data->insurn_mainIDType;
        $insurn_mainID = $data->insurn_mainID;
        $insurn_id_expire = $data->insurn_id_expire;
        $insurn_placeofissue = $data->insurn_placeofissue;
        $approval_amount = $data->propos_amt;
        $insurn_insuranceIssueDate = $data->insurn_insuranceIssueDate;
        /* Migration */

        if ($loan_product == '80' or $loan_product == '100') {
          /* CO Borrower Details */
          $name = $data->name;
          $dateOfBirth  = $data->dateOfBirth;
          $memberNo = $data->memberNo;
          $fatherOrHusband =  $data->fatherOrHusband;
          $motherName =  $data->motherName;
          $gender =  $data->gender;
          $maritalStatus =  $data->maritalStatus;
          $education =  $data->education;
          $totalFamilyMember =  $data->totalFamilyMember;
          if (isset($data->nidOrBirth)) {
            $nidOrBirth =  $data->nidOrBirth;
          } else {
            $nidOrBirth =  $data->id_number;
          }

          $presentAddress =  $data->presentAddress;
          $permanentAddress =  $data->permanentAddress;
          if ($data->mobileNo != '' or $data->mobileNo != null) {
            $mobileNo =  $data->mobileNo;
          } else {
            $mobileNo = 0;
          }

          $tinNo =  $data->tinNo;
          $cbPersonalAsset = $data->cbPersonalAsset;
          $genderId = $data->genderId;
          $maritalStatusId = $data->maritalStatusId;
          $educationId = $data->educationId;
          if (isset($data->job_type)) {
            $job_type = $data->job_type;
          } else {
            $job_type = null;
          }
          if (isset($data->id_type)) {
            $id_type = $data->id_type;
          } else {
            $id_type = null;
          }
          if (isset($data->relationship)) {
            $relationship = $data->relationship;
          } else {
            $relationship = null;
          }
          if (isset($data->org_name)) {
            $org_name = $data->org_name;
          } else {
            $org_name = null;
          }
          if (isset($data->designation)) {
            $designation = $data->designation;
          } else {
            $designation = null;
          }
          if (isset($data->job_typeID)) {
            $job_typeID = $data->job_typeID;
          } else {
            $job_typeID = null;
          }
          if (isset($data->relationshipID)) {
            $relationshipID = $data->relationshipID;
          } else {
            $relationshipID = null;
          }
          if (isset($data->id_typeID)) {
            $id_typeID = $data->id_typeID;
          } else {
            $id_typeID = null;
          }
          if (isset($data->issueDate)) {
            $issueDate = $data->issueDate;
          } else {
            $issueDate = null;
          }
          if (isset($data->expireDate)) {
            $CoexpireDate = $data->expireDate;
          } else {
            $CoexpireDate = null;
          }
          if (isset($data->issueCountry)) {
            $issueCountry = $data->issueCountry;
          } else {
            $issueCountry = null;
          }
          // dd($genderId);
        }
        //$issueDate ,$CoexpireDate, $issueCountry
        if ($loan_product == '100') { //nirvorota
          $orgName = $data->oiOrgName;
          $oiAddress = $data->oiAddress;
          $oiDateOfJoining = $data->oiDateOfJoining;
          $oiDesignation = $data->oiDesignation;
          $oiJobType = $data->oiJobType;
          $oiPaySlip = $data->oiPaySlip;
        }
        if ($loan_product == '80') { // migration
          //Borrower passport details
          $issueDate =  $data->passportIssueDate;
          $expireDate =  $data->passportExpireDate;
          $passportOccupationId =  $data->passportOccupationId;
          $occupation =  $data->passportOccupation;
          $passportNo =  $data->passportNo;
          $passportImage =  $data->passportImage;
          $passportPermanentAddress = $data->passportPermanentAddress;

          //Borrower visa/work permit Details

          $visaDetails =  $data->visaDetails;
          $visaNo =  $data->visaNo;
          $visaIssueDate = $data->visaIssueDate;
          $visaExpireDate = $data->visaExpireDate;
          $destination =  $data->destination;
          $lastDateOfEntry =  $data->lastDateOfEntry;
          $validity =  $data->validity;
          $entryNo =  $data->entryNo;
          $orgNameAddress =  $data->orgNameAddress;
          $jobExpireDate =  $data->jobExpireDate;
          $salary =  $data->salary;
          $recruitingAgentName =  $data->recruitingAgentName;
          $visaPhoto = $data->visaPhoto;
        }
        if ($loan_product == '80' or $loan_product == '100') {   //migration and Nirvorota
          // Other Loan Information
          $organizationNameBo =  $data->organizationNameBo;
          $organizationNameCo =  $data->organizationNameCo;
          $loanTakingDateBo =  $data->loanTakingDateBo;
          $loanTakingDateCo =  $data->loanTakingDateCo;
          $loanAmountBo =  $data->loanAmountBo;
          $loanAmountCo =  $data->loanAmountCo;
          $validityBo =  $data->validityBo;
          $validityCo =  $data->validityCo;
          $dateOfPaymentBo =  $data->dateOfPaymentBo;
          $dateOfPaymentCo =  $data->dateOfPaymentCo;
          $dueAmountBo =  $data->dueAmountBo;
          $dueAmountCo =  $data->dueAmountCo;
          $hasOtherLoanBo = $data->hasOtherLoanBo;
          $hasOtherLoanCo = $data->hasOtherLoanCo;
          //$hasOtherLoanBo, $hasOtherLoanCo
        }
        /* gurantor */
        if ($loan_product != '80') {   // migration Product
          $grantor_name1 = $data->grantor_name1;
          $grantor_fatherOrHusbandName1 = $data->grantor_fatherOrHusbandName1;
          $grantor_idType1 = $data->grantor_idType1;
          $grantor_idTypeId1 = $data->grantor_idTypeId1;
          $grantor_nidNo1 = $data->grantor_idNo1;
          $grantor_dob1 = $data->grantor_dob1;
          $grantor_occupation1 = $data->grantor_occupation1;
          $grantor_presentAddress1 = $data->grantor_presentAddress1;
          $grantor_permanentAddress1 = $data->grantor_permanentAddress1;
          $grantor_mobileNo1 = $data->grantor_mobileNo1;
          $grantor_monthlyIncome1 = $data->grantor_monthlyIncome1;
          $grantor_monthlyExpense1 = $data->grantor_monthlyExpense1;
          $grantor_photo1 = $data->grantor_photo1;
          $grantor_nidFront1 = $data->grantor_nidFront1;
          $grantor_nidBack1 = $data->grantor_nidBack1;

          $grantor_name2 = $data->grantor_name2;
          $grantor_fatherOrHusbandName2 = $data->grantor_fatherOrHusbandName2;
          $grantor_idType2 = $data->grantor_idType2;
          $grantor_idTypeId2 = $data->grantor_idTypeId2;
          $grantor_nidNo2 = $data->grantor_idNo2;
          $grantor_dob2 = $data->grantor_dob2;
          $grantor_occupation2 = $data->grantor_occupation2;
          $grantor_presentAddress2 = $data->grantor_presentAddress2;
          $grantor_permanentAddress2 = $data->grantor_permanentAddress2;
          $grantor_mobileNo2 = $data->grantor_mobileNo2;
          $grantor_monthlyIncome2 = $data->grantor_monthlyIncome2;
          $grantor_monthlyExpense2 = $data->grantor_monthlyExpense2;
          $grantor_photo2 = $data->grantor_photo2;
          $grantor_nidFront2 = $data->grantor_nidFront2;
          $grantor_nidBack2 = $data->grantor_nidBack2;
          $loan_roductCode = $data->loan_productCode;
          if (isset($data->grantor_idIssueDate1)) {
            $grantor_idIssueDate1 = $data->grantor_idIssueDate1;
          } else {
            $grantor_idIssueDate1 = null;
          }
          if (isset($data->grantor_idExpireDate1)) {
            $grantor_idExpireDate1 = $data->grantor_idExpireDate1;
          } else {
            $grantor_idExpireDate1 = null;
          }
          if (isset($data->grantor_idIssueCountry1)) {
            $grantor_idIssueCountry1 = $data->grantor_idIssueCountry1;
          } else {
            $grantor_idIssueCountry1 = null;
          }
          if (isset($data->grantor_idIssueDate2)) {
            $grantor_idIssueDate2 = $data->grantor_idIssueDate2;
          } else {
            $grantor_idIssueDate2 = null;
          }
          if (isset($data->grantor_idExpireDate2)) {
            $grantor_idExpireDate2 = $data->grantor_idExpireDate2;
          } else {
            $grantor_idExpireDate2 = null;
          }
          if (isset($data->grantor_idIssueCountry2)) {
            $grantor_idIssueCountry2 = $data->grantor_idIssueCountry2;
          } else {
            $grantor_idIssueCountry2 = null;
          }
        }
        //$grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2
        /* business info*/
        if ($loan_product != '79' and $loan_product != '80' and $loan_product != '100') { // trade and agri
          //dd("HUda" . $loan_product);
          $businessName = $data->businessName;
          $businessAddress = $data->businessAddress;
          $fundingPeriod = $data->fundingPeriod;
          $ownershipType = $data->ownershipType;
          $businessType = $data->businessType;
          $tradeLicense = $data->tradeLicense;
          $licenseExpiredDate = $data->licenseExpiredDate;
          $employeeNo = $data->employeeNo;
          /* personal Asset Info */
          $totalAssetValue = $data->totalAssetValue;
        }
        if ($loan_product == '80') {
          $totalAssetValue = $data->totalAssetValue;
        }
        /* bank info */
        $bi_bankName = $data->bi_bankName;
        $bi_bankName = $data->bi_bankName;
        $bi_branch = $data->bi_branch;
        $bi_acName = $data->bi_acName;
        $bi_acType = $data->bi_acType;
        $bi_acNumber = $data->bi_acNumber;
        $bi_routingNum = $data->bi_routingNum;
        $bi_chequePhotoFront = $data->bi_chequePhotoFront;
        $bi_chequePhotoBack = $data->bi_chequePhotoFront;

        if (
          $loan_product == '79'
        ) { // Residance   Remittance
          $residence_name = $data->residence_name;
          $residence_fatherOrHusbandName = $data->residence_fatherOrHusbandName;
          $residence_idNo = $data->residence_idNo;
          $residence_countryName = $data->residence_countryName;
          $residence_workDuration = $data->residence_workDuration;
          $residence_visaExpDate = $data->residence_visaExpDate;
          $residence_loneeRelation = $data->residence_loneeRelation;
          $residence_landValidation = $data->residence_landValidation;
          $residence_landPhoto1 = $data->residence_landPhoto1;
          $residence_landPhoto2 = $data->residence_landPhoto2;
          $residence_landPhoto3 = $data->residence_landPhoto3;
          $residence_landPhoto4 = $data->residence_landPhoto4;
          //remitance
          $remittance1Date = $data->remittance1Date;
          $remittance1Amt = $data->remittance1Amt;
          $remittance1BankName = $data->remittance1BankName;
          $remittance1BranchName = $data->remittance1BranchName;
          $remittance1Pic = $data->remittance1Pic;
          $remittance2Date = $data->remittance2Date;
          $remittance2Amt = $data->remittance2Amt;
          $remittance2BankName = $data->remittance2BankName;
          $remittance2BranchName = $data->remittance2BranchName;
          $remittance2Pic = $data->remittance3Pic;
          $remittance3Date = $data->remittance3Date;
          $remittance3Amt = $data->remittance3Amt;
          $remittance3BankName = $data->remittance3BankName;
          $remittance3BranchName = $data->remittance3BranchName;
          $remittance3Pic = $data->remittance3Pic;
        }

        if ($dynamicfieldvalueLoan == '') {
          $dynamicfieldvalueLoan = null;
        }
        // $dynamicfieldvalueLoan=json_encode($loanjson);
        $checkData = DB::table($db . '.loans')->where('loan_id', $loanid)->first();
        // DB::beginTransaction();
        // try {
        $updatedate = date('Y-m-d H:i:s');
        if ($checkData == null) {
          $doc_id = DB::Table($db . '.loans')->insertGetId(['mem_id' => $mem_id, 'loan_product' => $loan_product, 'loan_duration' => $loan_duration, 'invest_sector' => $invest_sector, 'propos_amt' => $propos_amt, 'instal_amt' => $instal_amt, 'bracloan_family' => $bracloan_family, 'insurn_type' => $insurn_type, 'insurn_option' => $insurn_option, 'insurn_spouseName' => $insurn_spouseName, 'insurn_spouseNid' => $insurn_spouseNid, 'insurn_spouseDob' => $insurn_spouseDob, 'insurn_gender' => $insurn_gender, 'insurn_relation' => $insurn_relation, 'insurn_name' => $insurn_name, 'insurn_dob' => $insurn_dob, 'insurn_mainID' => $insurn_mainID,  'DynamicFieldValue' => $dynamicfieldvalueLoan, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'pin' => $pin, 'roleid' => $roleid, 'reciverrole' => $reciverrole, 'status' => $status, 'loan_id' => $loanid, 'assignedpo' => $assignedpo, 'orgno' => $orgno, 'erp_mem_id' => $erp_mem_id, 'scheme' => $scheme, "memberTypeId" => $memberTypeId, "subSectorId" => $subSectorId, "frequencyId" => $frequencyId, "insurn_mainIDType" => $insurn_mainIDType, "insurn_id_expire" => $insurn_id_expire, "insurn_placeofissue" => $insurn_placeofissue, 'surveyid' => $surveyid, 'amount_inword' => $amount_inword, 'loan_purpose' => $loan_purpose, 'loan_user' => $loan_user, 'loan_type' => $loan_type, 'brac_loancount' => $brac_loancount, 'orgmemno' => $orgmemno, 'update_at' => $updatedate, 'microinsurance' => $microInsurance, 'approval_amount' => $approval_amount, 'insurn_issuedate' => $insurn_insuranceIssueDate]);
          //$doc_id = $loanid;
          if ($loan_product != '79' and $loan_product != '80' and $loan_product != '100') {
            /*   if ($grantor_name1 != '') {
              $guarantor1 = DB::Table($db . '.guarantor_details1')->insert(['loan_id' => $doc_id, 'name' => $grantor_name1, 'father_husband_name' => $grantor_fatherOrHusbandName1, 'nid_no' => $grantor_nidNo1, 'dob' => $grantor_dob1, 'occupation' => $grantor_occupation1, 'present_address' => $grantor_presentAddress1, 'parmanent_address' => $grantor_permanentAddress1, 'mobile_no' => $grantor_mobileNo1, 'monthly_income' => $grantor_monthlyIncome1, 'monthly_expenses' => $grantor_monthlyExpense1, 'guarantor_photo' => $grantor_photo1, 'guarantor_nid_front' => $grantor_nidFront1, 'guarantor_nid_back' => $grantor_nidBack1,'updated_at' => $updatedate]);
            }
            if ($grantor_name2 != '') {
              $guarantor2 = DB::Table($db . '.guarantor_details1')->insert(['loan_id' => $doc_id, 'name' => $grantor_name2, 'father_husband_name' => $grantor_fatherOrHusbandName2, 'nid_no' => $grantor_nidNo2, 'dob' => $grantor_dob2, 'occupation' => $grantor_occupation2, 'present_address' => $grantor_presentAddress2, 'parmanent_address' => $grantor_permanentAddress2, 'mobile_no' => $grantor_mobileNo2, 'monthly_income' => $grantor_monthlyIncome2, 'monthly_expenses' => $grantor_monthlyExpense2, 'guarantor_photo' => $grantor_photo2, 'guarantor_nid_front' => $grantor_nidFront2, 'guarantor_nid_back' => $grantor_nidBack2,'updated_at' => $updatedate]);
            } */
            // Gurantors
            try {
              $this->Guarentors_Insert($db, $loanid, $grantor_name1, $grantor_fatherOrHusbandName1, $grantor_nidNo1, $grantor_dob1, $grantor_occupation1, $grantor_presentAddress1, $grantor_permanentAddress1, $grantor_mobileNo1, $grantor_monthlyIncome1, $grantor_monthlyExpense1, $grantor_photo1, $grantor_nidFront1, $grantor_nidBack1, $grantor_name2, $grantor_fatherOrHusbandName2, $grantor_nidNo2, $grantor_dob2, $grantor_occupation2, $grantor_presentAddress2, $grantor_permanentAddress2, $grantor_mobileNo2, $grantor_monthlyIncome2, $grantor_monthlyExpense2, $grantor_photo2, $grantor_nidFront2, $grantor_nidBack2, $updatedate, $branchcode, $assignedpo, $grantor_idType1, $grantor_idTypeId1, $grantor_idType2, $grantor_idTypeId2, $loan_roductCode, $grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueCountry1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2);
              // BusinessInfo
              $this->BusinessInfo($db, $loanid, $businessName, $businessAddress, $fundingPeriod, $ownershipType, $businessType, $tradeLicense, $licenseExpiredDate, $employeeNo, $updatedate, $branchcode, $assignedpo);
              //$business_info = DB::table($db . '.business_info')->insert(['loan_id' => $doc_id, 'bussiness_name' => $businessName, 'bussiness_address' => $businessAddress, 'founding_period' => $fundingPeriod, 'business_own_type' => $ownershipType, 'business_type' => $businessType, 'trade_licenseno' => $tradeLicense, 'trade_license_expdate' => $licenseExpiredDate, 'employees_no' => $employeeNo, 'updated_at' => $updatedate]);
              // Personal Assest Information
              $this->PersonalAssetInformation($db, $loanid, $totalAssetValue, $updatedate, $branchcode, $assignedpo);
              //$personal_asset_info = DB::Table($db . '.personal_asset_info')->insert(['loan_id' => $doc_id, 'total_current_price' => $totalAssetValue, 'updated_at' => $updatedate]);
              // bannk Information
              $this->BankInformation($db, $loanid, $bi_bankName, $bi_branch, $bi_acName, $bi_acType, $bi_acNumber, $bi_routingNum, $bi_chequePhotoFront, $bi_chequePhotoBack, $updatedate, $branchcode, $assignedpo);
              //$bank_info = DB::Table($db . '.bank_info')->insert(['loan_id' => $doc_id, 'bank_name' => $bi_bankName, 'branch' => $bi_branch, 'account_name' => $bi_acName, 'account_type' => $bi_acType, 'account_no' => $bi_acNumber, 'routing_no' => $bi_routingNum, 'bank_cheque_photo' => $bi_chequePhotoFront, 'bank_cheque_photo_back' => $bi_chequePhotoBack, 'updated_at' => $updatedate]);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
           }
            
          } else if ($loan_product == '79') {
            //dd($loan_product);
            try {
              $this->Guarentors_Insert($db, $loanid, $grantor_name1, $grantor_fatherOrHusbandName1, $grantor_nidNo1, $grantor_dob1, $grantor_occupation1, $grantor_presentAddress1, $grantor_permanentAddress1, $grantor_mobileNo1, $grantor_monthlyIncome1, $grantor_monthlyExpense1, $grantor_photo1, $grantor_nidFront1, $grantor_nidBack1, $grantor_name2, $grantor_fatherOrHusbandName2, $grantor_nidNo2, $grantor_dob2, $grantor_occupation2, $grantor_presentAddress2, $grantor_permanentAddress2, $grantor_mobileNo2, $grantor_monthlyIncome2, $grantor_monthlyExpense2, $grantor_photo2, $grantor_nidFront2, $grantor_nidBack2, $updatedate, $branchcode, $assignedpo, $grantor_idType1, $grantor_idTypeId1, $grantor_idType2, $grantor_idTypeId2, $loan_roductCode, $grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueCountry1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2);
              // bannk Information
              $this->BankInformation($db, $loanid, $bi_bankName, $bi_branch, $bi_acName, $bi_acType, $bi_acNumber, $bi_routingNum, $bi_chequePhotoFront, $bi_chequePhotoBack, $updatedate, $branchcode, $assignedpo);

              //$bank_info = DB::Table($db . '.bank_info')->insert(['loan_id' => $doc_id, 'bank_name' => $bi_bankName, 'branch' => $bi_branch, 'account_name' => $bi_acName, 'account_type' => $bi_acType, 'account_no' => $bi_acNumber, 'routing_no' => $bi_routingNum, 'bank_cheque_photo' => $bi_chequePhotoFront, 'bank_cheque_photo_back' => $bi_chequePhotoBack, 'updated_at' => $updatedate]);
              //Residance Information
              $this->ResidanceInformation($db, $loanid, $residence_name, $residence_fatherOrHusbandName, $residence_idNo, $residence_countryName, $residence_workDuration, $residence_visaExpDate, $residence_loneeRelation, $residence_landPhoto1, $residence_landPhoto2, $residence_landPhoto3, $residence_landPhoto4, $residence_landValidation, $updatedate, $branchcode, $assignedpo);

              //$informationResident = DB::Table($db . '.information_abroad_resident')->insert(['loan_id' => $doc_id, 'name' => $residence_name, 'father_husband_name' => $residence_fatherOrHusbandName, 'nid_birth_certificate_no' => $residence_idNo, 'country_name' => $residence_countryName, 'duration_of_working_foregin' => $residence_workDuration, 'visa_expire_duration' => $residence_visaExpDate, 'relationship_with_borrower' => $residence_loneeRelation, 'land_photo1' => $residence_landPhoto1, 'land_photo2' => $residence_landPhoto2, 'land_photo3' => $residence_landPhoto3, 'land_photo4' => $residence_landPhoto4, 'land_validation' => $residence_landValidation, 'updated_at' => $updatedate]);
              //Remittance Information
              $this->RemittanceInformation($db, $loanid, $remittance1Date, $remittance1Amt, $remittance1BankName, $remittance1BranchName, $remittance1Pic, $remittance2Date, $remittance2Amt, $remittance2BankName, $remittance2BranchName, $remittance2Pic, $remittance3Date, $remittance3Amt, $remittance3BankName, $remittance3BranchName, $remittance3Pic, $updatedate, $branchcode, $assignedpo);
              //$Remittance = DB::Table($db . '.information_remittances')->insert(['loan_id' => $doc_id, 'remittance1_date' => $remittance1Date, 'remittance1_amount' => $remittance1Amt, 'remittance1_bank_name' => $remittance1BankName, 'remittance1_branch' => $remittance1BranchName, 'remittance1_photo' => $remittance1Pic, 'remittance2_date' => $remittance2Date, 'remittance2_amount' => $remittance2Amt, 'remittance2_bank_name' => $remittance2BankName, 'remittance2_branch' => $remittance2BranchName, 'remittance2_photo' => $remittance2Pic, 'remittance3_date' => $remittance3Date, 'remittance3_amount' => $remittance3Amt, 'remittance3_bank_name' => $remittance3BankName, 'remittance3_branch' => $remittance3BranchName, 'remittance3_photo' => $remittance3Pic, 'updated_at' => $updatedate]);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
            }
            
          } else if ($loan_product == '80') {
            try {
              $this->PersonalAssetInformation($db, $loanid, $totalAssetValue, $updatedate, $branchcode, $assignedpo);
              // Co Borrower Details
              $this->CO_Borrower_Details($db, $loanid, $name, $dateOfBirth, $memberNo, $fatherOrHusband, $motherName, $gender, $maritalStatus, $education, $totalFamilyMember, $nidOrBirth, $presentAddress, $permanentAddress, $mobileNo, $tinNo, $branchcode, $assignedpo, $updatedate, $cbPersonalAsset, $genderId, $maritalStatusId, $educationId, $job_type, $id_type, $relationship, $org_name, $designation, $job_typeID, $relationshipID, $id_typeID, $issueDate, $CoexpireDate, $issueCountry);
              // Borrower Pssport and Visa Details
              $this->Borrower_PassPort_Visa_Details($db, $loanid, $issueDate, $expireDate, $occupation, $passportNo, $passportImage, $visaDetails, $visaNo, $visaIssueDate, $visaExpireDate, $destination, $lastDateOfEntry, $validity, $entryNo, $orgNameAddress, $jobExpireDate, $salary, $recruitingAgentName, $visaPhoto, $branchcode, $assignedpo, $updatedate, $passportOccupationId, $passportPermanentAddress);
              //Other Loan Information
              $this->OtherLoanInformation($db, $loanid, $organizationNameBo, $organizationNameCo, $loanTakingDateBo, $loanTakingDateCo, $loanAmountBo, $loanAmountCo, $validityBo, $validityCo, $dateOfPaymentBo, $dateOfPaymentCo, $dueAmountBo, $dueAmountCo, $branchcode, $assignedpo, $updatedate, $hasOtherLoanBo, $hasOtherLoanCo);
              // Bank Information
              $this->BankInformation($db,$loanid,$bi_bankName,$bi_branch,$bi_acName,$bi_acType,$bi_acNumber,$bi_routingNum,$bi_chequePhotoFront,$bi_chequePhotoBack,$updatedate,$branchcode,$assignedpo);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
           }
            
          } else if ($loan_product == '100') {
            try {
              // Gurantors
              $this->Guarentors_Insert($db, $loanid, $grantor_name1, $grantor_fatherOrHusbandName1, $grantor_nidNo1, $grantor_dob1, $grantor_occupation1, $grantor_presentAddress1, $grantor_permanentAddress1, $grantor_mobileNo1, $grantor_monthlyIncome1, $grantor_monthlyExpense1, $grantor_photo1, $grantor_nidFront1, $grantor_nidBack1, $grantor_name2, $grantor_fatherOrHusbandName2, $grantor_nidNo2, $grantor_dob2, $grantor_occupation2, $grantor_presentAddress2, $grantor_permanentAddress2, $grantor_mobileNo2, $grantor_monthlyIncome2, $grantor_monthlyExpense2, $grantor_photo2, $grantor_nidFront2, $grantor_nidBack2, $updatedate, $branchcode, $assignedpo, $grantor_idType1, $grantor_idTypeId1, $grantor_idType2, $grantor_idTypeId2, $loan_roductCode, $grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueCountry1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2);
              //Other Loan Information
              $this->OtherLoanInformation($db, $loanid, $organizationNameBo, $organizationNameCo, $loanTakingDateBo, $loanTakingDateCo, $loanAmountBo, $loanAmountCo, $validityBo, $validityCo, $dateOfPaymentBo, $dateOfPaymentCo, $dueAmountBo, $dueAmountCo, $branchcode, $assignedpo, $updatedate, $hasOtherLoanBo, $hasOtherLoanCo);
              // Co Borrower Details
              $this->CO_Borrower_Details($db, $loanid, $name, $dateOfBirth, $memberNo, $fatherOrHusband, $motherName, $gender, $maritalStatus, $education, $totalFamilyMember, $nidOrBirth, $presentAddress, $permanentAddress, $mobileNo, $tinNo, $branchcode, $assignedpo, $updatedate, $cbPersonalAsset, $genderId, $maritalStatusId, $educationId, $job_type, $id_type, $relationship, $org_name, $designation, $job_typeID, $relationshipID, $id_typeID, $issueDate, $CoexpireDate, $issueCountry);
              // Bank Information
              $this->BankInformation($db,$loanid,$bi_bankName,$bi_branch,$bi_acName,$bi_acType,$bi_acNumber,$bi_routingNum,$bi_chequePhotoFront,$bi_chequePhotoBack,$updatedate,$branchcode,$assignedpo);
              // Borrower Office Ino
              $this->Borrower_Office_Info( $db, $loanid, $orgName, $oiAddress, $oiDateOfJoining, $oiDesignation, $oiJobType, $oiPaySlip, $branchcode, $assignedpo, $updatedate);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
           }
            
          }
        } else {
          $doc_id = $checkData->id;
          $loan_id = $checkData->loan_id;
          DB::Table($db . '.loans')->where('loan_id', $loan_id)->update(['mem_id' => $mem_id, 'loan_product' => $loan_product, 'loan_duration' => $loan_duration, 'invest_sector' => $invest_sector, 'propos_amt' => $propos_amt, 'instal_amt' => $instal_amt, 'bracloan_family' => $bracloan_family, 'insurn_type' => $insurn_type, 'insurn_option' => $insurn_option, 'insurn_spouseName' => $insurn_spouseName, 'insurn_spouseNid' => $insurn_spouseNid, 'insurn_spouseDob' => $insurn_spouseDob, 'insurn_gender' => $insurn_gender, 'insurn_relation' => $insurn_relation, 'insurn_name' => $insurn_name, 'insurn_dob' => $insurn_dob, 'insurn_mainID' => $insurn_mainID,  'DynamicFieldValue' => $dynamicfieldvalueLoan, 'projectcode' => $projectcode, 'branchcode' => $branchcode, 'pin' => $pin, 'roleid' => $roleid, 'reciverrole' => $reciverrole, 'status' => $status, 'loan_id' => $loanid, 'assignedpo' => $assignedpo, 'orgno' => $orgno, 'erp_mem_id' => $erp_mem_id, 'scheme' => $scheme, "memberTypeId" => $memberTypeId, "subSectorId" => $subSectorId, "frequencyId" => $frequencyId, "insurn_mainIDType" => $insurn_mainIDType, "insurn_id_expire" => $insurn_id_expire, "insurn_placeofissue" => $insurn_placeofissue, 'surveyid' => $surveyid, 'amount_inword' => $amount_inword, 'loan_purpose' => $loan_purpose, 'loan_user' => $loan_user, 'loan_type' => $loan_type, 'brac_loancount' => $brac_loancount, 'orgmemno' => $orgmemno, 'update_at' => $updatedate, 'microinsurance' => $microInsurance, 'approval_amount' => $approval_amount, 'insurn_issuedate' => $insurn_insuranceIssueDate]);
          // Trade and Agri
          if ($loan_product != '79' and $loan_product != '80' and $loan_product != '100') {
            try {
              $this->Guarentors_Insert($db, $loanid, $grantor_name1, $grantor_fatherOrHusbandName1, $grantor_nidNo1, $grantor_dob1, $grantor_occupation1, $grantor_presentAddress1, $grantor_permanentAddress1, $grantor_mobileNo1, $grantor_monthlyIncome1, $grantor_monthlyExpense1, $grantor_photo1, $grantor_nidFront1, $grantor_nidBack1, $grantor_name2, $grantor_fatherOrHusbandName2, $grantor_nidNo2, $grantor_dob2, $grantor_occupation2, $grantor_presentAddress2, $grantor_permanentAddress2, $grantor_mobileNo2, $grantor_monthlyIncome2, $grantor_monthlyExpense2, $grantor_photo2, $grantor_nidFront2, $grantor_nidBack2, $updatedate, $branchcode, $assignedpo, $grantor_idType1, $grantor_idTypeId1, $grantor_idType2, $grantor_idTypeId2, $loan_roductCode, $grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueCountry1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2);
              // bannk Information
              $this->BankInformation($db,$loanid,$bi_bankName,$bi_branch,$bi_acName,$bi_acType,$bi_acNumber,$bi_routingNum,$bi_chequePhotoFront,$bi_chequePhotoBack,$updatedate,$branchcode,$assignedpo);
              //Business Information
              $this->BusinessInfo($db, $loanid, $businessName, $businessAddress, $fundingPeriod, $ownershipType, $businessType, $tradeLicense, $licenseExpiredDate, $employeeNo, $updatedate, $branchcode, $assignedpo);
              //Personal Assest Information
              $this->PersonalAssetInformation($db, $loanid, $totalAssetValue, $updatedate, $branchcode, $assignedpo);
              // $business_info = DB::table($db . '.business_info')->where('loan_id', $doc_id)->update(['loan_id' => $doc_id, 'bussiness_name' => $businessName, 'bussiness_address' => $businessAddress, 'founding_period' => $fundingPeriod, 'business_own_type' => $ownershipType, 'business_type' => $businessType, 'trade_licenseno' => $tradeLicense, 'trade_license_expdate' => $licenseExpiredDate, 'employees_no' => $employeeNo, 'updated_at' => $updatedate]);
              //$personal_asset_info = DB::Table($db . '.personal_asset_info')->where('loan_id', $doc_id)->update(['loan_id' => $doc_id, 'total_current_price' => $totalAssetValue, 'updated_at' => $updatedate]);

              //$bank_info = DB::Table($db . '.bank_info')->where('loan_id', $doc_id)->update(['loan_id' => $doc_id, 'bank_name' => $bi_bankName, 'branch' => $bi_branch, 'account_name' => $bi_acName, 'account_type' => $bi_acType, 'account_no' => $bi_acNumber, 'routing_no' => $bi_routingNum, 'bank_cheque_photo' => $bi_chequePhotoFront, 'bank_cheque_photo_back' => $bi_chequePhotoBack, 'updated_at' => $updatedate]);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
            }  
            
          }
          // Remittance Info
          else if ($loan_product == '79') { //Remittance
            try {
              $this->Guarentors_Insert($db, $loanid, $grantor_name1, $grantor_fatherOrHusbandName1, $grantor_nidNo1, $grantor_dob1, $grantor_occupation1, $grantor_presentAddress1, $grantor_permanentAddress1, $grantor_mobileNo1, $grantor_monthlyIncome1, $grantor_monthlyExpense1, $grantor_photo1, $grantor_nidFront1, $grantor_nidBack1, $grantor_name2, $grantor_fatherOrHusbandName2, $grantor_nidNo2, $grantor_dob2, $grantor_occupation2, $grantor_presentAddress2, $grantor_permanentAddress2, $grantor_mobileNo2, $grantor_monthlyIncome2, $grantor_monthlyExpense2, $grantor_photo2, $grantor_nidFront2, $grantor_nidBack2, $updatedate, $branchcode, $assignedpo, $grantor_idType1, $grantor_idTypeId1, $grantor_idType2, $grantor_idTypeId2, $loan_roductCode, $grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueCountry1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2);
              // bannk Information
              $this->BankInformation($db,$loanid,$bi_bankName,$bi_branch,$bi_acName,$bi_acType,$bi_acNumber,$bi_routingNum,$bi_chequePhotoFront,$bi_chequePhotoBack,$updatedate,$branchcode,$assignedpo);
              //Residance Information
              $this->ResidanceInformation($db,$loanid,$residence_name,$residence_fatherOrHusbandName,$residence_idNo,$residence_countryName,$residence_workDuration,$residence_visaExpDate,$residence_loneeRelation,$residence_landPhoto1,$residence_landPhoto2,$residence_landPhoto3,$residence_landPhoto4,$residence_landValidation,$updatedate,$branchcode,$assignedpo);
              //Remittance Information
              $this->RemittanceInformation($db, $loanid, $remittance1Date, $remittance1Amt, $remittance1BankName, $remittance1BranchName, $remittance1Pic, $remittance2Date, $remittance2Amt, $remittance2BankName, $remittance2BranchName, $remittance2Pic, $remittance3Date, $remittance3Amt, $remittance3BankName, $remittance3BranchName, $remittance3Pic, $updatedate, $branchcode, $assignedpo);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
            }
            
          } else if ($loan_product == '80') { //Migration
            try {
              $this->PersonalAssetInformation($db, $loanid, $totalAssetValue, $updatedate, $branchcode, $assignedpo);
              // Co Borrower Details
              $this->CO_Borrower_Details($db, $loanid, $name, $dateOfBirth, $memberNo, $fatherOrHusband, $motherName, $gender, $maritalStatus, $education, $totalFamilyMember, $nidOrBirth, $presentAddress, $permanentAddress, $mobileNo, $tinNo, $branchcode, $assignedpo, $updatedate, $cbPersonalAsset, $genderId, $maritalStatusId, $educationId, $job_type, $id_type, $relationship, $org_name, $designation, $job_typeID, $relationshipID, $id_typeID, $issueDate, $CoexpireDate, $issueCountry);
              // Borrower Pssport and Visa Details
              $this->Borrower_PassPort_Visa_Details($db, $loanid, $issueDate, $expireDate, $occupation, $passportNo, $passportImage, $visaDetails, $visaNo, $visaIssueDate, $visaExpireDate, $destination, $lastDateOfEntry, $validity, $entryNo, $orgNameAddress, $jobExpireDate, $salary, $recruitingAgentName, $visaPhoto, $branchcode, $assignedpo, $updatedate, $passportOccupationId, $passportPermanentAddress);
              //Other Loan Information
              $this->OtherLoanInformation($db, $loanid, $organizationNameBo, $organizationNameCo, $loanTakingDateBo, $loanTakingDateCo, $loanAmountBo, $loanAmountCo, $validityBo, $validityCo, $dateOfPaymentBo, $dateOfPaymentCo, $dueAmountBo, $dueAmountCo, $branchcode, $assignedpo, $updatedate, $hasOtherLoanBo, $hasOtherLoanCo);
              // Bank Information
              $this->BankInformation($db,$loanid,$bi_bankName,$bi_branch,$bi_acName,$bi_acType,$bi_acNumber,$bi_routingNum,$bi_chequePhotoFront,$bi_chequePhotoBack,$updatedate,$branchcode,$assignedpo);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
            }
            
          } else if ($loan_product == '100') { //Nirvorota
            // Gurantors
            try {
              $this->Guarentors_Insert($db, $loanid, $grantor_name1, $grantor_fatherOrHusbandName1, $grantor_nidNo1, $grantor_dob1, $grantor_occupation1, $grantor_presentAddress1, $grantor_permanentAddress1, $grantor_mobileNo1, $grantor_monthlyIncome1, $grantor_monthlyExpense1, $grantor_photo1, $grantor_nidFront1, $grantor_nidBack1, $grantor_name2, $grantor_fatherOrHusbandName2, $grantor_nidNo2, $grantor_dob2, $grantor_occupation2, $grantor_presentAddress2, $grantor_permanentAddress2, $grantor_mobileNo2, $grantor_monthlyIncome2, $grantor_monthlyExpense2, $grantor_photo2, $grantor_nidFront2, $grantor_nidBack2, $updatedate, $branchcode, $assignedpo, $grantor_idType1, $grantor_idTypeId1, $grantor_idType2, $grantor_idTypeId2, $loan_roductCode, $grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueCountry1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2);
              //Other Loan Information
              $this->OtherLoanInformation($db, $loanid, $organizationNameBo, $organizationNameCo, $loanTakingDateBo, $loanTakingDateCo, $loanAmountBo, $loanAmountCo, $validityBo, $validityCo, $dateOfPaymentBo, $dateOfPaymentCo, $dueAmountBo, $dueAmountCo, $branchcode, $assignedpo, $updatedate, $hasOtherLoanBo, $hasOtherLoanCo);
              // Co Borrower Details
              $this->CO_Borrower_Details($db, $loanid, $name, $dateOfBirth, $memberNo, $fatherOrHusband, $motherName, $gender, $maritalStatus, $education, $totalFamilyMember, $nidOrBirth, $presentAddress, $permanentAddress, $mobileNo, $tinNo, $branchcode, $assignedpo, $updatedate, $cbPersonalAsset, $genderId, $maritalStatusId, $educationId, $job_type, $id_type, $relationship, $org_name, $designation, $job_typeID, $relationshipID, $id_typeID, $issueDate, $CoexpireDate, $issueCountry);
              // Bank Information
              $this->BankInformation($db,$loanid,$bi_bankName,$bi_branch,$bi_acName,$bi_acType,$bi_acNumber,$bi_routingNum,$bi_chequePhotoFront,$bi_chequePhotoBack,$updatedate,$branchcode,$assignedpo);
              // Borrower Office Ino
              $this->Borrower_Office_Info($db,$loanid,$orgName,$oiAddress,$oiDateOfJoining,$oiDesignation,$oiJobType,$oiPaySlip,$branchcode,$assignedpo,$updatedate);
            } catch (\Exception $e) {
              $expetion =  $e->getMessage();
              DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
            }
            
          }
        }
        /* RCA Part */
        if ($loan_product != '80') {

          /* income info */
          if (isset($dataRca->businessIncome)) {
            $businessIncome = $dataRca->businessIncome;
          } else {
            $businessIncome = null;
          }
          if (isset($dataRca->jobIncome)) {
            $jobIncome = $dataRca->jobIncome;
          } else {
            $jobIncome = null;
          }
          if (isset($dataRca->familyBusinessIncome)) {
            $familyBusinessIncome = $dataRca->familyBusinessIncome;
          } else {
            $familyBusinessIncome = null;
          }
          if (isset($dataRca->remittanceIncome)) {
            $remittanceIncome = $dataRca->remittanceIncome;
          } else {
            $remittanceIncome = null;
          }

          if (isset($dataRca->otherBusinessIncome)) {
            $otherBusinessIncome = $dataRca->otherBusinessIncome;
          } else {
            $otherBusinessIncome = null;
          }
          if (isset($dataRca->monthlyIncome)) {
            $monthlyIncome = $dataRca->monthlyIncome;
          } else {
            $monthlyIncome = null;
          }
          if (isset($dataRca->monthlyIncomeCo)) {
            $monthlyIncomeCo = $dataRca->monthlyIncomeCo;
          } else {
            $monthlyIncomeCo = null;
          }
          if (isset($dataRca->incomeType)) {
            $incomeType = $dataRca->incomeType;
          } else {
            $incomeType = 0;
          }
          $othersIncome = $dataRca->othersIncome;
          $totalIncome = $dataRca->totalIncome;
          /* expense info */
          $houseRent = $dataRca->houseRent;
          $utilityBill = $dataRca->utilityBill;
          $healthEducation = $dataRca->healthEducation;
          $dailyExpense = $dataRca->dailyExpense;
          $businessExpense = $dataRca->businessExpense;
          $loanInstallmentExpense = $dataRca->loanInstallmentExpense;
          $totalExpense = $dataRca->totalExpense;
        }

        /* Check List */

        /**************************** COMMON CHECK LIST **************************************** */
        $commitmentLetter = $dataRca->commitmentLetter;
        $collateralBond = $dataRca->collateralBond;
        $investigateReport = $dataRca->investigateReport;
        $bankStatement = $dataRca->bankStatement;
        $securityCheck = $dataRca->securityCheck;
        $originalDeed = $dataRca->originalDeed;
        $bayaDeed = $dataRca->bayaDeed;
        $duplicateDoc = $dataRca->duplicateDoc;
        $dcr = $dataRca->dcr;
        $dismissalForm = $dataRca->dismissalForm;
        $saOriginalPaper = $dataRca->saOriginalPaper;
        $rsOriginalPaper = $dataRca->rsOriginalPaper;
        $taxReceipt = $dataRca->taxReceipt;
        $heirCertificate = $dataRca->heirCertificate;
        $agreement = $dataRca->agreement;
        $other = $dataRca->other;
        $remarks = $dataRca->remarks;
        $pitt_deed = $dataRca->pittDead;
        $position_deed = $dataRca->positionDeed;
        /*if ($loan_product == '79') {
          $isFamilyMemKnow = $data->isFamilyMemKnow;
          $isLoanerKnow = $data->isLoanerKnow;
          $talkForeignResident = $data->talkForeignResident;
          $tellResponsibility = $data->tellResponsibility;
          $isPermanent = $data->isPermanent;
        }*/
        /*****************************************Loan Check List **********************************/
        if ($loan_product != '79' and $loan_product != '80' and $loan_product != '100') {
          $isKnownLoan = $dataRca->isKnownLoan;
          $isKnownCondition = $dataRca->isKnownCondition;
          $isSectorCorrect = $dataRca->isSectorCorrect;
          $isProfitableLocation  = $dataRca->isProfitableLocation;
          $isGuarantorInformed = $dataRca->isGuarantorInformed;
          $isLoanSectorProfitable = $dataRca->isLoanSectorProfitable;
          //$isKnownLoan,$isKnownCondition, $isSectorCorrect, $isProfitableLocation,$isGuarantorInformed,$isLoanSectorProfitable
        }
        if ($loan_product == '100') {
          $hasGuarantorLiabilityDiscussed = $dataRca->hasGuarantorLiabilityDiscussed;
          $isPayslipSalaryVerified = $dataRca->isPayslipSalaryVerified;
          $hasApplicantWorkingForOneYear = $dataRca->hasApplicantWorkingForOneYear;
          $isSameFamilyHaveOtherLoanPending = $dataRca->isSameFamilyHaveOtherLoanPending;
          $haveNecessaryCheckSheetCollected = $dataRca->haveNecessaryCheckSheetCollected;
          $isApplicantGuarantorOfNirvorotaLoan = $dataRca->isApplicantGuarantorOfNirvorotaLoan;
          $haveTwoReferencePersonContacted = $dataRca->haveTwoReferencePersonContacted;
          $isAmountAndTypeOfLoanFair = $dataRca->isAmountAndTypeOfLoanFair;
        }
        if ($loan_product == '79') {
          $isFamilyMemKnow =  $dataRca->isFamilyMemKnow;
          $isLoanerKnow =  $dataRca->isLoanerKnow;
          $talkForeignResident =  $dataRca->talkForeignResident;
          $tellResponsibility =  $dataRca->tellResponsibility;
          $ableToPay =  $dataRca->ableToPay;
          $isPermanent =  $dataRca->isPermanent;
        }
        /*if ($loan_product != '79' and $loan_product != '80' and $loan_product != '100') {
          $isKnownLoan = $dataRca->isKnownLoan;
          $isSectorCorrect = $dataRca->isSectorCorrect;
          $isProfitableLocation = $dataRca->isProfitableLocation;
          $isGuarantorInformed = $dataRca->isGuarantorInformed;
          $isLoanSectorProfitable = $dataRca->isLoanSectorProfitable;
        }*/
        /* assest lib*/
        if ($loan_product != '80') {
          $monthly_instal = $dataRca->monthly_instal ?? null;
          $debt = $dataRca->debt ?? null;
          $monthly_cash = $dataRca->monthly_cash ?? null;
          $instal_proposloan = $dataRca->instal_proposloan ?? null;
          $dynamicfieldvalueRca = $dataRca->extra;
        } else {
          $monthly_instal = null;
          $debt = null;
          $monthly_cash = null;
          $instal_proposloan = null;
          $dynamicfieldvalueRca = null;
        }

        if ($dynamicfieldvalueRca == '') {
          $dynamicfieldvalueRca = null;
        }
        if ($checkData == null) {
          try {
            if ($loan_product != '80') {
              $this->RcaDataStore($db, $doc_id, $monthly_instal, $debt, $monthly_cash, $instal_proposloan, $dynamicfieldvalueRca);
  
              // DB::Table($db . '.rca')->insert([
              //   'loan_id' => $doc_id, 'primary_earner' => 1, 'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan,
              //   'DynamicFieldValue' => $dynamicfieldvalueRca
              // ]);
            } else {
              $this->RcaDataStore($db,$doc_id,$monthly_instal,$debt,$monthly_cash,$instal_proposloan,$dynamicfieldvalueRca);
              // DB::Table($db . '.rca')->insert([
              //   'loan_id' => $doc_id, 'primary_earner' => 1, 'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan,
              //   'DynamicFieldValue' => $dynamicfieldvalueRca
              // ]);
            }
            if ($loan_product != '79' and $loan_product != '80' and $loan_product != '100') {
              $this->IncomeInformation($db, $loanid, $businessIncome, $jobIncome, $familyBusinessIncome, $otherBusinessIncome, $remittanceIncome, $othersIncome, $totalIncome, $updatedate, $branchcode, $assignedpo, $monthlyIncome, $monthlyIncomeCo, $incomeType);
              $this->ExpenseInformation($db, $loanid, $houseRent, $utilityBill, $healthEducation, $dailyExpense, $businessExpense, $loanInstallmentExpense, $totalExpense, $updatedate, $branchcode, $assignedpo);
              $this->Trade_Agri_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $isKnownLoan, $isKnownCondition, $isSectorCorrect, $isProfitableLocation, $isGuarantorInformed, $isLoanSectorProfitable);
              //$this->TradeAgri($db, $doc_id, $branchcode, $assignedpo, $updatedate, $isKnownLoan, $isSectorCorrect, $isProfitableLocation, $isGuarantorInformed, $isLoanSectorProfitable);
              //$income_info = DB::Table($db . '.income_info')->insert(['loan_id' => $doc_id, 'business_income' => $businessIncome, 'job_income' => $jobIncome, 'family_member_income' => $familyBusinessIncome, 'others_business_income' => $otherBusinessIncome, 'remittance_income' => $remittanceIncome, 'others_income' => $othersIncome, 'total_income' => $totalIncome, 'updated_at' => $updatedate]);
  
              // $expense_info = DB::Table($db . '.expenses_info')->insert(['loan_id' => $doc_id, 'house_rent' => $houseRent, 'utility_bil' => $utilityBill, 'health_education_expns' => $healthEducation, 'others_daily_expns' => $dailyExpense, 'business_expns' => $businessExpense, 'bank_loan_instlmnt_savings' => $loanInstallmentExpense, 'total_expns' => $totalExpense, 'updated_at' => $updatedate]);
              //$checkList = DB::Table($db . '.checklist')->insert(['family_member_knwo_loan' => $isKnownLoan, 'loan_borrower_know_condition' => $isKnownCondition, 'use_of_loan_money_correct' => $isSectorCorrect, 'business_profitable_location' => $isProfitableLocation, 'have_both_guarantors' => $isGuarantorInformed, 'loan_sector_profitable' => $isLoanSectorProfitable]);
            }
  
            if ($loan_product == '79') {
              $this->IncomeInformation($db,$loanid,$businessIncome,$jobIncome,$familyBusinessIncome,$otherBusinessIncome,$remittanceIncome,$othersIncome,$totalIncome,$updatedate,$branchcode,$assignedpo,$monthlyIncome,$monthlyIncomeCo,$incomeType);
              $this->ExpenseInformation($db, $loanid, $houseRent, $utilityBill, $healthEducation, $dailyExpense, $businessExpense, $loanInstallmentExpense, $totalExpense, $updatedate, $branchcode, $assignedpo);
              $this->Remittace_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $isFamilyMemKnow, $isLoanerKnow, $talkForeignResident, $tellResponsibility, $ableToPay, $isPermanent);
              //$income_info = DB::Table($db . '.income_info')->insert(['loan_id' => $doc_id, 'business_income' => $businessIncome, 'job_income' => $jobIncome, 'family_member_income' => $familyBusinessIncome, 'others_business_income' => $otherBusinessIncome, 'remittance_income' => $remittanceIncome, 'others_income' => $othersIncome, 'total_income' => $totalIncome, 'updated_at' => $updatedate]);
  
              //$expense_info = DB::Table($db . '.expenses_info')->insert(['loan_id' => $doc_id, 'house_rent' => $houseRent, 'utility_bil' => $utilityBill, 'health_education_expns' => $healthEducation, 'others_daily_expns' => $dailyExpense, 'business_expns' => $businessExpense, 'bank_loan_instlmnt_savings' => $loanInstallmentExpense, 'total_expns' => $totalExpense, 'updated_at' => $updatedate]);
              //$checkList = DB::Table($db . '.checklist')->insert(['family_member_knwo_loan' => $isKnownLoan, 'loan_borrower_know_condition' => $isKnownCondition, 'use_of_loan_money_correct' => $isSectorCorrect, 'business_profitable_location' => $isProfitableLocation, 'have_both_guarantors' => $isGuarantorInformed, 'loan_sector_profitable' => $isLoanSectorProfitable]);
            }
            if ($loan_product == '100') {
              $this->IncomeInformation($db, $loanid, $businessIncome, $jobIncome, $familyBusinessIncome, $otherBusinessIncome, $remittanceIncome, $othersIncome, $totalIncome, $updatedate, $branchcode, $assignedpo, $monthlyIncome, $monthlyIncomeCo, $incomeType);
              $this->Nirvorota_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $hasGuarantorLiabilityDiscussed, $isPayslipSalaryVerified, $hasApplicantWorkingForOneYear, $isSameFamilyHaveOtherLoanPending, $haveNecessaryCheckSheetCollected, $isApplicantGuarantorOfNirvorotaLoan, $haveTwoReferencePersonContacted, $isAmountAndTypeOfLoanFair);
            }
          } catch (\Exception $e) {
            $expetion =  $e->getMessage();
            DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
          }
          
        } else {
          try {
            if ($loan_product != '80') {
              $this->RcaDataStore($db,$doc_id,$monthly_instal,$debt,$monthly_cash,$instal_proposloan,$dynamicfieldvalueRca);
              // DB::Table($db . '.rca')->where('loan_id', $doc_id)->update([
              //   'loan_id' => $doc_id, 'primary_earner' => 1, 'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan,
              //   'DynamicFieldValue' => $dynamicfieldvalueRca
              // ]);
            } else {
              $this->RcaDataStore($db,$doc_id,$monthly_instal,$debt,$monthly_cash,$instal_proposloan,$dynamicfieldvalueRca);
              // DB::Table($db . '.rca')->where('loan_id', $doc_id)->update([
              //   'loan_id' => $doc_id, 'primary_earner' => 1, 'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan,
              //   'DynamicFieldValue' => $dynamicfieldvalueRca
              // ]);
            }
            if ($loan_product != '79' and $loan_product != '80' and $loan_product != '100') {
              $this->IncomeInformation($db, $loanid, $businessIncome, $jobIncome, $familyBusinessIncome, $otherBusinessIncome, $remittanceIncome, $othersIncome, $totalIncome, $updatedate, $branchcode, $assignedpo, $monthlyIncome, $monthlyIncomeCo, $incomeType);
              $this->ExpenseInformation($db, $loanid, $houseRent, $utilityBill, $healthEducation, $dailyExpense, $businessExpense, $loanInstallmentExpense, $totalExpense, $updatedate, $branchcode, $assignedpo);
              $this->Trade_Agri_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $isKnownLoan, $isKnownCondition, $isSectorCorrect, $isProfitableLocation, $isGuarantorInformed, $isLoanSectorProfitable);
              //$this->TradeAgri($db, $doc_id, $branchcode, $assignedpo, $updatedate, $isKnownLoan, $isSectorCorrect, $isProfitableLocation, $isGuarantorInformed, $isLoanSectorProfitable);
              // $income_info = DB::Table($db . '.income_info')->where('loan_id', $doc_id)->update(['loan_id' => $doc_id, 'business_income' => $businessIncome, 'job_income' => $jobIncome, 'family_member_income' => $familyBusinessIncome, 'others_business_income' => $otherBusinessIncome, 'remittance_income' => $remittanceIncome, 'others_income' => $othersIncome, 'total_income' => $totalIncome, 'updated_at' => $updatedate]);
  
              //$expense_info = DB::Table($db . '.expenses_info')->where('loan_id', $doc_id)->update(['loan_id' => $doc_id, 'house_rent' => $houseRent, 'utility_bil' => $utilityBill, 'health_education_expns' => $healthEducation, 'others_daily_expns' => $dailyExpense, 'business_expns' => $businessExpense, 'bank_loan_instlmnt_savings' => $loanInstallmentExpense, 'total_expns' => $totalExpense, 'updated_at' => $updatedate]);
              //  $checkList = DB::Table($db . '.checklist')->where('loan_id', $doc_id)->update(['family_member_knwo_loan' => $isKnownLoan, 'loan_borrower_know_condition' => $isKnownCondition, 'use_of_loan_money_correct' => $isSectorCorrect, 'business_profitable_location' => $isProfitableLocation, 'have_both_guarantors' => $isGuarantorInformed, 'loan_sector_profitable' => $isLoanSectorProfitable]);
            }
            if ($loan_product == '79') {
              $this->IncomeInformation($db,$loanid,$businessIncome,$jobIncome,$familyBusinessIncome,$otherBusinessIncome,$remittanceIncome,$othersIncome,$totalIncome,$updatedate,$branchcode,$assignedpo,$monthlyIncome,$monthlyIncomeCo,$incomeType);
              $this->ExpenseInformation($db, $loanid, $houseRent, $utilityBill, $healthEducation, $dailyExpense, $businessExpense, $loanInstallmentExpense, $totalExpense, $updatedate, $branchcode, $assignedpo);
              $this->Remittace_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $isFamilyMemKnow, $isLoanerKnow, $talkForeignResident, $tellResponsibility, $ableToPay, $isPermanent);
              // $income_info = DB::Table($db . '.income_info')->where('loan_id', $doc_id)->update(['loan_id' => $doc_id, 'business_income' => $businessIncome, 'job_income' => $jobIncome, 'family_member_income' => $familyBusinessIncome, 'others_business_income' => $otherBusinessIncome, 'remittance_income' => $remittanceIncome, 'others_income' => $othersIncome, 'total_income' => $totalIncome, 'updated_at' => $updatedate]);
              //dd($income_info);
              //$expense_info = DB::Table($db . '.expenses_info')->where('loan_id', $doc_id)->update(['loan_id' => $doc_id, 'house_rent' => $houseRent, 'utility_bil' => $utilityBill, 'health_education_expns' => $healthEducation, 'others_daily_expns' => $dailyExpense, 'business_expns' => $businessExpense, 'bank_loan_instlmnt_savings' => $loanInstallmentExpense, 'total_expns' => $totalExpense, 'updated_at' => $updatedate]);
            }
            if ($loan_product == '100') {
              $this->IncomeInformation($db, $loanid, $businessIncome, $jobIncome, $familyBusinessIncome, $otherBusinessIncome, $remittanceIncome, $othersIncome, $totalIncome, $updatedate, $branchcode, $assignedpo, $monthlyIncome, $monthlyIncomeCo, $incomeType);
              $this->Nirvorota_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $hasGuarantorLiabilityDiscussed, $isPayslipSalaryVerified, $hasApplicantWorkingForOneYear, $isSameFamilyHaveOtherLoanPending, $haveNecessaryCheckSheetCollected, $isApplicantGuarantorOfNirvorotaLoan, $haveTwoReferencePersonContacted, $isAmountAndTypeOfLoanFair);
            }
          } catch (\Exception $e) {
            $expetion =  $e->getMessage();
            DB::Table($db . '.loans')->where('loan_id', $loanid)->delete();
          }
          
        }
        //Common check List
        $this->Common_CheckList($db,$loanid,$branchcode,$assignedpo,$updatedate,$commitmentLetter,$collateralBond,$investigateReport,$bankStatement,$securityCheck,$originalDeed,$bayaDeed,$duplicateDoc,$dcr,$dismissalForm,$saOriginalPaper,$rsOriginalPaper,$taxReceipt,$heirCertificate,$agreement,$other,$remarks,$pitt_deed,$position_deed);
      } else {
        $result = array("status" => "E", "message" => "Invalid Project Code");
        return json_encode($result);
      }

      // } catch (\Throwable $e) {
      // 	DB::rollback();
      // 	Log::channel('daily')->info('loan data store error: ' . $e);

      // 	$result = array("status" => "E", "message" => $e);
      // 	return json_encode($result);
      // }


      $document_url = $baseUrl . "/DocumentManager?doc_id=$doc_id&projectcode=$projectcode&doc_type=loan&pin=$pin&role=0&branchcode=$branchcode&action=Request";
      // dd($document_url);
      // $document_url = "http://scm.brac.net/dcs/DocumentManager?doc_id=1&projectcode=$projectcode&doc_type=admission&pin=$pin&role=0&branchcode=$branchcode";
      // echo $document_url;
      Log::channel('daily')->info('Document_url : ' . $document_url);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $document_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $documentoutput = curl_exec($ch);
      curl_close($ch);

      $collectionfordocument = json_decode($documentoutput);

      Log::channel('daily')->info('Document_response : ' . $documentoutput);
      // dd($collection);
      // if($collectionfordocument->status=='S'){
      // 	$result = array("status"=>"S","message"=>"Data send to server");
      // 	echo json_encode($result);
      // }

      $notification_url = $baseUrl . "/NotificatioManager?projectcode=$projectcode&doc_type=loan&pin=$pin&role=0&branchcode=$branchcode&entollmentid=$loanid&action=Request&assigndpo=$assignedpo";
      // echo $notification_url;
      Log::channel('daily')->info('notification_url : ' . $notification_url);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $notification_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $notificationoutput = curl_exec($ch);
      curl_close($ch);

      $collectionfornotification = json_decode($notificationoutput);

      Log::channel('daily')->info('notification_response : ' . $notificationoutput);

      // dd($collection);
      if ($collectionfornotification->status == 'S' and $collectionfordocument->status == 'S') {
        $result = array("status" => "S", "message" => "অভিনন্দন! লোনের আবেদন সফলভাবে পাঠানো হয়েছে।\n•	আবেদনের অবস্থান জানতে নোটিফিকেশন ও স্ট্যাটাস চেক করুন।\n•	লোন প্রপোজাল স্ট্যাটাস 'ERP Approved' এ পরিবর্তন হলে সদস্যকে লোন গ্রহণের জন্য আমন্ত্রণ জানান।");
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
    // if($token=='xxxxxxxxx'){
    // 	if($projectcode=='015'){

    // 	}else{
    // 		$result = array("status"=>"E","message"=>"","Please check project");
    // 		echo json_encode($result);
    // 	}
    // }else{
    // 	$result = array("status"=>"E","message"=>"","Unauthorized Request");
    // 	echo json_encode($result);
    // }

  }

  public function AllLoanRcaData(Request $request)
  {
    $db = $this->db;
    // $projectCode = Request::input('projectcode');
    // $appid = Request::input('appid');
    // $processId = Request::input('processId');
    // $apikey = Request::input('apikey');
    $loans = DB::Table($db . '.loans')->get();
    $rca = DB::Table($db . '.rca')->get();

    if ($loans->isEmpty()) {
      $result = array("status" => "E", "message" => "Data Not Found!");
      echo json_encode($result);
    } else {
      $result = array("status" => "S", "message" => "", "loans" => $loans, "rca" => $rca);
      echo json_encode($result);
    }
  }

  public function NotificationManager(Request $request)  //dummy
  {
    $db = $this->db;
    // $projectCode = Request::input('projectcode');
    // $appid = Request::input('appid');
    // $processId = Request::input('processId');
    // $apikey = Request::input('apikey');
    $result = array("status" => "E", "message" => "Data Not Found!");
    echo json_encode($result);
  }

  public function AdmissionDataSync(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $branchcode = Request::input('branchcode');
    $projectcode = Request::input('projectcode');
    $pin = Request::input('pin');
    $branch__code = str_pad($branchcode, 4, "0", STR_PAD_LEFT);
    //after schuler have to remove this function call
    $this->GetErpPostedAdmissionData($branch__code); //erp dcs admission data sync 
    // dd(date('Y-m-d H:i:s'));

    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($branchcode != null and $pin == null) {
        // $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->orderBy('id', 'desc')->get();
        $admissionsDataWithoutPending = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'));
        $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->Where('status', '1')->unionAll($admissionsDataWithoutPending)->orderBy('id', 'desc')->get();
      } elseif ($branchcode != null and $pin != null) {
        $admissionsDataWithoutPending = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'));
        $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->Where('status', '1')->unionAll($admissionsDataWithoutPending)->orderBy('id', 'desc')->get();
        // dd($admissiondata);
        // $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('assignedpo', $pin)->orderBy('id', 'desc')->get();
        // $admissiondata = DB::table($db . '.admissions')->where('branchcode', $branchcode)->where('assignedpo', $pin)->orWhere('status', 1)->orWhere(function ($query) {
        // 	$query->where('updated_at', '<=', Carbon::now()->subMonth(2)->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now());
        // })->orderBy('id', 'desc')->get();

      } else {
        $result = array("status" => "E", "message" => "parameter missing!");
        echo json_encode($result);
      }

      if ($admissiondata->isEmpty()) {
        $result = array("status" => "E", "message" => "Data Not Found!");
        echo json_encode($result);
      } else {
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

          $WalletOwner = $WalletOwner->data_name ?? null;
          $NomineeNidType = $NomineeNidType->data_name ?? null;
          $SpuseOccupationId = $SpuseOccupationId->data_name ?? null;
          $SpouseCardType = $SpouseCardType->data_name ?? null;
          $OtherIdTypeId = $OtherIdTypeId->data_name ?? null;
          $presentUpazilaId = $presentUpazilaId->thana_name ?? null;
          $parmanentUpazilaId = $parmanentUpazilaId->thana_name ?? null;
          $PresentDistrictId = $PresentDistrictId->district_name ?? null;
          $PermanentDistrictId = $PermanentDistrictId->district_name ?? null;
          if ($dochistory == null) {
            Log::channel('daily')->info('document problem admission : ' . $data->id);
          }
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
            $checkPostedAdmission = DB::table($db . '.posted_admission')->where('admission_id', $data->entollmentid)->first();
            if ($checkPostedAdmission != null) {
              $ErpStatusId = $checkPostedAdmission->statusid;
              if ($ErpStatusId == 1) {
                $ErpStatus = 'Pending';
              } elseif ($ErpStatusId == 2) {
                $ErpStatus = 'Approved';
              } elseif ($ErpStatusId == 3) {
                $ErpStatus = 'Rejected';
              }
              $ErpRejectionReason = $checkPostedAdmission->rejectionreason;
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
          $created_at = date('Y-m-d', strtotime($data->created_at));
          $updated_at = date('Y-m-d', strtotime($data->updated_at));

          $arrayData = array(
            "id" => $data->id,
            "IsRefferal" => $data->IsRefferal,
            "RefferedById" => $data->RefferedById,
            "MemberId" => $data->MemberId,
            "MemberCateogryId" => $data->MemberCateogryId,
            "MemberCateogry" => $MemberCateogryId->categoryname,
            "ApplicantsName" => $data->ApplicantsName,
            "ApplicantSinglePic" => $data->ApplicantSinglePic,
            "MainIdType" => $MainIdTypeId->data_name,
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
            "Education" => $EducationId->data_name,
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
            "MaritalStatus" => $MaritalStatusId->data_name ?? null,
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
            "Relationship" => $RelationshipId->data_name,
            "ApplicantCpmbinedImg" => $data->ApplicantCpmbinedImg,
            "ReffererImg" => $data->ReffererImg,
            "ReffererIdImg" => $data->ReffererIdImg,
            "FrontSideOfIdImg" => $data->FrontSideOfIdImg,
            "BackSideOfIdimg" => $data->BackSideOfIdimg,
            "NomineeIdImg" => $data->NomineeIdImg,
            "DynamicFieldValue" => $data->DynamicFieldValue,
            "created_at" => $created_at,
            "updated_at" => $updated_at,
            "branchcode" => $data->branchcode,
            "projectcode" => $data->projectcode,
            "Occupation" => $Occupation->data_name,
            "OccupationId" => $data->Occupation,
            "IsBkash" => $IsBkash,
            "WalletNo" => $data->WalletNo,
            "WalletOwnerId" => $data->WalletOwner,
            "WalletOwner" => $WalletOwner,
            "NomineeName" => $data->NomineeName,
            "PrimaryEarner" => $PrimaryEarner->data_name,
            "PrimaryEarnerId" => $data->PrimaryEarner,
            "dochistory_id" => $data->dochistory_id,
            "roleid" => $data->roleid,
            "pin" => $data->pin,
            "action" => $data->action,
            "reciverrole" => $data->reciverrole,
            "status" => $status->status_name,
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
            "Gender" => $genderId->data_name,
            "SavingsProductId" => $data->SavingsProductId,
            "role_name" => $role_name->designation,
            "reciverrole_name" => $recieverrole_name->designation,
            "SurveyId" => $data->surveyid,
            "Comment" => $comment,
            "ErpStatus" => $ErpStatus,
            "ErpStatusId" => $ErpStatusId,
            "ErpRejectionReason" => $ErpRejectionReason,
            "Flag" => $data->Flag
          );
          $admissiondataary[] = $arrayData;
        }
        $result = array("status" => "S", "message" => "", "data" => $admissiondataary);
        return json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function LoanDataSync(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $dberp = $this->dberp;
    $branchcode = Request::input('branchcode');
    $projectcode = Request::input('projectcode');
    $pin = Request::input('pin');
    $appid = Request::input('appid');
    $appversion = Request::input('appversion');
    $branch__code = str_pad($branchcode, 4, "0", STR_PAD_LEFT);
    //after schuler have to remove this function call
    $this->GetErpPostedLoanData($branch__code); //erp dcs Loan data sync 
    $todate = date('Y-m-d');
    $fromdate = date('Y-m-d', strtotime($todate . ' - 10 days'));
    Log::info("fromdate" . $fromdate);
    $dataset = [];
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($branchcode != null and $pin == null) {
        // $loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->orderBy('id', 'desc')->get();
        // $loansDataWithoutPending = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'));
        // $loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->Where('status', '1')->unionAll($loansDataWithoutPending)->orderBy('id', 'desc')->get();
        //$loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate' order by id desc"));
        $loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate' order by id desc"));
      } elseif ($branchcode != null and $pin != null) {
        // $loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->orderBy('id', 'desc')->get();
        // $loansDataWithoutPending = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->where('status', '!=', '1')->where('updated_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))->where('updated_at', '>=', Carbon::now()->subMonth(6)->format('Y-m-d H:i:s'));
        //$loandata = DB::table($db . '.loans')->where('branchcode', $branchcode)->where('projectcode', $projectcode)->where('assignedpo', $pin)->Where('status', '1')->unionAll($loansDataWithoutPending)->orderBy('id', 'desc')->get();
        // $loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and assignedpo='$pin' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate' order by id desc"));
        $loandata = DB::select(DB::raw("select * from $db.loans where branchcode='$branchcode' and projectcode='$projectcode' and assignedpo='$pin' and cast(time as date) >='$fromdate' and cast(time as date) <='$todate' order by id desc"));
      } else {
        $result = array("status" => "E", "message" => "parameter missing!");
        return json_encode($result);
      }

      if (empty($loandata)) {
        $result = array("status" => "E", "message" => "Data Not Found!");
        return json_encode($result);
      } else {
        foreach ($loandata as $data) {
          $grntorRlationClient = DB::table($db . '.payload_data')->select('data_name')->where('data_type', 'relationshipId')->where('data_id', $data->grntor_rlationClient)->first();
          $investSector = DB::table($db . '.schemem_sector_subsector')->select('sectorname')->where('sectorid', $data->invest_sector)->first();
          $subSectorId = DB::table($db . '.schemem_sector_subsector')->select('subsectorname')->where('subsectorid', $data->subSectorId)->first();
          $frequencyId = DB::table($db . '.product_details')->select('frequency')->where('frequencyid', $data->frequencyId)->first();
          $scheme = DB::table($db . '.schemem_sector_subsector')->select('schemename')->where('schemeid', $data->scheme)->first();
          $role_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $data->projectcode)->where('position', $data->roleid)->first();
          $recieverrole_name = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $data->projectcode)->where('position', $data->reciverrole)->first();
          $memberTypeId = DB::table($db . '.projectwise_member_category')->select('categoryname')->where('categoryid', $data->memberTypeId)->first();
          $loan_product = DB::table($db . '.product_project_member_category')->select('productcode')->where('productid', $data->loan_product)->first();
          $loan_product_name = DB::table($db . '.product_project_member_category')->select('productname')->where('productid', $data->loan_product)->first();
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
          $grntorRlationClient  = $grntorRlationClient->data_name ?? null;
          // if ($data->status == '2') {
          // 	$checkPostedLoan = DB::table($db . '.posted_loan')->where('loan_id', $data->loan_id)->first();
          // 	if ($checkPostedLoan != null) {
          // 		$ErpStatusId = $checkPostedLoan->loanproposalstatusid;
          // 		if ($ErpStatusId == 1) {
          // 			$ErpStatus = 'Pending';
          // 		} elseif ($ErpStatusId == 2) {
          // 			$ErpStatus = 'Approved';
          // 		} elseif ($ErpStatusId == 3) {
          // 			$ErpStatus = 'Rejected';
          // 		}
          // 		$ErpRejectionReason = $checkPostedLoan->rejectionreason;
          // 	}
          // } else {
          // 	$ErpStatus = null;
          // 	$ErpStatusId = null;
          // 	$ErpRejectionReason = null;
          // }
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
          $key = '5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae';
          $UpdatedAt = "2000-01-01 00:00:00";
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
          //dd($member);
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
          // dd($time);
          $arrayData['loan'] = array(
            "id" => $data->id,
            "orgno" => $data->orgno,
            "branchcode" => $data->branchcode,
            "projectcode" => $data->projectcode,
            "loan_product" => $data->loan_product,
            "loan_product_code" => $loan_product->productcode,
            "loan_product_name" => $loan_product_name->productname,
            "loan_duration" => $data->loan_duration,
            "invest_sector_id" => $data->invest_sector,
            "invest_sector" => $investSector->sectorname,
            "scheme_id" => $data->scheme,
            "scheme" => $scheme->schemename,
            "propos_amt" => $data->propos_amt,
            "instal_amt" => $data->instal_amt,
            "bracloan_family" => $data->bracloan_family,
            "vo_leader" => $data->vo_leader,
            "recommender" => $data->recommender,
            "grntor_name" => $data->grntor_name,
            "grntor_phone" => $data->grntor_phone,
            "grntor_rlationClient" => $grntorRlationClient,
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
            "memberType" => $memberTypeId->categoryname,
            "frequencyId" => $data->frequencyId,
            "frequency" => $frequencyId->frequency,
            "subSectorId" => $data->subSectorId,
            "subSector" => $subSectorId->subsectorname,
            "insurn_mainIDTypeId" => $data->insurn_mainIDType,
            "insurn_mainIDType" => $insurnMainIDType,
            "insurn_id_expire" => $data->insurn_id_expire,
            "insurn_placeofissue" => $data->insurn_placeofissue,
            "ErpHttpStatus" => $data->ErpHttpStatus,
            "ErpErrorMessage" => $data->ErpErrorMessage,
            "ErpErrors" => $data->ErpErrors,
            "erp_loan_id" => $data->erp_loan_id,
            "role_name" => $role_name->designation,
            "reciverrole_name" => $recieverrole_name->designation,
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
          // dd($PrimaryEarner);
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
            "primary_earner_id" => $rca->primary_earner,
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
            "po_seasonal_income"  => $rca->po_seasonal_income,
            "bm_seasonal_income"  => $rca->bm_seasonal_income,
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
          $arrayData['clientInfo'] = $member;
          $dataset[] = $arrayData;
        }
        // foreach($loandata as $row){
        // 	$data['loan']=$row;
        // 	$rca=DB::table($db.'.rca')->where('loan_id',$row->id)->first();
        // 	$data['rca']=$rca;
        // 	$dataset[]=$data;
        // }
        $result = array("status" => "S", "message" => "", "data" => $dataset);
        Log::channel('daily')->info('Loan Data Sync: ' . json_encode($result));
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }


  public function BmDataModify(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $json = Request::input('json');
    // Log::channel('daily')->info('Bm Assesment Data: '.$json);
    $dataset = json_decode($json);
    // dd($dataset);
    $token = $dataset->token;
    $data = $dataset->loan_bm[0];
    $dataRca = $dataset->rca_bm[0];
    $loanid = $data->loan_id;

    $loan = DB::table($db . '.loans')->where('loan_id', $loanid)->first();
    $token = Request::input('token');
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($loan != null) {
        $id = $loan->id;
        $bm_repay_loan = $data->repay_loan;
        $bm_conduct_activity = $data->conduct_activity;
        $bm_action_required = $data->action_required;
        $bm_rca_rating = $data->rca_rating;
        //loan new
        $bm_noofChild = $data->bm_noofChild;
        $bm_earningMember = $data->bm_earningMember;
        $bm_duration = $data->bm_duration;
        $bm_hometown = $data->bm_hometown;
        $bm_landloard = $data->bm_landloard;
        $bm_recomand = $data->bm_recomand;
        $bm_occupation = $data->bm_occupation;
        $bm_aware = $data->bm_aware;
        $bm_grantor = $data->bm_grantor;
        $bm_socialAcecptRating = $data->bm_socialAcecptRating;
        $bm_grantorRating = $data->bm_grantorRating;
        $bm_clienthouse = $data->bm_clienthouse;
        $bm_remarks = $data->bm_remarks;

        DB::table($db . '.loans')->where('id', $id)->update(['bm_repay_loan' => $bm_repay_loan, 'bm_conduct_activity' => $bm_conduct_activity, 'bm_action_required' => $bm_action_required, 'bm_rca_rating' => $bm_rca_rating, 'bm_noofChild' => $bm_noofChild, 'bm_earningMember' => $bm_earningMember, 'bm_duration' => $bm_duration, 'bm_hometown' => $bm_hometown, 'bm_landloard' => $bm_landloard, 'bm_recomand' => $bm_recomand, 'bm_occupation' => $bm_occupation, 'bm_aware' => $bm_aware, 'bm_grantor' => $bm_grantor, 'bm_socialAcecptRating' => $bm_socialAcecptRating, 'bm_grantorRating' => $bm_grantorRating, 'bm_clienthouse' => $bm_clienthouse, 'bm_remarks' => $bm_remarks]);

        $rca = DB::table($db . '.rca')->where('loan_id', $id)->first();
        if ($rca != null) {
          $bm_primary_earner = $dataRca->primary_earner;
          $bm_monthlyincome_main = $dataRca->monthlyincome_main;
          $bm_monthlyincome_other = $dataRca->monthlyincome_other;
          $bm_house_rent = $dataRca->house_rent;
          $bm_food = $dataRca->food;
          $bm_education = $dataRca->education;
          $bm_medical = $dataRca->medical;
          $bm_festive = $dataRca->festive;
          $bm_utility = $dataRca->utility;
          $bm_saving = $dataRca->saving;
          $bm_other = $dataRca->other;
          $bm_monthly_instal = $dataRca->monthly_instal;
          $bm_debt = $dataRca->debt;
          $bm_monthly_cash = $dataRca->monthly_cash;
          $bm_instal_proposloan = $dataRca->instal_proposloan;

          DB::table($db . '.rca')->where('loan_id', $id)->update(['bm_primary_earner' => $bm_primary_earner, 'bm_monthlyincome_main' => $bm_monthlyincome_main, 'bm_monthlyincome_other' => $bm_monthlyincome_other, 'bm_house_rent' => $bm_house_rent, 'bm_food' => $bm_food, 'bm_education' => $bm_education, 'bm_medical' => $bm_medical, 'bm_festive' => $bm_festive, 'bm_utility' => $bm_utility, 'bm_saving' => $bm_saving, 'bm_other' => $bm_other, 'bm_monthly_instal' => $bm_monthly_instal, 'bm_debt' => $bm_debt, 'bm_monthly_cash' => $bm_monthly_cash, 'bm_instal_proposloan' => $bm_instal_proposloan]);

          $result = array("status" => "S", "message" => "", "loan_id" => $loanid);
          echo json_encode($result);
        }
      } else {
        $result = array("status" => "E", "message" => "Data not found!");
        echo json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function DocumentManager(Request $request)
  {
    // dd("Huda");
    $this->LaravelLog();
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
    Log::info("BMSubmit-doc_id=" . $doc_id . "projectcode=" . $projectcode . "doc_type=" . $doc_type . "pin=" . $pin . "role=" . $roleid . "branchcode=" . $branchcode . "action=" . $action . "entollmentid=" . $entollmentid . "comment" . $comment); //http://scmtest.brac.net/dcs/DocumentManager?doc_id=2686&projectcode=015&doc_type=loan&pin=00128662&role=0&branchcode=0611&action=Request
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
        $doc1 = DB::table($db . '.admissions')->select('assignedpo')->where('entollmentid', $entollmentid)->first();
        $assignedpo = $doc1->assignedpo;
      } elseif ($doc_type == 'loan') {
        $doc = DB::table($db . '.loans')->select('id')->where('loan_id', $entollmentid)->first();
        $doc_id = $doc->id;
        $doc1 = DB::table($db . '.loans')->select('assignedpo')->where('loan_id', $entollmentid)->first();
        $assignedpo = $doc1->assignedpo;
      }
    }
    //dd($doc_id);
    //get enrollment id by doc id
    if ($doc_id != '' and $entollmentid == '') {
      if ($doc_type == 'admission') {
        $doc = DB::table($db . '.admissions')->select('entollmentid')->where('id', $doc_id)->first();
        $entollmentid = $doc->entollmentid;
        $doc1 = DB::table($db . '.admissions')->select('assignedpo')->where('entollmentid', $entollmentid)->first();
        $assignedpo = $doc1->assignedpo;
      } elseif ($doc_type == 'loan') {
        $doc = DB::table($db . '.loans')->select('loan_id')->where('id', $doc_id)->first();
        $entollmentid = $doc->loan_id;
        $doc1 = DB::table($db . '.loans')->select('assignedpo')->where('loan_id', $entollmentid)->first();
        $assignedpo = $doc1->assignedpo;
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
          $loan_type = $document->loan_type;
          $proposeAmount = $document->approval_amount;
        }
        if ($roleid != $document->reciverrole) {
          $result = array("status" => "E", "message" => "Domument already been proccesed.");
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
          //dd($action);
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
              Log::info("Loan Test" . $doc_id . "/" . $doc_type);
              // die;
              if ($doc_type == 'loan') {
                //dd($doc_type);
                $checkApprove = $this->Approval_Amount_Check($db, $branchcode, $projectcode, $loan_type, $proposeAmount, $roleid);
              }
              $checkErpResponse = $this->documentErpPosting($doc_id, $doc_type);
              if ($checkErpResponse[0] != '200') {
                if ($doc_type == 'admission' and $projectcode == '060') {
                  $deleteadmission = DB::Table($db . '.admissions')->where('entollmentid', $entollmentid)->where('id', $doc_id)->delete();
                  $result = array("status" => "E", "httpstatus" => $checkErpResponse[0], "errors" => $checkErpResponse[1]);
                  return json_encode($result);
                  die;
                } else {
                  $bufferexits = $checkErpResponse[1];
                  if($doc_type == 'admission')
                  {
                    if(isset($bufferexits[0]->message))
                    {
                      if($bufferexits[0]->message == "Buffer Member already exists with given id.")
                      {
                        $id = $bufferexits[0]->id;
                        if($doc_type == 'admission'){
                          DB::Table($db . '.admissions')->where('entollmentid', $id)->update(['ErpStatus'=>1,'erpstatus'=>1]);
                        }
                        Log::channel('daily')->info("Bits Server message Local Status Done Type".$doc_type."Id".$id);
                      }
                    }
                    
                  }
                  else
                  {
                    //Log::info("Does Not Send3".$checkErpResponse);
                    if(isset($bufferexits[0]->message)){
                      if($bufferexits[0]->message == "Buffer Loan Proposal already exists with given id.")
                      {
                        $id = $bufferexits[0]->id;
                        DB::Table($db . '.loans')->where('loan_id', $id)->update(['ErpStatus'=>1,'erpstatus'=>1]);
                        Log::channel('daily')->info("Bits Server message Local Status Done Type".$doc_type."Id".$id);
                      }
                    }
                    
                  }
                  $result = array("status" => "E", "httpstatus" => $checkErpResponse[0], "errors" => $checkErpResponse[1]);
                  return json_encode($result);
                  die;
                }
              } else {
                //$checkErpResponse = 'OK';
                Log::channel('daily')->info("Check Approve Log" . $nextrole . "/" . $nextroledesig . "/" . $action . "/" . $reciverrole . "/" . $pin . "/" . $processid . "/" . $doc_type . "/" . $doc_id . '/' . $projectcode);
                // dd("Huda");
                Log::channel('daily')->info('Erp Response : ' . json_encode($checkErpResponse));


                $checkApprove = $this->actionForApprove($nextrole, $nextroledesig, $action, $reciverrole, $pin, $processid, $doc_type, $doc_id, $projectcode);
                // dd($checkApprove);
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
            $notification_url = $baseUrl . "/NotificatioManager?projectcode=$projectcode&doc_type=$doc_type&pin=$docpin&role=$roleid&branchcode=$branchcode&entollmentid=$entollmentid&action=$action&assigndpo=$assignedpo";

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
    $updatedAt = date('Y-m-d H:i:s');
    $currentDatetime = date("Y-m-d h:i:s");
    $action = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $action->id;
    $actioncounter = DB::table($db . '.document_history')->where('doc_id', $doc_id)->max('action_counter');
    $actioncounter = $actioncounter + 1;
    $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'projectcode' => $projectcode, 'action' => $actionid, 'roleid' => $role, 'reciverrole' => $nextrole, 'action_counter' => $actioncounter, 'comment' => $comment]);
    DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
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
    $updatedAt = date('Y-m-d H:i:s');
    if ($doc_type == 'admission') {
      DB::table($db . '.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
    } elseif ($doc_type == 'loan') {
      DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
    }

    return true;
  }

  public function actionForReject($nextrole, $nextroledesig, $action, $role, $pin, $processid, $doc_type, $doc_id, $projectcode, $comment)
  {
    $db = $this->db;
    $status = '3';
    $updatedAt = date('Y-m-d H:i:s');
    $currentDatetime = date("Y-m-d h:i:s");
    $action = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $action->id;
    $actioncounter = DB::table($db . '.document_history')->where('doc_id', $doc_id)->max('action_counter');
    $actioncounter = $actioncounter + 1;
    $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'projectcode' => $projectcode, 'action' => $actionid, 'roleid' => $role, 'reciverrole' => $nextrole, 'action_counter' => $actioncounter, 'comment' => $comment]);
    if ($doc_type == 'admission') {
      DB::table($db . '.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'reciverrole' => $nextrole, 'status' => $status, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
    } elseif ($doc_type == 'loan') {
      DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'status' => $status, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
    }

    return true;
  }

  public function actionForApprove($nextrole, $nextroledesig, $action, $role, $pin, $processid, $doc_type, $doc_id, $projectcode)
  {
    $db = $this->db;
    $status = '2';
    $erpstatus = 1;
    $currentDatetime = date("Y-m-d h:i:s");
    $updatedAt = date('Y-m-d H:i:s');
    $action = DB::table($db . '.action_lists')->select('id')->where('actionname', $action)->where('process_id', $processid)->where('projectcode', $projectcode)->first();
    $actionid = $action->id;
    //dd($actionid);
    $actioncounter = DB::table($db . '.document_history')->where('doc_id', $doc_id)->max('action_counter');
    // dd($actioncounter);
    $actioncounter = $actioncounter + 1;
    // dd($actioncounter);
    $dochistory_id = DB::Table($db . '.document_history')->insertGetId(['doc_id' => $doc_id, 'doc_type' => $doc_type, 'pin' => $pin, 'projectcode' => $projectcode, 'action' => $actionid, 'roleid' => $role, 'action_counter' => $actioncounter]);
    if ($doc_type == 'admission') {
      DB::table($db . '.admissions')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'status' => $status, 'ErpStatus' => $erpstatus, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt,'erpstatus'=>$erpstatus]);
    } elseif ($doc_type == 'loan') {
      DB::table($db . '.loans')->where('id', $doc_id)->update(['dochistory_id' => $dochistory_id, 'roleid' => $role, 'pin' => $pin, 'action' => $actionid, 'status' => $status, 'ErpStatus' => $erpstatus, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt,'erpstatus'=>$erpstatus]);
    }
    Log::channel('daily')->info('Loan Action Approve  Loan Id : ' . $doc_id);
    return true;
  }
  //end document manager functions

  //start notification Manager
  public function NotificatioManager(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $projectcode = Request::input('projectcode');
    $doc_type = Request::input('doc_type');
    $doc_id = Request::input('doc_id');
    $entollmentid = Request::input('entollmentid');
    $pin = Request::input('pin');
    $roleid = Request::input('role');
    $branchcode = Request::input('branchcode');
    $action = Request::input('action');
    $assigndpo = Request::input('assigndpo');
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
        // dd($pin);
        $inAppReturn = $this->inAppAction($roleid, $roleDesignation, $reciverrole, $reciverroleDesignation, $msgcontent, $projectcode, $pin, $processid, $doc_type, $doc_id, $entollmentid, $actionid, $action, $branchcode, $assigndpo);
        // return $inAppReturn;
      } else {
        foreach ($reciverroleary as $reciverrole) {
          //find designation
          $reciverRoleDesignationQuery = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', $reciverrole)->first();
          $reciverroleDesignation = $reciverRoleDesignationQuery->designation;

          $inAppReturn = $this->inAppAction($roleid, $roleDesignation, $reciverrole, $reciverroleDesignation, $msgcontent, $projectcode, $pin, $processid, $doc_type, $doc_id, $entollmentid, $actionid, $action, $branchcode, $assigndpo);
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

  public function inAppAction($role, $roleDesignation, $reciverrole, $reciverroleDesignation, $msgcontent, $projectcode, $pin, $processid, $doc_type, $doc_id, $entollmentid, $actionid, $action, $branchcode, $assigndpo)
  {
    $db = $this->db;
    $dberp = $this->dberp;
    $baseUrl = url('');
    $trendxurl = 'http://trendxstage.brac.net/api/';
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

    //dd($doc_type . "" . $doc_id);

    if ($reciverroleDesignation == 'BM') {
      $checkPO = DB::Table($db . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)->where('cono', $assigndpo)->where('abm', '!=', '')->get();
      //dd($checkPO);
      if ($checkPO->isEmpty()) {
        $findpin = DB::table($db . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)->where('desig', 'Branch Manager')->get();
        if ($findpin != null) {
          $reciverpin = $findpin[0]->cono;
        }
      } else {
        $findpin = DB::table($db . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)->where('desig', 'Assistant Branch Manager')->get();
        //dd($findpin);
        if ($findpin != null) {
          $reciverpin = $findpin[0]->cono;
          //dd($reciverpin);
        }
      }
      /*$findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
        ->Where(function ($query) {
          // $query->where('desig','Branch Manager')->orWhere('desig','Assistant Branch Manager');
          $query->where('desig', 'Branch Manager');
        })->first();
      if ($findpin != null) {
        $reciverpin = $findpin->cono;
      } else {
        $findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
          ->Where(function ($query) {
            $query->Where('desig', 'Assistant Branch Manager');
            // $query->where('desig','Branch Manager');
          })->first();
        if ($findpin != null) {
          $reciverpin = $findpin->cono;
        }
      }*/
    } else if ($reciverroleDesignation == 'PO') {
      $findpin = DB::table($db . '.document_history')->select('pin')->where('doc_type', $doc_type)->where('doc_id', $doc_id)->where('projectcode', $projectcode)->where('action_counter', 1)->first();

      if ($findpin != null) {
        $reciverpin = $findpin->pin;
      }
      // $reciverpin='186251';
    }
    //dd($reciverpin);
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

    //$reciverpin = $pin;
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
    $res['timestamp'] = date('Y-m-d H:i:s');
    $data['data'] = $res;
    $topic = $this->Topic . "" . $reciverpin;
    Log::info("Topic-" . $topic);
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

  //start erp api's functions
  public function tokenVerify()
  {
    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    $tokenCheckDb = Db::table($db . '.oauth2')->get();

    if ($tokenCheckDb->isEmpty()) {
      $jsonresponse = $this->getToken();
      $response_ary = json_decode($jsonresponse);
      if (json_last_error() === JSON_ERROR_NONE) {
        // JSON is valid
        $expires_time = $response_ary->expires_in;
        $access_token = $response_ary->access_token;
        $expires_in = date("Y-m-d h:i:s", time() + $expires_time);

        DB::table($db . '.oauth2')->insert(['expires_time' => $expires_time, 'expires_in' => $expires_in, 'access_token' => $access_token]);

        return $access_token;
      } else {
        //invalid json
        Log::channel('daily')->info('ERP access token error');

        return json_last_error();
      }
    } else {
      $id = $tokenCheckDb[0]->id;
      $expires_in = $tokenCheckDb[0]->expires_in;
      if ($expires_in > $currentDatetime) {

        //get token from DB 
        $access_token = $tokenCheckDb[0]->access_token;
        return $access_token;
      } else {
        //token expired
        $jsonresponse = $this->getToken();
        $response_ary = json_decode($jsonresponse);
        if (json_last_error() === JSON_ERROR_NONE) {
          // JSON is valid
          $expires_time = $response_ary->expires_in;
          $access_token = $response_ary->access_token;
          $expires_in = date("Y-m-d h:i:s", time() + $expires_time);

          DB::table($db . '.oauth2')->where('id', $id)->update(['expires_time' => $expires_time, 'expires_in' => $expires_in, 'access_token' => $access_token]);

          return $access_token;
        } else {
          //invalid json
          Log::channel('daily')->info('ERP access token error');

          return json_last_error();
        }
      }
    }
  }

  public function getToken()
  {
    /*'x-client-id:1_43wc41hen7cwg0sg4s044c0scc8wck4o',
        'x-client-secret:654spemp5qckcg4g448044kco4k0g8wwo0440osgwosggwg4'*/
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/oauth/v2/token?grant_type=client_credentials';

    $headers = array(
      // 'Authorization: key=' . $FIREBASE_API_KEY,
      'Accept: application/json',
      'X-CLIENT-ID: ' . $clientid,
      'X-CLIENT-SECRET: ' . $clientsecret
    );


    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      return $response;
    }
  }

  public function documentErpPosting($doc_id, $doc_type)
  {
    if ($doc_type == 'admission') {
      $response = $this->admissionDataProccessForErp($doc_id);
      return $response;
    } elseif ($doc_type == 'loan') {
      $response = $this->loanDataProccessForErp($doc_id);
      return $response;
    }
  }

  //admission member erp posting
  public function admissionErpPosting($admission)
  {
    $this->LaravelLog();
    // dd('asd');
    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    /*$access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/buffer-members';

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
      CURLOPT_URL => $url2 . 'buffer-members',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $admission,
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      $documentAry = json_decode($admission);
      $id = $documentAry[0]->id;
      Log::channel('daily')->info('Admission Erp Posting response: ' . $response);
      $response = json_decode($response);
      //dd($response);
      if ($response->message == "OK") {
        $ErpHttpStatus = '200';
        $ErpMessage = 'OK';
        DB::table($db . '.admissions')->where('entollmentid', $id)->update(['ErpHttpStatus' => 200, 'ErpErrorMessage' => null, 'ErpErrors' => null, 'updated_at' => $currentDatetime]);
        Log::channel('daily')->info('Admission Erp Posting SuccessUpdate: ' . json_encode($response));
        //return $response->message;
        $message = array($ErpHttpStatus, $ErpMessage);
        return $message;
      } else {
        Log::channel('daily')->info('Admission Erp Posting errors: ' . json_encode($response));
        // dd($response);
        $ErpHttpStatus = $response->httpStatus;
        $ErpErrorMessage = $response->errorMessage;
        $ErpErrors = $response->errors;
        // dd($ErpErrors);

        DB::table($db . '.admissions')->where('entollmentid', $id)->update(['ErpHttpStatus' => $ErpHttpStatus, 'ErpErrorMessage' => $ErpErrorMessage, 'ErpErrors' => $ErpErrors, 'updated_at' => $currentDatetime]);
        //   dd($responseAry);
        $message = array($ErpHttpStatus, $ErpErrors);
        return $message;
      }
      /*if (array_key_exists("message", $response)) {
        
      }*/
    }
  }

  //loan member erp posting
  public function loanErpPosting($loan)
  {
    $this->LaravelLog();
    //dd("T");
    Log::channel('daily')->info('Loan Request File: ' . $loan);
    Log::channel('daily')->info("Loan Data Set" . $loan);
    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    /*$access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/buffer-loan-proposals';

    $headers = array(
      'Authorization: Bearer ' . $access_token,
      'Content-Type: application/json'
    );*/
    // dd("Huda");
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
      CURLOPT_URL => $url2 . 'buffer-loan-proposals',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $loan,
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    //dd($response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      //   return $response;
      $documentAry = json_decode($loan);
      $id = $documentAry[0]->id;
      Log::channel('daily')->info('Loan Erp Posting response: ' . $response);
      $response = json_decode($response);

      /*if (array_key_exists("message", $response)) {
        
      }*/
      $updatedAt = date('Y-m-d H:i:s');
      if ($response->message == "OK") {
        //dd("OK");
        $ErpHttpStatus = '200';
        $Erpmessage = 'OK';
        DB::table($db . '.loans')->where('loan_id', $id)->update(['ErpHttpStatus' => 200, 'ErpErrorMessage' => null, 'ErpErrors' => null, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
        $message = array($ErpHttpStatus, $Erpmessage);
        return $message;
      } else {
        //dd("Error");
        Log::channel('daily')->info('Loan Erp Posting errors: ' . json_encode($response));
        $ErpHttpStatus = $response->httpStatus;
        $ErpErrorMessage = $response->errorMessage;
        $ErpErrors = $response->errors;
        //   dd($id);

        DB::table($db . '.loans')->where('loan_id', $id)->update(['ErpHttpStatus' => $ErpHttpStatus, 'ErpErrorMessage' => $ErpErrorMessage, 'ErpErrors' => $ErpErrors, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
        //print_r($response);
        $message = array($ErpHttpStatus, $ErpErrors);
        // dd($message);
        return $message;
      }
    }
  }

  //admission data proccessing for erp posting
  public function admissionDataProccessForErp($id)
  {
    $this->LaravelLog();
    $db = $this->db;
    $data = DB::table($db . '.admissions')->where('id', $id)->first();
    if ($data->SposeDOB == null) {
      $SposeDOB = null;
    } else {
      $SposeDOB = date('Y-m-d', strtotime($data->SposeDOB));
    }
    if ($data->SpouseIdExpiredate == null) {
      $SpouseIdExpiredate = null;
    } else {
      $SpouseIdExpiredate = date('Y-m-d', strtotime($data->SpouseIdExpiredate));
    }
    if ($data->NomineeDOB == null) {
      $NomineeDOB = null;
    } else {
      $NomineeDOB = date('Y-m-d', strtotime($data->NomineeDOB));
    }
    if ($data->NomineeIdExpiredate == null) {
      $NomineeIdExpiredate = null;
    } else {
      $NomineeIdExpiredate = date('Y-m-d', strtotime($data->NomineeIdExpiredate));
    }
    if ($data->DOB == null) {
      $DOB = null;
    } else {
      $DOB = date('Y-m-d', strtotime($data->DOB));
    }
    // dd($data);
    $arrayData = array();
    $guarantor = array();
    $guarantor[] = array(
      "dateOfBirth" => null,
      "idCard" => array(),
      "idCard" => array(
        "backImageUrl" => null,
        "cardTypeId" => null,
        "expiryDate" => null,
        "frontImageUrl" => null,
        "idCardNo" => null,
        "issueDate" => null,
        "issuePlace" => null,
      ),
      "nameEn" => null,
      "relationshipId" => null,
    );
    $nominees = array();
    $nominees[] = array(
      "contactNo" => $data->NomineePhoneNumber,
      "dateOfBirth" => $NomineeDOB,
      "id" => null,
      "idCard" => array(),
      "idCard" => array(
        "backImageUrl" => $data->NomineeNidBack,
        "cardTypeId" => $data->NomineeNidType,
        "expiryDate" => $NomineeIdExpiredate,
        "frontImageUrl" => $data->NomineeNidFront,
        "idCardNo" => $data->NomineeNidNo,
        "issueDate" => null,
        "issuePlace" => $data->NomineeIdPlaceOfissue,
      ),
      "name" => $data->NomineeName,
      "relationshipId" => "$data->RelationshipId",
    );
    $projectcode = (int)$data->projectcode;
    $arrayData[] = array(
      "applicationDate" => date('Y-m-d', strtotime($data->created_at)),
      "assignedPoPin" => $data->assignedpo,
      "bankAccountNumber" => null,
      "bankBranchId" => null,
      "bankId" => null,
      "bkashWalletNo" => $data->WalletNo,
      "branchCode" => $data->branchcode,
      "contactNo" => $data->Phone,
      "dateOfBirth" => $DOB,
      "educationId" => $data->EducationId,
      "fatherNameEn" => $data->FatherName,
      "flag" => $data->Flag,
      "genderId" => $data->GenderId,
      "guarantor" => null,
      "id" => $data->entollmentid,
      "idCard" => array(),
      "idCard" => array(
        "backImageUrl" => $data->BackSideOfIdimg,
        "cardTypeId" => $data->MainIdTypeId,
        "expiryDate" => $data->ExpiryDate,
        "frontImageUrl" => $data->FrontSideOfIdImg,
        "idCardNo" => $data->IdNo,
        "issueDate" => null,
        "issuePlace" => $data->IssuingCountry,
      ),
      "maritalStatusId" => $data->MaritalStatusId,
      "memberId" => $data->MemberId,
      "memberImageUrl" => $data->ApplicantSinglePic,
      "memberTypeId" => $data->MemberCateogryId,
      "motherNameEn" => $data->MotherName,
      "nameEn" => $data->ApplicantsName,
      "nominees" => $nominees,
      "occupationId" => $data->Occupation,
      "passbookNumber" => null,
      "permanentAddress" => $data->PermanentAddress,
      "permanentDistrictId" => $data->PermanentDistrictId,
      "permanentUpazilaId" => $data->parmanentUpazilaId,
      "poId" => $data->assignedpo,
      "presentAddress" => $data->PresentAddress,
      "presentDistrictId" => $data->PresentDistrictId,
      "presentUpazilaId" => $data->presentUpazilaId,
      "projectCode" => $projectcode,
      "rejectionReason" => null,
      "routingNumber" => null,
      "savingsProductId" => $data->SavingsProductId,
      "spouseDateOfBirth" => $SposeDOB,
      "spouseIdCard" => array(),
      "spouseIdCard" => array(
        "backImageUrl" => $data->SpouseNidBack,
        "cardTypeId" => $data->SpouseCardType,
        "expiryDate" => $SpouseIdExpiredate,
        "frontImageUrl" => $data->SpouseNidFront,
        "idCardNo" => $data->SpouseNidOrBid,
        "issueDate" => null,
        "issuePlace" => $data->SpouseIdPlaceOfissue,
      ),
      "spouseNameEn" => $data->SpouseName,
      "statusId" => null,
      "targetAmount" => $data->TargetAmount,
      "tinNumber" => null,
      "updated" => true,
      "voCode" => $data->orgno,
      "voId" => null,
      "orgId" => 2
    );
    $jsonData = json_encode($arrayData);
    Log::channel('daily')->info('Dcs_erp_admission_erp_dataset : ' . $jsonData);
    //erp curl posting
    $response = $this->admissionErpPosting($jsonData);

    return $response;
  }

  //loan data proccessing for erp posting
  public function loanDataProccessForErp($id)
  {
    $this->LaravelLog();
    $db = $this->db;
    $dberp = $this->dberp;
    $data = DB::table($db . '.loans')->where('id', $id)->first();
    //dd($data);
    $loanapprover = DB::table($db . '.role_hierarchies')->where('projectcode', $data->projectcode)->where('position', $data->reciverrole)->first();
    // $memberInfo = DB::table($db . '.posted_admission')->where('memberid', $data->erp_mem_id)->first();

    // $serverurl = DB::Table($dberp . '.server_url')->where('server_status', 3)->where('status', 1)->first();
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
    $key = '5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae';
    $UpdatedAt = "2000-01-01 00:00:00";
    if ($data->projectcode == '060') {
      $member = Http::get($url . 'MemberList', [
        'BranchCode' => $data->branchcode,
        'CONo' => $data->assignedpo,
        'ProjectCode' => $data->projectcode,
        'UpdatedAt' => $UpdatedAt,
        'Status' => 1,
        'OrgMemNo' => $data->orgmemno,
        'key' => $key
      ]);
    } else {
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
    }
    $member = $member->object();
    //print_r($member);
    //die;

    if ($member != null) {
      if ($member->data != null) {
        $memberInfo = $member->data[0];
        $classificationid = $memberInfo->MemberClassificationId;
      } else {
        $memberInfo = null;
        $classificationid = null;
      }
    } else {
      $memberInfo = null;
      $classificationid = null;
    }

    Log::info("Member Data- " . json_encode($memberInfo));
    $projectcode = (int)$data->projectcode;
    $productcode = '';
    // $insuranceProduct = DB::table($db . '.insurance_products')->where('project_code', $projectcode)->first();
    $insuranceProduct = $this->Insurance_ProductId($db, $data->loan_product, $projectcode, $productcode);
    if ($insuranceProduct != '') {
      $insuranceProductId = $insuranceProduct;
    } else {
      $insuranceProductId = null;
    }
    // dd($memberInfo->membertypeid);
    $arrayData = array();
    //$nominees = array();
    /*if ($memberInfo->Nominees != null) {
      $nominees[] = array(
        "contactNo" => null,
        "dateOfBirth" => $memberInfo->Nominees[0]->DateOfBirth,
        "id" => null,
        "idCard" => array(),
        "idCard" => array(
          "backImageUrl" => null,
          "cardTypeId" => $memberInfo->Nominees[0]->CardTypeId,
          "expiryDate" => null,
          "frontImageUrl" => null,
          "idCardNo" => '7654891255', //$memberInfo->Nominees[0]->CardTypeId,
          "issueDate" => null,
          "issuePlace" => null,
        ),
        "name" => $memberInfo->Nominees[0]->DateOfBirth,
        "relationshipId" => $memberInfo->Nominees[0]->RelationshipId,
      );
    } else {
      $nominees = null;
    }*/
    $nominees = null;

    // $coBorrowerDto=array();
    // $coBorrowerDto=array(
    //         "idCard"=>array(),
    //         "idCard"=>array(
    //             "backImageUrl" => null,
    //             "cardTypeId" => $data->insurn_mainIDType,
    //             "expiryDate" => null,
    //             "frontImageUrl" => null,
    //             "idCardNo" => $data->insurn_mainID,
    //             "issueDate" => null,
    //             "issuePlace" => null,
    //         ),
    //         "name" => $data->grntor_name,
    //         "relationshipId" => $data->grntor_rlationClient,
    // );

    $coBorrowerDto = null;
    $name = null;
    if ($projectcode != '15') {
      //dd($id);
      $getcard = DB::table($db . '.co_borrower_details')->where('loan_id', $data->loan_id)->get();

      if (!$getcard->isEmpty()) {
        // dd("Huda");
        $getNid = $getcard[0]->nid_birth_certificate;
        $name = $getcard[0]->name ?? null;
        $RelationshipId =$getcard[0]->relationshipid ?? null;
        //dd($name);
        $cardid = strlen($getNid);
        if ($cardid == '10') {
          $crdtypeid = 5;
        } else if ($cardid == '17') {
          $crdtypeid = 2;
        } else {
          $crdtypeid = null;
        }
        if($getNid !='' and $name !='')
        {
          $coBorrowerDto = array(
            "idCard" => array(
              "backImageUrl" => null,
              "cardTypeId" => $crdtypeid,
              "expiryDate" => null,
              "frontImageUrl" => null,
              "idCardNo" => $getNid,
              "issueDate" => null,
              "issuePlace" => null,
            ),
            "name" => $name,
            "relationshipId" => $RelationshipId,
          );
        }
        else
        {
          $coBorrowerDto = null;
        }
        
      }
    }
    //dd($coBorrowerDto);
    if ($data->insurn_type == 1) {
      $secondInsurer = null;
    } else if ($data->insurn_type == null) {
      $secondInsurer = null;
    } elseif ($data->insurn_type == 2) {
      if ($data->insurn_option == 1) {
        $secondInsurer = array();
        if ($memberInfo->SpouseIDCard != null) {
          //dd($memberInfo->SpouseIDCard);
          $secondInsurer = array(
            "dateOfBirth" => null,
            "genderId" => null,
            "idCard" => array(),
            "idCard" => array(
              "backImageUrl" => null,
              "cardTypeId" => $memberInfo->SpouseIDCard->CardTypeId,
              "expiryDate" => $memberInfo->SpouseIDCard->ExpiryDate,
              "frontImageUrl" => null,
              "idCardNo" => $memberInfo->SpouseIDCard->IdCardNo,
              "issueDate" => null,
              "issuePlace" => $memberInfo->SpouseIDCard->IssuePlace,
            ),
            "name" => null,
            "relationshipId" => null,
          );
        } else {
          $secondInsurer = null;
        }
      } elseif ($data->insurn_option == 2) {
        $secondInsurer = array();
        $secondInsurer = array(
          "dateOfBirth" => $data->insurn_dob,
          "genderId" => $data->insurn_gender,
          "idCard" => array(),
          "idCard" => array(
            "backImageUrl" => null,
            "cardTypeId" => $data->insurn_mainIDType,
            "expiryDate" => $data->insurn_id_expire,
            "frontImageUrl" => null,
            "idCardNo" => $data->insurn_mainID,
            "issueDate" => null,
            "issuePlace" => $data->insurn_placeofissue,
          ),
          "name" => $data->insurn_name,
          "relationshipId" => $data->insurn_relation,
        );
      }
    }

    $projectcode = (int)$data->projectcode;
    if ($projectcode != '15') {
      $microInsurance = $data->microinsurance;
      if ($microInsurance == false) {
        $policyTypeid = null;
      } else {
        $policyTypeid = $data->insurn_type;
      }
    } else {
      $microInsurance = true;
      $policyTypeid = $data->insurn_type;
    }
    $approvalamount = $data->approval_amount;
    //dd($approvalamount);
    $arrayData[] = array(
      "applicationDate" => date('Y-m-d', strtotime($data->time)),
      "approvedDurationInMonths" => null,
      "approvedLoanAmount" => null,
      "branchCode" => $data->branchcode,
      "coBorrowerDto" => $coBorrowerDto,
      "consentUrl" => null,
      "disbursementDate" => null,
      "flag" => 1,
      "frequencyId" => $data->frequencyId,
      "id" => $data->loan_id,
      "insuranceProductId" => $insuranceProductId, //$insuranceProduct->product_id,
      "loanAccountId" => null,
      "loanApprover" => $loanapprover->role,
      "loanProductId" => $data->loan_product,
      "loanProposalStatusId" => null,                         //test
      "memberId" => $data->erp_mem_id,
      "memberTypeId" => $classificationid,//$memberInfo->MemberClassificationId,
      "microInsurance" => $microInsurance, //progoti false
      "modeOfPaymentId" => 1,
      "nominees" => $nominees,           //array
      "policyTypeId" => $policyTypeid, //$data->insurn_type,  //insurenc type single or double //type null
      "premiumAmount" => null,
      "projectCode" => $projectcode,
      "proposalDurationInMonths" => $data->loan_duration,
      "proposedLoanAmount" => $approvalamount,
      "rejectionReason" => $data->comment,
      "schemeId" => $data->scheme,
      // "spouseIdCard"=>$spouseIdCard,       //array
      "secondInsurer" => $secondInsurer,  //array
      "sectorId" => $data->invest_sector,
      "signConsent" => null,
      "subSectorId" => $data->subSectorId,
      "updated" => null,
      "voCode" => $data->orgno,
      "voId" => null,
      "orgId" => 2

    );
    $jsonData = json_encode($arrayData);
    // return $jsonData;
    //echo $jsonData;
    //die;
    $type = 'Loan';
    $this->LogCreate($data->branchcode, $projectcode, $data->assignedpo, $type, $jsonData);
    //loan curl request
    //die;
    $response = $this->loanErpPosting($jsonData);
    // dd($response);
    return $response;
  }

  //erp get api admission data
  public function GetErpPostedAdmissionData($branchcode)
  {
    $this->LaravelLog();
    /*$access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/branches/' . $branchcode . '/buffer-members';

    $headers = array(
      'Authorization: Bearer ' . $access_token,
      'Accept: application/json',
    );*/
    $db = $this->db;
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
    //dd($headers);
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url2 . 'branches/' . $branchcode . '/buffer-members',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    //dd($response);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      json_decode($response);
      if (json_last_error() == 0) {
        return $this->insertPostedAddmissionList($response);
      } else {
        return "Erp Server Down";
      }
    }
  }

  //erp get api admission data's database insertion
  public function insertPostedAddmissionList($response)
  {
    $this->LaravelLog();
    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    $arrayAddmission = json_decode($response);
    // dd($arrayAddmission);
    if (!empty($arrayAddmission)) {
      foreach ($arrayAddmission as $data) {
        // dd($data->id);
        // if ($data->guarantor != null) {
        // $guarantordateofbirth = $data->guarantor[0]->dateOfBirth;
        // $guarantorbackimageurl = $data->guarantor[0]->idCard->backImageUrl;
        // $guarantorcardtypeid = $data->guarantor[0]->idCard->cardTypeId;
        // $guarantorissueplace = $data->guarantor[0]->idCard->issuePlace;
        // $guarantorexpirydate = $data->guarantor[0]->idCard->expiryDate;
        // $guarantorfrontimageurl = $data->guarantor[0]->idCard->frontImageUrl;
        // $guarantoridcardno = $data->guarantor[0]->idCard->idCardNo;
        // $guarantorissuedate = $data->guarantor[0]->idCard->issueDate;
        // $guarantornameen = $data->guarantor[0]->nameEn;
        // $guarantorrelationshipid = $data->guarantor[0]->relationshipId;
        // } else {

        // }
        $guarantordateofbirth = null;
        $guarantorbackimageurl = null;
        $guarantorcardtypeid = null;
        $guarantorissueplace = null;
        $guarantorexpirydate = null;
        $guarantorfrontimageurl = null;
        $guarantoridcardno = null;
        $guarantorissuedate = null;
        $guarantornameen = null;
        $guarantorrelationshipid = null;
        if ($data->nominees != null) {
          $nomineescontactNo = $data->nominees[0]->contactNo;
          if (isset($data->nominees[0]->dateOfBirth)) {
            $nomineesdateofbirth = $data->nominees[0]->dateOfBirth;
          } else {
            $nomineesdateofbirth = null;
          }
          $nomineesbackimageurl = $data->nominees[0]->idCard->idCardNo;
          $nomineescardtypeid = $data->nominees[0]->idCard->cardTypeId;
          $nomineesexpirydate = $data->nominees[0]->idCard->expiryDate;
          $nomineesfrontimageurl = $data->nominees[0]->idCard->frontImageUrl;
          $nomineesidcardno = $data->nominees[0]->idCard->idCardNo;
          $nomineesissuedate = $data->nominees[0]->idCard->issueDate;
          $nomineesissueplace = $data->nominees[0]->idCard->issuePlace;
          $nomineesname = $data->nominees[0]->name;

          if (array_key_exists('relationshipId', $data->nominees)) {
            $nomineesrelationshipid = $data->nominees->relationshipId;
          } else {
            $nomineesrelationshipid = null;
          }
        } else {
          $nomineescontactNo = null;
          $nomineesdateofbirth = null;
          $nomineesbackimageurl = null;
          $nomineescardtypeid = null;
          $nomineesexpirydate = null;
          $nomineesfrontimageurl = null;
          $nomineesidcardno = null;
          $nomineesissuedate = null;
          $nomineesissueplace = null;
          $nomineesname = null;
          $nomineesrelationshipid = null;
        }

        $values = array(
          'applicationdate' => $data->applicationDate,
          'assignedpopin' => $data->assignedPoPin,
          'bankaccountnumber' => $data->bankAccountNumber,
          'bankbranchid' => $data->bankBranchId,
          'bankid' => $data->bankId,
          'bkashwalletno' => $data->bkashWalletNo,
          'branchcode' => $data->branchCode,
          'contactno' => $data->contactNo,
          'dateofbirth' => $data->dateOfBirth,
          'educationid' => $data->educationId,
          'fathernameen' => $data->fatherNameEn,
          'flag' => $data->flag,
          'genderid' => $data->genderId,
          //guarantor
          "guarantordateofbirth" => $guarantordateofbirth,
          "guarantorbackimageurl" => $guarantorbackimageurl,
          "guarantorcardtypeid" => $guarantorcardtypeid,
          "guarantorissueplace" => $guarantorissueplace,
          "guarantorexpirydate" => $guarantorexpirydate,
          "guarantorfrontimageurl" => $guarantorfrontimageurl,
          "guarantoridcardno" => $guarantoridcardno,
          "guarantorissuedate" => $guarantorissuedate,
          "guarantornameen" => $guarantornameen,
          "guarantorrelationshipid" => $guarantorrelationshipid,
          'addmission_id' => $data->id,
          //idCard
          "idcardbackimageurl" => $data->idCard->backImageUrl,
          "idcardcardtypeid" => $data->idCard->cardTypeId,
          "idcardexpirydate" => $data->idCard->expiryDate,
          "idcardfrontimageurl" => $data->idCard->frontImageUrl,
          "idcardidcardno" => $data->idCard->idCardNo,
          "idcardissuedate" => $data->idCard->issueDate,
          "idcardissueplace" => $data->idCard->issuePlace,
          'maritalstatusid' => $data->maritalStatusId,
          'memberid' => $data->memberId,
          'memberimageurl' => $data->memberImageUrl,
          'membertypeid' => $data->memberTypeId,
          'mothernameen' => $data->motherNameEn,
          'nameen' => $data->nameEn,
          //nominees
          "nomineescontactno" => $nomineescontactNo,
          "nomineesdateofbirth" => $nomineesdateofbirth,
          // "id" => $data->nominees[0]->id,
          "nomineesbackimageurl" => $nomineesbackimageurl,
          "nomineescardtypeid" => $nomineescardtypeid,
          "nomineesexpirydate" => $nomineesexpirydate,
          "nomineesfrontimageurl" => $nomineesfrontimageurl,
          "nomineesidcardno" => $nomineesidcardno,
          "nomineesissuedate" => $nomineesissuedate,
          "nomineesissueplace" => $nomineesissueplace,
          "nomineesname" => $nomineesname,
          "nomineesrelationshipid" => $nomineesrelationshipid,
          'occupationid' => $data->occupationId,
          'passbooknumber' => $data->passbookNumber,
          'permanentaddress' => $data->permanentAddress,
          'permanentdistrictid' => $data->permanentDistrictId,
          'permanentupazilaid' => $data->permanentUpazilaId,
          'poid' => $data->poId,
          'presentaddress' => $data->presentAddress,
          'presentdistrictid' => $data->presentDistrictId,
          'presentupazilaid' => $data->presentUpazilaId,
          'projectcode' => $data->projectCode,
          'rejectionreason' => $data->rejectionReason,
          'routingnumber' => $data->routingNumber,
          'savingsproductid' => $data->savingsProductId,
          'spousedateofbirth' => $data->spouseDateOfBirth,
          // // spouseIdCard
          "spouseidcardbackimageurl" => $data->spouseIdCard->backImageUrl,
          "spouseidcardcardtypeid" => $data->spouseIdCard->cardTypeId,
          "spouseidcardexpirydate" => $data->spouseIdCard->expiryDate,
          "spouseidcardfrontimageurl" => $data->spouseIdCard->frontImageUrl,
          "spouseidcardidcardno" => $data->spouseIdCard->idCardNo,
          "spouseidcardissuedate" => $data->spouseIdCard->issueDate,
          "spouseidcardissueplace" => $data->spouseIdCard->issuePlace,
          'spousenameen' => $data->spouseNameEn,
          'statusid' => $data->statusId,
          'targetamount' => $data->targetAmount,
          'tinnumber' => $data->tinNumber,
          'updated' => $data->updated,
          'vocode' => $data->voCode,
          'void' => $data->voId,
          'admission_id' => $data->id,
        );

        $checkPostedAdmission = DB::table($db . '.posted_admission')->where('admission_id', $data->id)->first();
        $checkAdmission = DB::table($db . '.admissions')->where('entollmentid', $data->id)->first();
        $checkLoan = DB::table($db . '.loans')->where('mem_id', $data->id)->first();

        if ($data->statusId == 2 or $data->statusId == 3) {  //if erp approve and reject
          if ($checkAdmission != null) {                //if addmission has data
            if ($checkAdmission->MemberId == null and $checkAdmission->ErpStatus == 1) {    //if erp member id empty in dcs admission table
              $this->sendAppNotificationForErpAddmissionAction($data);
            }
          }
        }


        if ($checkPostedAdmission == null) {
          DB::table($db . '.posted_admission')->insert($values);
          if ($data->statusId == 2) {
            if ($checkAdmission != null) {
              DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['MemberId' => $data->memberId, 'ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime]);
            }
            if ($checkLoan != null) {
              DB::table($db . '.loans')->where('mem_id', $data->id)->update(['erp_mem_id' => $data->memberId, 'updated_at' => $currentDatetime]);
            }
          } elseif ($data->statusId == 3) {
            if ($checkAdmission != null) {
              DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime]);
            }
          }
        } else {
          // if ($data->updated == TRUE) {
          DB::table($db . '.posted_admission')->where('admission_id', $data->id)->update($values);
          // }
          if ($data->statusId == 2) {
            if ($checkAdmission != null) {
              DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['MemberId' => $data->memberId, 'ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime]);
            }
            if ($checkLoan != null) {
              DB::table($db . '.loans')->where('mem_id', $data->id)->update(['erp_mem_id' => $data->memberId, 'updated_at' => $currentDatetime]);
            }
          } elseif ($data->statusId == 3) {
            if ($checkAdmission != null) {
              DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime]);
            }
          }
        }
      }
    }
    return "Data sync successful";
  }

  //erp get api loan data
  public function GetErpPostedLoanData($branchcode)
  {
    $this->LaravelLog();
    /*$access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/branches/' . $branchcode . '/buffer-loan-proposals';

    $headers = array(
      'Authorization: Bearer ' . $access_token,
      'Accept: application/json',
    );*/
    $db = $this->db;
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
      CURLOPT_URL => $url2 . 'branches/' . $branchcode . '/buffer-loan-proposals',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 300,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    //dd($response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      //   return $response;
      json_decode($response);
      if (json_last_error() == 0) {
        return $this->insertPostedLoanList($response);
      } else {
        return "Erp Server Down";
      }
    }
  }

  //erp get api loan data's database insertion
  public function insertPostedLoanList($response)
  {
    $this->LaravelLog();
    Log::channel('daily')->info('Posted Loan ' . $response);
    $BufferMemberStatus = $response;
    $db = $this->db;
    $dberp = $this->dberp;
    $currentDatetime = date("Y-m-d h:i:s");
    $arrayLoan = json_decode($response);
    if (!empty($arrayLoan)) {
      foreach ($arrayLoan as $data) {
        //dd($data);

        if ($data->secondInsurer != null) {
          $secondinsurerdateofbirth = $data->secondInsurer->dateOfBirth;
          $secondinsurerbackimageurl = $data->secondInsurer->idCard->idCardNo;
          $secondinsurercardtypeid = $data->secondInsurer->idCard->cardTypeId;
          $secondinsurerexpirydate = $data->secondInsurer->idCard->expiryDate;
          $secondinsurerfrontimageurl = $data->secondInsurer->idCard->frontImageUrl;
          $secondinsureridcardno = $data->secondInsurer->idCard->idCardNo;
          $secondinsurerissuedate = $data->secondInsurer->idCard->issueDate;
          $secondinsurerissueplace = $data->secondInsurer->idCard->issuePlace;
          $secondinsurername = $data->secondInsurer->name;

          /* if (array_key_exists('relationshipId', $data->secondInsurer)) {
            $secondinsurerrelationshipid = $data->secondInsurer->relationshipId;
          } else {
            $secondinsurerrelationshipid = null;
          }*/
          if ('relationshipId' == $data->secondInsurer) {
            $secondinsurerrelationshipid = $data->secondInsurer->relationshipId;
          } else {
            $secondinsurerrelationshipid = null;
          }
          if ('genderId' == $data->secondInsurer) {
            $secondinsurergenderid = $data->secondInsurer->genderId;
          } else {
            $secondinsurergenderid = null;
          }
        } else {
          $secondinsurerdateofbirth = null;
          $secondinsurergenderid = null;
          $secondinsurerbackimageurl = null;
          $secondinsurercardtypeid = null;
          $secondinsurerexpirydate = null;
          $secondinsurerfrontimageurl = null;
          $secondinsureridcardno = null;
          $secondinsurerissuedate = null;
          $secondinsurerissueplace = null;
          $secondinsurername = null;
          $secondinsurerrelationshipid = null;
        }
        $nomineescontactNo = null;
        $nomineesdateofbirth = null;
        $nomineesbackimageurl = null;
        $nomineescardtypeid = null;
        $nomineesexpirydate = null;
        $nomineesfrontimageurl = null;
        $nomineesidcardno = null;
        $nomineesissuedate = null;
        $nomineesissueplace = null;
        $nomineesname = null;
        $nomineesrelationshipid = null;
        /*if ($data->nominees != null) {
          $nomineescontactNo = $data->nominees[0]->contactNo;
          $nomineesdateofbirth = $data->nominees[0]->dateOfBirth;
          $nomineesbackimageurl = $data->nominees[0]->idCard->idCardNo;
          $nomineescardtypeid = $data->nominees[0]->idCard->cardTypeId;
          $nomineesexpirydate = $data->nominees[0]->idCard->expiryDate;
          $nomineesfrontimageurl = $data->nominees[0]->idCard->frontImageUrl;
          $nomineesidcardno = $data->nominees[0]->idCard->idCardNo;
          $nomineesissuedate = $data->nominees[0]->idCard->issueDate;
          $nomineesissueplace = $data->nominees[0]->idCard->issuePlace;
          $nomineesname = $data->nominees[0]->name;

          if (array_key_exists('relationshipId', $data->nominees)) {
            $nomineesrelationshipid = $data->nominees->relationshipId;
          } else {
            $nomineesrelationshipid = null;
          }
        } else {
          $nomineescontactNo = null;
          $nomineesdateofbirth = null;
          $nomineesbackimageurl = null;
          $nomineescardtypeid = null;
          $nomineesexpirydate = null;
          $nomineesfrontimageurl = null;
          $nomineesidcardno = null;
          $nomineesissuedate = null;
          $nomineesissueplace = null;
          $nomineesname = null;
          $nomineesrelationshipid = null;
        }*/

        $values = array(
          "applicationdate" => $data->applicationDate,
          "approveddurationinmonths" => $data->approvedDurationInMonths,
          "approvedloanamount" => $data->approvedLoanAmount,
          "branchcode" => $data->branchCode,
          // coBorrowerDto
          // "coborrowerdtobackimageurl" => $data->coBorrowerDto->idCard->backImageUrl,
          // "coborrowerdtocardtypeid" => $data->coBorrowerDto->idCard->cardTypeId,
          // "coborrowerdtoexpirydate" => $data->coBorrowerDto->idCard->expiryDate,
          // "frontImageUrl" => $data->coBorrowerDto->idCard->backImageUrl,
          // "coborrowerdtoidcardno" => $data->coBorrowerDto->idCard->idCardNo,
          // "coborrowerdtoissuedate" => $data->coBorrowerDto->idCard->issueDate,
          // "coborrowerdtoissueplace" => $data->coBorrowerDto->idCard->issuePlace,            
          // "coborrowerdtoname" => $data->coBorrowerDto->name,
          // "coborrowerdtorelationshipid" => $data->coBorrowerDto->relationshipId,
          "consenturl" => $data->consentUrl,
          "disbursementdate" => $data->disbursementDate,
          // "flag" => $data->flag,
          "frequencyid" => $data->frequencyId,
          "loan_id" => $data->id,
          "insuranceproductid" => $data->insuranceProductId,
          "loanaccountid" => $data->loanAccountId,
          "loanapprover" => $data->loanApprover,
          "loanproductid" => $data->loanProductId,
          "loanproposalstatusid" => $data->loanProposalStatusId,
          "memberid" => $data->memberId,
          "membertypeid" => $data->memberTypeId,
          "microinsurance" => $data->microInsurance,
          "modeofpaymentid" => $data->modeOfPaymentId,
          // nominee
          "nomineescontactno" => $nomineescontactNo,
          "nomineesdateofbirth" => $nomineesdateofbirth,
          // "id" => $data->nominees[0]->id,
          "nomineesbackimageurl" => $nomineesbackimageurl,
          "nomineescardtypeid" => $nomineescardtypeid,
          "nomineesexpirydate" => $nomineesexpirydate,
          "nomineesfrontimageurl" => $nomineesfrontimageurl,
          "nomineesidcardno" => $nomineesidcardno,
          "nomineesissuedate" => $nomineesissuedate,
          "nomineesissueplace" => $nomineesissueplace,
          "nomineesname" => $nomineesname,
          "nomineesrelationshipid" => $nomineesrelationshipid,
          "policytypeid" => $data->policyTypeId,
          "premiumamount" => $data->premiumAmount,
          "projectcode" => $data->projectCode,
          "proposaldurationinmonths" => $data->proposalDurationInMonths,
          "proposedloanamount" => $data->proposedLoanAmount,
          "rejectionreason" => $data->rejectionReason,
          "schemeid" => $data->schemeId,
          "secondinsurerdateofbirth" => $secondinsurerdateofbirth,
          "secondinsurergenderid" => $secondinsurergenderid,
          "secondinsurerbackimageurl" => $secondinsurerbackimageurl,
          "secondinsurercardtypeid" => $secondinsurercardtypeid,
          "secondinsurerexpirydate" => $secondinsurerexpirydate,
          "secondinsurerfrontimageurl" => $secondinsurerfrontimageurl,
          "secondinsureridcardno" => $secondinsureridcardno,
          "secondinsurerissuedate" => $secondinsurerissuedate,
          "secondinsurerissueplace" => $secondinsurerissueplace,
          "secondinsurername" => $secondinsurername,
          "secondinsurerrelationshipid" => $secondinsurerrelationshipid,
          "sectorid" => $data->sectorId,
          "signconsent" => $data->signConsent,
          "subsectorid" => $data->subSectorId,
          "updated" => $data->updated,
          "vocode" => $data->voCode,
          "void" => $data->voId,
        );

        $checkPostedLoan = DB::table($db . '.posted_loan')->where('loan_id', $data->id)->first();
        $checkLoan = DB::table($db . '.loans')->where('loan_id', $data->id)->first();

        if ($data->loanProposalStatusId == 4 or $data->loanProposalStatusId == 3) {  //if erp loan disbursed or reject
          if ($checkLoan != null) {                //if addmission has data
            // $member = DB::table($db . '.posted_admission')->where('memberid', $data->memberId)->first();
            //$serverurl = DB::Table($dberp . '.server_url')->where('server_status', 3)->where('status', 1)->first();
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
            if (
              $servertoken != ''
            ) {
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
            $key = '5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae';
            $UpdatedAt = "2000-01-01 00:00:00";
            $member = Http::get($url . 'MemberList', [
              'BranchCode' => $checkLoan->branchcode,
              'CONo' => $checkLoan->assignedpo,
              'ProjectCode' => $checkLoan->projectcode,
              'UpdatedAt' => $UpdatedAt,
              'Status' => 1,
              'OrgNo' => $checkLoan->orgno,
              'OrgMemNo' => $checkLoan->orgmemno,
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
            if ($checkLoan->erp_loan_id == null and $checkLoan->ErpStatus == 1) {    //if erp member id empty in dcs admission table
              if ($member != null) {
                $this->sendAppNotificationForErpLoanAction($data, $member);
              }
            }
          }
        }

        if ($checkPostedLoan == null) {
          DB::table($db . '.posted_loan')->insert($values);
          if ($data->loanProposalStatusId == 4) {
            if ($checkLoan != null) {
              DB::table($db . '.loans')->where('loan_id', $data->id)->update(['erp_loan_id' => $data->loanAccountId, 'ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime]);
            }
          } else {
            if ($checkLoan != null) {
              DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime]);
            }
          }
        } else {
          // if ($data->updated == TRUE) {
          DB::table($db . '.posted_loan')->where('loan_id', $data->id)->update($values);
          if ($data->loanProposalStatusId == 4) {
            if ($checkLoan != null) {
              DB::table($db . '.loans')->where('loan_id', $data->id)->update(['erp_loan_id' => $data->loanAccountId, 'ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime]);
            }
          } else {
            if ($checkLoan != null) {
              DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime]);
            }
          }
          // }
        }
      }
    }
    return "Data sync successful";
  }

  public function sendAppNotificationForErpAddmissionAction($data)
  {
    $this->LaravelLog();
    $db = $this->db;
    $entollmentid = $data->id;
    $dberp = $this->dberp;
    $doc_type = 'admission';
    $popin = $data->assignedPoPin;
    $projectcode = $data->projectCode;
    $projectcode = str_pad($projectcode, 3, "0", STR_PAD_LEFT);
    $brcode = $data->branchCode;
    if ($data->statusId == 2) {
      $msgcontent = 'Member Addmission Approved In Erp';
      $action = 'ErpApprove';
    } elseif ($data->statusId == 3) {
      $msgcontent = 'Member Addmission Rejected In Erp';
      $action = 'ErpReject';
    }

    $checkRoleHierarchie = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', 1)->first();

    // for bm role 
    if ($checkRoleHierarchie->designation == 'BM') {
      $findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
        ->Where(function ($query) {
          // $query->where('desig','Branch Manager')->orWhere('desig','Assistant Branch Manager');
          $query->where('desig', 'Branch Manager');
        })->first();
      if ($findpin != null) {
        $nextrolepin = $findpin->cono;
      } else {
        $findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
          ->Where(function ($query) {
            $query->Where('desig', 'Assistant Branch Manager');
            // $query->where('desig','Branch Manager');
          })->first();
        if ($findpin != null) {
          $nextrolepin = $findpin->cono;
        }
      }
    }

    // for am role
    if ($checkRoleHierarchie->designation == 'AM') {
      $nextrolepin = 'b123';
    }



    $checkPostedAdmission = DB::table($db . '.posted_admission')->where('admission_id', $data->id)->first();
    $checkAdmission = DB::table($db . '.admissions')->where('entollmentid', $data->id)->first();
    $checkLoan = DB::table($db . '.loans')->where('mem_id', $data->id)->first();

    $this->sendAppNotification($entollmentid, $doc_type, $popin, $msgcontent, $action);
    $this->sendAppNotification($entollmentid, $doc_type, $nextrolepin, $msgcontent, $action);
  }

  public function sendAppNotificationForErpLoanAction($data, $member)
  {
    $this->LaravelLog();
    $db = $this->db;
    $entollmentid = $data->id;
    $dberp = $this->dberp;
    $doc_type = 'loan';
    // dd($member);
    $popin = $member->AssignedPoPin;
    $projectcode = $data->projectCode;
    $projectcode = str_pad($projectcode, 3, "0", STR_PAD_LEFT);
    $brcode = $data->branchCode;
    if ($data->loanProposalStatusId == 4) {
      $msgcontent = 'Loan Disbursed In Erp';
      $action = 'ErpApprove';
    } elseif ($data->loanProposalStatusId == 3) {
      $msgcontent = 'Loan Rejected In Erp';
      $action = 'ErpReject';
    } else if ($data->loanProposalStatusId == 2) {
      $msgcontent = "ERP Approve BAO In Erp";
      $action = 'ErpApproveBAO';
    }

    $checkRoleHierarchie = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', $projectcode)->where('position', 1)->first();

    // for bm role 
    if ($checkRoleHierarchie->designation == 'BM') {
      $findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
        ->Where(function ($query) {
          // $query->where('desig','Branch Manager')->orWhere('desig','Assistant Branch Manager');
          $query->where('desig', 'Branch Manager');
        })->first();
      if ($findpin != null) {
        $nextrolepin = $findpin->cono;
      } else {
        $findpin = DB::table($dberp . '.polist')->select('cono')->where('status', 1)->where('branchcode', $brcode)->where('projectcode', $projectcode)
          ->Where(function ($query) {
            $query->Where('desig', 'Assistant Branch Manager');
            // $query->where('desig','Branch Manager');
          })->first();
        if ($findpin != null) {
          $nextrolepin = $findpin->cono;
        }
      }
    }

    // for am role
    if ($checkRoleHierarchie->designation == 'AM') {
      $nextrolepin = 'b123';
    }

    $this->sendAppNotification($entollmentid, $doc_type, $popin, $msgcontent, $action);
    $this->sendAppNotification($entollmentid, $doc_type, $nextrolepin, $msgcontent, $action);
  }
  //end erp api's functions

  //tab po bm dashboard reports api
  public function ReportSync(Request $req)
  {
    $this->LaravelLog();
    //echo "Huda";
    //dd("H");
    $db = $this->db;
    $token = Request::input('token');
    $branchcode = Request::input('branchcode');
    $ProjectCode = Request::input('projectcode');
    $project_code = Request::input('projectcode');
    $branchcode = (int)$branchcode;
    $projectcode = (int)$ProjectCode;
    if ($token == '7f30f4491cb4435984616d1913e88389') {
      if ($branchcode != null and $projectcode != null) {
        $FormConfig = DB::Table($db . '.form_configs')->where('projectcode', $project_code)->get();


        $result = array(
          "status" => "S",
          "message" => "",
          "FormConfig" => $FormConfig,
          "PayloadData" => $PayloadData,
          "OfficeMapping" => $OfficeMapping,
          "ProductDetail" => $ProductDetail,
          "ProjectwiseMemberCategory" => $ProjectwiseMemberCategory,
          "ProductProjectMemberCategory" => $ProductProjectMemberCategory,
          "SchememSectorSubsector" => $SchememSectorSubsector,
          "AuthConfig" => $auth_array,
        );
        return json_encode($result);
      } else {
        $result = array("status" => "E", "message" => "Invalid perameter!");
        return json_encode($result);
      }
    } else {
      $result = array("status" => "E", "message" => "Invalid token!");
      return json_encode($result);
    }
  }

  public function DcsBufferStatusCheck()
  {
    $this->LaravelLog();
    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    $admissionBufferPendings = DB::Table($db . '.admissions')->select('branchcode', 'projectcode', 'created_at')->where('ErpStatus', 1)->get();
    //dd($admissionBufferPendings);
    $loanBufferPendings = DB::Table($db . '.loans')->select('branchcode', 'projectcode', 'time')->where('ErpStatus', 1)->orWhere('ErpStatus', 2)->get();
    Log::channel('daily')->info('Dcs Buffer Status Check at ' . $currentDatetime);

    foreach ($loanBufferPendings as $row) {
      $applicationdate = date('Y-m-d', strtotime($row->time));
      $branchcode = $row->branchcode;
      $projectcode = $row->projectcode;
      $this->GetErpPendingLoanDataStatus($branchcode, $projectcode, $applicationdate);
    }

    foreach ($admissionBufferPendings as $row) {
      $applicationdate = date('Y-m-d', strtotime($row->created_at));
      $branchcode = $row->branchcode;
      $projectcode = $row->projectcode;
      $this->GetErpPendingAdmissionDataStatus($branchcode, $projectcode, $applicationdate);
    }
  }

  //erp get api admission data
  public function GetErpPendingAdmissionDataStatus($branchcode, $projectcode, $applicationdate)
  {
    $this->LaravelLog();
    /*$access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/branches/' . $branchcode . '/buffer-members?projectcode=' . $projectcode . '&applicationDate=' . $applicationdate;
    // $url = 'https://bracapitesting.brac.net/dcs/v1/branches/1344/buffer-members?projectcode=015';

    $headers = array(
      'Authorization: Bearer ' . $access_token,
      'Accept: application/json',
    );*/
    $db = $this->db;
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
    $projectcode = (int)$projectcode;
    $urlset = $url2 . "branches/$branchcode/buffer-members?projectCode=$projectcode&applicationDate=$applicationdate";
    Log::info("DCS Buffer Url" . $urlset);
    curl_setopt_array($curl, array(
      CURLOPT_URL => $urlset, //$url2 . 'branches / ' . $branchcode . ' / buffer - members ? projectcode = ' . $projectcode . ' & applicationDate = ' . $applicationdate,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpcode != 200) {
      Log::info("Admission Buffer Status Check" . $response);
      die;
    }
    $err = curl_error($curl);
    curl_close($curl);
    Log::info("Admission Buffer Status Check" . $response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {

      // dd(json_decode($response));
      if (json_last_error() == 0) {
        return $this->checkPostedAdmissionStatus($response);
      } else {
        return "Erp Server Down";
      }
    }
  }

  //erp get api admission data's database insertion
  public function checkPostedAdmissionStatus($response)
  {
    $this->LaravelLog();
    // dd($response);
    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    $arrayAddmission = json_decode($response);
    if (empty($arrayAddmission)) {
      $result = array("status" => "E", "message" => "Data Set Empty");
      echo json_encode($result);
      die;
    }
    foreach ($arrayAddmission as $data) {
      // dd($data->id);
      // if ($data->guarantor != null) {
      // $guarantordateofbirth = $data->guarantor[0]->dateOfBirth;
      // $guarantorbackimageurl = $data->guarantor[0]->idCard->backImageUrl;
      // $guarantorcardtypeid = $data->guarantor[0]->idCard->cardTypeId;
      // $guarantorissueplace = $data->guarantor[0]->idCard->issuePlace;
      // $guarantorexpirydate = $data->guarantor[0]->idCard->expiryDate;
      // $guarantorfrontimageurl = $data->guarantor[0]->idCard->frontImageUrl;
      // $guarantoridcardno = $data->guarantor[0]->idCard->idCardNo;
      // $guarantorissuedate = $data->guarantor[0]->idCard->issueDate;
      // $guarantornameen = $data->guarantor[0]->nameEn;
      // $guarantorrelationshipid = $data->guarantor[0]->relationshipId;
      // } else {

      // }
      $guarantordateofbirth = null;
      $guarantorbackimageurl = null;
      $guarantorcardtypeid = null;
      $guarantorissueplace = null;
      $guarantorexpirydate = null;
      $guarantorfrontimageurl = null;
      $guarantoridcardno = null;
      $guarantorissuedate = null;
      $guarantornameen = null;
      $guarantorrelationshipid = null;

      if ($data->nominees != null) {
        $nomineescontactNo = $data->nominees[0]->contactNo;
        $nomineesdateofbirth = $data->nominees[0]->dateOfBirth;
        $nomineesbackimageurl = $data->nominees[0]->idCard->idCardNo;
        $nomineescardtypeid = $data->nominees[0]->idCard->cardTypeId;
        $nomineesexpirydate = $data->nominees[0]->idCard->expiryDate;
        $nomineesfrontimageurl = $data->nominees[0]->idCard->frontImageUrl;
        $nomineesidcardno = $data->nominees[0]->idCard->idCardNo;
        $nomineesissuedate = $data->nominees[0]->idCard->issueDate;
        $nomineesissueplace = $data->nominees[0]->idCard->issuePlace;
        $nomineesname = $data->nominees[0]->name;

        if (array_key_exists('relationshipId', $data->nominees)) {
          $nomineesrelationshipid = $data->nominees->relationshipId;
        } else {
          $nomineesrelationshipid = null;
        }
      } else {
        $nomineescontactNo = null;
        $nomineesdateofbirth = null;
        $nomineesbackimageurl = null;
        $nomineescardtypeid = null;
        $nomineesexpirydate = null;
        $nomineesfrontimageurl = null;
        $nomineesidcardno = null;
        $nomineesissuedate = null;
        $nomineesissueplace = null;
        $nomineesname = null;
        $nomineesrelationshipid = null;
      }

      $values = array(
        'applicationdate' => $data->applicationDate,
        'assignedpopin' => $data->assignedPoPin,
        'bankaccountnumber' => $data->bankAccountNumber,
        'bankbranchid' => $data->bankBranchId,
        'bankid' => $data->bankId,
        'bkashwalletno' => $data->bkashWalletNo,
        'branchcode' => $data->branchCode,
        'contactno' => $data->contactNo,
        'dateofbirth' => $data->dateOfBirth,
        'educationid' => $data->educationId,
        'fathernameen' => $data->fatherNameEn,
        'flag' => $data->flag,
        'genderid' => $data->genderId,
        //guarantor
        "guarantordateofbirth" => $guarantordateofbirth,
        "guarantorbackimageurl" => $guarantorbackimageurl,
        "guarantorcardtypeid" => $guarantorcardtypeid,
        "guarantorissueplace" => $guarantorissueplace,
        "guarantorexpirydate" => $guarantorexpirydate,
        "guarantorfrontimageurl" => $guarantorfrontimageurl,
        "guarantoridcardno" => $guarantoridcardno,
        "guarantorissuedate" => $guarantorissuedate,
        "guarantornameen" => $guarantornameen,
        "guarantorrelationshipid" => $guarantorrelationshipid,
        'addmission_id' => $data->id,
        //idCard
        "idcardbackimageurl" => $data->idCard->backImageUrl,
        "idcardcardtypeid" => $data->idCard->cardTypeId,
        "idcardexpirydate" => $data->idCard->expiryDate,
        "idcardfrontimageurl" => $data->idCard->frontImageUrl,
        "idcardidcardno" => $data->idCard->idCardNo,
        "idcardissuedate" => $data->idCard->issueDate,
        "idcardissueplace" => $data->idCard->issuePlace,
        'maritalstatusid' => $data->maritalStatusId,
        'memberid' => $data->memberId,
        'memberimageurl' => $data->memberImageUrl,
        'membertypeid' => $data->memberTypeId,
        'mothernameen' => $data->motherNameEn,
        'nameen' => $data->nameEn,
        //nominees
        "nomineescontactno" => $nomineescontactNo,
        "nomineesdateofbirth" => $nomineesdateofbirth,
        // "id" => $data->nominees[0]->id,
        "nomineesbackimageurl" => $nomineesbackimageurl,
        "nomineescardtypeid" => $nomineescardtypeid,
        "nomineesexpirydate" => $nomineesexpirydate,
        "nomineesfrontimageurl" => $nomineesfrontimageurl,
        "nomineesidcardno" => $nomineesidcardno,
        "nomineesissuedate" => $nomineesissuedate,
        "nomineesissueplace" => $nomineesissueplace,
        "nomineesname" => $nomineesname,
        "nomineesrelationshipid" => $nomineesrelationshipid,
        'occupationid' => $data->occupationId,
        'passbooknumber' => $data->passbookNumber,
        'permanentaddress' => $data->permanentAddress,
        'permanentdistrictid' => $data->permanentDistrictId,
        'permanentupazilaid' => $data->permanentUpazilaId,
        'poid' => $data->poId,
        'presentaddress' => $data->presentAddress,
        'presentdistrictid' => $data->presentDistrictId,
        'presentupazilaid' => $data->presentUpazilaId,
        'projectcode' => $data->projectCode,
        'rejectionreason' => $data->rejectionReason,
        'routingnumber' => $data->routingNumber,
        'savingsproductid' => $data->savingsProductId,
        'spousedateofbirth' => $data->spouseDateOfBirth,
        // // spouseIdCard
        "spouseidcardbackimageurl" => $data->spouseIdCard->backImageUrl,
        "spouseidcardcardtypeid" => $data->spouseIdCard->cardTypeId,
        "spouseidcardexpirydate" => $data->spouseIdCard->expiryDate,
        "spouseidcardfrontimageurl" => $data->spouseIdCard->frontImageUrl,
        "spouseidcardidcardno" => $data->spouseIdCard->idCardNo,
        "spouseidcardissuedate" => $data->spouseIdCard->issueDate,
        "spouseidcardissueplace" => $data->spouseIdCard->issuePlace,
        'spousenameen' => $data->spouseNameEn,
        'statusid' => $data->statusId,
        'targetamount' => $data->targetAmount,
        'tinnumber' => $data->tinNumber,
        'updated' => $data->updated,
        'vocode' => $data->voCode,
        'void' => $data->voId,
        'admission_id' => $data->id,
      );

      $checkPostedAdmission = DB::table($db . '.posted_admission')->where('admission_id', $data->id)->first();
      $checkAdmission = DB::table($db . '.admissions')->where('entollmentid', $data->id)->first();
      $checkLoan = DB::table($db . '.loans')->where('mem_id', $data->id)->first();

      if ($data->statusId == 2 or $data->statusId == 3) {  //if erp approve and reject
        if ($checkAdmission != null) {                //if addmission has data
          if ($checkAdmission->MemberId == null and $checkAdmission->ErpStatus == 1) {    //if erp member id empty in dcs admission table
            $this->sendAppNotificationForErpAddmissionAction($data);
          }
        }
      }

      $updatedAt = date('Y-m-d H:i:s');
      if ($checkPostedAdmission == null) {
        DB::table($db . '.posted_admission')->insert($values);
        if ($data->statusId == 2) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['MemberId' => $data->memberId, 'ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('mem_id', $data->id)->update(['erp_mem_id' => $data->memberId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        } elseif ($data->statusId == 3) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        }
      } else {
        // if ($data->updated == TRUE) {
        DB::table($db . '.posted_admission')->where('admission_id', $data->id)->update($values);
        // }
        if ($data->statusId == 2) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['MemberId' => $data->memberId, 'ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('mem_id', $data->id)->update(['erp_mem_id' => $data->memberId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        } elseif ($data->statusId == 3) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        }
      }
    }
    return "Data sync successful";
  }

  //erp get api loan data
  public function GetErpPendingLoanDataStatus($branchcode, $projectcode, $applicationdate)
  {
    $this->LaravelLog();
    /*$access_token = $this->tokenVerify();
    $clientid = 'Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935';
    $clientsecret = '4H2QJ89kYQBStaCuY73h';
    $url = 'https://bracapitesting.brac.net/dcs/v1/branches/' . $branchcode . '/buffer-loan-proposals?projectcode=' . $projectcode . '&applicationDate=' . $applicationdate;

    $headers = array(
      'Authorization: Bearer ' . $access_token,
      'Accept: application/json',
    );*/
    $db = $this->db;
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
    // echo $servertoken;
    if ($servertoken != '') {
      $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $servertoken
      );
    } else {
      $statuss = array(
        "status" => "CUSTMSG", "message" => "Token Not Found"
      );
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    $curl = curl_init();
    $projectcode = (int)$projectcode;
    $urlset = $url2 . "branches/$branchcode/buffer-loan-proposals?projectCode=$projectcode&applicationDate=$applicationdate";
    //echo $urlset;
    curl_setopt_array($curl, array(
      CURLOPT_URL => $urlset, //$url2 . 'branches/' . $branchcode . '/buffer-loan-proposals?projectcode=' . $projectcode . '&applicationDate=' . $applicationdate,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    //dd($response);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpcode != 200) {
      Log::info("Loan Buffer Status Check" . $response);
      die;
    }
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      //   return $response;
      json_decode($response);
      if (json_last_error() == 0) {
        return $this->checkPostedLoanStatus($response);
      } else {
        return "Erp Server Down";
      }
    }
  }

  //erp get api loan data's database insertion
  public function checkPostedLoanStatus($response)
  {
    $this->LaravelLog();
    $BufferMemberStatus = $response;
    $db = $this->db;
    $dberp = $this->dberp;
    $currentDatetime = date("Y-m-d h:i:s");
    $arrayLoan = json_decode($response);
    foreach ($arrayLoan as $data) {
      // dd($data->secondInsurer);

      if ($data->secondInsurer != null) {
        $secondinsurerdateofbirth = $data->secondInsurer->dateOfBirth;
        $secondinsurerbackimageurl = $data->secondInsurer->idCard->idCardNo;
        $secondinsurercardtypeid = $data->secondInsurer->idCard->cardTypeId;
        $secondinsurerexpirydate = $data->secondInsurer->idCard->expiryDate;
        $secondinsurerfrontimageurl = $data->secondInsurer->idCard->frontImageUrl;
        $secondinsureridcardno = $data->secondInsurer->idCard->idCardNo;
        $secondinsurerissuedate = $data->secondInsurer->idCard->issueDate;
        $secondinsurerissueplace = $data->secondInsurer->idCard->issuePlace;
        $secondinsurername = $data->secondInsurer->name;

        if ('relationshipId' == $data->secondInsurer) {
          $secondinsurerrelationshipid = $data->secondInsurer->relationshipId;
        } else {
          $secondinsurerrelationshipid = null;
        }
        if ('genderId' == $data->secondInsurer) {
          $secondinsurergenderid = $data->secondInsurer->genderId;
        } else {
          $secondinsurergenderid = null;
        }
      } else {
        $secondinsurerdateofbirth = null;
        $secondinsurergenderid = null;
        $secondinsurerbackimageurl = null;
        $secondinsurercardtypeid = null;
        $secondinsurerexpirydate = null;
        $secondinsurerfrontimageurl = null;
        $secondinsureridcardno = null;
        $secondinsurerissuedate = null;
        $secondinsurerissueplace = null;
        $secondinsurername = null;
        $secondinsurerrelationshipid = null;
      }

      if ($data->nominees != null) {
        $nomineescontactNo = $data->nominees[0]->contactNo;
        $nomineesdateofbirth = $data->nominees[0]->dateOfBirth;
        $nomineesbackimageurl = $data->nominees[0]->idCard->idCardNo;
        $nomineescardtypeid = $data->nominees[0]->idCard->cardTypeId;
        $nomineesexpirydate = $data->nominees[0]->idCard->expiryDate;
        $nomineesfrontimageurl = $data->nominees[0]->idCard->frontImageUrl;
        $nomineesidcardno = $data->nominees[0]->idCard->idCardNo;
        $nomineesissuedate = $data->nominees[0]->idCard->issueDate;
        $nomineesissueplace = $data->nominees[0]->idCard->issuePlace;
        $nomineesname = $data->nominees[0]->name;

        if ('relationshipId' == $data->nominees) {
          $nomineesrelationshipid = $data->nominees->relationshipId;
        } else {
          $nomineesrelationshipid = null;
        }
      } else {
        $nomineescontactNo = null;
        $nomineesdateofbirth = null;
        $nomineesbackimageurl = null;
        $nomineescardtypeid = null;
        $nomineesexpirydate = null;
        $nomineesfrontimageurl = null;
        $nomineesidcardno = null;
        $nomineesissuedate = null;
        $nomineesissueplace = null;
        $nomineesname = null;
        $nomineesrelationshipid = null;
      }

      $values = array(
        "applicationdate" => $data->applicationDate,
        "approveddurationinmonths" => $data->approvedDurationInMonths,
        "approvedloanamount" => $data->approvedLoanAmount,
        "branchcode" => $data->branchCode,
        // coBorrowerDto
        // "coborrowerdtobackimageurl" => $data->coBorrowerDto->idCard->backImageUrl,
        // "coborrowerdtocardtypeid" => $data->coBorrowerDto->idCard->cardTypeId,
        // "coborrowerdtoexpirydate" => $data->coBorrowerDto->idCard->expiryDate,
        // "frontImageUrl" => $data->coBorrowerDto->idCard->backImageUrl,
        // "coborrowerdtoidcardno" => $data->coBorrowerDto->idCard->idCardNo,
        // "coborrowerdtoissuedate" => $data->coBorrowerDto->idCard->issueDate,
        // "coborrowerdtoissueplace" => $data->coBorrowerDto->idCard->issuePlace,            
        // "coborrowerdtoname" => $data->coBorrowerDto->name,
        // "coborrowerdtorelationshipid" => $data->coBorrowerDto->relationshipId,
        "consenturl" => $data->consentUrl,
        "disbursementdate" => $data->disbursementDate,
        // "flag" => $data->flag,
        "frequencyid" => $data->frequencyId,
        "loan_id" => $data->id,
        "insuranceproductid" => $data->insuranceProductId,
        "loanaccountid" => $data->loanAccountId,
        "loanapprover" => $data->loanApprover,
        "loanproductid" => $data->loanProductId,
        "loanproposalstatusid" => $data->loanProposalStatusId,
        "memberid" => $data->memberId,
        "membertypeid" => $data->memberTypeId,
        "microinsurance" => $data->microInsurance,
        "modeofpaymentid" => $data->modeOfPaymentId,
        // nominee
        "nomineescontactno" => $nomineescontactNo,
        "nomineesdateofbirth" => $nomineesdateofbirth,
        // "id" => $data->nominees[0]->id,
        "nomineesbackimageurl" => $nomineesbackimageurl,
        "nomineescardtypeid" => $nomineescardtypeid,
        "nomineesexpirydate" => $nomineesexpirydate,
        "nomineesfrontimageurl" => $nomineesfrontimageurl,
        "nomineesidcardno" => $nomineesidcardno,
        "nomineesissuedate" => $nomineesissuedate,
        "nomineesissueplace" => $nomineesissueplace,
        "nomineesname" => $nomineesname,
        "nomineesrelationshipid" => $nomineesrelationshipid,
        "policytypeid" => $data->policyTypeId,
        "premiumamount" => $data->premiumAmount,
        "projectcode" => $data->projectCode,
        "proposaldurationinmonths" => $data->proposalDurationInMonths,
        "proposedloanamount" => $data->proposedLoanAmount,
        "rejectionreason" => $data->rejectionReason,
        "schemeid" => $data->schemeId,
        "secondinsurerdateofbirth" => $secondinsurerdateofbirth,
        "secondinsurergenderid" => $secondinsurergenderid,
        "secondinsurerbackimageurl" => $secondinsurerbackimageurl,
        "secondinsurercardtypeid" => $secondinsurercardtypeid,
        "secondinsurerexpirydate" => $secondinsurerexpirydate,
        "secondinsurerfrontimageurl" => $secondinsurerfrontimageurl,
        "secondinsureridcardno" => $secondinsureridcardno,
        "secondinsurerissuedate" => $secondinsurerissuedate,
        "secondinsurerissueplace" => $secondinsurerissueplace,
        "secondinsurername" => $secondinsurername,
        "secondinsurerrelationshipid" => $secondinsurerrelationshipid,
        "sectorid" => $data->sectorId,
        "signconsent" => $data->signConsent,
        "subsectorid" => $data->subSectorId,
        "updated" => $data->updated,
        "vocode" => $data->voCode,
        "void" => $data->voId,
      );

      $checkPostedLoan = DB::table($db . '.posted_loan')->where('loan_id', $data->id)->first();
      $checkLoan = DB::table($db . '.loans')->where('loan_id', $data->id)->first();
      // dd($checkLoan);

      if ($data->loanProposalStatusId == 4 or $data->loanProposalStatusId == 3) {  //if erp loan disbursed or reject
        if ($checkLoan != null) {                //if addmission has data
          // $member = DB::table($db . '.posted_admission')->where('memberid', $data->memberId)->first();
          // $serverurl = DB::Table($dberp . '.server_url')->where('server_status', 3)->where('status', 1)->first();
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
          $key = '5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae';
          $UpdatedAt = "2000-01-01 00:00:00";
          $member = Http::get($url . 'MemberList', [
            'BranchCode' => $checkLoan->branchcode,
            'CONo' => $checkLoan->assignedpo,
            'ProjectCode' => $checkLoan->projectcode,
            'UpdatedAt' => $UpdatedAt,
            'Status' => 1,
            'OrgNo' => $checkLoan->orgno,
            'OrgMemNo' => $checkLoan->orgmemno,
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
          // dd($member);
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
          /*if ($checkLoan->erp_loan_id == null and $checkLoan->ErpStatus == 1) {    //if erp member id empty in dcs admission table
            if ($member != null) {
              $this->sendAppNotificationForErpLoanAction($data, $member);
            }
          }*/
        }
      }
      $updatedAt = date('Y-m-d H:i:s');
      if ($checkPostedLoan == null) {
        DB::table($db . '.posted_loan')->insert($values);
        if ($data->loanProposalStatusId == 4) {
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['erp_loan_id' => $data->loanAccountId, 'ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        } else {
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        }
      } else {
        // if ($data->updated == TRUE) {
        DB::table($db . '.posted_loan')->where('loan_id', $data->id)->update($values);
        if ($data->loanProposalStatusId == 4) {
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['erp_loan_id' => $data->loanAccountId, 'ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        } else {
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        }
        // }
      }
    }
    return "Data sync successful";
  }

  public function DcsDataPulling()
  {
    $this->LaravelLog();
    $db = $this->db;
    $currentDatetime = date("Y-m-d h:i:s");
    /* $access_token = $this->tokenVerify();
    $url = 'https://bracapitesting.brac.net/dcs/v1/';*/
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
    DB::beginTransaction();
    try {
      // table deletion
      DB::table($db . '.project_wise_branch_growth_types')->truncate();
      DB::table($db . '.celing_configs')->truncate();
      DB::table($db . '.projectwise_member_category')->truncate();
      DB::table($db . '.product_project_member_category')->truncate();
      DB::table($db . '.product_details')->truncate();
      DB::table($db . '.schemem_sector_subsector')->truncate();
      DB::table($db . '.insurance_products')->truncate();
      DB::table($db . '.office_mapping')->truncate();
      // end table delete

      //branch-project-growth-type-mappings (5 api dociment)
      $page = 0;
      $branchprojectgrowthtypemappings = Http::withToken($servertoken)->get('https://erp.brac.net/api/dcs/v2/branch-project-growth-type-mappings?page=' . $page);
      $branchprojectgrowthtypemappings = $branchprojectgrowthtypemappings->object();
      if ($branchprojectgrowthtypemappings) {
        foreach ($branchprojectgrowthtypemappings as $row) {
          DB::Table($db . '.project_wise_branch_growth_types')->insert(['office_id' => null, 'office_code' => $row->officeCode, 'office_name' => $row->officeName, 'project_code' => $row->projectCode, 'project_name' => $row->projectName, 'branch_growth_type' => $row->branchGrowthType,'group'=>$row->groupCategory]);
        }
        $page++;
        while ($branchprojectgrowthtypemappings) {
          $branchprojectgrowthtypemappings = Http::withToken($servertoken)->get('https://erp.brac.net/api/dcs/v2/branch-project-growth-type-mappings?page=' . $page);
          $branchprojectgrowthtypemappings = $branchprojectgrowthtypemappings->object();

          if ($branchprojectgrowthtypemappings) {
            foreach ($branchprojectgrowthtypemappings as $row) {
              DB::Table($db . '.project_wise_branch_growth_types')->insert(['office_id' => null, 'office_code' => $row->officeCode, 'office_name' => $row->officeName, 'project_code' => $row->projectCode, 'project_name' => $row->projectName, 'branch_growth_type' => $row->branchGrowthType,'group'=>$row->groupCategory]);
            }
          }
          $page++;
        }
      }
      //end branch-project-growth-type-mappings (5 api dociment)

      //Approver wise loan limit mapping API  (6 api dociment)
      $approverwiseloanlimitmappings = Http::withToken($servertoken)->get($url2 . 'approver-wise-loan-limit-mappings');
      $approverwiseloanlimitmappings = $approverwiseloanlimitmappings->object();
      //dd($approverwiseloanlimitmappings);

      if ($approverwiseloanlimitmappings) {
        foreach ($approverwiseloanlimitmappings as $row) {
          //print_r($row->projectCode);
          DB::Table($db . '.celing_configs')->insert(['projectcode' => $row->projectCode, 'approver' => $row->approverName, 'growth_rate' => $row->branchGrowthType, 'limit_form' => $row->limitFrom, 'limit_to' => $row->limitTo, 'repeat_limit_form' => $row->repeatLimitFrom, 'repeat_limit_to' => $row->repeatLimitTo,'group'=>$row->groupCategory]);
        }
      }
      //end Approver wise loan limit mapping API  (6 api dociment)

      //Project wise Member type mappings API  (7 api dociment)
      $projectwisemembertypes = Http::withToken($servertoken)->get($url2 . 'project-wise-member-types');
      $projectwisemembertypes = $projectwisemembertypes->object();
      if ($projectwisemembertypes) {
        foreach ($projectwisemembertypes as $row) {
          DB::Table($db . '.projectwise_member_category')->insert(['categoryid' => $row->categoryId, 'categoryname' => $row->categoryName, 'projectcode' => $row->projectCode]);
        }
      }
      //end Project wise Member type mappings API  (7 api dociment)

      //specific branch wise loan products (8 api dociment)
      $allbranchwiseloanproducts = Http::withToken($servertoken)->get($url2 . 'all-branch-wise-loan-products');
      $allbranchwiseloanproducts = $allbranchwiseloanproducts->object();
      foreach ($allbranchwiseloanproducts as $row) {
        DB::Table($db . '.product_project_member_category')->insert(['productcode' => $row->productCode, 'productname' => $row->productName, 'projectcode' => $row->projectCode, 'membercategory' => $row->memberCategoryName, 'membercategoryid' => $row->memberCategoryId, 'productid' => $row->productId, 'branchcode' => "*"]);
      }
      //end specific branch wise loan products (8 api dociment)

      //specific branch wise loan products (8.1 api dociment)
      $page = 0;
      $specificbranchwiseloanproducts = Http::withToken($servertoken)->get($url2 . 'specific-branch-wise-loan-products?page=' . $page);
      $specificbranchwiseloanproducts = $specificbranchwiseloanproducts->object();
      if ($specificbranchwiseloanproducts) {
        foreach ($specificbranchwiseloanproducts as $row) {
          DB::Table($db . '.product_project_member_category')->insert(['productcode' => $row->productCode, 'productname' => $row->productName, 'projectcode' => $row->projectCode, 'membercategory' => $row->memberCategoryName, 'membercategoryid' => $row->memberCategoryId, 'productid' => $row->productId, 'branchcode' => $row->branchCode]);
        }
        $page++;
        while ($specificbranchwiseloanproducts) {
          $specificbranchwiseloanproducts = Http::withToken($servertoken)->get($url2 . 'specific-branch-wise-loan-products?page=' . $page);
          $specificbranchwiseloanproducts = $specificbranchwiseloanproducts->object();

          if ($specificbranchwiseloanproducts) {
            foreach ($specificbranchwiseloanproducts as $row) {
              DB::Table($db . '.product_project_member_category')->insert(['productcode' => $row->productCode, 'productname' => $row->productName, 'projectcode' => $row->projectCode, 'membercategory' => $row->memberCategoryName, 'membercategoryid' => $row->memberCategoryId, 'productid' => $row->productId, 'branchcode' => $row->branchCode]);
            }
          }
          $page++;
        }
      }
      //end specific branch wise loan products (8.1 api dociment)

      //Loan product-wise frequency mapping (9 api dociment)
      $loanproductwisefrequencymappings = Http::withToken($servertoken)->get($url2 . 'loan-product-wise-frequency-mappings');
      $loanproductwisefrequencymappings = $loanproductwisefrequencymappings->object();
      foreach ($loanproductwisefrequencymappings as $row) {
        DB::Table($db . '.product_details')->insert(['productcode' => $row->loanProductCode, 'productname' => $row->loanProductName, 'frequency' => $row->frequency, 'frequencyid' => $row->frequencyId, 'noofinstallment' => $row->noOfInstallment, 'loanduration' => $row->loanDuration]);
      }
      //end Loan product-wise frequency mapping (9 api dociment)

      //Branch-wise Project and insurance mapping  (10 api dociment)
      /*$branchwiseprojectinsuranceproductsmappings = Http::withToken($servertoken)->get($url2 . 'branch-wise-projects-insurance-products-mappings');
      $branchwiseprojectinsuranceproductsmappings = $branchwiseprojectinsuranceproductsmappings->object();
      foreach ($branchwiseprojectinsuranceproductsmappings as $row) {
        DB::Table($db . '.insurance_products')->insert(['product_id' => $row->insuranceProductId, 'product_code' => $row->insuranceProductCode, 'product_name' => $row->insuranceProductName, 'project_code' => $row->projectCode, 'branchcode' => $row->branchCode]);
      }*/
      //end Branch-wise Project and insurance mapping (10 api dociment)

      //Geo division district thana Upazila mapping API (11 api dociment)
      $page = 0;
      $districtdivisionupazilaofficemappings = Http::withToken($servertoken)->get($url2 . 'district-division-upazila-office-mappings?page=' . $page);
      $districtdivisionupazilaofficemappings = $districtdivisionupazilaofficemappings->object();
      if ($districtdivisionupazilaofficemappings) {
        foreach ($districtdivisionupazilaofficemappings as $row) {
          DB::Table($db . '.office_mapping')->insert(['division_id' => $row->divisionId, 'division_name' => $row->divisionName, 'district_id' => $row->districtId, 'district_name' => $row->districtName, 'thana_id' => $row->thanaId, 'thana_name' => $row->thanaName, 'branchcode' => $row->officeCode, 'branch_name' => $row->officeName, 'status' => 1]);
        }
        $page++;
        while ($districtdivisionupazilaofficemappings) {
          $districtdivisionupazilaofficemappings = Http::withToken($servertoken)->get($url2 . 'district-division-upazila-office-mappings?page=' . $page);
          $districtdivisionupazilaofficemappings = $districtdivisionupazilaofficemappings->object();

          if ($districtdivisionupazilaofficemappings) {
            foreach ($districtdivisionupazilaofficemappings as $row) {
              DB::Table($db . '.office_mapping')->insert(['division_id' => $row->divisionId, 'division_name' => $row->divisionName, 'district_id' => $row->districtId, 'district_name' => $row->districtName, 'thana_id' => $row->thanaId, 'thana_name' => $row->thanaName, 'branchcode' => $row->officeCode, 'branch_name' => $row->officeName, 'status' => 1]);
            }
          }
          $page++;
        }
      }
      //end Geo division district thana Upazila mapping API (11 api dociment)

      //All Branch-wise Project Product Scheme and Sector mapping API (12 api dociment)
      $page = 0;
      $allbranchwiseprojectproductschemesectormappings = Http::withToken($servertoken)->get($url2 . 'all-branch-wise-project-product-scheme-sector-mappings?page=' . $page);
      $allbranchwiseprojectproductschemesectormappings = $allbranchwiseprojectproductschemesectormappings->object();
      if ($allbranchwiseprojectproductschemesectormappings) {
        foreach ($allbranchwiseprojectproductschemesectormappings as $row) {
          DB::Table($db . '.schemem_sector_subsector')->insert(['sectorid' => $row->sectorId, 'sectorcode' => $row->sectorCode, 'sectorname' => $row->sectorName, 'subsectorid' => $row->subSectorId, 'subsectorcode' => $row->subSectorCode, 'subsectorname' => $row->subSectorName, 'schemeid' => $row->schemeId, 'schemecode' => $row->schemeCode, 'schemename' => $row->schemeName, 'branchcode' => "*", 'loanproductid' => $row->loanProductId, 'productcode' => $row->loanProductCode, 'productname' => $row->loanProductName, 'productid' => null, 'projectcode' => $row->projectCode]);
        }
        $page++;
        while ($allbranchwiseprojectproductschemesectormappings) {
          $allbranchwiseprojectproductschemesectormappings = Http::withToken($servertoken)->get($url2 . 'all-branch-wise-project-product-scheme-sector-mappings?page=' . $page);
          $allbranchwiseprojectproductschemesectormappings = $allbranchwiseprojectproductschemesectormappings->object();

          if ($allbranchwiseprojectproductschemesectormappings) {
            foreach ($allbranchwiseprojectproductschemesectormappings as $row) {
              DB::Table($db . '.schemem_sector_subsector')->insert(['sectorid' => $row->sectorId, 'sectorcode' => $row->sectorCode, 'sectorname' => $row->sectorName, 'subsectorid' => $row->subSectorId, 'subsectorcode' => $row->subSectorCode, 'subsectorname' => $row->subSectorName, 'schemeid' => $row->schemeId, 'schemecode' => $row->schemeCode, 'schemename' => $row->schemeName, 'branchcode' => "*", 'loanproductid' => $row->loanProductId, 'productcode' => $row->loanProductCode, 'productname' => $row->loanProductName, 'productid' => null, 'projectcode' => $row->projectCode]);
            }
          }
          $page++;
        }
      }
      //end All Branch-wise Project Product Scheme and Sector mapping API (12 api dociment)

      // Specific Branch-wise Project Product Scheme and Sector mapping API  (12.1 api dociment)
      $page = 0;
      $specificbranchwiseprojectproductschemesectormappings = Http::withToken($servertoken)->get($url2 . 'specific-branch-wise-project-product-scheme-sector-mappings?page=' . $page);

      $specificbranchwiseprojectproductschemesectormappings = $specificbranchwiseprojectproductschemesectormappings->object();
      if ($specificbranchwiseprojectproductschemesectormappings) {
        foreach ($specificbranchwiseprojectproductschemesectormappings as $row) {
          DB::Table($db . '.schemem_sector_subsector')->insert(['sectorid' => $row->sectorId, 'sectorcode' => $row->sectorCode, 'sectorname' => $row->sectorName, 'subsectorid' => $row->subSectorId, 'subsectorcode' => $row->subSectorCode, 'subsectorname' => $row->subSectorName, 'schemeid' => $row->schemeId, 'schemecode' => $row->schemeCode, 'schemename' => $row->schemeName, 'branchcode' => $row->branchCode, 'loanproductid' => $row->loanProductId, 'productcode' => $row->loanProductCode, 'productname' => $row->loanProductName, 'productid' => null, 'projectcode' => $row->projectCode]);
        }
        $page++;
        while ($specificbranchwiseprojectproductschemesectormappings) {
          $specificbranchwiseprojectproductschemesectormappings = Http::withToken($servertoken)->get($url2 . 'specific-branch-wise-project-product-scheme-sector-mappings?page=' . $page);
          $specificbranchwiseprojectproductschemesectormappings = $specificbranchwiseprojectproductschemesectormappings->object();

          if ($specificbranchwiseprojectproductschemesectormappings) {
            foreach ($specificbranchwiseprojectproductschemesectormappings as $row) {
              DB::Table($db . '.schemem_sector_subsector')->insert(['sectorid' => $row->sectorId, 'sectorcode' => $row->sectorCode, 'sectorname' => $row->sectorName, 'subsectorid' => $row->subSectorId, 'subsectorcode' => $row->subSectorCode, 'subsectorname' => $row->subSectorName, 'schemeid' => $row->schemeId, 'schemecode' => $row->schemeCode, 'schemename' => $row->schemeName, 'branchcode' => $row->branchCode, 'loanproductid' => $row->loanProductId, 'productcode' => $row->loanProductCode, 'productname' => $row->loanProductName, 'productid' => null, 'projectcode' => $row->projectCode]);
            }
          }
          $page++;
        }
      }
      //end Specific Branch-wise Project Product Scheme and Sector mapping API  (12.1 api dociment)
      Log::channel('daily')->info('Data mapping Sucessfull');
      DB::commit();

      return "Data Pulled Sucessfully";
    } catch (\Throwable $e) {
      DB::rollback();
      throw $e;
      Log::channel('daily')->info('Data mapping pull error: ' . $e);
    }
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
      //$clienturl = 'https://bracapitesting.brac.net/oauth/v2/token?grant_type=client_credentials'; //test
      $clienturl = 'https://erp.brac.net/oauth/v2/token?grant_type=client_credentials'; // live
      //live api
      $header = array(
        'x-client-id:1_43wc41hen7cwg0sg4s044c0scc8wck4o',
        'x-client-secret:654spemp5qckcg4g448044kco4k0g8wwo0440osgwosggwg4'
      );
      /*$header = array( //test
        'x-client-id:Ieg1N5W2qh3hF0qS9Zh2wq6eex2DB935',
        'x-client-secret:4H2QJ89kYQBStaCuY73h'
      );*/
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $clienturl); //https://erp.brac.net/oauth/v2/token?grant_type=client_credentials
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_POSTFIELDS, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $auth_output = curl_exec($ch);
      //dd($auth_output);
      Log::info("Access Token" . $auth_output);
      $auth = json_decode($auth_output);
      if (isset($auth->access_token)) {
        $accesstoken = $auth->access_token;
      } else {
        $result = array("status" => "E", "message" => "Access Token Not Found!");
        Log::info("Access Token" . json_encode($result));
        echo json_encode($result);
        die;
      }

      $_SESSION["access_token"] = $accesstoken;
    }
    if (isset($_SESSION["access_token"])) {
      $token = $_SESSION["access_token"];
      //echo $token;
    }
    return $token;
    //Log::info('bksh json Check -' . $PIN . "-" . $walletno . "-" . $qsoftids . "-" . $auth_output . "-" . $token);
  }
  public function ServerURL($db)
  {
    $db = $this->db;
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
  public function ImageUploadOthers(Request $request)
  {
    $this->LaravelLog();
    $db = $this->db;
    $appid = Request::input('appid');
    $apikey = Request::input('apikey');
    $image = Request::input('file');
    $uploaddir = '/data/uploads/';
    $baseurl = 'http://35.194.177.21/data/uploads/';
    $time = date('Y-m-d h:i:s');
    $uploadfile = $uploaddir . $time . basename($_FILES['file']['name']);
    $responsefile = $baseurl . $time . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
      $result = array("status" => "S", "message" => "", "data" => $responsefile);
      echo json_encode($result);
    } else {
      $result = array("status" => "E", "message" => "Failed Upload");
      echo json_encode($result);
    }
  }
  public function LogCreate($branchcode, $projectcode, $pin, $type, $message)
  {
    $db = $this->db;
    $insertquery = DB::Table($db . '.logs')->insert(['branchcode' => $branchcode, "projectcode" => $projectcode, "pin" => $pin, "type" => $type, "message" => $message]);
  }
  public function LaravelLog()
  {
    $dateCurrent = date('Y-m-d');
    $file_pointer1 = storage_path('logs/laravel-' . $dateCurrent . '.log');

    if (file_exists($file_pointer1)) {
      $contents = File::get(storage_path('logs/laravel-' . $dateCurrent . '.log'));
      if ($contents) {
        $fileSize = File::size(storage_path('logs/laravel-' . $dateCurrent . '.log'));
        //dd($fileSize);
        if ($fileSize > 20000000) {
          $currentTimeStamp = date('Y-m-d-h-i');
          rename(storage_path('logs/laravel-' . $dateCurrent . '.log'), storage_path('logs/laravel-' . $currentTimeStamp . '.log'));
        }
      }
    }
    $dateCurrent = date('Y-m-d');
    $file_pointer1 = storage_path('logs/laravel.log');

    if (file_exists($file_pointer1)) {
      $contents = File::get(storage_path('logs/laravel.log'));
      if ($contents) {
        $fileSize = File::size(storage_path('logs/laravel.log'));
        //dd($fileSize);
        if ($fileSize > 20000000) {
          $currentTimeStamp = date('Y-m-d-h-i');
          rename(storage_path('logs/laravel.log'), storage_path('logs/laravelLog-' . $currentTimeStamp . '.log'));
        }
      }
    }
  }
  public function Insurance_ProductId($db, $loanProductId, $projectCode, $productcode1)
  {
    $insuranceProductId = '';
    if ($productcode1 != '') {
      $getproductid = DB::Table($db . '.product_project_member_category')->select('productcode')->where('productcode', $productcode1)->groupBy('productcode')->get();
    } else {
      $getproductid = DB::Table($db . '.product_project_member_category')->select('productcode')->where('productid', $loanProductId)->groupBy('productcode')->get();
    }
    //dd($getproductid[0]->productcode);

    if ($getproductid->isEmpty()) {
      $result = array("status" => "E", "message" => "Insurance Product Id Not Found");
      echo json_encode($result);
      die;
    } else {
      if ($projectCode == '15') {
        $productcode = $getproductid[0]->productcode;
        if ($productcode == 'BD-10113') {
          $insuranceProductId = 26;
        } elseif ($productcode == 'BD-10103') {
          $insuranceProductId = 25;
        } else {
          $insuranceProductId = 16;
        }
      } else if ($projectCode == '60') {
        $productcode = $getproductid[0]->productcode;
        $insuranceProductId = 37;
        /* if ($productcode == 'BD-10115') {
          $insuranceProductId = 37;
        } else {
          $insuranceProductId = 20;
        } */
      } else {
        $insuranceProductId = null;
      }
    }
    return $insuranceProductId;
  }
  public function Guarentors_Insert($db, $loanid, $grantor_name1, $grantor_fatherOrHusbandName1, $grantor_nidNo1, $grantor_dob1, $grantor_occupation1, $grantor_presentAddress1, $grantor_permanentAddress1, $grantor_mobileNo1, $grantor_monthlyIncome1, $grantor_monthlyExpense1, $grantor_photo1, $grantor_nidFront1, $grantor_nidBack1, $grantor_name2, $grantor_fatherOrHusbandName2, $grantor_nidNo2, $grantor_dob2, $grantor_occupation2, $grantor_presentAddress2, $grantor_permanentAddress2, $grantor_mobileNo2, $grantor_monthlyIncome2, $grantor_monthlyExpense2, $grantor_photo2, $grantor_nidFront2, $grantor_nidBack2, $updatedate, $branchcode, $assignedpo, $grantor_idType1, $grantor_idTypeId1, $grantor_idType2, $grantor_idTypeId2, $loan_roductCode, $grantor_idIssueDate1, $grantor_idExpireDate1, $grantor_idIssueCountry1, $grantor_idIssueDate2, $grantor_idExpireDate2, $grantor_idIssueCountry2) //'grantor_idissuedate1'=>$grantor_idIssueDate1, 'grantor_idexpiredate1'=>$grantor_idExpireDate1,'grantor_idissuecountry1'=>$grantor_idIssueCountry1,'grantor_idissuedate2' =>$grantor_idIssueDate2, 'grantor_idexpiredate2'=>$grantor_idExpireDate2, 'grantor_idissuecountry2'=>$grantor_idIssueCountry2
  {
    $check = DB::Table($db . '.guarantor_details1')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $guarantor1 = DB::Table($db . '.guarantor_details1')->insert(['loan_id' => $loanid, 'name' => $grantor_name1, 'father_husband_name' => $grantor_fatherOrHusbandName1, 'nid_no' => $grantor_nidNo1, 'dob' => $grantor_dob1, 'occupation' => $grantor_occupation1, 'present_address' => $grantor_presentAddress1, 'parmanent_address' => $grantor_permanentAddress1, 'mobile_no' => $grantor_mobileNo1, 'monthly_income' => $grantor_monthlyIncome1, 'monthly_expenses' => $grantor_monthlyExpense1, 'guarantor_photo' => $grantor_photo1, 'guarantor_nid_front' => $grantor_nidFront1, 'guarantor_nid_back' => $grantor_nidBack1, 'name2' => $grantor_name2, 'father_husband_name2' => $grantor_fatherOrHusbandName2, 'nid_no2' => $grantor_nidNo2, 'dob2' => $grantor_dob2, 'occupation2' => $grantor_occupation2, 'present_address2' => $grantor_presentAddress2, 'parmanent_address2' => $grantor_permanentAddress2, 'mobile_no2' => $grantor_mobileNo2, 'monthly_income2' => $grantor_monthlyIncome2, 'monthly_expenses2' => $grantor_monthlyExpense2, 'guarantor_photo2' => $grantor_photo2, 'guarantor_nid_front2' => $grantor_nidFront2, 'guarantor_nid_back2' => $grantor_nidBack2, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'grantor_idtype1' => $grantor_idType1, 'grantor_idtypeid1' => $grantor_idTypeId1, 'grantor_idtype2' => $grantor_idType2, 'grantor_idtypeid2' => $grantor_idTypeId2, 'loan_productcode' => $loan_roductCode, 'grantor_idissuedate1' => $grantor_idIssueDate1, 'grantor_idexpiredate1' => $grantor_idExpireDate1, 'grantor_idissuecountry1' => $grantor_idIssueCountry1, 'grantor_idissuedate2' => $grantor_idIssueDate2, 'grantor_idexpiredate2' => $grantor_idExpireDate2, 'grantor_idissuecountry2' => $grantor_idIssueCountry2]);
    } else {
      $id = $check[0]->id;
      $guarantor1 = DB::Table($db . '.guarantor_details1')->where('id', $id)->update(['loan_id' => $loanid, 'name' => $grantor_name1, 'father_husband_name' => $grantor_fatherOrHusbandName1, 'nid_no' => $grantor_nidNo1, 'dob' => $grantor_dob1, 'occupation' => $grantor_occupation1, 'present_address' => $grantor_presentAddress1, 'parmanent_address' => $grantor_permanentAddress1, 'mobile_no' => $grantor_mobileNo1, 'monthly_income' => $grantor_monthlyIncome1, 'monthly_expenses' => $grantor_monthlyExpense1, 'guarantor_photo' => $grantor_photo1, 'guarantor_nid_front' => $grantor_nidFront1, 'guarantor_nid_back' => $grantor_nidBack1, 'name2' => $grantor_name2, 'father_husband_name2' => $grantor_fatherOrHusbandName2, 'nid_no2' => $grantor_nidNo2, 'dob2' => $grantor_dob2, 'occupation2' => $grantor_occupation2, 'present_address2' => $grantor_presentAddress2, 'parmanent_address2' => $grantor_permanentAddress2, 'mobile_no2' => $grantor_mobileNo2, 'monthly_income2' => $grantor_monthlyIncome2, 'monthly_expenses2' => $grantor_monthlyExpense2, 'guarantor_photo2' => $grantor_photo2, 'guarantor_nid_front2' => $grantor_nidFront2, 'guarantor_nid_back2' => $grantor_nidBack2, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'grantor_idtype1' => $grantor_idType1, 'grantor_idtypeid1' => $grantor_idTypeId1, 'grantor_idtype2' => $grantor_idType2, 'grantor_idtypeid2' => $grantor_idTypeId2, 'loan_productcode' => $loan_roductCode, 'grantor_idissuedate1' => $grantor_idIssueDate1, 'grantor_idexpiredate1' => $grantor_idExpireDate1, 'grantor_idissuecountry1' => $grantor_idIssueCountry1, 'grantor_idissuedate2' => $grantor_idIssueDate2, 'grantor_idexpiredate2' => $grantor_idExpireDate2, 'grantor_idissuecountry2' => $grantor_idIssueCountry2]);
    }
  }
  public function BankInformation($db, $loanid, $bi_bankName, $bi_branch, $bi_acName, $bi_acType, $bi_acNumber, $bi_routingNum, $bi_chequePhotoFront, $bi_chequePhotoBack, $updatedate, $branchcode, $assignedpo)
  {
    $check = DB::Table($db . '.bank_info')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $bank_info = DB::Table($db . '.bank_info')->insert(['loan_id' => $loanid, 'bank_name' => $bi_bankName, 'branch' => $bi_branch, 'account_name' => $bi_acName, 'account_type' => $bi_acType, 'account_no' => $bi_acNumber, 'routing_no' => $bi_routingNum, 'bank_cheque_photo' => $bi_chequePhotoFront, 'bank_cheque_photo_back' => $bi_chequePhotoBack, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    } else {
      $id = $check[0]->id;
      $bank_info = DB::Table($db . '.bank_info')->where('id', $id)->update(['loan_id' => $loanid, 'bank_name' => $bi_bankName, 'branch' => $bi_branch, 'account_name' => $bi_acName, 'account_type' => $bi_acType, 'account_no' => $bi_acNumber, 'routing_no' => $bi_routingNum, 'bank_cheque_photo' => $bi_chequePhotoFront, 'bank_cheque_photo_back' => $bi_chequePhotoBack, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    }
  }
  public function ResidanceInformation($db, $loanid, $residence_name, $residence_fatherOrHusbandName, $residence_idNo, $residence_countryName, $residence_workDuration, $residence_visaExpDate, $residence_loneeRelation, $residence_landPhoto1, $residence_landPhoto2, $residence_landPhoto3, $residence_landPhoto4, $residence_landValidation, $updatedate, $branchcode, $assignedpo)
  {

    $check = DB::Table($db . '.information_abroad_resident')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $informationResident = DB::Table($db . '.information_abroad_resident')->insert(['loan_id' => $loanid, 'name' => $residence_name, 'father_husband_name' => $residence_fatherOrHusbandName, 'nid_birth_certificate_no' => $residence_idNo, 'country_name' => $residence_countryName, 'duration_of_working_foregin' => $residence_workDuration, 'visa_expire_duration' => $residence_visaExpDate, 'relationship_with_borrower' => $residence_loneeRelation, 'land_photo1' => $residence_landPhoto1, 'land_photo2' => $residence_landPhoto2, 'land_photo3' => $residence_landPhoto3, 'land_photo4' => $residence_landPhoto4, 'land_validation' => $residence_landValidation, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    } else {
      $id = $check[0]->id;
      //echo $id;
      $informationResident = DB::Table($db . '.information_abroad_resident')->where('id', $id)->update(['loan_id' => $loanid, 'name' => $residence_name, 'father_husband_name' => $residence_fatherOrHusbandName, 'nid_birth_certificate_no' => $residence_idNo, 'country_name' => $residence_countryName, 'duration_of_working_foregin' => $residence_workDuration, 'visa_expire_duration' => $residence_visaExpDate, 'relationship_with_borrower' => $residence_loneeRelation, 'land_photo1' => $residence_landPhoto1, 'land_photo2' => $residence_landPhoto2, 'land_photo3' => $residence_landPhoto3, 'land_photo4' => $residence_landPhoto4, 'land_validation' => $residence_landValidation, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    }
  }
  public function RemittanceInformation($db, $loanid, $remittance1Date, $remittance1Amt, $remittance1BankName, $remittance1BranchName, $remittance1Pic, $remittance2Date, $remittance2Amt, $remittance2BankName, $remittance2BranchName, $remittance2Pic, $remittance3Date, $remittance3Amt, $remittance3BankName, $remittance3BranchName, $remittance3Pic, $updatedate, $branchcode, $assignedpo)
  {
    $check = DB::Table($db . '.information_remittances')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $Remittance = DB::Table($db . '.information_remittances')->insert(['loan_id' => $loanid, 'remittance1_date' => $remittance1Date, 'remittance1_amount' => $remittance1Amt, 'remittance1_bank_name' => $remittance1BankName, 'remittance1_branch' => $remittance1BranchName, 'remittance1_photo' => $remittance1Pic, 'remittance2_date' => $remittance2Date, 'remittance2_amount' => $remittance2Amt, 'remittance2_bank_name' => $remittance2BankName, 'remittance2_branch' => $remittance2BranchName, 'remittance2_photo' => $remittance2Pic, 'remittance3_date' => $remittance3Date, 'remittance3_amount' => $remittance3Amt, 'remittance3_bank_name' => $remittance3BankName, 'remittance3_branch' => $remittance3BranchName, 'remittance3_photo' => $remittance3Pic, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    } else {
      $id = $check[0]->id;
      $Remittance = DB::Table($db . '.information_remittances')->where('id', $id)->update(['loan_id' => $loanid, 'remittance1_date' => $remittance1Date, 'remittance1_amount' => $remittance1Amt, 'remittance1_bank_name' => $remittance1BankName, 'remittance1_branch' => $remittance1BranchName, 'remittance1_photo' => $remittance1Pic, 'remittance2_date' => $remittance2Date, 'remittance2_amount' => $remittance2Amt, 'remittance2_bank_name' => $remittance2BankName, 'remittance2_branch' => $remittance2BranchName, 'remittance2_photo' => $remittance2Pic, 'remittance3_date' => $remittance3Date, 'remittance3_amount' => $remittance3Amt, 'remittance3_bank_name' => $remittance3BankName, 'remittance3_branch' => $remittance3BranchName, 'remittance3_photo' => $remittance3Pic, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    }
  }
  public function BusinessInfo($db, $loanid, $businessName, $businessAddress, $fundingPeriod, $ownershipType, $businessType, $tradeLicense, $licenseExpiredDate, $employeeNo, $updatedate, $branchcode, $assignedpo)
  {
    $check = DB::Table($db . '.business_info')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $business_info = DB::table($db . '.business_info')->insert(['loan_id' => $loanid, 'bussiness_name' => $businessName, 'bussiness_address' => $businessAddress, 'founding_period' => $fundingPeriod, 'business_own_type' => $ownershipType, 'business_type' => $businessType, 'trade_licenseno' => $tradeLicense, 'trade_license_expdate' => $licenseExpiredDate, 'employees_no' => $employeeNo, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    } else {
      $id = $check[0]->id;
      $business_info = DB::table($db . '.business_info')->where('id', $id)->update(['loan_id' => $loanid, 'bussiness_name' => $businessName, 'bussiness_address' => $businessAddress, 'founding_period' => $fundingPeriod, 'business_own_type' => $ownershipType, 'business_type' => $businessType, 'trade_licenseno' => $tradeLicense, 'trade_license_expdate' => $licenseExpiredDate, 'employees_no' => $employeeNo, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    }
  }
  public function PersonalAssetInformation($db, $loanid, $totalAssetValue, $updatedate, $branchcode, $assignedpo)
  {
    $check = DB::Table($db . '.personal_asset_info')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $personal_asset_info = DB::Table($db . '.personal_asset_info')->insert(['loan_id' => $loanid, 'total_current_price' => $totalAssetValue, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    } else {
      $id = $check[0]->id;
      $personal_asset_info = DB::Table($db . '.personal_asset_info')->where('id', $id)->update(['loan_id' => $loanid, 'total_current_price' => $totalAssetValue, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    }
  }
  public function OtherLoanInformation($db, $loanid, $organizationNameBo, $organizationNameCo, $loanTakingDateBo, $loanTakingDateCo, $loanAmountBo, $loanAmountCo, $validityBo, $validityCo, $dateOfPaymentBo, $dateOfPaymentCo, $dueAmountBo, $dueAmountCo, $branchcode, $assignedpo, $updatedate, $hasOtherLoanBo, $hasOtherLoanCo)
  {
    $check = DB::Table($db . '.other_loan_information')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $Otherloan = DB::Table($db . '.other_loan_information')->insert(['branchcode' => $branchcode, "assignedpo" => $assignedpo, "loan_id" => $loanid, "organizationname_bo" => $organizationNameBo, "organizationname_co" => $organizationNameCo, "loantakingdate_bo" => $loanTakingDateBo, "loantakingdate_co" => $loanTakingDateCo, "loanamount_bo" => $loanAmountBo, "loanamount_co" => $loanAmountCo, "validity_bo" => $validityBo, "validity_co" => $validityCo, "dateofpayment_bo" => $dateOfPaymentBo, "dateofpayment_co" => $dateOfPaymentCo, "dueamount_bo" => $dueAmountBo, "dueamount_co" => $dueAmountCo, "updated_at" => $updatedate, 'has_other_loan_bo' => $hasOtherLoanBo, 'has_other_loan_co' => $hasOtherLoanCo]); //$hasOtherLoanBo, $hasOtherLoanCo
    } else {
      $id = $check[0]->id;
      $Otherloan = DB::Table($db . '.other_loan_information')->where('id', $id)->update(['branchcode' => $branchcode, "assignedpo" => $assignedpo, "loan_id" => $loanid, "organizationname_bo" => $organizationNameBo, "organizationname_co" => $organizationNameCo, "loantakingdate_bo" => $loanTakingDateBo, "loantakingdate_co" => $loanTakingDateCo, "loanamount_bo" => $loanAmountBo, "loanamount_co" => $loanAmountCo, "validity_bo" => $validityBo, "validity_co" => $validityCo, "dateofpayment_bo" => $dateOfPaymentBo, "dateofpayment_co" => $dateOfPaymentCo, "dueamount_bo" => $dueAmountBo, "dueamount_co" => $dueAmountCo, "updated_at" => $updatedate, 'has_other_loan_bo' => $hasOtherLoanBo, 'has_other_loan_co' => $hasOtherLoanCo]);
    }
  }
  public function CO_Borrower_Details($db, $loanid, $name, $dateOfBirth, $memberNo, $fatherOrHusband, $motherName, $gender, $maritalStatus, $education, $totalFamilyMember, $nidOrBirth, $presentAddress, $permanentAddress, $mobileNo, $tinNo, $branchcode, $assignedpo, $updatedate, $cbPersonalAsset, $genderId, $maritalStatusId, $educationId, $job_type, $id_type, $relationship, $org_name, $designation, $job_typeID, $relationshipID, $id_typeID, $issueDate, $CoexpireDate, $issueCountry)
  {
    $check = DB::Table($db . '.co_borrower_details')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $co_borrower_details = DB::Table($db . '.co_borrower_details')->insert([
        "loan_id" => $loanid, "name" => $name, "date_of_birth" => $dateOfBirth, "member_no" => $memberNo, "father_husband_name" => $fatherOrHusband, "mother_name" => $motherName, "gender" => $gender, "marital_status" => $maritalStatus,
        "education_qualification" => $education, "total_family_members" => $totalFamilyMember, "nid_birth_certificate" => $nidOrBirth,
        "present_address" => $presentAddress, "permanent_address" => $permanentAddress, "mobile_no" => $mobileNo, "tin_no" => $tinNo, "updated_at" => $updatedate, 'branchcode' => $branchcode, "assignedpo" => $assignedpo, 'co_personalasset' => $cbPersonalAsset, 'genderid' => $genderId, 'educationid' => $educationId, 'maritalstatusid' => $maritalStatusId, 'type_of_job' => $job_type, 'job_type_id' => $job_typeID, 'relationship' => $relationship, 'relationshipid' => $relationshipID, 'id_type' => $id_type, 'id_type_id' => $id_typeID, 'organization_name' => $org_name, 'designation' => $designation, 'issuedate' => $issueDate, 'expiredate' => $CoexpireDate, 'issuecountry' => $issueCountry
      ]);
    } else {
      $id = $check[0]->id;
      $co_borrower_details = DB::Table($db . '.co_borrower_details')->where('id', $id)->update([
        "loan_id" => $loanid, "name" => $name, "date_of_birth" => $dateOfBirth, "member_no" => $memberNo, "father_husband_name" => $fatherOrHusband, "mother_name" => $motherName, "gender" => $gender, "marital_status" => $maritalStatus,
        "education_qualification" => $education, "total_family_members" => $totalFamilyMember, "nid_birth_certificate" => $nidOrBirth,
        "present_address" => $presentAddress, "permanent_address" => $permanentAddress, "mobile_no" => $mobileNo, "tin_no" => $tinNo, "updated_at" => $updatedate, 'branchcode' => $branchcode, "assignedpo" => $assignedpo, 'co_personalasset' => $cbPersonalAsset, 'genderid' => $genderId, 'educationid' => $educationId, 'maritalstatusid' => $maritalStatusId, 'type_of_job' => $job_type, 'job_type_id' => $job_typeID, 'relationship' => $relationship, 'relationshipid' => $relationshipID, 'id_type' => $id_type, 'id_type_id' => $id_typeID, 'organization_name' => $org_name, 'designation' => $designation, 'issuedate' => $issueDate, 'expiredate' => $CoexpireDate, 'issuecountry' => $issueCountry
      ]);
    }
  }
  public function Borrower_PassPort_Visa_Details($db, $loanid, $issueDate, $expireDate, $occupation, $passportNo, $passportImage, $visaDetails, $visaNo, $visaIssueDate, $visaExpireDate, $destination, $lastDateOfEntry, $validity, $entryNo, $orgNameAddress, $jobExpireDate, $salary, $recruitingAgentName, $visaPhoto, $branchcode, $assignedpo, $updatedate, $passportOccupationId, $passportPermanentAddress)
  {
    $check = DB::Table($db . '.borrower_passport_visa_details')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $borrower_Passport_Visa = DB::Table($db . '.borrower_passport_visa_details')->insert([
        "loan_id" => $loanid, "passport_issue_date" => $issueDate,
        "passport_expire_date" => $expireDate, "passport_ocupation" => $occupation, "passport_no" => $passportNo, "passport_permanent_address" => $passportPermanentAddress, "passport_photo" => $passportImage, "visa_type_details" => $visaDetails,
        "visa_expire_date" => $visaExpireDate, "visa_issue_date" => $visaIssueDate, "visa_work_permit_no" => $visaNo,
        "visa_name_of_destination_country" => $destination, "last_date_of_entry_visa_work_permit_issue_country" => $lastDateOfEntry, "visa_validity" => $validity, "visa_entry_no" => $entryNo, "visa_work_permit_given_organization_name_address" => $orgNameAddress, "visa_job_expired_date" => $jobExpireDate, "visa_salary" => $salary, "visa_recuiting_agent_name_address" => $recruitingAgentName, "visa_photo" => $visaPhoto, "updated_at" => $updatedate, 'branchcode' => $branchcode, "assignedpo" => $assignedpo, 'passport_ocupationid' => $passportOccupationId
      ]);
    } else {
      $id = $check[0]->id;
      $borrower_Passport_Visa = DB::Table($db . '.borrower_passport_visa_details')->where('id', $id)->update([
        "loan_id" => $loanid, "passport_issue_date" => $issueDate,
        "passport_expire_date" => $expireDate, "passport_ocupation" => $occupation, "passport_no" => $passportNo, "passport_permanent_address" => $passportPermanentAddress, "passport_photo" => $passportImage, "visa_type_details" => $visaDetails,
        "visa_expire_date" => $visaExpireDate, "visa_issue_date" => $visaIssueDate, "visa_work_permit_no" => $visaNo,
        "visa_name_of_destination_country" => $destination, "last_date_of_entry_visa_work_permit_issue_country" => $lastDateOfEntry, "visa_validity" => $validity, "visa_entry_no" => $entryNo, "visa_work_permit_given_organization_name_address" => $orgNameAddress, "visa_job_expired_date" => $jobExpireDate, "visa_salary" => $salary, "visa_recuiting_agent_name_address" => $recruitingAgentName, "visa_photo" => $visaPhoto, "updated_at" => $updatedate, 'branchcode' => $branchcode, "assignedpo" => $assignedpo, 'passport_ocupationid' => $passportOccupationId
      ]);
    }
  }
  public function IncomeInformation($db, $loanid, $businessIncome, $jobIncome, $familyBusinessIncome, $otherBusinessIncome, $remittanceIncome, $othersIncome, $totalIncome, $updatedate, $branchcode, $assignedpo, $monthlyIncome, $monthlyIncomeCo, $incomeType)
  {
    $check = DB::Table($db . '.income_info')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $income_info = DB::Table($db . '.income_info')->insert(['loan_id' => $loanid, 'business_income' => $businessIncome, 'job_income' => $jobIncome, 'family_member_income' => $familyBusinessIncome, 'others_business_income' => $otherBusinessIncome, 'remittance_income' => $remittanceIncome, 'others_income' => $othersIncome, 'monthly_income' => $monthlyIncome, 'co_borrower_monthly_income' => $monthlyIncomeCo, 'total_income' => $totalIncome, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'income_type' => $incomeType]);
    } else {
      $id = $check[0]->id;
      $income_info = DB::Table($db . '.income_info')->where('id', $id)->update(['loan_id' => $loanid, 'business_income' => $businessIncome, 'job_income' => $jobIncome, 'family_member_income' => $familyBusinessIncome, 'others_business_income' => $otherBusinessIncome, 'remittance_income' => $remittanceIncome, 'others_income' => $othersIncome, 'total_income' => $totalIncome, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'income_type' => $incomeType]);
    }
  }
  public function ExpenseInformation($db, $loanid, $houseRent, $utilityBill, $healthEducation, $dailyExpense, $businessExpense, $loanInstallmentExpense, $totalExpense, $updatedate, $branchcode, $assignedpo)
  {
    $check = DB::Table($db . '.expenses_info')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $expense_info = DB::Table($db . '.expenses_info')->insert(['loan_id' => $loanid, 'house_rent' => $houseRent, 'utility_bil' => $utilityBill, 'health_education_expns' => $healthEducation, 'others_daily_expns' => $dailyExpense, 'business_expns' => $businessExpense, 'bank_loan_instlmnt_savings' => $loanInstallmentExpense, 'total_expns' => $totalExpense, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    } else {
      $id = $check[0]->id;
      $expense_info = DB::Table($db . '.expenses_info')->where('id', $id)->update(['loan_id' => $loanid, 'house_rent' => $houseRent, 'utility_bil' => $utilityBill, 'health_education_expns' => $healthEducation, 'others_daily_expns' => $dailyExpense, 'business_expns' => $businessExpense, 'bank_loan_instlmnt_savings' => $loanInstallmentExpense, 'total_expns' => $totalExpense, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    }
  }
  public function Borrower_Office_Info($db, $loanid, $orgName, $oiAddress, $oiDateOfJoining, $oiDesignation, $oiJobType, $oiPaySlip, $branchcode, $assignedpo, $updatedate)
  {
    $check = DB::Table($db . '.borrower_office_info')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $borrower_office_info = DB::Table($db . '.borrower_office_info')->insert(['loan_id' => $loanid, 'organization_name' => $orgName, 'organization_address' => $oiAddress, 'date_of_joining' => $oiDateOfJoining, 'present_designation' => $oiDesignation, 'type_of_job' => $oiJobType, 'pay_slip' => $oiPaySlip, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    } else {
      $id = $check[0]->id;
      $borrower_office_info = DB::Table($db . '.borrower_office_info')->where('id', $id)->update(['loan_id' => $loanid, 'organization_name' => $orgName, 'organization_address' => $oiAddress, 'date_of_joining' => $oiDateOfJoining, 'present_designation' => $oiDesignation, 'type_of_job' => $oiJobType, 'pay_slip' => $oiPaySlip, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo]);
    }
  }
  public function Common_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $commitmentLetter, $collateralBond, $investigateReport, $bankStatement, $securityCheck, $originalDeed, $bayaDeed, $duplicateDoc, $dcr, $dismissalForm, $saOriginalPaper, $rsOriginalPaper, $taxReceipt, $heirCertificate, $agreement, $other, $remarks, $pitt_deed, $position_deed)
  {
    $check = DB::Table($db . '.common_checklist')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $common_checklist = DB::Table($db . '.common_checklist')->insert([
        'loan_id' => $loanid, 'commitment_letter' => $commitmentLetter, 'collateral_bond' => $collateralBond, 'investigate_report' => $investigateReport, 'bank_statement' => $bankStatement, 'security_check' => $securityCheck, 'original_deed' => $originalDeed, 'baya_deed' => $bayaDeed, 'pitt_deed' => $pitt_deed,
        'duplicate_document' => $duplicateDoc, 'position_deed' => $position_deed, 'dcr' => $dcr, 'dismissal_form' => $dismissalForm, 'sa_orginal_paper' => $saOriginalPaper, 'rs_orginal_paper' => $rsOriginalPaper, 'tex_receipt' => $taxReceipt, 'heir_certificate' => $heirCertificate, 'shop_rent' => $agreement, 'other' => $other, 'remarks' => $remarks, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo
      ]);
    } else {
      $id = $check[0]->id;
      $common_checklist = DB::Table($db . '.common_checklist')->where('id', $id)->update([
        'loan_id' => $loanid, 'commitment_letter' => $commitmentLetter, 'collateral_bond' => $collateralBond, 'investigate_report' => $investigateReport, 'bank_statement' => $bankStatement, 'security_check' => $securityCheck, 'original_deed' => $originalDeed, 'baya_deed' => $bayaDeed, 'pitt_deed' => $pitt_deed,
        'duplicate_document' => $duplicateDoc, 'position_deed' => $position_deed, 'dcr' => $dcr, 'dismissal_form' => $dismissalForm, 'sa_orginal_paper' => $saOriginalPaper, 'rs_orginal_paper' => $rsOriginalPaper, 'tex_receipt' => $taxReceipt, 'heir_certificate' => $heirCertificate, 'shop_rent' => $agreement, 'other' => $other, 'remarks' => $remarks, 'updated_at' => $updatedate, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo
      ]);
    }
  }
  public function Nirvorota_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $hasGuarantorLiabilityDiscussed, $isPayslipSalaryVerified, $hasApplicantWorkingForOneYear, $isSameFamilyHaveOtherLoanPending, $haveNecessaryCheckSheetCollected, $isApplicantGuarantorOfNirvorotaLoan, $haveTwoReferencePersonContacted, $isAmountAndTypeOfLoanFair)
  {
    $check = DB::Table($db . '.checklist_nirvorota')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $nirvorota_checklist = DB::Table($db . '.checklist_nirvorota')->insert(['loan_id' => $loanid, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'guarantor_met_and_discussed_about_liability' => $hasGuarantorLiabilityDiscussed, 'employment_certificates_payslips_other_verified' => $isPayslipSalaryVerified, 'loan_related_everyone_nid_and_photo_verified' => false, 'applicant_working_current_organization_1year' => $hasApplicantWorkingForOneYear, 'loan_pending_same_house_family_of_applicant' => $isSameFamilyHaveOtherLoanPending, 'necessary_check_sheets_collected' => $haveNecessaryCheckSheetCollected, 'loan_applicant_guarantor_of_nirvorota_checklist_loan' => $isApplicantGuarantorOfNirvorotaLoan, 'two_reference_persons_contacted' => $haveTwoReferencePersonContacted, 'amount_nature_and_type_of_application_fair' => $isAmountAndTypeOfLoanFair, 'updated_at' => $updatedate]);
    } else {
      $id = $check[0]->id;
      $nirvorota_checklist = DB::Table($db . '.checklist_nirvorota')->insert(['loan_id' => $loanid, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'guarantor_met_and_discussed_about_liability' => $hasGuarantorLiabilityDiscussed, 'employment_certificates_payslips_other_verified' => $isPayslipSalaryVerified, 'loan_related_everyone_nid_and_photo_verified' => false, 'applicant_working_current_organization_1year' => $hasApplicantWorkingForOneYear, 'loan_pending_same_house_family_of_applicant' => $isSameFamilyHaveOtherLoanPending, 'necessary_check_sheets_collected' => $haveNecessaryCheckSheetCollected, 'loan_applicant_guarantor_of_nirvorota_checklist_loan' => $isApplicantGuarantorOfNirvorotaLoan, 'two_reference_persons_contacted' => $haveTwoReferencePersonContacted, 'amount_nature_and_type_of_application_fair' => $isAmountAndTypeOfLoanFair, 'updated_at' => $updatedate]);
    }
  }

  public function Trade_Agri_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $isKnownLoan, $isKnownCondition, $isSectorCorrect, $isProfitableLocation, $isGuarantorInformed, $isLoanSectorProfitable)
  {
    $check = DB::Table($db . '.checklist_remitance')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $trade_checklist = DB::Table($db . '.checklist_trade_agri')->insert(['loan_id' => $loanid, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'family_member_know_about_loan' => $isKnownLoan, 'borrower_know_about_loan_condition' => $isKnownCondition, 'loan_money_correct_use' => $isSectorCorrect, 'project_profitable_location' => $isProfitableLocation, 'guarantors_informed_obligation' => $isGuarantorInformed, 'profitable_loan_sector' => $isLoanSectorProfitable, 'updated_at' => $updatedate]);
    } else {
      $id = $check[0]->id;
      $trade_checklist = DB::Table($db . '.checklist_trade_agri')->where('id', $id)->update(['loan_id' => $loanid, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'family_member_know_about_loan' => $isKnownLoan, 'borrower_know_about_loan_condition' => $isKnownCondition, 'loan_money_correct_use' => $isSectorCorrect, 'project_profitable_location' => $isProfitableLocation, 'guarantors_informed_obligation' => $isGuarantorInformed, 'profitable_loan_sector' => $isLoanSectorProfitable, 'updated_at' => $updatedate]);
    }
  }
  public function Remittace_CheckList($db, $loanid, $branchcode, $assignedpo, $updatedate, $isFamilyMemKnow, $isLoanerKnow, $talkForeignResident, $tellResponsibility, $ableToPay, $isPermanent)
  {
    $check = DB::Table($db . '.checklist_trade_agri')->where('loan_id', $loanid)->get();
    if ($check->isEmpty()) {
      $trade_checklist = DB::Table($db . '.checklist_remitance')->insert(['loan_id' => $loanid, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'family_member_know_about_loan' => $isFamilyMemKnow, 'loaner_know_about_loan' => $isLoanerKnow, 'foriegn_resident_about_loan' => $talkForeignResident, 'grantor_about_responsibility' => $tellResponsibility, 'loan_repayment_amount' => $ableToPay, 'loaner_and_abroad_resident_permanent' => $isPermanent, 'updated_at' => $updatedate]);
    } else {
      $id = $check[0]->id;
      $trade_checklist = DB::Table($db . '.checklist_remitance')->where('id', $id)->update(['loan_id' => $loanid, 'branchcode' => $branchcode, 'assignedpo' => $assignedpo, 'family_member_know_about_loan' => $isFamilyMemKnow, 'loaner_know_about_loan' => $isLoanerKnow, 'foriegn_resident_about_loan' => $talkForeignResident, 'grantor_about_responsibility' => $tellResponsibility, 'loan_repayment_amount' => $ableToPay, 'loaner_and_abroad_resident_permanent' => $isPermanent, 'updated_at' => $updatedate]);
    }
  }
  public function RcaDataStore($db, $doc_id, $monthly_instal, $debt, $monthly_cash, $instal_proposloan, $dynamicfieldvalueRca)
  {
    $check = DB::Table($db . '.rca')->where('loan_id', $doc_id)->get();
    if ($check->isEmpty()) {
      DB::Table($db . '.rca')->insert([
        'loan_id' => $doc_id, 'primary_earner' => 1, 'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan,
        'DynamicFieldValue' => $dynamicfieldvalueRca
      ]);
    } else {
      $id = $check[0]->id;
      DB::Table($db . '.rca')->where('id', $id)->update([
        'loan_id' => $doc_id, 'primary_earner' => 1, 'monthly_instal' => $monthly_instal, 'debt' => $debt, 'monthly_cash' => $monthly_cash, 'instal_proposloan' => $instal_proposloan,
        'DynamicFieldValue' => $dynamicfieldvalueRca
      ]);
    }
  }
  public function AdmissionERPBufferCheck()
  {
    $db = $this->db;
    $currentdate = date('Y-m-d');
    $applicationdate = date('Y-m-d', strtotime('-7 days', strtotime($currentdate)));
    $admissionBufferPendings = DB::Table($db . '.admissions')->select('branchcode', 'projectcode', DB::raw("cast(created_at as date) as tt"))->where(DB::raw("cast(created_at as date)"), '>=', $applicationdate)->where("ErpStatus", 1)->groupBy('branchcode', 'projectcode', 'tt')->get();
    if (!empty($admissionBufferPendings)) {
      $branchcode = '0000';
      $projectcode = '000';
      $pin = '00000000';
      $type = 'BufferStatus';
      $message = 'Admission Buffer Status Start';
      $this->LogCreate($branchcode, $projectcode, $pin, $type, $message);
      foreach ($admissionBufferPendings as $row) {
        $branchcode = $row->branchcode;
        $projectcode = $row->projectcode;
        $applicationdates = $row->tt;
        $this->GetDataFromERPForAdmission($db, $branchcode, $projectcode, $applicationdates);
      }
      $branchcode = '0000';
      $projectcode = '000';
      $pin = '00000000';
      $type = 'BufferStatus';
      $message = 'Admission Buffer Status End';
      $this->LogCreate($branchcode, $projectcode, $pin, $type, $message);
    }
  }
  public function ERPBufferCheck()
  {
    $db = $this->db;
    $currentdate = date('Y-m-d');
    $applicationdate = date('Y-m-d', strtotime('-10 days', strtotime($currentdate)));
    $admissionBufferPendings = DB::Table($db . '.admissions')->select('branchcode', 'projectcode', DB::raw("cast(created_at as date) as tt"))->where(DB::raw("cast(created_at as date)"), '>=', $applicationdate)->where("ErpStatus", 1)->groupBy('branchcode', 'projectcode', 'tt')->get();
    $loanBufferPendings = DB::Table($db . '.loans')->select('branchcode', 'projectcode', DB::raw("cast(time as date) as tt"))->where(DB::raw("cast(time as date)"), '>=', $applicationdate)->where("ErpStatus", 1)->orWhere("ErpStatus", 2)->groupBy('branchcode', 'projectcode', 'tt')->get();
    if (!empty($loanBufferPendings)) {
      $branchcode = '0000';
      $projectcode = '000';
      $pin = '00000000';
      $type = 'BufferStatus';
      $message = 'Loan Buffer Status Start';
      $this->LogCreate($branchcode, $projectcode, $pin, $type, $message);
      foreach ($loanBufferPendings as $row) {
        $branchcode = $row->branchcode;
        //dd($branchcode);
        $projectcode = $row->projectcode;
        $applicationdates = $row->tt;
        //$message = "Loan IN" . $branchcode;
        // $this->LogCreate($branchcode, $projectcode, $pin, $type, $message);
        // $this->LogCreate('0000', '000', $pin, $type, $message);
        // dd("Huda");
        $this->GetDataFromERPForLoan($db, $branchcode, $projectcode, $applicationdates);
        // $message = 'Loan End' . $branchcode;
        // $this->LogCreate('0000', '000', $pin, $type, $message);
      }
      $branchcode = '0000';
      $projectcode = '000';
      $pin = '00000000';
      $type = 'BufferStatus';
      $message = 'Loan Buffer Status End';
      $this->LogCreate($branchcode, $projectcode, $pin, $type, $message);
    }
    if (!empty($admissionBufferPendings)) {
      $branchcode = '0000';
      $projectcode = '000';
      $pin = '00000000';
      $type = 'BufferStatus';
      $message = 'Admission Buffer Status Start';
      $this->LogCreate($branchcode, $projectcode, $pin, $type, $message);
      foreach ($admissionBufferPendings as $row) {
        $branchcode = $row->branchcode;
        $projectcode = $row->projectcode;
        $applicationdates = $row->tt;
        $this->GetDataFromERPForAdmission($db, $branchcode, $projectcode, $applicationdates);
      }
      $branchcode = '0000';
      $projectcode = '000';
      $pin = '00000000';
      $type = 'BufferStatus';
      $message = 'Admission Buffer Status End';
      $this->LogCreate($branchcode, $projectcode, $pin, $type, $message);
    }
  }
  public function GetDataFromERPForLoan($db, $branchcode, $projectcode, $applicationdates)
  {
    $this->LaravelLog();
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
    //echo $servertoken;
    if ($servertoken != '') {
      $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $servertoken
      );
    } else {
      $statuss = array(
        "status" => "CUSTMSG", "message" => "Token Not Found"
      );
      $json = json_encode($statuss);
      echo $json;
      die;
    }
    $curl = curl_init();
    $projectcode = (int)$projectcode;
    $urlset = $url2 . "branches/$branchcode/buffer-loan-proposals?projectCode=$projectcode&applicationDate=$applicationdates";
    //echo $urlset;
    curl_setopt_array($curl, array(
      CURLOPT_URL => $urlset,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    //print_r($response);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpcode != 200) {
      Log::info("Loan Buffer Status Check ERROR:- " . $response);
      die;
    }
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      //   return $response;
      json_decode($response);
      if (json_last_error() == 0) {
        return $this->checkPostedLoanStatusFromErp($db, $response);
      } else {
        return "Erp Server Down";
      }
    }
  }
  public function checkPostedLoanStatusFromErp($db, $response)
  {
    //dd($response);
    $this->LaravelLog();
    $currentDatetime = date("Y-m-d h:i:s");
    $arrayLoan = json_decode($response);
    foreach ($arrayLoan as $data) {
      // dd($data->secondInsurer);

      if ($data->secondInsurer != null) {
        $secondinsurerdateofbirth = $data->secondInsurer->dateOfBirth;
        $secondinsurerbackimageurl = $data->secondInsurer->idCard->idCardNo;
        $secondinsurercardtypeid = $data->secondInsurer->idCard->cardTypeId;
        $secondinsurerexpirydate = $data->secondInsurer->idCard->expiryDate;
        $secondinsurerfrontimageurl = $data->secondInsurer->idCard->frontImageUrl;
        $secondinsureridcardno = $data->secondInsurer->idCard->idCardNo;
        $secondinsurerissuedate = $data->secondInsurer->idCard->issueDate;
        $secondinsurerissueplace = $data->secondInsurer->idCard->issuePlace;
        $secondinsurername = $data->secondInsurer->name;

        if ('relationshipId' == $data->secondInsurer) {
          $secondinsurerrelationshipid = $data->secondInsurer->relationshipId;
        } else {
          $secondinsurerrelationshipid = null;
        }
        if ('genderId' == $data->secondInsurer) {
          $secondinsurergenderid = $data->secondInsurer->genderId;
        } else {
          $secondinsurergenderid = null;
        }
      } else {
        $secondinsurerdateofbirth = null;
        $secondinsurergenderid = null;
        $secondinsurerbackimageurl = null;
        $secondinsurercardtypeid = null;
        $secondinsurerexpirydate = null;
        $secondinsurerfrontimageurl = null;
        $secondinsureridcardno = null;
        $secondinsurerissuedate = null;
        $secondinsurerissueplace = null;
        $secondinsurername = null;
        $secondinsurerrelationshipid = null;
      }

      if ($data->nominees != null) {
        $nomineescontactNo = $data->nominees[0]->contactNo;
        $nomineesdateofbirth = $data->nominees[0]->dateOfBirth;
        $nomineesbackimageurl = $data->nominees[0]->idCard->idCardNo;
        $nomineescardtypeid = $data->nominees[0]->idCard->cardTypeId;
        $nomineesexpirydate = $data->nominees[0]->idCard->expiryDate;
        $nomineesfrontimageurl = $data->nominees[0]->idCard->frontImageUrl;
        $nomineesidcardno = $data->nominees[0]->idCard->idCardNo;
        $nomineesissuedate = $data->nominees[0]->idCard->issueDate;
        $nomineesissueplace = $data->nominees[0]->idCard->issuePlace;
        $nomineesname = $data->nominees[0]->name;

        if ('relationshipId' == $data->nominees) {
          $nomineesrelationshipid = $data->nominees->relationshipId;
        } else {
          $nomineesrelationshipid = null;
        }
      } else {
        $nomineescontactNo = null;
        $nomineesdateofbirth = null;
        $nomineesbackimageurl = null;
        $nomineescardtypeid = null;
        $nomineesexpirydate = null;
        $nomineesfrontimageurl = null;
        $nomineesidcardno = null;
        $nomineesissuedate = null;
        $nomineesissueplace = null;
        $nomineesname = null;
        $nomineesrelationshipid = null;
      }

      $values = array(
        "applicationdate" => $data->applicationDate,
        "approveddurationinmonths" => $data->approvedDurationInMonths,
        "approvedloanamount" => $data->approvedLoanAmount,
        "branchcode" => $data->branchCode,
        // coBorrowerDto
        // "coborrowerdtobackimageurl" => $data->coBorrowerDto->idCard->backImageUrl,
        // "coborrowerdtocardtypeid" => $data->coBorrowerDto->idCard->cardTypeId,
        // "coborrowerdtoexpirydate" => $data->coBorrowerDto->idCard->expiryDate,
        // "frontImageUrl" => $data->coBorrowerDto->idCard->backImageUrl,
        // "coborrowerdtoidcardno" => $data->coBorrowerDto->idCard->idCardNo,
        // "coborrowerdtoissuedate" => $data->coBorrowerDto->idCard->issueDate,
        // "coborrowerdtoissueplace" => $data->coBorrowerDto->idCard->issuePlace,            
        // "coborrowerdtoname" => $data->coBorrowerDto->name,
        // "coborrowerdtorelationshipid" => $data->coBorrowerDto->relationshipId,
        "consenturl" => $data->consentUrl,
        "disbursementdate" => $data->disbursementDate,
        // "flag" => $data->flag,
        "frequencyid" => $data->frequencyId,
        "loan_id" => $data->id,
        "insuranceproductid" => $data->insuranceProductId,
        "loanaccountid" => $data->loanAccountId,
        "loanapprover" => $data->loanApprover,
        "loanproductid" => $data->loanProductId,
        "loanproposalstatusid" => $data->loanProposalStatusId,
        "memberid" => $data->memberId,
        "membertypeid" => $data->memberTypeId,
        "microinsurance" => $data->microInsurance,
        "modeofpaymentid" => $data->modeOfPaymentId,
        // nominee
        "nomineescontactno" => $nomineescontactNo,
        "nomineesdateofbirth" => $nomineesdateofbirth,
        // "id" => $data->nominees[0]->id,
        "nomineesbackimageurl" => $nomineesbackimageurl,
        "nomineescardtypeid" => $nomineescardtypeid,
        "nomineesexpirydate" => $nomineesexpirydate,
        "nomineesfrontimageurl" => $nomineesfrontimageurl,
        "nomineesidcardno" => $nomineesidcardno,
        "nomineesissuedate" => $nomineesissuedate,
        "nomineesissueplace" => $nomineesissueplace,
        "nomineesname" => $nomineesname,
        "nomineesrelationshipid" => $nomineesrelationshipid,
        "policytypeid" => $data->policyTypeId,
        "premiumamount" => $data->premiumAmount,
        "projectcode" => $data->projectCode,
        "proposaldurationinmonths" => $data->proposalDurationInMonths,
        "proposedloanamount" => $data->proposedLoanAmount,
        "rejectionreason" => $data->rejectionReason,
        "schemeid" => $data->schemeId,
        "secondinsurerdateofbirth" => $secondinsurerdateofbirth,
        "secondinsurergenderid" => $secondinsurergenderid,
        "secondinsurerbackimageurl" => $secondinsurerbackimageurl,
        "secondinsurercardtypeid" => $secondinsurercardtypeid,
        "secondinsurerexpirydate" => $secondinsurerexpirydate,
        "secondinsurerfrontimageurl" => $secondinsurerfrontimageurl,
        "secondinsureridcardno" => $secondinsureridcardno,
        "secondinsurerissuedate" => $secondinsurerissuedate,
        "secondinsurerissueplace" => $secondinsurerissueplace,
        "secondinsurername" => $secondinsurername,
        "secondinsurerrelationshipid" => $secondinsurerrelationshipid,
        "sectorid" => $data->sectorId,
        "signconsent" => $data->signConsent,
        "subsectorid" => $data->subSectorId,
        "updated" => $data->updated,
        "vocode" => $data->voCode,
        "void" => $data->voId,
      );

      $checkPostedLoan = DB::table($db . '.posted_loan')->where('loan_id', $data->id)->first();
      $checkLoan = DB::table($db . '.loans')->where('loan_id', $data->id)->first();
      // dd($checkLoan);
      //Log::alert("Loan Id" . $data->id);
      if ($data->loanProposalStatusId != 1) {  //if erp loan disbursed or reject
        if ($checkLoan != null) {                //if addmission has data
          // $member = DB::table($db . '.posted_admission')->where('memberid', $data->memberId)->first();
          // $serverurl = DB::Table($dberp . '.server_url')->where('server_status', 3)->where('status', 1)->first();
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
          $key = '5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae';
          $UpdatedAt = "2000-01-01 00:00:00";
          $member = Http::get($url . 'MemberList', [
            'BranchCode' => $checkLoan->branchcode,
            'CONo' => $checkLoan->assignedpo,
            'ProjectCode' => $checkLoan->projectcode,
            'UpdatedAt' => $UpdatedAt,
            'Status' => 1,
            'OrgNo' => $checkLoan->orgno,
            'OrgMemNo' => $checkLoan->orgmemno,
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
          // dd($member);
          /*if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }*/
          /*if ($checkLoan->erp_loan_id == null and $checkLoan->ErpStatus == 1) {    //if erp member id empty in dcs admission table
            if ($member != null) {
              $this->sendAppNotificationForErpLoanAction($data, $member);
            }
          }*/
        }
      }
      $updatedAt = date('Y-m-d H:i:s');
      if ($checkPostedLoan == null) {
        DB::table($db . '.posted_loan')->insert($values);
        if ($checkLoan->ErpStatus == 1 and $data->loanProposalStatusId != 1) {
          DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        } else if ($checkLoan->ErpStatus == '2' and $data->loanProposalStatusId != '2') {
          if ($data->loanProposalStatusId == '4') {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['erp_loan_id' => $data->loanAccountId, 'ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          } else {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          }
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        } else if ($checkLoan->ErpStatus == '3' and $data->loanProposalStatusId != '3') {
          DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        } else if ($checkLoan->ErpStatus == '4' and $data->loanProposalStatusId != '4') {
          DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        }
      } else {
        // if ($data->updated == TRUE) {
        if ($checkLoan == null or $checkLoan == '') {
          continue;
        }
        DB::table($db . '.posted_loan')->where('loan_id', $data->id)->update($values);
        if ($checkLoan->ErpStatus == '1' and $data->loanProposalStatusId != '1') {
          DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        } else if ($checkLoan->ErpStatus == '2' and $data->loanProposalStatusId != '2') {
          if ($data->loanProposalStatusId == 4) {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['erp_loan_id' => $data->loanAccountId, 'ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          } else {
            DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          }
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        } else if ($checkLoan->ErpStatus == 3 and $data->loanProposalStatusId != '3') {
          DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        } else if ($checkLoan->ErpStatus == '4' and $data->loanProposalStatusId != '4') {
          DB::table($db . '.loans')->where('loan_id', $data->id)->update(['ErpStatus' => $data->loanProposalStatusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->loanProposalStatusId]);
          if ($member != null) {
            $this->sendAppNotificationForErpLoanAction($data, $member);
          }
        }
      }
    }
    return "Data sync successful";
  }
  public function GetDataFromERPForAdmission($db, $branchcode, $projectcode, $applicationdates)
  {
    $this->LaravelLog();
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
    $projectcode = (int)$projectcode;
    $urlset = $url2 . "branches/$branchcode/buffer-members?projectCode=$projectcode&applicationDate=$applicationdates";
    // Log::info("DCS Buffer Url" . $urlset);
    curl_setopt_array($curl, array(
      CURLOPT_URL => $urlset, //$url2 . 'branches / ' . $branchcode . ' / buffer - members ? projectcode = ' . $projectcode . ' & applicationDate = ' . $applicationdate,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpcode != 200) {
      Log::info("Admission Buffer Status Check" . $response);
      die;
    }
    $err = curl_error($curl);
    curl_close($curl);
    Log::info("Admission Buffer Status Check" . $response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {

      // dd(json_decode($response));
      if (json_last_error() == 0) {
        return $this->checkPostedAdmissionStatusFromErp($db, $response);
      } else {
        return "Erp Server Down";
      }
    }
  }
  public function checkPostedAdmissionStatusFromErp($db, $response)
  {
    $this->LaravelLog();
    $currentDatetime = date("Y-m-d h:i:s");
    $arrayAddmission = json_decode($response);
    if (empty($arrayAddmission)) {
    }
    foreach ($arrayAddmission as $data) {
      $guarantordateofbirth = null;
      $guarantorbackimageurl = null;
      $guarantorcardtypeid = null;
      $guarantorissueplace = null;
      $guarantorexpirydate = null;
      $guarantorfrontimageurl = null;
      $guarantoridcardno = null;
      $guarantorissuedate = null;
      $guarantornameen = null;
      $guarantorrelationshipid = null;

      if ($data->nominees != null) {
        $nomineescontactNo = $data->nominees[0]->contactNo;
        $nomineesdateofbirth = $data->nominees[0]->dateOfBirth;
        $nomineesbackimageurl = $data->nominees[0]->idCard->idCardNo;
        $nomineescardtypeid = $data->nominees[0]->idCard->cardTypeId;
        $nomineesexpirydate = $data->nominees[0]->idCard->expiryDate;
        $nomineesfrontimageurl = $data->nominees[0]->idCard->frontImageUrl;
        $nomineesidcardno = $data->nominees[0]->idCard->idCardNo;
        $nomineesissuedate = $data->nominees[0]->idCard->issueDate;
        $nomineesissueplace = $data->nominees[0]->idCard->issuePlace;
        $nomineesname = $data->nominees[0]->name;

        if (array_key_exists('relationshipId', $data->nominees)) {
          $nomineesrelationshipid = $data->nominees->relationshipId;
        } else {
          $nomineesrelationshipid = null;
        }
      } else {
        $nomineescontactNo = null;
        $nomineesdateofbirth = null;
        $nomineesbackimageurl = null;
        $nomineescardtypeid = null;
        $nomineesexpirydate = null;
        $nomineesfrontimageurl = null;
        $nomineesidcardno = null;
        $nomineesissuedate = null;
        $nomineesissueplace = null;
        $nomineesname = null;
        $nomineesrelationshipid = null;
      }

      $values = array(
        'applicationdate' => $data->applicationDate,
        'assignedpopin' => $data->assignedPoPin,
        'bankaccountnumber' => $data->bankAccountNumber,
        'bankbranchid' => $data->bankBranchId,
        'bankid' => $data->bankId,
        'bkashwalletno' => $data->bkashWalletNo,
        'branchcode' => $data->branchCode,
        'contactno' => $data->contactNo,
        'dateofbirth' => $data->dateOfBirth,
        'educationid' => $data->educationId,
        'fathernameen' => $data->fatherNameEn,
        'flag' => $data->flag,
        'genderid' => $data->genderId,
        //guarantor
        "guarantordateofbirth" => $guarantordateofbirth,
        "guarantorbackimageurl" => $guarantorbackimageurl,
        "guarantorcardtypeid" => $guarantorcardtypeid,
        "guarantorissueplace" => $guarantorissueplace,
        "guarantorexpirydate" => $guarantorexpirydate,
        "guarantorfrontimageurl" => $guarantorfrontimageurl,
        "guarantoridcardno" => $guarantoridcardno,
        "guarantorissuedate" => $guarantorissuedate,
        "guarantornameen" => $guarantornameen,
        "guarantorrelationshipid" => $guarantorrelationshipid,
        'addmission_id' => $data->id,
        //idCard
        "idcardbackimageurl" => $data->idCard->backImageUrl,
        "idcardcardtypeid" => $data->idCard->cardTypeId,
        "idcardexpirydate" => $data->idCard->expiryDate,
        "idcardfrontimageurl" => $data->idCard->frontImageUrl,
        "idcardidcardno" => $data->idCard->idCardNo,
        "idcardissuedate" => $data->idCard->issueDate,
        "idcardissueplace" => $data->idCard->issuePlace,
        'maritalstatusid' => $data->maritalStatusId,
        'memberid' => $data->memberId,
        'memberimageurl' => $data->memberImageUrl,
        'membertypeid' => $data->memberTypeId,
        'mothernameen' => $data->motherNameEn,
        'nameen' => $data->nameEn,
        //nominees
        "nomineescontactno" => $nomineescontactNo,
        "nomineesdateofbirth" => $nomineesdateofbirth,
        // "id" => $data->nominees[0]->id,
        "nomineesbackimageurl" => $nomineesbackimageurl,
        "nomineescardtypeid" => $nomineescardtypeid,
        "nomineesexpirydate" => $nomineesexpirydate,
        "nomineesfrontimageurl" => $nomineesfrontimageurl,
        "nomineesidcardno" => $nomineesidcardno,
        "nomineesissuedate" => $nomineesissuedate,
        "nomineesissueplace" => $nomineesissueplace,
        "nomineesname" => $nomineesname,
        "nomineesrelationshipid" => $nomineesrelationshipid,
        'occupationid' => $data->occupationId,
        'passbooknumber' => $data->passbookNumber,
        'permanentaddress' => $data->permanentAddress,
        'permanentdistrictid' => $data->permanentDistrictId,
        'permanentupazilaid' => $data->permanentUpazilaId,
        'poid' => $data->poId,
        'presentaddress' => $data->presentAddress,
        'presentdistrictid' => $data->presentDistrictId,
        'presentupazilaid' => $data->presentUpazilaId,
        'projectcode' => $data->projectCode,
        'rejectionreason' => $data->rejectionReason,
        'routingnumber' => $data->routingNumber,
        'savingsproductid' => $data->savingsProductId,
        'spousedateofbirth' => $data->spouseDateOfBirth,
        // // spouseIdCard
        "spouseidcardbackimageurl" => $data->spouseIdCard->backImageUrl,
        "spouseidcardcardtypeid" => $data->spouseIdCard->cardTypeId,
        "spouseidcardexpirydate" => $data->spouseIdCard->expiryDate,
        "spouseidcardfrontimageurl" => $data->spouseIdCard->frontImageUrl,
        "spouseidcardidcardno" => $data->spouseIdCard->idCardNo,
        "spouseidcardissuedate" => $data->spouseIdCard->issueDate,
        "spouseidcardissueplace" => $data->spouseIdCard->issuePlace,
        'spousenameen' => $data->spouseNameEn,
        'statusid' => $data->statusId,
        'targetamount' => $data->targetAmount,
        'tinnumber' => $data->tinNumber,
        'updated' => $data->updated,
        'vocode' => $data->voCode,
        'void' => $data->voId,
        'admission_id' => $data->id,
      );

      $checkPostedAdmission = DB::table($db . '.posted_admission')->where('admission_id', $data->id)->first();
      $checkAdmission = DB::table($db . '.admissions')->where('entollmentid', $data->id)->first();
      $checkLoan = DB::table($db . '.loans')->where('mem_id', $data->id)->first();

      if ($data->statusId == 2 or $data->statusId == 3) {  //if erp approve and reject
        if ($checkAdmission != null) {                //if addmission has data
          if ($checkAdmission->MemberId == null and $checkAdmission->ErpStatus == 1) {    //if erp member id empty in dcs admission table
            $this->sendAppNotificationForErpAddmissionAction($data);
          }
        }
      }

      $updatedAt = date('Y-m-d H:i:s');
      if ($checkPostedAdmission == null) {
        DB::table($db . '.posted_admission')->insert($values);
        if ($data->statusId == 2) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['MemberId' => $data->memberId, 'ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->statusId, 'erp_member_id' => $data->memberId]);
          }
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('mem_id', $data->id)->update(['erp_mem_id' => $data->memberId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        } elseif ($data->statusId == 3) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->statusId]);
          }
        }
      } else {
        // if ($data->updated == TRUE) {
        DB::table($db . '.posted_admission')->where('admission_id', $data->id)->update($values);
        // }
        if ($data->statusId == 2) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['MemberId' => $data->memberId, 'ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->statusId, 'erp_member_id' => $data->memberId]);
          }
          if ($checkLoan != null) {
            DB::table($db . '.loans')->where('mem_id', $data->id)->update(['erp_mem_id' => $data->memberId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt]);
          }
        } elseif ($data->statusId == 3) {
          if ($checkAdmission != null) {
            DB::table($db . '.admissions')->where('entollmentid', $data->id)->update(['ErpStatus' => $data->statusId, 'updated_at' => $currentDatetime, 'update_at' => $updatedAt, 'erpstatus' => $data->statusId]);
          }
        }
      }
    }
    return "Data sync successful";
  }
  public function Approval_Amount_Check($db, $branchcode, $projectcode, $loan_type, $proposeAmount, $roleid)
  {
    if ($projectcode == '015') {
      if ($roleid == '1') {
        $designation = 'BM';
      } else if ($roleid == '2') {
        $designation = 'AM';
      } else if ($roleid == '3') {
        $designation = 'RM';
      } else if ($roleid == '4') {
        $designation = 'DM';
      }
    } else {
      if ($roleid == '1') {
        $designation = 'AM';
      } else if ($roleid == '2') {
        $designation = 'RM';
      } else if ($roleid == '3') {
        $designation = 'DM';
      }
    }

    $approveStatus = 0;
    if ($branchcode == '0605') {
      $groupcate = 'GC1';
    } else {
      $groupcate = 'GC1';
    }
    //dd($nextrole);
    // work with celling data
    $project = $projectcode;
    if ($project == '015') {
      $projectcd = '15';
    } else if ($project == '060') {
      $projectcd = '60';
    }
    $grouthGet = DB::Table($db . '.project_wise_branch_growth_types')->select('branch_growth_type')->where('project_code', $projectcd)->where('office_code', $branchcode)->get(); //"HIGH";
    $growth_rate = $grouthGet[0]->branch_growth_type;
    //dd($designation);
    if ($project == '060') {
      $cellingData = DB::table($db . '.celing_configs')
        ->select('limit_form', 'limit_to', 'repeat_limit_form', 'repeat_limit_to')
        ->where('approver', $designation)
        ->where('growth_rate', $growth_rate)->where('projectcode', $projectcd)->where('group', $groupcate)
        ->first();
    } else {
      //dd("Test");
      $cellingData = DB::table($db . '.celing_configs')
        ->select('limit_form', 'limit_to', 'repeat_limit_form', 'repeat_limit_to')
        ->where('approver', $designation)
        ->where('growth_rate', $growth_rate)->where('projectcode', $projectcd)
        ->first();
    }
    //dd($cellingData);
    Log::info("Celling" . json_encode($cellingData));
    if ($cellingData) {
      if ($loan_type == "New") {
        $limitFrom = $cellingData->limit_form;
        $limitTo = $cellingData->limit_to;
      } elseif ($loan_type == "Repeat") {
        $limitFrom = $cellingData->repeat_limit_form;
        $limitTo = $cellingData->repeat_limit_to;
      }
      //dd($limitFrom . "-" . $limitTo . "/" . $proposeAmount);
      if ($proposeAmount < $limitFrom or $proposeAmount > $limitTo) {
        // dd($proposeAmount, $limitFrom, $limitTo);
        $result = array("status" => "E", "message" => "Loan amount Limit exceed for your designation");
        echo json_encode($result);
        die;
        //return redirect()->back()->with('error', 'Loan amount Limit exceed for your designation');
      } else {
        $approveStatus = 1;
      }
    } else {
      $result = array("status" => "E", "message" => "Can't get celling data");
      echo json_encode($result);
      die;
      //return redirect()->back()->with('error', "Can't get celling data");
    }
    return $approveStatus;
  }
}
