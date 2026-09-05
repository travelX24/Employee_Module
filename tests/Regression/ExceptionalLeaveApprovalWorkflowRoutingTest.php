<?php

$controllerFile = dirname(__DIR__, 2)
    . '/src/Http/Controllers/Api/EmployeeController.php';

$code = file_get_contents($controllerFile);

if ($code === false) {
    fwrite(STDERR, "FAIL: EmployeeController.php could not be read\n");
    exit(1);
}

$start = strpos(
    $code,
    'public function createLeaveRequest(Request $request)'
);

$end = strpos(
    $code,
    'public function permissionPolicy(Request $request)',
    $start === false ? 0 : $start
);

if ($start === false || $end === false) {
    fwrite(STDERR, "FAIL: createLeaveRequest() could not be isolated\n");
    exit(1);
}

$method = substr($code, $start, $end - $start);

$checks = [
    'APPROVAL TYPE DEPENDS ON IS_EXCEPTION'
        => preg_match(
            '/\$approvalType\s*=\s*!empty\(\$data\[[\'"]is_exception[\'"]\]\)\s*\?\s*[\'"]leave_exceptions[\'"]\s*:\s*[\'"]leaves[\'"]/',
            $method
        ) === 1,

    'WORKFLOW VALIDATION USES DYNAMIC TYPE'
        => str_contains(
            $method,
            'hasApproversForEmployee($approvalType'
        ),

    'ACTIVE POLICY CHECK USES DYNAMIC TYPE'
        => str_contains(
            $method,
            'hasActivePolicies($approvalType'
        ),

    'TASK DISPATCH USES DYNAMIC TYPE'
        => str_contains(
            $method,
            'GenerateApprovalTasksJob::dispatch($approvalType'
        ),

    'NO HARDCODED LEAVES VALIDATION REMAINS'
        => !str_contains(
            $method,
            "hasApproversForEmployee('leaves'"
        ),

    'NO HARDCODED LEAVES DISPATCH REMAINS'
        => !str_contains(
            $method,
            "GenerateApprovalTasksJob::dispatch('leaves'"
        ),
];

$failed = false;

foreach ($checks as $label => $ok) {
    echo ($ok ? 'PASS: ' : 'FAIL: ') . $label . PHP_EOL;

    if (!$ok) {
        $failed = true;
    }
}

exit($failed ? 1 : 0);