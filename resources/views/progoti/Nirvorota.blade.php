<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.GuarantorDetails1')</th>
  </tr>
  <tr>
    <td rowspan="14"></td>
    <td>@lang('loanApproval.name')</td>
    <td rowspan="14"></td>
    <td colspan="2">
      {{$gurantor[0]->name ?? null}}
    </td>
    <td rowspan="12"></td>
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
    <th colspan="7" class="bgColor">@lang('loanApproval.co_borrower')</th>
  </tr>
  <tr>
    <td rowspan="12"></td>
    <td>@lang('loanApproval.name')</td>
    <td rowspan="12"></td>
    <td colspan="2">
      {{$co_borrower_details[0]->name ?? null}}
    </td>
    <td rowspan="12"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.label36')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->relationship ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.NIDBirthCertificateNo')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->nid_birth_certificate ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.OrganizationName')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->organization_name ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.header3')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->organization_address ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Designation')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->designation ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.MobileNo')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->mobile_no ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Typeofjob')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->type_of_job ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.PresentAddress')</td>
    <td colspan="2">
      {{$co_borrower_details[0]->present_address ?? null}}
    </td>
  </tr>
</table>
<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.LoanerOfficeInformation')</th>
  </tr>
  <tr>
    <td rowspan="12"></td>
    <td>@lang('loanApproval.OrganizationName')</td>
    <td rowspan="12"></td>
    <td colspan="2">
      {{$borrower_office_info[0]->organization_name ?? null }}
    </td>
    <td rowspan="12"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.header3')</td>
    <td colspan="2">
      {{$borrower_office_info[0]->organization_address ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.DateofJoining')</td>
    <td colspan="2">
      {{$borrower_office_info[0]->date_of_joining ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.PresentDesignation')</td>
    <td colspan="2">
      {{$borrower_office_info[0]->present_designation ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Typeofjob')</td>
    <td colspan="2">
      {{$borrower_office_info[0]->type_of_job ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.PayslipSalaryBookRegisterbookphoto')</td>
    <td colspan="2">
      <a href="{{$borrower_office_info[0]->pay_slip ?? null }}">Image</a>
    </td>
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
    <td>@lang('loanApproval.MonthlyIncome')</td>
    <td rowspan="12"></td>
    <td align="right" colspan="2">
      {{$income_info[0]->monthly_income ?? null}}
    </td>
    <td rowspan="12"></td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Co_Borrower_monthlyincome')</td>
    <td align="right" colspan="2">
      {{$income_info[0]->co_borrower_monthly_income ?? null}}
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
<!--<table class="table table-bordered">
  <tr>
    <th colspan="7" class="bgColor">@lang('loanApproval.MonthlyExpenses')</th>
  </tr>
  <tr>
    <td rowspan="12"></td>
    <td>@lang('loanApproval.Expenditure_food')</td>
    <td rowspan="12"></td>
    <td colspan="2">
      {{$expenses_info[0]->food_expenditure ?? null}}
    </td>
    <td rowspan="12"></td>
  </tr>
  </tr>
  <tr>
    <td>@lang('loanApproval.HouseRentUtility_Bill')</td>
    <td colspan="2">
      {{$expenses_info[0]->house_utility_bil ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.HealthAndMedicineExpenditure')</td>
    <td colspan="2">
      {{$expenses_info[0]->health_medicine_expns ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Education_Expenditure')</td>
    <td colspan="2">
      {{$expenses_info[0]->education_expenditure ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.DailyExpenses')</td>
    <td colspan="2">
      {{$expenses_info[0]->daily_expns ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.BankInstallmentSavings')</td>
    <td colspan="2">
      {{$expenses_info[0]->bank_loan_instlmnt_savings ?? null}}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.TotalExpenses')</td>
    <td colspan="2">
      {{$expenses_info[0]->total_expns ?? null}}
    </td>
  </tr>
</table>-->