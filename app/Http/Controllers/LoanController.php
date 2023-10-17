<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loans;
use App\Models\Admission;
use DB;
use Log;
use App\Models\RcaTable;
use App\Models\Branch;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\DashboardController;
use Session;
Use Alert;
class LoanController extends Controller
{

    public function index()
    {

        $db = config('database.db');
        $role_designation = session('role_designation');

        //dd($role_designation);
        // dd(session('projectcode'));

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
            return view('progoti/loan-request')->with('branch', $branch)->with('value', $value)->with('search2', $search2)->with('status', $status);
        } else {
            return view('loan-request')->with('branch', $branch)->with('value', $value)->with('search2', $search2)->with('status', $status);
        }
    }

    public function loanTable(Request $req)
    {
        //dd("Huda");
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

            $value1 = DB::table($db . '.loans')->select('branchcode', 'assignedpo')
                ->where('branchcode', $branchCode)->groupBy('branchcode', 'assignedpo')->get();
            if (!$value1->isEmpty()) {
                foreach ($value1 as $assignedpo) {
                    $all_branchcode[] = $assignedpo->branchcode;
                    $all_assignedpo[] = str_pad($assignedpo->assignedpo, 8, "0", STR_PAD_LEFT);
                }
            }
        }
        //dd($all_branchcode);
        $polist = DB::table($db . '.polist')
            ->where('projectcode', session('projectcode'))
            ->where('status', '1')
            ->whereIn('branchcode', $all_branchcode)
            ->get();
        /*$responseLoan = Http::post('http://scm.brac.net/sc/GetPO', [
            'branchcode' => $all_branchcode,
            'projectcode' => session('projectcode'),
        ]);
        $polist = $responseLoan->object()->data;*/
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
        $branch_search = $req->branch;
        $branchcode_search = str_pad($branch_search, 4, "0", STR_PAD_LEFT);
        $dateForm = $req->dateFrom;
        $dateTo = $req->dateTo;
        $po_search = $req->po;
        // dd("Te");
        // division & date search
        if ($division != null && $region == null && $area == null && $branch_search == null && $po_search == null && $status_search == null) {
            //dd("Najmul");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->whereIn($db . '.loans.branchcode', $division_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                // dd("t5");
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }
        // region & date search
        if ($region != null && $area == null && $branch_search == null && $po_search == null && $status_search == null) {
           // dd("Huda1");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->whereIn($db . '.loans.branchcode', $region_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }
        // area & date search
        if ($area != null && $branch_search == null && $po_search == null && $status_search == null) {
            //dd("huda2");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->whereIn($db . '.loans.branchcode', $area_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }
        // area & date & status search
        if ($area != null && $branch_search == null && $po_search == null && $status_search != null) {
            //dd("huda2");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->whereIn($db . '.loans.branchcode', $area_search)
                ->where($db . '.loans.status', $status_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }
        // region & date & status search
        if ($region != null && $area == null && $branch_search == null && $po_search == null && $status_search != null) {
            //dd("huda3");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->whereIn($db . '.loans.branchcode', $region_search)
                ->where($db . '.loans.status', $status_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }
        // division & date & status search
        if ($division != null && $region == null && $area == null && $branch_search == null && $po_search == null && $status_search != null) {
            //dd("huda4");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->whereIn($db . '.loans.branchcode', $division_search)
                ->where($db . '.loans.status', $status_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }

        // branch & date & status  search
        if ($branch_search != null  && $status_search != null && $dateForm != null && $dateTo != null && $po_search == null) {
            //dd("huda5");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->where($db . '.loans.branchcode', $branchcode_search)
                ->where($db . '.loans.status', $status_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                return date('d-m-Y', strtotime($datas->time));
            })->addColumn('assignedpo', function ($datas) {
                $db = config('database.db');
                $coname = '';
                $assignedpo = $datas->assignedpo;
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->make(true);
        }
        // branch & date search
        if ($branch_search != null && $dateForm != null && $dateTo != null && $status_search == null && $po_search == null) {
           //dd("huda6");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->where($db . '.loans.branchcode', $branchcode_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
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
                    $time = date('Y-m-d', strtotime($datas->time));
                    return $time;
                })->addColumn('status', function ($datas) {
                    $db = config('database.db');
                    $Mainstatus = '';
                    $recieverRole = $datas->reciverrole;
                    $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                    $recieverRole_destination = $getRecieverRole->designation;
                    if (session('role_designation') == 'AM') {
                        if ($recieverRole_destination == 'AM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name;
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'RM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at RM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'DM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at DM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'BM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at BM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'HO') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at HO";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'PO') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at PO";
                            return $Mainstatus;
                        }
                        return $Mainstatus;
                    }
                    if (session('role_designation') == 'RM') {
                        if ($recieverRole_destination == 'AM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at AM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'RM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name;
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'DM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at DM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'BM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at BM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'HO') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at HO";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'PO') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at PO";
                            return $Mainstatus;
                        }
                        return $Mainstatus;
                    }
                    if (session('role_designation') == 'DM') {
                        if ($recieverRole_destination == 'AM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at AM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'RM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at RM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'DM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name;
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'BM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at BM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'PO') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at PO";
                            return $Mainstatus;
                        }
                        return $Mainstatus;
                    }
                    if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                        if ($recieverRole_destination == 'AM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at AM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'RM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at RM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'DM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at DM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'BM') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at BM";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'PO') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name . " at PO";
                            return $Mainstatus;
                        }
                        if ($recieverRole_destination == 'HO') {
                            $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                            $Mainstatus = $statusQuery->status_name;
                            return $Mainstatus;
                        }
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                })->addColumn('Erpstatus', function ($datas) {
                    $db = config('database.db');
                    $Mainstatus = '';
                    $recieverRole = $datas->reciverrole;
                    $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                    $recieverRole_destination = $getRecieverRole->designation;
                    if ($recieverRole_destination == 'AM') {
                        if ($datas->ErpStatus == 1) {
                            $Mainstatus = 'ERP Pending';
                        } else if ($datas->ErpStatus == 2) {
                            $Mainstatus = 'ERP Approved';
                        } else if ($datas->ErpStatus == 3) {
                            $Mainstatus = 'ERP Rejected';
                        } else if ($datas->ErpStatus == 4) {
                            $Mainstatus = 'ERP Disburse';
                        } else {
                            $Mainstatus = '';
                        }

                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                })->addColumn('action', function ($datas) {
                    return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
                })->toJson();
        }

        // date & status search
        if ($dateForm != null && $dateTo != null && $status_search != null && $po_search == null && $branch_search == null) {
            //dd("huda7");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->where($db . '.loans.status', $status_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
                ->get();
            // dd($datas);
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }

        // po & date & status
        if ($branch_search != null  && $status_search != null && $dateForm != null && $dateTo != null && $po_search != null) {
            //dd("huda8");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->where($db . '.loans.status', $status_search)
                ->where($db . '.loans.assignedpo', $po_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                return date('d-m-Y', strtotime($datas->time));
            })->addColumn('assignedpo', function ($datas) {
                $db = config('database.db');
                $coname = '';
                $assignedpo = $datas->assignedpo;
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->make(true);
        }
        // po & date
        if ($status_search == null && $dateForm != null && $dateTo != null && $po_search != null) {
            //dd("huda9");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->where($db . '.loans.assignedpo', $po_search)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                return date('d-m-Y', strtotime($datas->time));
            })->addColumn('assignedpo', function ($datas) {
                $db = config('database.db');
                $coname = '';
                $assignedpo = $datas->assignedpo;
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->make(true);
        }

        // date search
        if ($dateForm != null && $dateTo != null && $status_search == null && $branch_search == null && $po_search == null) {
            //dd("huda10");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                // ->whereBetween($db.'.loans.time', [$dateForm, $dateTo])
                ->whereDate($db . '.loans.time', '>=', $dateForm)
                ->whereDate($db . '.loans.time', '<=', $dateTo)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }

        if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
            //dd("huda10");
            //dd("huda");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
                // ->whereDate($db.'.loans.time', Carbon::today())
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
                //dd($branch_name);
                return $branch_name;
            })->addColumn('assignedpo', function ($datas) {
                $db = config('database.db');
                $coname = '';
                $assignedpo = $datas->assignedpo;
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                //dd($co_qry);
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
                //dd("Huda");
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('ApplicantsName', function ($datas) {
                $MemberName = '';
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
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

                //dd($MemberName);
                return $MemberName;
            })->addColumn('status', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }

                    return $Mainstatus;
                }


                return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }

        if ((session('role_designation') != 'HO') or (session('role_designation') != 'PH')) {
            //dd("huda11");
            //dd("huda");
            $today = date('Y-m-d');
            $fdate = date('Y-m');
            $frmdate = $fdate . "-01";
            // dd("Test");
            $datas = DB::table($db . '.loans')
                ->whereIn($db . '.loans.assignedpo', $po)
                ->where($db . '.loans.projectcode', session('projectcode'))
                ->whereDate($db . '.loans.time', '>=', $frmdate)
                ->whereDate($db . '.loans.time', '<=', $today)
                ->where($db . '.loans.reciverrole', session('roll'))
                ->select($db . '.loans.*', $db . '.admissions.ApplicantsName', $db . '.admissions.MainIdTypeId')
                ->leftJoin($db . '.admissions', $db . '.loans.erp_mem_id', '=', $db . '.admissions.MemberId')->distinct('admissions.MemberId')
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
                if ($datas->ApplicantsName) {
                    $MemberName = $datas->ApplicantsName;
                    return $MemberName;
                } else {
                    //dd("Huda");
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
                /*$dashboardpolist = new DashboardController();
                $polist = $dashboardpolist->Individual_GetPO($datas->branchcode, session('projectcode'), $assignedpo);
                $coname = $polist[0]->coname;*/
                $co_qry = DB::table($db . '.polist')->where('cono', $assignedpo)->first();
                if ($co_qry) {
                    $coname = $co_qry->coname;
                    return $coname;
                }
                return $coname;
            })->addColumn('time', function ($datas) {
                $time = date('Y-m-d', strtotime($datas->time));
                return $time;
            })->addColumn('status', function ($datas) {
                // dd("H");
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                // dd($recieverRole_destination);
                if (session('role_designation') == 'AM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        // dd($Mainstatus);
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'RM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at HO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                if (session('role_designation') == 'DM') {
                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }

                if ((session('role_designation') == 'HO') or (session('role_designation') == 'PH')) {

                    if ($recieverRole_destination == 'AM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at AM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'RM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at RM";

                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'DM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at DM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'BM') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at BM";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'PO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name . " at PO";
                        return $Mainstatus;
                    }
                    if ($recieverRole_destination == 'HO') {
                        $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                        $Mainstatus = $statusQuery->status_name;
                        return $Mainstatus;
                    }
                    return $Mainstatus;
                }
                //dd($Mainstatus);
                // return $Mainstatus;
            })->addColumn('Erpstatus', function ($datas) {
                $db = config('database.db');
                $Mainstatus = '';
                $recieverRole = $datas->reciverrole;
                $getRecieverRole = DB::table($db . '.role_hierarchies')->select('designation')->where('projectcode', session('projectcode'))->where('position', $recieverRole)->first();
                $recieverRole_destination = $getRecieverRole->designation;
                if ($recieverRole_destination == 'AM') {
                    if ($datas->ErpStatus == 1) {
                        $Mainstatus = 'ERP Pending';
                    } else if ($datas->ErpStatus == 2) {
                        $Mainstatus = 'ERP Approved';
                    } else if ($datas->ErpStatus == 3) {
                        $Mainstatus = 'ERP Rejected';
                    } else if ($datas->ErpStatus == 4) {
                        $Mainstatus = 'ERP Disburse';
                    } else {
                        $Mainstatus = '';
                    }

                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'RM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at RM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'DM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at DM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'BM') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at BM";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'HO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at HO";
                    return $Mainstatus;
                }
                if ($recieverRole_destination == 'PO') {
                    $statusQuery = DB::table($db . '.status')->select('status_name')->where('status_id', $datas->status)->first();
                    $Mainstatus = $statusQuery->status_name . " at PO";
                    return $Mainstatus;
                }
                return $Mainstatus;
            })->addColumn('action', function ($datas) {
                return '<a href="loan-approval/' . $datas->id . '" class="btn btn-warning">Details</a>';
            })->toJson();
        }
    }

    public function loan_approve($id)
    {
        $db = config('database.db');
        $data = DB::table($db . '.loans')
            ->where($db . '.loans.id', '=', $id)
            ->where($db . '.loans.projectcode', session('projectcode'))
            ->first();
        if ($data) {
            $data2 = json_decode($data->DynamicFieldValue);

            $rca = RcaTable::where(['loan_id' => $id])->first();
            $admissionApi = '';
            $admissionData = Admission::select('*')->where(['MemberId' => $data->erp_mem_id])->first();
            if ($admissionData == null) {
                $branchCode = $data->branchcode;
                $CONo = $data->assignedpo;
                $projectCode = $data->projectcode;
                $OrgNo = $data->orgno;
                $OrgMemNo = $data->orgmemno;
                $url = "https://erp.brac.net/node/scapir/MemberList?BranchCode=$branchCode&CONo=$CONo&ProjectCode=$projectCode&UpdatedAt=2000-01-01%2000%3A00%3A00&Status=1&OrgNo=$OrgNo&OrgMemNo=$OrgMemNo&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae";
                $response = Http::get($url);
                $admissionArray = $response->object();
                if (isset($admissionArray->data[0])) {
                    $admissionApi = $admissionArray->data[0];
                } else {
                    Session::put('error', "Member List Data Is Empty");
                    return redirect()->back();
                    //return redirect()->back()->with('error', 'Member List Data Is Empty');
                }
            }
            if (session('projectcode') == '060') {
                return view('progoti/loanApproval')->with('data', $data)->with('data2', $data2)->with('rca', $rca)->with('admissionApi', $admissionApi)->with('admissionData', $admissionData);
            } else {
                return view('loanApproval')->with('data', $data)->with('data2', $data2)->with('rca', $rca)->with('admissionApi', $admissionApi)->with('admissionData', $admissionData);
            }
        }
        Session::put('error', "Data doesn't exist");
        return redirect('/operation/loan')->back();
        //return redirect('/operation/loan')->with('error', "Data doesn't exist");
    }

    public function assessmentInsertion($request)
    {
        $db = config('database.db');
        //dd("Huda");

        if (session('role_designation') == 'AM' and $request->action != "Reject") {

            $am_assessment = array(
                'am_primary_earner' => $request->all_primary_earner1,
                'am_monthlyincome_main' => $request->all_monthlyincome_main1,
                'am_monthlyincome_spouse_child' => $request->all_monthlyincome_spouse_child1,
                'am_monthlyincome_other' => $request->all_monthlyincome_other1,
                'am_house_rent' => $request->all_house_rent1,
                'am_food' => $request->all_food1,
                'am_education' => $request->all_education1,
                'am_medical' => $request->all_medical1,
                'am_festive' => $request->all_festive1,
                'am_utility' => $request->all_utility1,
                'am_saving' => $request->all_saving1,
                'am_other' => $request->all_other1,
                'am_debt' => $request->all_debt1,
                'am_monthly_cash' => $request->all_monthly_cash1,
                'am_instal_proposloan' => $request->all_instal_proposloan1,
                'am_seasonal_income' => $request->all_seasonal_income1,
                'am_incomeformfixedassets' => $request->all_incomeformfixedassets1,
                'am_imcomeformsavings' => $request->all_imcomeformsavings1,
                'am_houseconstructioncost' => $request->all_houseconstructioncost1,
                'am_expendingonmarriage' => $request->all_expendingonmarriage1,
                'am_operation_childBirth' => $request->all_operation_childBirth1,
                'am_foreigntravel' => $request->all_foreigntravel1
            );
            DB::table($db . '.rca')->where('loan_id', $request->id)->update($am_assessment);
        }
        if (session('role_designation') == 'RM' and $request->action != "Reject") {
            $rm_assessment = array(
                'rm_primary_earner' => $request->all_primary_earner1,
                'rm_monthlyincome_main' => $request->all_monthlyincome_main1,
                'rm_monthlyincome_spouse_child' => $request->all_monthlyincome_spouse_child1,
                'rm_monthlyincome_other' => $request->all_monthlyincome_other1,
                'rm_house_rent' => $request->all_house_rent1,
                'rm_food' => $request->all_food1,
                'rm_education' => $request->all_education1,
                'rm_medical' => $request->all_medical1,
                'rm_festive' => $request->all_festive1,
                'rm_utility' => $request->all_utility1,
                'rm_saving' => $request->all_saving1,
                'rm_other' => $request->all_other1,
                'rm_debt' => $request->all_debt1,
                'rm_monthly_cash' => $request->all_monthly_cash1,
                'rm_instal_proposloan' => $request->all_instal_proposloan1,
                'rm_seasonal_income' => $request->all_seasonal_income1,
                'rm_incomeformfixedassets' => $request->all_incomeformfixedassets1,
                'rm_imcomeformsavings' => $request->all_imcomeformsavings1,
                'rm_houseconstructioncost' => $request->all_houseconstructioncost1,
                'rm_expendingonmarriage' => $request->all_expendingonmarriage1,
                'rm_operation_childBirth' => $request->all_operation_childBirth1,
                'rm_foreigntravel' => $request->all_foreigntravel1,
            );
            DB::table($db . '.rca')->where('loan_id', $request->id)->update($rm_assessment);
        }
    }

    public function action_btn(Request $request)
    {
        $db = config('database.db');
        //dd("Test");
        //$this->assesesmentInsertion($request);
        $this->assessmentInsertion($request);

        $role = session('roll');
        $db1 = DB::table($db . '.loans')
            ->where($db . '.loans.id', '=', $request->id)
            ->where($db . '.loans.projectcode', session('projectcode'))
            ->first();
        $branchcode = $db1->branchcode;
        $loan_id = $db1->loan_id;
        $projectcode = $db1->projectcode;
        $action = $request->action;
        $doc_id = $request->id;
        $pin = session('user_pin');
        $comment = urlencode($request->comment);


        $document_url = "http://dcsprogoti.brac.net/dcs/DocumentManager?doc_id=$doc_id&projectcode=$projectcode&doc_type=loan&pin=$pin&role=$role&branchcode=$branchcode&action=$action&comment=$comment";
        // dd($document_url);
        Log::channel('daily')->info('Document_url : ' . $document_url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $document_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $documentoutput = curl_exec($ch);
        curl_close($ch);
        //dd($documentoutput);
        $collectionfordocument = json_decode($documentoutput);
        // dd($collectionfordocument);

        if ($collectionfordocument != NULL) {
            if($action=='Recommend')
            {
               // Alert::success('Success Title', "অভিনন্দন! লোনের আবেদন সফলভাবে সুপারিশ করা হয়েছে।\n• আবেদনের অবস্থান জানতে স্ট্যাটাস চেক করুন।");
                Session::put('recommend', "অভিনন্দন! লোনের আবেদন সফলভাবে সুপারিশ করা হয়েছে।\n• আবেদনের অবস্থান জানতে স্ট্যাটাস চেক করুন।");
                return redirect('/operation/loan');
            }
            else if($action=='Sendback')
            {
                Session::put('sendback', "অভিনন্দন! লোনের আবেদন সফলভাবে সেন্ডব্যাক করা হয়েছে।\n• সংশোধন করে পাঠালে অনুমোদন করতে পারবেন।");
                return redirect('/operation/loan');
            }
            else
            {
                Session::put('success', "Action suucessful.");
                return redirect('/operation/loan');
            }
            //return redirect('/operation/loan')->with('success', 'Action suucessful.');
        } else {
            Session::put('error', "Data does not send!");
            return redirect()->back();
            // return redirect()->back()->with('error', 'Data does not send!');
        }
    }

    public function approve_loan(Request $request)
    {
        //dd("Test");
        $db = config('database.db');
        $doc_type = "loan";
        $role = session('roll');
        $db1 = DB::table($db . '.loans')
            ->where($db . '.loans.id', '=', $request->id)
            ->where($db . '.loans.projectcode', session('projectcode'))
            ->first();
        $branchcode = $db1->branchcode;
        $loan_id = $db1->loan_id;
        $projectcode = $db1->projectcode;
        $action = "Approve";
        $doc_id = $request->id;
        //dd($doc_id);
        $pin = session('user_pin');
        $proposeAmount1 = $db1->propos_amt;
        //dd($proposeAmount);
        // work with celling data
        $project = session('projectcode');
        if ($project == '015') {
            $projectcd = '15';
        } else if ($project == '060') {
            $projectcd = '60';
        }
        $grouthGet = DB::Table($db . '.project_wise_branch_growth_types')->select('branch_growth_type','group')->where('project_code', $projectcd)->where('office_code', $branchcode)->get(); //"HIGH";
        $growth_rate = $grouthGet[0]->branch_growth_type;
        $groupcate = $grouthGet[0]->group;
        $loan_type = $request->loan_type;
        $proposeAmount = $request->approve_amount;
        /*if ($branchcode == '0605') {
            $groupcate = 'GC1';
        } else {
            $groupcate = 'GC1';
        }*/

        //dd($proposeAmount);
        $loan_approve = DB::Table($db . '.loans')->where('id', $request->id)->update(['approval_amount' => $proposeAmount]);
        /*$cellingData = DB::table($db . '.celing_configs')
            ->select('limit_form', 'limit_to', 'repeat_limit_form', 'repeat_limit_to')
            ->where('approver', session('role_designation'))
            ->where('growth_rate', $growth_rate)->where('projectcode', $projectcd)->where('group', $groupcate)
            ->first();*/
        //dd($cellingData);
        if ($project == '060') {
            $cellingData = DB::table($db . '.celing_configs')
                ->select('limit_form', 'limit_to', 'repeat_limit_form', 'repeat_limit_to')
                ->where('approver', session('role_designation'))
                ->where('growth_rate', $growth_rate)->where('projectcode', $projectcd)->where('group', $groupcate)
                ->first();
        } else {
            $cellingData = DB::table($db . '.celing_configs')
                ->select('limit_form', 'limit_to', 'repeat_limit_form', 'repeat_limit_to')
                ->where('approver', session('role_designation'))
                ->where('growth_rate', $growth_rate)->where('projectcode', $projectcd)->first();
        }
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
                // dd($proposeAmount,$limitFrom,$limitTo);
                Session::put('error', "Loan amount Limit exceed for your designation");
                return redirect()->back();
                //return redirect()->back()->with('error', 'Loan amount Limit exceed for your designation');
            }
        } else {
            Session::put('error', "Can't get celling data");
            return redirect()->back();
            //return redirect()->back()->with('error', "Can't get celling data");
        }

        $document_url = "http://dcsprogoti.brac.net/dcs/DocumentManager?doc_id=$doc_id&projectcode=$projectcode&doc_type=loan&pin=$pin&role=$role&branchcode=$branchcode&action=$action";
        //dd($document_url);
        Log::channel('daily')->info('Document_url : ' . $document_url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $document_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $documentoutput = curl_exec($ch);
        curl_close($ch);

        $collectionfordocument = json_decode($documentoutput);
        // dd($documentoutput);
        // $status = $collectionfordocument[0]
        Log::info("Loan Approve message" . $documentoutput);

        if ($collectionfordocument != NULL) {
            if ($collectionfordocument->status == "E") {
                if (isset($collectionfordocument->errors)) {
                    //dd("Huda");
                    if (isset($collectionfordocument->errors[0]->message)) {
                        $errors = $collectionfordocument->errors[0]->message;
                        Session::put('error', $errors);
                        return redirect()->back();
                        //return redirect()->back()->with('error', $errors);
                    } else if (isset($collectionfordocument->errors[0]->fieldErrors)) {
                        foreach ($collectionfordocument->errors[0]->fieldErrors as $row) {
                            $errors[] = [
                                "field" => $row->field,
                                "message" => $row->message
                            ];
                        }
                        Session::put('errors', $errors);
                        return redirect()->back();
                        //return redirect()->back()->with('errors', $errors);
                    } else {
                        $errors = $collectionfordocument->errors;
                        Session::put('error', $errors);
                        return redirect()->back();
                        //return redirect()->back()->with('error', $errors);
                    }
                } else {
                    $errors = $collectionfordocument->message;
                    Session::put('error', $errors);
                    return redirect()->back();
                    //return redirect()->back()->with('error', $errors);
                }
            } else {
                //Session::put('success', 'Action suucessful.');
                Session::put('approve', "অভিনন্দন! লোনের আবেদন সফলভাবে অনুমোদন করা হয়েছে।\n• আবেদনের অবস্থান জানতে স্ট্যাটাস চেক করুন।");
               // return redirect('/operation/loan');
                return redirect('/operation/loan');
                //return redirect('/operation/loan')->with('success', 'Action suucessful.');
            }
        } else {
            Session::put('error', 'Data does not send!');
            return redirect()->back();
            //return redirect()->back()->with('error', 'Data does not send!');
        }
    }

    public function closeLoan(Request $request)
    {
        $erp_mem_id = $request->ErpMemId;
        $branchcode = $request->branchcode;
        $url = "http://dcsprogoti.brac.net/dcs/LastOneCloseLoanBehavior?token=7f30f4491cb4435984616d1913e88389&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&BranchCode=$branchcode&MemberId=$erp_mem_id";
        $response = Http::get($url);
        $data = $response->object();
        return response()->json($data);
    }
    public function Savings_Behaviours(Request $request)
    {
        $orgno = $request->orgno;

        $orgmemno = $request->orgmemno;
        $branchcode = $request->branchcode;
        $projectname = $request->projectcode;
        if ($projectname == 'Progoti') {
            $projectcode = '060';
        } else {
            $projectcode = '015';
        }
        if ($orgno != null or $orgno != '') {
            $urlparams = "token=7f30f4491cb4435984616d1913e88389&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&BranchCode=$branchcode&OrgNo=$orgno&OrgMemNo=$orgmemno&ProjectCode=$projectcode";
        } else {
            $urlparams = "token=7f30f4491cb4435984616d1913e88389&key=5d0a4a85-df7a-scapi-bits-93eb-145f6a9902ae&BranchCode=$branchcode&OrgMemNo=$orgmemno&ProjectCode=$projectcode";
        }
        $url = "http://dcsprogoti.brac.net/dcs/operation/savingsTransaction?" . $urlparams;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $output_colsed = curl_exec($ch);
        //dd($output_colsed);
        curl_close($ch);
        //return response()->json($data);
    }
}
