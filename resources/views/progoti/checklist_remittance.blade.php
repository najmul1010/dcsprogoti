<table class="table table-bordered">
  <tr class="font_red">
    <th colspan="6">@lang('loanApproval.CheckList')</th>
  </tr>
  <tr>
    <td>@lang('loanApproval.Is_the_family_member_know_about_the_loan')</td>
    <td colspan="2">
      <?php
      if ($checklist[0]->family_member_know_about_loan == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Is_loaner_know_about_the_loan')</td>
    <td colspan="2">
      <?php
      if ($checklist[0]->loaner_know_about_loan == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Did_you_tell_the_Guaranto_about_their_responsibility')</td>
    <td colspan="2">
      <?php
      if ($checklist[0]->foriegn_resident_about_loan == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Did_you_talk_about_the_loan_to_the_foregin_resident')</td>
    <td colspan="2">
      <?php
      if ($checklist[0]->grantor_about_responsibility == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.What_is_the_maximum_loan_repayment_amount')</td>
    <td colspan="2">
      <?php
      if ($checklist[0]->loan_repayment_amount == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Is_the_loaner_and_ther_esident_of_abroad_is_permanent')</td>
    <td colspan="2">
      <?php
      if ($checklist[0]->loaner_and_abroad_resident_permanent == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
</table>