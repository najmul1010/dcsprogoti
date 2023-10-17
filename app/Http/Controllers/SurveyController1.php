<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\Branch;
use DB;
use Log;
use Illuminate\Support\Facades\Http;

class SurveyController extends Controller
{
	public function index()
	{
		return view('Survey');
	}

	public function store(Request $request)
	{

		$data = new Survey();
		$data->entollmentid = $request->get('enrolmentid');
		$data->name = $request->get('name');
		$data->mainidtypeid = $request->get('mainidtype');
		$data->idno = $request->get('mainidnumber');
		$data->phone = $request->get('phonenumber');
		$data->status = $request->get('status');
		$data->label = $request->get('level');
		$data->targetdate = $request->get('follow-upDate');
		$data->refferdbyid = $request->get('referreredname');
		$data->save();
		return redirect()->back();
	}
	public function Survey(Request $req)
	{

		$db = config('database.db');
		$role_designation = session('role_designation');
		//dd(session('role_designation'));

		if (session('role_designation') == 'AM') {
			$value = Branch::where([
				'program_id' => session('program_id')
			])->get();
			$search2 = Branch::where([
				'area_id' => session('asid'),
				'program_id' => session('program_id')
			])->distinct('branch_id')->get();
			$branch = Branch::where([
				'area_id' => session('asid'),
				'program_id' => session('program_id')
			])->first();
			// $data1=Branch::select('branch_id','branch_name')
			//                 ->where([
			//                 'area_id' => session('asid'),
			//                 'program_id' => session('program_id')])->get();
		} else if (session('role_designation') == 'RM') {
			$value = Branch::where([
				'program_id' => session('program_id')
			])->get();
			$search2 = Branch::where([
				'region_id' => session('asid'),
				'program_id' => session('program_id')
			])->distinct('area_id')->get();

			$branch = Branch::where([
				'region_id' => session('asid'),
				'program_id' => session('program_id')
			])->first();
		} else if (session('role_designation') == 'DM') {
			$value = Branch::where([
				'program_id' => session('program_id')
			])->get();
			$search2 = Branch::where([
				'division_id' => session('asid'),
				'program_id' => session('program_id')
			])->distinct('region_id')->get();
			$branch = Branch::where([
				'division_id' => session('asid'),
				'program_id' => session('program_id')
			])->first();
		} else if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
			$value = Branch::where([
				'program_id' => session('program_id')
			])->get();
			$branch = Branch::where([
				'division_id' => session('asid'),
				'program_id' => session('program_id')
			])->first();
			$search2 = Branch::where([
				'program_id' => session('program_id')
			])->distinct('division_id')->get();
		} else if (session('role_designation') == 'BM') {
			$value = Branch::where([
				'branch_id' => session('asid'),
				'program_id' => session('program_id')
			])->get();
			$branch = Branch::where([
				'branch_id' => session('asid'),
				'program_id' => session('program_id')
			])->first();
			$search2 = Branch::where([
				'program_id' => session('program_id')
			])->distinct('division_id')->get();
		} else {
			return redirect()->back()->with('error', 'data does not match');
		}
		// $polist = DB::table($db.'.polist')
		//         ->where('projectcode',$projectcode)
		//         ->where('status','1')
		//         ->whereIn('branchcode', $all_branchcode)
		//         ->get();
		// $po=array();
		// foreach($polist as $cono)
		// {
		//     foreach($all_assignedpo as $key=> $value)
		//     {
		//         if($cono->cono == $value)
		//         {
		//             $po[] = $value;
		//         }
		//     }
		// }
		// if(session('roll')!=7)
		//     {
		// $datas = DB::table($db.'.loan')
		//     ->whereIn('assignedpo', $po)
		//     // ->where('status', 'Pending')
		//     ->where('reciverrole', session('roll'))
		//     ->get();
		// }
		$status = DB::table($db . '.status')->where('process', '*')->orderBy('status_id', 'asc')->get();
		if (session('projectcode') == '060') {
			return view('progoti/survey_request')->with('branch', $branch)->with('value', $value)->with('search2', $search2)->with('status', $status);
		} else {
			return view('loan-request')->with('branch', $branch)->with('value', $value)->with('search2', $search2)->with('status', $status);
		}
	}
	public function SurveyTable(Request $req)
	{
		//dd("Huda");
		$db = config('database.db');
		$db = config('database.db');
		if (session('role_designation') == 'AM') {
			$value = Branch::where([
				'area_id' => session('asid'),
				'program_id' => session('program_id')
			])->get();
		} else if (session('role_designation') == 'RM') {
			$value = Branch::where([
				'region_id' => session('asid'),
				'program_id' => session('program_id')
			])->get();
		} else if (session('role_designation') == 'DM') {
			$value = Branch::where([
				'division_id' => session('asid'),
				'program_id' => session('program_id')
			])->get();
		} else if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
			$value = Branch::where([
				'program_id' => session('program_id')
			])->get();
		} else if (session('role_designation') == 'BM') {
			$value = Branch::where([
				'branch_id' => session('asid'),
				'program_id' => session('program_id')
			])->get();
		} else {
			return redirect()->back()->with('error', 'data does not match');
		}
		$all_branchcode = array();
		$all_assignedpo = array();
		foreach ($value as $row) {
			$branchCode1 = $row->branch_id;
			$branchCode = str_pad($branchCode1, 4, "0", STR_PAD_LEFT);

			$value1 = DB::table($db . '.surveys')->select('branchcode', 'assignedpo')
				->where('branchcode', $branchCode)->groupBy('branchcode', 'assignedpo')->get();
			if (!$value1->isEmpty()) {
				foreach ($value1 as $assignedpo) {
					$all_branchcode[] = $assignedpo->branchcode;
					$all_assignedpo[] = str_pad($assignedpo->assignedpo, 8, "0", STR_PAD_LEFT);
				}
			}
		}

		$polist = DB::table($db . '.polist')
			->where('projectcode', session('projectcode'))
			->where('status', '1')
			->whereIn('branchcode', $all_branchcode)
			->get();
		$po = array();
		foreach ($polist as $cono) {
			foreach ($all_assignedpo as $key => $value) {
				if ($cono->cono == $value) {
					$po[] = $value;
				}
			}
		}
		// division search
		$division = $req->division;
		if ($division != null) {
			$d_branch = DB::table('public.branch')
				->where('program_id', session('program_id'))
				->where('division_id', $req->division)
				->distinct('branch_id')
				->get();
			foreach ($d_branch as $key => $value) {
				$division_search1 = str_pad($value->branch_id, 4, "0", STR_PAD_LEFT);
				$division_search[] = $division_search1;
			}
		}

		//find branch for region search
		$region = $req->region;
		if ($region != null) {
			$r_branch = DB::table('public.branch')
				->where('program_id', session('program_id'))
				->where('region_id', $region)
				->distinct('branch_id')
				->get();
			foreach ($r_branch as $key => $value) {
				$region_search1 = str_pad($value->branch_id, 4, "0", STR_PAD_LEFT);
				$region_search[] = $region_search1;
			}
		}
		//find branch for area search
		$area = $req->area;
		if ($area != null) {
			$area_branch = DB::table('public.branch')
				->where('program_id', session('program_id'))
				->where('area_id', $area)
				->distinct('branch_id')
				->get();

			foreach ($area_branch as $key => $value) {
				$area_search1 = str_pad($value->branch_id, 4, "0", STR_PAD_LEFT);
				$area_search[] = $area_search1;
			}
		}
		$status_search = $req->status;
		//dd($status_search);
		$branch_search = $req->branch;
		$branchcode_search = str_pad($branch_search, 4, "0", STR_PAD_LEFT);
		$dateForm = $req->dateFrom;
		$dateTo = $req->dateTo;
		$po_search = $req->po;
		// dd("Te");
		// division & date search
		if ($division != null && $region == null && $area == null && $branch_search == null && $po_search == null && $status_search == null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();

			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				//dd("t5");
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					//dd(session('projectcode'));
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}

					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;
				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}
		// region & date search
		if ($region != null && $area == null && $branch_search == null && $po_search == null && $status_search == null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();

			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					// $member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;

				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}
		// area & date search
		if ($area != null && $branch_search == null && $po_search == null && $status_search == null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();
			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;

				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}
		// area & date & status search
		if ($area != null && $branch_search == null && $po_search == null && $status_search != null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.status', $status_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();

			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;
				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}
		// region & date & status search
		if ($region != null && $area == null && $branch_search == null && $po_search == null && $status_search != null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.status', $status_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();

			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;
				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}
		// division & date & status search
		if ($division != null && $region == null && $area == null && $branch_search == null && $po_search == null && $status_search != null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.status', $status_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();
			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;

				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}

		// branch & date & status  search
		if ($branch_search != null  && $status_search != null && $dateForm != null && $dateTo != null && $po_search == null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.status', $status_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();

			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->editColumn('time', function ($datas) {
				return date('d-m-Y', strtotime($datas->survey_date));
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;

				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->make(true);
		}
		// branch & date search
		if ($branch_search != null && $dateForm != null && $dateTo != null && $status_search == null && $po_search == null) {
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();

			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})
				// ->addColumn('propos_amt', function ($datas) {
				//     $def_amount='';
				//     $amount=$datas->propos_amt;
				//     if($amount == null){
				//         $def_amount='0';
				//         return $def_amount;
				//     }
				//     return $def_amount;
				// })
				->addColumn('time', function ($datas) {
					$time = date('m/d/Y', strtotime($datas->survey_date));
					return $time;
				})->addColumn('status', function ($datas) {
					$db = config('database.db');
					$Mainstatus = '';
					$Mainstatus = $datas->status;
					return $Mainstatus;
				})->addColumn('action', function ($datas) {
					return '<a href="#" class="btn btn-warning">Details</a>';
				})->toJson();
		}

		// date & status search
		if ($dateForm != null && $dateTo != null && $status_search != null && $po_search == null && $branch_search == null) {
			//dd("huda");
			$datas = DB::table($db . '.surveys')
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.status', $status_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();
			//dd($datas);
			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;

				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}

		// po & date & status
		if ($branch_search != null  && $status_search != null && $dateForm != null && $dateTo != null && $po_search != null) {
			//dd(11);
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.status', $status_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();
			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->editColumn('time', function ($datas) {
				return date('d-m-Y', strtotime($datas->survey_date));
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;
				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
			})->make(true);
		}
		// po & date
		if ($status_search == null && $dateForm != null && $dateTo != null && $po_search != null) {
			//dd(12);
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.assignedpo', $po_search)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();
			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->editColumn('time', function ($datas) {
				return date('d-m-Y', strtotime($datas->survey_date));
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;
				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->make(true);
		}

		// date search
		if ($dateForm != null && $dateTo != null && $status_search == null && $branch_search == null && $po_search == null) {
			//dd(13);
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				// ->whereBetween($db.'.loans.time', [$dateForm, $dateTo])
				->whereDate($db . '.surveys.survey_date', '>=', $dateForm)
				->whereDate($db . '.surveys.survey_date', '<=', $dateTo)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();

			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;
				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
			})->toJson();
		}

		if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
			//dd(13);
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->where($db . '.surveys.projectcode', session('projectcode'))
				// ->whereDate($db.'.loans.time', Carbon::today())
				->get();
			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('d/m/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('ApplicantsName', function ($datas) {
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					if (session('projectcode') != '060') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}
					//  $member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					// $member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					$response = Http::get($member);
					$admissionArray = $response->object();
					if ($admissionArray != null) {
						$admissionApi = $admissionArray->data[0];
						$MemberName = $admissionApi->MemberName;
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('status', function ($datas) {
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus = $datas->status;
				return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}

		if ((session('role_designation') != 'HO') or (session('role_designation') != 'PH')) {
			$today = date('Y-m-d');
			//dd("Test");
			$datas = DB::table($db . '.surveys')
				->whereIn($db . '.surveys.assignedpo', $po)
				->where($db . '.surveys.projectcode', session('projectcode'))
				->get();
			return datatables($datas)->addColumn('branchcode', function ($datas) {
				$branch_name = '';
				$branchcode = $datas->branchcode;
				$branch_qry = DB::table('public.branch')->where('branch_id', $branchcode)->first();
				if ($branch_qry) {
					$branch_name = $branch_qry->branch_name;
					return $branch_name;
				}
				return $branch_name;
			})->addColumn('ApplicantsName', function ($datas) {
				//dd("te");
				$MemberName = '';
				if ($datas->name) {
					$MemberName = $datas->name;
					return $MemberName;
				} else {
					//  dd("Huda");
					$db = config('database.db');
					$urllink = DB::table($db . '.server_url')->where('status', 1)->first();
					$url = $urllink->url;
					//  $member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					if (session('projectcode') == '015') {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgNo=$datas->orgno&OrgMemNo=$datas->orgmemno";
					} else {
						$member = $url . "SavingsInfo?BranchCode=$datas->branchcode&CONo=$datas->assignedpo&ProjectCode=$datas->projectcode&UpdatedAt=2000-01-01%2010:00:00&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&Status=2&OrgMemNo=$datas->orgmemno";
					}

					//dd($member);
					Log::info($member);
					$response = Http::get($member);
					$admissionArray = $response->object();
					//dd($admissionArray);
					if ($admissionArray != null) {
						//dd($admissionArray->data);
						$admissionApi = $admissionArray->data;
						//dd($admissionApi[0]->MemberName);
						if (!empty($admissionApi)) {
							$MemberName = $admissionApi[0]->MemberName;
						} else {
							$MemberName = '';
						}

						//dd($MemberName);
						return $MemberName;
					}
				}
				return $MemberName;
			})->addColumn('assignedpo', function ($datas) {
				$db = config('database.db');
				$coname = '';
				$assignedpo = $datas->assignedpo;
				$co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
				if ($co_qry) {
					$coname = $co_qry->coname;
					return $coname;
				}
				return $coname;
			})->addColumn('time', function ($datas) {
				$time = date('m/d/Y', strtotime($datas->survey_date));
				return $time;
			})->addColumn('status', function ($datas) {
				// dd("H");
				$db = config('database.db');
				$Mainstatus = '';
				$Mainstatus  = $datas->status;
				return $Mainstatus;

				//dd($Mainstatus);
				// return $Mainstatus;
			})->addColumn('action', function ($datas) {
				return '<a href="#" class="btn btn-warning">Details</a>';
			})->toJson();
		}
	}
}
