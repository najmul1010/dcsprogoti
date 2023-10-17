<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.Personal_Asset')</th>
  </tr>
  <tr>
    <td rowspan="12"></td>
    <td>@lang('loanApproval.TotalCurrentPrie')</td>
    <td rowspan="12"></td>
    <td align="right" colspan="2">
      {{$personal_asset_info[0]->total_current_price ?? null}}
    </td>
    <td rowspan="12"></td>
  </tr>
</table>
<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.BankInfo')</th>
  </tr>
  <tr>
    <td rowspan="12"></td>
    <td>@lang('loanApproval.BankName')</td>
    <td rowspan="12"></td>
    <td colspan="2">
      {{ $bank_info[0]->bank_name ?? null}}
    </td>
    <td rowspan="12"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Branch')</td>
    <td colspan="2">
      {{ $bank_info[0]->branch ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.AccountName')</td>
    <td colspan="2">
      {{ $bank_info[0]->account_name ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.AccountType')</td>
    <td colspan="2">
      {{ $bank_info[0]->account_type ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.AccountNo')</td>
    <td colspan="2">
      {{ $bank_info[0]->account_no ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.CheckPhoto')</td>
    <td colspan="2">
      <a href="{{ $bank_info[0]->bank_cheque_photo ?? null}}" target="_blank">Image</a>
    </td>
  </tr>
</table>
<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.MonthlyIncome')</th>
  </tr>
  <tr>
    <td rowspan="12"></td>
    <td>@lang('loanApproval.BusinessIncome')</td>
    <td rowspan="12"></td>
    <td align="right" colspan="2">
      {{$income_info[0]->business_income ?? null}}
    </td>
    <td rowspan="12"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.JobIncome')</td>
    <td align="right" colspan="2">
      {{$income_info[0]->job_income ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.FamilyMembersIncome')</td>
    <td align="right" colspan="2">
      {{$income_info[0]->family_member_income ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.OthersBusinessIncome')</td>
    <td align="right" colspan="2">
      {{$income_info[0]->others_business_income ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.RemittanceIncome')</td>
    <td align="right" colspan="2">
      {{$income_info[0]->remittance_income ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.OtherIncome')</td>
    <td align="right" colspan="2">
      {{$income_info[0]->others_income ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.TotalIncome')</td>
    <td align="right" colspan="2">
      {{$income_info[0]->total_income ?? null}}
    </td>
  </tr>
</table>
<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.MonthlyExpenses')</th>
  </tr>
  <tr>
    <td rowspan="12"></td>
    <td>@lang('loanApproval.HouseRent')</td>
    <td rowspan="12"></td>
    <td align="right" colspan="2">
      {{$expenses_info[0]->house_rent ?? null}}
    </td>
    <td rowspan="12"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.UtilityBill')</td>
    <td align="right" colspan="2">
      {{$expenses_info[0]->utility_bil ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.HealthAndEducationExpenses')</td>
    <td align="right" colspan="2">
      {{$expenses_info[0]->health_education_expns ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.OthersDailyExpenses')</td>
    <td align="right" colspan="2">
      {{$expenses_info[0]->others_daily_expns ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.BankInstallmentSavings')</td>
    <td align="right" colspan="2">
      {{$expenses_info[0]->bank_loan_instlmnt_savings ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.TotalExpenses')</td>
    <td align="right" colspan="2">
      {{$expenses_info[0]->total_expns ?? null}}
    </td>
  </tr>
</table>
<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.GuarantorDetails1')</th>
  </tr>
  <tr>
    <td rowspan="14"></td>
    <td>@lang('loanApproval.Name')</td>
    <td rowspan="14"></td>
    <td colspan="2">
      {{ $gurantor[0]->name ?? null }}
    </td>
    <td rowspan="14"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.FatherName')</td>
    <td colspan="2">
      {{$gurantor[0]->father_husband_name ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.NIDNo')</td>
    <td colspan="2">
      {{$gurantor[0]->nid_no ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.DOB')</td>
    <td colspan="2">
      {{$gurantor[0]->dob ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Occupation')</td>
    <td colspan="2">
      {{$gurantor[0]->occupation ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.PresentAddress')</td>
    <td colspan="2">
      {{$gurantor[0]->present_address ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.PermanentAddress')</td>
    <td colspan="2">
      {{$gurantor[0]->parmanent_address ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.MobileNo')</td>
    <td colspan="2">
      {{$gurantor[0]->mobile_no ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.MonthlyIncome')</td>
    <td align="right" colspan="2">
      {{$gurantor[0]->monthly_income ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.MonthlyExpenses')</td>
    <td align="right" colspan="2">
      {{$gurantor[0]->monthly_expenses ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.GuarantorImage')</td>
    <td colspan="2">
      <a href="{{$gurantor[0]->guarantor_photo ?? null}}">Image</a>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Nid1st')</td>
    <td colspan="2">
      <a href="{{$gurantor[0]->guarantor_nid_front ?? null}}">Image</a>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Nid2nd')</td>
    <td colspan="2">
      <a href="{{$gurantor[0]->guarantor_nid_back ?? null}}">Image</a>
    </td>
  </tr>
</table>
<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.GuarantorDetails2')</th>
  </tr>
  <tr>
    <td rowspan="14"></td>
    <td>@lang('loanApproval.Name')</td>
    <td rowspan="14"></td>
    <td colspan="2">
      {{ $gurantor[0]->name2 ?? null }}
    </td>
    <td rowspan="14"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.FatherName')</td>
    <td colspan="2">
      {{$gurantor[0]->father_husband_name2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.NIDNo')</td>
    <td colspan="2">
      {{$gurantor[0]->nid_no2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.DOB')</td>
    <td colspan="2">
      {{$gurantor[0]->dob2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Occupation')</td>
    <td colspan="2">
      {{$gurantor[0]->occupation2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.PresentAddress')</td>
    <td colspan="2">
      {{$gurantor[0]->present_address2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.PermanentAddress')</td>
    <td colspan="2">
      {{$gurantor[0]->parmanent_address2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.MobileNo')</td>
    <td colspan="2">
      {{$gurantor[0]->mobile_no2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.MonthlyIncome')</td>
    <td align="right" colspan="2">
      {{$gurantor[0]->monthly_income2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.MonthlyExpenses')</td>
    <td align="right" colspan="2">
      {{$gurantor[0]->monthly_expenses2 ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.GuarantorImage')</td>
    <td colspan="2">
      <a href="{{$gurantor[0]->guarantor_photo2 ?? null}}">Image</a>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Nid1st')</td>
    <td colspan="2">
      <a href="{{$gurantor[0]->guarantor_nid_front2 ?? null}}">Image</a>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Nid2nd')</td>
    <td colspan="2">
      <a href="{{$gurantor[0]->guarantor_nid_back2 ?? null}}">Image</a>
    </td>
  </tr>
</table>
<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.BusinessTitleName')</th>
  </tr>
  <tr>
    <td rowspan="14"></td>
    <td>@lang('loanApproval.Business_Name')</td>
    <td rowspan="14"></td>
    <td colspan="2">
      {{ $business_info [0]->bussiness_name ?? null }}
    </td>
    <td rowspan="14"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Address')</td>
    <td colspan="2">
      {{$business_info [0]->bussiness_address ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Founding_Period')</td>
    <td colspan="2">
      {{$business_info [0]->founding_period ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Business_Ownership_Type')</td>
    <td colspan="2">
      {{$business_info [0]->business_own_type ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Business_Nature')</td>
    <td colspan="2">
      {{$business_info [0]->business_type ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Employee_No')</td>
    <td colspan="2">
      {{$business_info [0]->employees_no ?? null}}
    </td>
  </tr>
</table>