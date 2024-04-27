<?php
$dir = __DIR__ . '/log';
$files = scandir($dir);
$files = array_diff($files, array('.', '..'));

//------------------------------------------------------------------------------
//$filepath = __DIR__ . '/log/file';
//$logFile = fopen($filepath, "r") or die("Could not open file");
//------------------------------------------------------------------------------

$errorGroups = [];

$ip = '#([a-z]* *[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)#';
$ipMatches = [];

$date = '#\[([^\[]*)\]#'; //------------------------------------------------------------------------------ make less specific
$dateMatches = [];


$errorE = '#error#i';
$warnE ='#warn#i';
$noticeE = '#notice#i';
$numberError = 0;
$numberWarn = 0;
$numberNotice = 0;
$errorA = [];
$warnA = [];
$noticeA = [];

$code = '#" ([0-9]{3})[ |\n]#';
$code100 = 0;
$code200 = 0;
$code300 = 0;
$code400 = 0;
$code500 = 0;
$codeOther = 0;

//httpd.conf
//.htaccess

//missing permissions, files, or directories

//top, htop, or free

//'#[not found|unable to stat]#'

//database or api

//HTTP requests and responses, JavaScript errors, and other client-side issues

foreach ($files as $file) {
    $filePath = $dir . '/' . $file;
    $logFile = fopen($filePath, "r") or die("Could not open file: $file");

    while (!feof($logFile)) {
        $line = fgets($logFile);
        
        if (preg_match_all($ip, $line, $matches)) {
            foreach ($matches[0] as $matchedIp) {
                $ipMatches[$matchedIp] = ($ipMatches[$matchedIp] ?? 0) + 1;
            }
        }

        if (preg_match($date, $line, $matches)) {
            $dateStr = $matches[1];
            $dateMatches[$dateStr] = ($dateMatches[$dateStr] ?? 0) + 1;
        }

        $errorText = explode("] ", $line);
        $errorType = end($errorText);
        if (isset($errorText[1])) {
            if (!empty($errorType)) {
                $errorGroups[$errorType] = ($errorGroups[$errorType] ?? 0) + 1;
            }
        }

        if (preg_match($errorE, $line, $matches)) {
            $numberError++;
        }
        if (preg_match($warnE, $line, $matches)) {
            $numberWarn++;
        }
        if (preg_match($noticeE, $line, $matches)) {
            $numberNotice++;
        }

        if (preg_match($code, $line, $matches)) {
            $code2 = (int)$matches[1];
            switch (floor($code2 / 100)) {
                case 1:
                    $code100++;
                    break;
                case 2:
                    $code200++;
                    break;
                case 3:
                    $code300++;
                    break;
                case 4:
                    $code400++;
                    break;
                case 5:
                    $code500++;
                    break;
                default:
                    $codeOther++;
                    break;
            }
        }
    }

    fclose($logFile);
}

function drawAsciiTable($data, $title = 'Type') {
    if (empty($data)) {
        return;
    }

    $widths = [
        $title => strlen($title),
        'Count' => 5,
    ];

    foreach ($data as $key => $value) {
        $widths[$title] = max($widths[$title], strlen($key));
        $widths['Count'] = max($widths['Count'], strlen((string)$value));
    }

    $lineSeparator = '+' . str_repeat('-', $widths[$title] + 2) . '+' . str_repeat('-', $widths['Count'] + 2) . '+';

    echo $lineSeparator . "<br>";
    echo '| ' . str_pad($title, $widths[$title]) . ' | ' . str_pad('Count', $widths['Count']) . " |<br>";
    echo $lineSeparator . "<br>";

    foreach ($data as $key => $value) {
        if ($value > 4) { //------------------------------------------------------------------------------ more than 4
            echo '| ' . str_pad($key, $widths[$title]) . ' | ' . str_pad($value, $widths['Count']) . " |<br>";
        }
    }

    echo $lineSeparator . "<br>";
}

function drawSummaryTable($data, $title = 'Type') {
    if (!is_array($data) || empty($data)) {
        echo "No data available.<br>";
        return;
    }

    $countTitle = 'Count';
    $widths = [
        'Type' => strlen($title),
        'Count' => strlen($countTitle),
    ];

    foreach ($data as $key => $value) {
        $widths['Type'] = max($widths['Type'], strlen($key));
        $widths['Count'] = max($widths['Count'], strlen((string)$value));
    }

    $lineSeparator = '+' . str_repeat('-', $widths['Type'] + 2) . '+' . str_repeat('-', $widths['Count'] + 2) . '+';

    echo $lineSeparator . "<br>";
    echo '| ' . str_pad($title, $widths['Type']) . ' | ' . str_pad($countTitle, $widths['Count']) . " |<br>";
    echo $lineSeparator . "<br>";

    foreach ($data as $key => $value) {
        echo '| ' . str_pad($key, $widths['Type']) . ' | ' . str_pad($value, $widths['Count']) . " |<br>";
    }

    echo $lineSeparator . "<br>";
}

$statusCodeCounts = [
    '1XX, request in progress' => $code100,
    '2XX, request success' => $code200,
    '3XX, redirection' => $code300,
    '4XX, client-side error' => $code400,
    '5XX, server-side error' => $code500,
    'Other' => $codeOther,
];

$errorWarnNoticeCounts = [
    'Error(s)' => $numberError,
    'Warn(s)' => $numberWarn,
    'Notice(s)' => $numberNotice,
];

drawAsciiTable($errorGroups, 'Error Type');
echo "<br>";
drawAsciiTable($ipMatches, 'IP Address');
echo "<br>";
drawAsciiTable($dateMatches, 'Date');
echo "<br>";
drawSummaryTable($errorWarnNoticeCounts, "Error Types");
echo "<br>";
drawSummaryTable($statusCodeCounts, "Status Codes");
echo "<br>";
//$codeTotal = $code100 + $code200 + $code300 + $code400 + $code500 + $codeOther;
//echo $codeTotal;

?>
