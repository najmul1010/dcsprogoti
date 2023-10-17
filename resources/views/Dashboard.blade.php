@extends('backend.layouts.master')

@section('title','Dashboard')
<link href="cdn.datatables.net/1.11.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
@section('style')
<style>
  .user_info {
    margin-bottom: 50px;
  }

  .text_aling {
    text-align: center;
    background: #FB3199;
    color: white;
    /* font-weight: bold; */
  }

  .view_btn {
    text-decoration: none;
    color: #fff;
    padding: 7px;
    background-color: #EE9D01;
    border-radius: 15%;
  }

  label {
    margin-left: 2px;
  }

  .select2-selection__rendered {
    line-height: 31px !important;
    padding-top: 0px !important;
    padding-left: 16px !important;
  }

  .select2-container .select2-selection--single {
    height: 38px !important;
  }

  .select2-selection__arrow {
    height: 34px !important;
  }

  .btn:hover {
    color: rgb(9, 8, 8) !important;
  }

  .roll_single_btn {
    border: groove;
    padding-top: 10px;
    transition: .5s;
    cursor: pointer;
    background-color: #7ed6df;
  }

  .roll_single_btn:hover {
    background-color: #FB3199;
  }

  .roll_btn h4 span {
    margin-left: 20px;
  }

  .active {
    color: #FB3199;
  }

  .card-title {
    text-align: center;

  }
</style>
@endsection

@section('content')
<?php $projectcode =  session('projectcode'); ?>
<input type="hidden" id='projectcode' value="<?php echo $projectcode; ?>">
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
  <!--begin::Subheader-->
  <div class="subheader py-2 py-lg-4 subheader-solid" id="kt_subheader">
    <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
      <!--begin::Info-->
      <div class="d-flex align-items-center flex-wrap mr-2">
        <!--begin::Page Title-->
        <h5 class="text-dark font-weight-bold mt-2 mb-2 mr-5">@lang('dashboard.title')</h5>
      </div>
      <!--end::Info-->
    </div>
  </div>
  <div class="d-flex flex-column-fluid">
    <!--begin::Container-->
    <div class="container">
      <!--begin::Card-->
      <div class="card card-custom">
        {{-- <div class="card-header flex-wrap py-5">
                <div class="card-title">
                <h3 class="card-label">Form </h3>
                 </div>
                 </div> --}}
        <div class="card-body">
          <!--begin: Datatable-->
          <div class="row">
            <div class="col-md-12">
              @if ($errors->any())
              <div class="alert alert-danger">
                <ul style="margin-bottom: 0rem;">
                  @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
              @endif
              @if (Session::has('success'))
              <div class="alert alert-success" role="success">
                {{ Session::get('success') }}
              </div>
              @endif
              @if (Session::has('error'))
              <div class="alert alert-danger" role="success">
                {{ Session::get('error') }}
              </div>
              @endif
              <div class="form_value">
                <!-- <form action="{{url('operation/admission-search')}}" method="get"> -->
                @if(session('role_designation')=='AM')
                <div class="form-row">
                  <div class="col-md-2 mb-3">
                    <label class="" for="division">@lang('admission.division')</label>
                    <h6 id="division" class="mt-2">
                      {{$branch->division_id}}-{{$branch->division_name}}
                    </h6>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="" for="region">@lang('admission.region')</label>
                    <h6 id="region" class="mt-2">{{$branch->region_id}}-{{$branch->region_name}}
                    </h6>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="" for="area">@lang('admission.area')</label>
                    <h6 id="area" class="mt-2">{{$branch->area_id}}-{{$branch->area_name}}</h6>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="branch">@lang('admission.branch')</label>
                    <select id="branch" name="branch" class="form-control">
                      <option value="">Select</option>
                      @foreach($search2 as $row)
                      <option value="{{$row->branch_id}}">
                        {{$row->branch_id}}-{{$row->branch_name}}
                      </option>
                      @endforeach
                    </select>
                  </div>
                  <?php if (session('projectcode') != '060') { ?>
                    <div class="col-md-2 mb-3">
                      <label class="ml-2" for="po">@lang('admission.po')</label>
                      <select id="po" name="po" class="form-control">
                      </select>
                    </div>
                  <?php } else { ?>
                    <div class="col-md-2 mb-3">
                      <label class="ml-2" for="po">@lang('admission.co')</label>
                      <select id="po" name="po" class="form-control">
                      </select>
                    </div>
                  <?php } ?>

                </div>
                <div class="form-row">

                  @php
                  $month = date('m');
                  $day = date('d');
                  $year = date('Y');
                  $today = $year . '-' . $month . '-' . $day;
                  $from_date = $year . '-' . $month . '-' .'01';
                  @endphp
                  <div class="col-md-2 mb-3">
                    <label for="dateFrom" class="form-label">@lang('admission.dateRange')</label>
                    <input type="date" value="<?php echo $from_date; ?>" class="form-control" id="dateFrom" name="dateFrom">
                  </div>
                  <div class="col-md-2 mb-3 mt-6">
                    <label class="ml-2" for="level"></label>
                    <input type="date" value="<?php echo $today; ?>" class="form-control" id="dateTo" name="dateTo">
                  </div>
                  <?php if (session('projectcode') == '015') { ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php } else {
                  ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter1" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php
                  } ?>

                </div>
                @endif
                @if(session('role_designation')=='RM')
                <div class="form-row">
                  <div class="col-md-2 mb-3">
                    <label class="" for="division">@lang('admission.division')</label>
                    <h6 id="division" class="mt-2">
                      {{$branch->division_id}}-{{$branch->division_name}}
                    </h6>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="" for="region">@lang('admission.region')</label>
                    <h6 id="region" class="mt-2">{{$branch->region_id}}-{{$branch->region_name}}
                    </h6>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="area">@lang('admission.area')</label>
                    <select id="area" name="area" class="form-control">
                      <option value="">Select</option>
                      @foreach($search2 as $row)
                      <option value="{{$row->area_id}}">{{$row->area_id}}-{{$row->area_name}}
                      </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="branch">@lang('admission.branch')</label>
                    <select id="branch" name="branch" class="form-control">
                    </select>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="po">@lang('admission.po')</label>
                    <select id="po" name="po" class="form-control">
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  @php
                  $month = date('m');
                  $day = date('d');
                  $year = date('Y');
                  $today = $year . '-' . $month . '-' . $day;
                  $from_date = $year . '-' . $month . '-' .'01';
                  @endphp
                  <div class="col-md-2 mb-3">
                    <label for="dateFrom" class="form-label">@lang('admission.dateRange')</label>
                    <input type="date" value="<?php echo $from_date; ?>" class="form-control" id="dateFrom" name="dateFrom">
                  </div>
                  <div class="col-md-2 mb-3 mt-6">
                    <label class="ml-2" for="level"></label>
                    <input type="date" value="<?php echo $today; ?>" class="form-control" id="dateTo" name="dateTo">
                  </div>
                  <?php if (session('projectcode') == '015') { ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php } else {
                  ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter1" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php
                  } ?>
                </div>
                @endif
                @if(session('role_designation')=='DM')
                <div class="form-row">
                  <div class="col-md-2 mb-3">
                    <label class="" for="division">@lang('admission.division')</label>
                    <h6 id="division" class="mt-2">
                      {{$branch->division_id}}-{{$branch->division_name}}
                    </h6>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="region">@lang('admission.region')</label>
                    <select id="region" name="region" class="form-control">
                      <option value="">Select</option>
                      @foreach($search2 as $row)
                      <option value="{{$row->region_id}}">
                        {{$row->region_id}}-{{$row->region_name}}
                      </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="area">@lang('admission.area')</label>
                    <select id="area" name="area" class="form-control">
                    </select>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="branch">@lang('admission.branch')</label>
                    <select id="branch" name="branch" class="form-control">
                    </select>
                  </div>
                  <?php if (session('projectcode') != '060') { ?>
                    <div class="col-md-2 mb-3">
                      <label class="ml-2" for="po">@lang('admission.po')</label>
                      <select id="po" name="po" class="form-control">
                      </select>
                    </div>
                  <?php } else { ?>
                    <div class="col-md-2 mb-3">
                      <label class="ml-2" for="po">@lang('admission.co')</label>
                      <select id="po" name="po" class="form-control">
                      </select>
                    </div>
                  <?php } ?>


                </div>
                <div class="form-row">
                  @php
                  $month = date('m');
                  $day = date('d');
                  $year = date('Y');
                  $today = $year . '-' . $month . '-' . $day;
                  $from_date = $year . '-' . $month . '-' .'01';
                  @endphp
                  <div class="col-md-2 mb-3">
                    <label for="dateFrom" class="form-label">@lang('admission.dateRange')</label>
                    <input type="date" value="<?php echo  $from_date; ?>" class="form-control" id="dateFrom" name="dateFrom">
                  </div>
                  <div class="col-md-2 mb-3 mt-6">
                    <label class="ml-2" for="level"></label>
                    <input type="date" value="<?php echo $today; ?>" class="form-control" id="dateTo" name="dateTo">
                  </div>
                  <?php if (session('projectcode') == '015') { ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php } else {
                  ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter1" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php
                  } ?>
                </div>
                @endif
                @if(session('role_designation')=='HO' or session('role_designation')=='PH')
                <div class="form-row">
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="division">@lang('admission.division')</label>
                    <select id="division" name="division" class="form-control">
                      <option value="">Select</option>
                      @foreach($search2 as $row)
                      <option value="{{$row->division_id}}">
                        {{$row->division_id}}-{{$row->division_name}}
                      </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="region">@lang('admission.region')</label>
                    <select id="region" name="region" class="form-control">
                    </select>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="area">@lang('admission.area')</label>
                    <select id="area" name="area" class="form-control">
                    </select>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="ml-2" for="branch">@lang('admission.branch')</label>
                    <select id="branch" name="branch" class="form-control">
                    </select>
                  </div>
                  <?php if (session('projectcode') != '060') { ?>
                    <div class="col-md-2 mb-3">
                      <label class="ml-2" for="po">@lang('admission.po')</label>
                      <select id="po" name="po" class="form-control">
                      </select>
                    </div>
                  <?php } else { ?>
                    <div class="col-md-2 mb-3">
                      <label class="ml-2" for="po">@lang('admission.co')</label>
                      <select id="po" name="po" class="form-control">
                      </select>
                    </div>
                  <?php } ?>

                </div>
                @php
                $month = date('m');
                $day = date('d');
                $year = date('Y');
                $today = $year . '-' . $month . '-' . $day;
                $from_date = $year . '-' . $month . '-' .'01';
                @endphp
                <div class="form-row">
                  <div class="col-md-2 mb-3">
                    <label for="dateFrom" class="form-label">@lang('admission.dateRange')</label>
                    <input type="date" value="<?php echo $from_date; ?>" id="dateFrom" class="form-control" id="Project" name="dateFrom">
                  </div>

                  <div class="col-md-2 mb-3 mt-6">
                    <label class="ml-2" for="dateTo"></label>
                    <input type="date" value="<?php echo $today; ?>" min="" max="" id="dateTo" class="form-control" id="Project" name="dateTo">
                  </div>
                  <?php if (session('projectcode') == '015') { ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php } else {
                  ?>
                    <div class="col-md-2 mb-3 mt-8">
                      <button type="submit" id="filter1" class="btn btn-secondary">@lang('actionBtn.search')</button>
                    </div>
                  <?php
                  } ?>
                </div>
                @endif
                <!-- </form> -->
              </div>
              <br>
              <!-- -- card************************* ---->
              <div class="card_section">
                <div class="row">
                  <div class="col-md-3">
                    <div class="card" style="width: 18rem; height: 131;">
                      <div class="card-body" style="background-color:#90ee90;">
                        <h5 class="card-title">@lang('dashboard.card1')</h5>
                        <h5 class="text-center" id="totalAdmission">{{ $pending_admission->count }}</h5>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card" style="width: 18rem; height: 131;">
                      <div class="card-body" style="background-color:#FF77FF">
                        <h6 class=" card-title">@lang('dashboard.card2')</h6>
                        <h5 class="text-center" id="totalLoan">{{ $pending_loan->count }}</h5>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card" style="width: 18rem; height: 131;">
                      <div class="card-body" style="background-color:#fed8b1">
                        <h5 class="card-title" style="padding: 0; margin: 0;">@lang('dashboard.card3')</h5>
                        <h5 class="text-center" style="margin-top:7px;" id="total_disbuse">{{ $disburse_amt->sum }}</h5>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card" style="width: 18rem; height: 131;">
                      <div class="card-body" style="background-color:#ffcccb">
                        <h5 class="card-title">@lang('dashboard.card4')</h5>
                        <h5 class="text-center"><span class="pending_data">{{$all_pending_loan->count}}</span></h5>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <br>
              <br>
              <h4>@lang('dashboard.title1')</h4>
              <div class="row">
                {{-- status button ********** --}}
                <div class="col-md-3" style="margin-top:42px;">
                  <?php if (session('projectcode') == '015') { ?>
                    <div class=" mb-3">
                      <button type="button" id="pending" class="btn btn-block btn-secondary active">@lang('actionBtn.pending') (<span class="pending_data">{{$all_pending_loan->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="approved" class="btn btn-block btn-secondary">@lang('actionBtn.approval')
                        (<span id="approved_data">{{$all_approve_loan->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="disbursement" class="btn btn-block btn-secondary">@lang('actionBtn.disbursement') (<span id="disbursement_data">{{$all_disbursement->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="disburse" class="btn btn-block btn-secondary">@lang('actionBtn.disburse')
                        (<span id="disburse_data">{{$all_disburse_loan->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="rejected" class="btn btn-block btn-secondary">@lang('actionBtn.reject')
                        (<span id="rejected_data">{{$all_reject_loan->count}}</span>)</button>
                    </div>
                  <?php } else { ?>
                    <div class=" mb-3">
                      <button type="button" id="pending1" class="btn btn-block btn-secondary active">@lang('actionBtn.pending') (<span class="pending_data">{{$all_pending_loan->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="approved1" class="btn btn-block btn-secondary">@lang('actionBtn.approval')
                        (<span id="approved_data">{{$all_approve_loan->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="disbursement1" class="btn btn-block btn-secondary">@lang('actionBtn.disbursement') (<span id="disbursement_data">{{$all_disbursement->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="disburse1" class="btn btn-block btn-secondary">@lang('actionBtn.disburse')
                        (<span id="disburse_data">{{$all_disburse_loan->count}}</span>)</button>
                    </div>
                    <div class=" mb-3">
                      <button type="button" id="rejected1" class="btn btn-block btn-secondary">@lang('actionBtn.reject')
                        (<span id="rejected_data">{{$all_reject_loan->count}}</span>)</button>
                    </div>
                  <?php } ?>

                </div>
                {{-- datatable*********** --}}
                <div class="col-md-9">
                  <div class="roll_btn">
                    <div class="row">
                      <?php if (session('projectcode') == '015') { ?>
                        <div class="col-md-3">
                          <div class="row">
                            <div class="col-md-4"></div>
                            <div class="col-md-8 roll_single_btn">
                              <h4 class="roll_class" id="roll_bm">@lang('dashboard.role1') <span id="btn_bm">{{$bm_pending_loan->count }}</span>
                              </h4>
                            </div>
                          </div>
                        </div>
                      <?php } ?>
                      <div class="col-md-3">
                        <div class="row">
                          <div class="col-md-4">

                          </div>
                          <div class="col-md-8 roll_single_btn">
                            <h4 class="roll_class" id="roll_am">@lang('dashboard.role2') <span id="btn_am">{{ $am_pending_loan->count }}</span>
                            </h4>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="row">
                          <div class="col-md-4"></div>
                          <div class="col-md-8 roll_single_btn">
                            <h4 class="roll_class" id="roll_rm">@lang('dashboard.role3') <span id="btn_rm">{{$rm_pending_loan->count }}</span>
                            </h4>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="row">
                          <div class="col-md-4"></div>
                          <div class="col-md-8 roll_single_btn">
                            <h4 class="roll_class" id="roll_dm">@lang('dashboard.role4') <span id="btn_dm">{{$dm_pending_loan->count }}</span>
                            </h4>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <?php
                  //dd(session('projectcode'));

                  if (session('projectcode') == '015') { ?>
                    <table style="text-align: center;" class="table table-bordered" id="myTable">
                    <?php } else { ?>

                      <table style="text-align: center;" class="table table-bordered" id="myTable1">
                      <?php } ?>
                      <thead>
                        <tr class="brac-color">
                          <th>@lang('dashboard.header6')</th>
                          <?php if (session('projectcode') == '015') { ?>
                            <th>@lang('dashboard.header9')</th>
                          <?php
                          }
                          ?>
                          <th>@lang('dashboard.header7')</th>
                          <th>@lang('dashboard.header2')</th>
                          <th>@lang('dashboard.header3')</th>
                          <th>@lang('dashboard.header8')</th>
                          <th>@lang('dashboard.header4')</th>
                          <th>@lang('dashboard.header5')</th>
                          <th>@lang('dashboard.header1')</th>
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                      </table>
                </div>
              </div>

            </div>
          </div>
          <!--end: Datatable-->
        </div>
      </div>
      <!--end::Card-->
    </div>
    <!--end::Container-->
  </div>
</div>
@endsection

@section('script')
<script>
  $(document).ready(function() {
    //max 30 days
    $("#dateTo").click(function() {
      var mydate = $('input[name^=dateFrom]').map(function(idx, elem) {
        return $(elem).val();
      }).get()
      caldate = new Date(mydate);

      caldate.setDate(caldate.getDate() + 30);
      // var day = caldate.getDate();
      var day = ("0" + (caldate.getDate() + 1)).slice(-2)
      var month = ("0" + (caldate.getMonth() + 1)).slice(-2)
      var year = caldate.getFullYear();
      var fullDate = year + '-' + month + '-' + day;
      var myDatePicker = document.getElementById('dateTo');
      myDatePicker.setAttribute('max', fullDate);
      myDatePicker.setAttribute('min', mydate);
    });
    //max 30 days end

    // search function call
    fill_datatable();

    function fill_datatable(division = '', region = '', area = '', branch = '', dateFrom = '',
      dateTo = '', po = '', roll = '') {
      var table = $('#myTable').DataTable({
        // dom: 'tp',
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        bLengthChange: false,
        responsive: true,
        ajax: {
          cache: false,
          url: "{{url('dashboardTable')}}",
          data: {
            division: division,
            region: region,
            area: area,
            branch: branch,
            dateFrom: dateFrom,
            dateTo: dateTo,
            po: po,
            roll: roll
          }
        },
        columns: [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false
          },
          {
            data: 'orgno',
            name: 'orgno'
          },
          {
            data: 'orgmemno',
            name: 'orgmemno'
          },
          {
            data: 'propos_amt',
            name: 'propos_amt'
          },

          {
            data: 'loan_product',
            name: 'loan_product'

          },
          {
            data: 'loan_type',
            name: 'loan_type'
          },
          {
            data: 'branchcode',
            name: 'branchcode'
          },
          {
            data: 'assignedpo',
            name: 'assignedpo'
          },
          {
            data: 'time',
            name: 'time',
            format: 'dd-mm-yyyy'
          },
        ]
      });
    }

    function fill_datatable(division = '', region = '', area = '', branch = '', dateFrom = '',
      dateTo = '', po = '', roll = '') {
      var table = $('#myTable1').DataTable({
        // dom: 'tp',
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        bLengthChange: false,
        responsive: true,
        ajax: {
          cache: false,
          url: "{{url('dashboardTable')}}",
          data: {
            division: division,
            region: region,
            area: area,
            branch: branch,
            dateFrom: dateFrom,
            dateTo: dateTo,
            po: po,
            roll: roll
          }
        },
        columns: [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false
          },
          {
            data: 'orgmemno',
            name: 'orgmemno'
          },
          {
            data: 'propos_amt',
            name: 'propos_amt'
          },

          {
            data: 'loan_product',
            name: 'loan_product'

          },
          {
            data: 'loan_type',
            name: 'loan_type'
          },
          {
            data: 'branchcode',
            name: 'branchcode'
          },
          {
            data: 'assignedpo',
            name: 'assignedpo'
          },
          {
            data: 'time',
            name: 'time',
            format: 'dd-mm-yyyy'
          },
        ]
      });
    }
    $('#filter').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();

      //find btn value
      $.ajax({
        cache: false,
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po

        },
        success: function(data) {
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data);
        }
      });

      $('#myTable').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po);

    });
    $('#filter1').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();

      //find btn value
      $.ajax({
        cache: false,
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po

        },
        success: function(data) {
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data);
        }
      });

      $('#myTable1').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po);

    });

    // status btn action*******************************************************
    $('#pending').click(function() {

      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session $ find btn value
      $.ajax({
        cache: false,
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'pending',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po

        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#pending').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data);
        }
      });
      // search for pending status
      setTimeout(function() {
        $('#myTable').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
    });
    $('#pending1').click(function() {

      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session $ find btn value
      $.ajax({
        cache: false,
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'pending',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po

        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#pending').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data);
        }
      });
      // search for pending status
      setTimeout(function() {
        $('#myTable1').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
    });

    $('#approved').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session
      $.ajax({
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'approve',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po
        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#approved').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data[9]);
        }
      });

      setTimeout(function() {
        $('#myTable').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
      // search for approve status

    });
    $('#approved1').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session
      $.ajax({
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'approve',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po
        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#approved').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data[9]);
        }
      });

      setTimeout(function() {
        $('#myTable1').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
      // search for approve status

    });
    $('#approved1').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session
      $.ajax({
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'approve',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po
        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#approved').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data[9]);
        }
      });

      setTimeout(function() {
        $('#myTable1').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
      // search for approve status

    });
    $('#disbursement').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session $ find btn value
      $.ajax({
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'disbursement',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po

        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#disbursement').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data);
        }
      });
      // search for pending status
      setTimeout(function() {
        $('#myTable').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
    });

    $('#disburse').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session $ find btn value
      $.ajax({
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'disburse',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po

        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#disburse').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data);
        }
      });
      // search for pending status
      setTimeout(function() {
        $('#myTable').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
    });

    $('#rejected').click(function() {
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      // set status code in session $ find btn value
      $.ajax({
        url: "{{url('operation/statusSession')}}",
        type: 'POST',
        data: {
          status: 'reject',
          division: division,
          region: region,
          area: area,
          branch: branch,
          dateFrom: dateFrom,
          dateTo: dateTo,
          po: po

        },
        success: function(data) {
          $('.btn').removeClass('active');
          $('#rejected').addClass('active');
          $('.roll_class').removeClass('active');
          $('.pending_data').text(data[0]);
          $('#approved_data').text(data[1]);
          $('#disbursement_data').text(data[2]);
          $('#disburse_data').text(data[3]);
          $('#rejected_data').text(data[4]);
          $('#btn_bm').text(data[5]);
          $('#btn_am').text(data[6]);
          $('#btn_rm').text(data[7]);
          $('#btn_dm').text(data[8]);
          $('#totalLoan').text(data[9]);
          $('#totalAdmission').text(data[10]);
          $('#total_disbuse').text(data[11]);
          console.log(data);
        }
      });
      // search for pending status
      setTimeout(function() {
        $('#myTable').DataTable().destroy();
        fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
      }, 2500);
    });

    // roll button******************************************************
    $('#roll_am').click(function() {
      // active button
      $('.roll_class').removeClass('active');
      $('#roll_am').addClass('active');

      // search for pending status
      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      var projectcode = $('#projectcode').val();
      // alert(projectcode);
      if (projectcode == '060') {
        var roll = '1';
      } else {
        var roll = '2';
      }


      $('#myTable').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po, roll);

    });
    $('#roll_rm').click(function() {
      // active button
      $('.roll_class').removeClass('active');
      $('#roll_rm').addClass('active');

      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      var projectcode = $('#projectcode').val();
      if (projectcode == '060') {
        var roll = '2';
      } else {
        var roll = '3';
      }
      // search for pending status
      $('#myTable').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po, roll);
    });
    $('#roll_dm').click(function() {
      // active button
      $('.roll_class').removeClass('active');
      $('#roll_dm').addClass('active');

      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      var projectcode = $('#projectcode').val();
      if (projectcode == '060') {
        var roll = '3';
      } else {
        var roll = '4';
      }
      // search for pending status
      $('#myTable').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po, roll);
    });
    $('#roll_bm').click(function() {
      // active button
      $('.roll_class').removeClass('active');
      $('#roll_bm').addClass('active');

      var division = $('#division').val();
      var region = $('#region').val();
      var area = $('#area').val();
      var branch = $('#branch').val();
      var dateFrom = $('#dateFrom').val();
      var dateTo = $('#dateTo').val();
      var po = $('#po').val();
      var roll = '1';
      // search for pending status
      $('#myTable').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po, roll);
    });

    // filter search*************************************************
    $('#division').on('change', function() {
      if ($(this).val() != '') {
        var division = $(this).val();
        $.ajax({
          url: "{{url('operation/admission-division')}}",
          type: 'POST',
          data: {
            "_token": "{{ csrf_token() }}",
            division: division,
          },
          success: function(data) {
            console.log(data);
            $("#region").empty();
            $(`<option value="">Select</option>`).appendTo("#region");
            $.each(data, function(key, value) {
              $("#region").append(`<option value="` + value
                .region_id + `">` + value.region_id + `-` +
                value.region_name +
                `</option>`)

            });
          }
        });
      }
    });

    $('#region').on('change', function() {
      if ($(this).val() != '') {
        var region = $(this).val();
        $.ajax({
          url: "{{url('operation/admission-region')}}",
          type: 'POST',
          data: {
            "_token": "{{ csrf_token() }}",
            region: region,
          },
          success: function(data) {
            console.log(data);
            $("#area").empty();
            $(`<option value="">Select</option>`).appendTo("#area");
            $.each(data, function(key, value) {
              $("#area").append(`<option value="` + value.area_id +
                `">` + value.area_id + `-` + value.area_name +
                `</option>`)
            });
          }
        });
      }
    });

    $('#area').on('change', function() {
      if ($(this).val() != '') {
        var area = $(this).val();

        $.ajax({
          url: "{{url('operation/admission-area')}}",
          type: 'POST',
          data: {
            "_token": "{{ csrf_token() }}",
            area: area,
          },
          success: function(data) {
            $("#branch").empty();
            $(`<option value="">Select</option>`).appendTo("#branch");
            $.each(data, function(key, value) {
              $("#branch").append(` <option value="` + value
                .branch_id + `">` + value.branch_id + `-` +
                value.branch_name +
                `</option>`)

            });
          }
        });
      }
    });

    $('#branch').on('change', function() {
      if ($(this).val() != '') {
        var branch = $(this).val();

        $.ajax({
          url: "{{url('operation/admission-branch')}}",
          type: 'POST',
          data: {
            "_token": "{{ csrf_token() }}",
            branch: branch,
          },
          success: function(data) {
            $("#po").empty();
            $(`<option value="">Select</option>`).appendTo("#po");
            $.each(data, function(key, value) {
              $("#po").append(` <option value="` + value
                .cono + `">` + value.cono + `-` + value.coname +
                `</option>`)

            });
          }
        });
      }
    });

  });
  // status btn action*******************************************************




  $('#disbursement1').click(function() {
    var division = $('#division').val();
    var region = $('#region').val();
    var area = $('#area').val();
    var branch = $('#branch').val();
    var dateFrom = $('#dateFrom').val();
    var dateTo = $('#dateTo').val();
    var po = $('#po').val();
    // set status code in session $ find btn value
    $.ajax({
      url: "{{url('operation/statusSession')}}",
      type: 'POST',
      data: {
        status: 'disbursement',
        division: division,
        region: region,
        area: area,
        branch: branch,
        dateFrom: dateFrom,
        dateTo: dateTo,
        po: po

      },
      success: function(data) {
        $('.btn').removeClass('active');
        $('#disbursement').addClass('active');
        $('.roll_class').removeClass('active');
        $('.pending_data').text(data[0]);
        $('#approved_data').text(data[1]);
        $('#disbursement_data').text(data[2]);
        $('#disburse_data').text(data[3]);
        $('#rejected_data').text(data[4]);
        $('#btn_bm').text(data[5]);
        $('#btn_am').text(data[6]);
        $('#btn_rm').text(data[7]);
        $('#btn_dm').text(data[8]);
        $('#totalLoan').text(data[9]);
        $('#totalAdmission').text(data[10]);
        $('#total_disbuse').text(data[11]);
        console.log(data);
      }
    });
    // search for pending status
    setTimeout(function() {
      $('#myTable1').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
    }, 2500);
  });

  $('#disburse1').click(function() {
    var division = $('#division').val();
    var region = $('#region').val();
    var area = $('#area').val();
    var branch = $('#branch').val();
    var dateFrom = $('#dateFrom').val();
    var dateTo = $('#dateTo').val();
    var po = $('#po').val();
    // set status code in session $ find btn value
    $.ajax({
      url: "{{url('operation/statusSession')}}",
      type: 'POST',
      data: {
        status: 'disburse',
        division: division,
        region: region,
        area: area,
        branch: branch,
        dateFrom: dateFrom,
        dateTo: dateTo,
        po: po

      },
      success: function(data) {
        $('.btn').removeClass('active');
        $('#disburse').addClass('active');
        $('.roll_class').removeClass('active');
        $('.pending_data').text(data[0]);
        $('#approved_data').text(data[1]);
        $('#disbursement_data').text(data[2]);
        $('#disburse_data').text(data[3]);
        $('#rejected_data').text(data[4]);
        $('#btn_bm').text(data[5]);
        $('#btn_am').text(data[6]);
        $('#btn_rm').text(data[7]);
        $('#btn_dm').text(data[8]);
        $('#totalLoan').text(data[9]);
        $('#totalAdmission').text(data[10]);
        $('#total_disbuse').text(data[11]);
        console.log(data);
      }
    });
    // search for pending status
    setTimeout(function() {
      $('#myTable1').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
    }, 2500);
  });

  $('#rejected1').click(function() {
    var division = $('#division').val();
    var region = $('#region').val();
    var area = $('#area').val();
    var branch = $('#branch').val();
    var dateFrom = $('#dateFrom').val();
    var dateTo = $('#dateTo').val();
    var po = $('#po').val();
    // set status code in session $ find btn value
    $.ajax({
      url: "{{url('operation/statusSession')}}",
      type: 'POST',
      data: {
        status: 'reject',
        division: division,
        region: region,
        area: area,
        branch: branch,
        dateFrom: dateFrom,
        dateTo: dateTo,
        po: po

      },
      success: function(data) {
        $('.btn').removeClass('active');
        $('#rejected').addClass('active');
        $('.roll_class').removeClass('active');
        $('.pending_data').text(data[0]);
        $('#approved_data').text(data[1]);
        $('#disbursement_data').text(data[2]);
        $('#disburse_data').text(data[3]);
        $('#rejected_data').text(data[4]);
        $('#btn_bm').text(data[5]);
        $('#btn_am').text(data[6]);
        $('#btn_rm').text(data[7]);
        $('#btn_dm').text(data[8]);
        $('#totalLoan').text(data[9]);
        $('#totalAdmission').text(data[10]);
        $('#total_disbuse').text(data[11]);
        console.log(data);
      }
    });
    // search for pending status
    setTimeout(function() {
      $('#myTable1').DataTable().destroy();
      fill_datatable(division, region, area, branch, dateFrom, dateTo, po);
    }, 2500);
  });
</script>
@endsection
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<script src="cdn.datatables.net/1.11.1/js/jquery.dataTables.min.js"></script>