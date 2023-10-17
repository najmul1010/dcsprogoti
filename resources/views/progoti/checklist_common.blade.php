<table class="table table-bordered">
  <tr class="font_red">
    <th colspan="6">@lang('loanApproval.Common_checkList')</th>
  </tr>
  <tr>
    <td>@lang('loanApproval.Commitment_letter')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->commitment_letter == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Collateral_Bond')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->collateral_bond == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Investigative_report_of_seized_property')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->investigate_report == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Bank_Statement')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->bank_statement == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Security_Check')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->security_check == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.original_Deed')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->original_deed == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Baya_Deed')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->baya_deed == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Pitt_Deed')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->pitt_deed == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Duplicate_document_with_withdrawal_receipt_of_original_Deed')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->duplicate_document == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Position_deed')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->position_deed == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.DCR')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->dcr == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Dismissal_form')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->dismissal_form == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.SA_original_paper')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->sa_orginal_paper == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.RS_original_paper')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->rs_orginal_paper == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Tax_receipt')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->tex_receipt == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Heir_Certificate')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->heir_certificate == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Shop_rent_advance_agreement')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->shop_rent == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Other')</td>
    <td colspan="2">
      <?php
      if ($common_checklist[0]->other == 1) {
        echo "Yes";
      } else {
        echo "No";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td>@lang('loanApproval.Remarks')</td>
    <td colspan="2">
      {{ $common_checklist[0]->remarks ?? null }}
    </td>
  </tr>
</table>