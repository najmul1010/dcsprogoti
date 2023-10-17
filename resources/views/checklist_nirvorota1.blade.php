<table class="table table-bordered">
  <tr class="font_red">
    <th colspan="6">@lang('loanApproval.CheckList')</th>
  </tr>
  <tr>
    <td>@lang('loanApproval.the_guarantor_been_personally_met_and_discussed')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->guarantor_met_and_discussed_about_liability ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.employment_certificates_and_pay_slips')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->employment_certificates_payslips_other_verified ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.the_national_identity_card_and_photo_of_the_loan_related_identification')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->loan_related_everyone_nid_and_photo_verified ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Has_the_applicant_been_working_in_the_current_organization')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->applicant_working_current_organization_1year ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Is_there_any_other_loan_pending')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->loan_pending_same_house_family_of_applicant ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Have_the_necessary_check_sheets_been_collected')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->necessary_check_sheets_collected ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Is_the_loan_applicant_guarantor')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->loan_applicant_guarantor_of_nirvorota_checklist_loan	 ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Have_two_reference_persons_been_contacted')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->two_reference_persons_contacted ?? null }}
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Is_the_amount_of_the_loan_application_nature')</td>
    <td colspan="2">
      {{ $nirvorota_checklist[0]->amount_nature_and_type_of_application_fair ?? null }}
    </td>
  </tr>
</table>