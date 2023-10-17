<table class="table table-bordered">
  <tr class="font_red">
    <th colspan="6">@lang('loanApproval.CheckList')</th>
  </tr>
  <tr>
    <td>@lang('loanApproval.Is_the_family_member_know_about_the_loan')</td>
    <td colspan="2">
      {{ $trade_checklist[0]->family_member_know_about_loan ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Loan_borrower_know_the_condition_loan')</td>
    <td colspan="2">
      {{ $trade_checklist[0]->borrower_know_about_loan_condition	 ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.sector_of_loan_money')</td>
    <td colspan="2">
      {{ $trade_checklist[0]->loan_money_correct_use ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.the_project_location')</td>
    <td colspan="2">
      {{ $trade_checklist[0]->project_profitable_location ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.have_both_guartors')</td>
    <td colspan="2">
      {{ $trade_checklist[0]->guarantors_informed_obligation ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.loan_sector')</td>
    <td colspan="2">
      {{ $trade_checklist[0]->profitable_loan_sector ?? null }}
    </td>
  </tr>
</table>