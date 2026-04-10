<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a2e; }
  .header { background: #3b7ab8; color: white; padding: 20px 24px; margin-bottom: 20px; }
  .header h1 { font-size: 18px; font-weight: 700; }
  .header p { font-size: 11px; margin-top: 4px; opacity: 0.85; }
  .container { padding: 0 24px 24px; }
  .meta { display: flex; gap: 24px; margin-bottom: 16px; }
  .meta-item { }
  .meta-item .label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7a99; }
  .meta-item .value { font-size: 12px; color: #1a1a2e; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  th { background: #f5f7fa; text-align: left; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7a99; border-bottom: 2px solid #e8edf4; }
  td { padding: 8px 10px; border-bottom: 1px solid #f0f2f5; font-size: 11px; color: #374151; }
  tr:nth-child(even) td { background: #fafbfc; }
  .rate-high { color: #15803d; font-weight: 600; }
  .rate-mid { color: #b45309; font-weight: 600; }
  .rate-low { color: #dc2626; font-weight: 600; }
  .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e8edf4; font-size: 10px; color: #9ca3af; }
</style>
</head>
<body>
  <div class="header">
    <h1>{{ $title }}</h1>
    <p>Securecy LMS &bull; Generated {{ now()->toFormattedDateString() }}</p>
  </div>

  <div class="container">
    <table>
      <thead>
        <tr>
          @foreach ($columns as $col)
            <th>{{ $col }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $row)
          <tr>
            @foreach ($row as $i => $cell)
              <td>
                @if ($i === 'completion_rate' || $i === 'pass_rate' || $i === 'correct_rate')
                  <span class="{{ $cell >= 80 ? 'rate-high' : ($cell >= 50 ? 'rate-mid' : 'rate-low') }}">
                    {{ $cell }}%
                  </span>
                @else
                  {{ $cell ?? '—' }}
                @endif
              </td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="footer">
      Total records: {{ count($rows) }} &bull; Report type: {{ $reportType }} &bull; Export ID: {{ $exportId }}
    </div>
  </div>
</body>
</html>
