<table style="text-align: center;" class="table table-bordered" id="detailed_table">

    <thead>
        <tr class="brac-color">
            <th>@lang('report.table_header7')</th>
            <th>@lang('report.table_header8')</th>
            <th>@lang('report.table_header9')</th>
            <th>@lang('report.table_header10')</th>
            <th>@lang('report.table_header11')</th>
            <th>@lang('report.table_header12')</th>
        </tr>
    </thead>
    <tbody>
        @if (!empty($loanData))
        @foreach ($loanData as $row)
        <tr>
            <td>{{ $row['branchname'] }}</td>
            <td>{{ $row['poname'] }}</td>
            <td>{{ $row['applicationdate'] }}</td>
            <td>{{ $row['disbamnt'] }}</td>
            <td>{{ $row['productname'] }}</td>
            <td>{{ $row['appliedby'] }}</td>
        </tr>
        @endforeach
        @endif


    </tbody>
</table>