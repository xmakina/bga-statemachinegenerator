<?php

declare(strict_types=1);

require_once('./functions.php');

const STATE_FUNCTION = "actionFunction";
const ARG_FUNCTION = "argFunction";
const POSSIBLE_ACTION_FUNCTIONS = "possibleActionFunctions";
const STATE_STRING = "state_string";

$inFilePath = isset($argv[1]) ? $argv[1] : "statemachine.dot";
$outFolder = isset($argv[2]) ? $argv[2] : "possible";

$graph = parseDotFile($inFilePath);

$constCount = 5;
$states = $graph->getVertices();

$const_ids = makeIdentifiers($states);

$builtStates = buildStates($states, $const_ids);
$FIRST_STATE_MACHINE_LINES = ["include_once(\"consts.inc.php\");", '$machinestates = ['];
$FINAL_STATE_MACHINE_LINES = ['];'];

$stateFunctions = [];
$argFunctions = [];
$possibleActionFunctions = [];
$stateStrings = [];

foreach ($builtStates as $state) {
    array_push($stateFunctions, $state[STATE_FUNCTION]);
    array_push($argFunctions, $state[ARG_FUNCTION]);
    array_push($stateStrings, $state[STATE_STRING]);

    foreach ($state[POSSIBLE_ACTION_FUNCTIONS] as $possibleActionFunction) {
        if (in_array($possibleActionFunction, $possibleActionFunctions)) {
            continue;
        }

        array_push($possibleActionFunctions, $possibleActionFunction);
    }
}

$constStrings = array_filter(array_map('buildConstString', $const_ids, range(1, 99, 5)), function ($value) {
    return strlen(trim($value)) > 0;
});

if (!is_dir($outFolder)) {
    mkdir($outFolder);
}

writeResult(array_merge(...$stateFunctions), "./$outFolder/stateactions.php");
writeResult(array_merge(...$argFunctions), "./$outFolder/argFunctions.php");
writeResult($possibleActionFunctions, "./$outFolder/playeractions.php");
writeResult($constStrings, "./$outFolder/consts.php");
writeResult([...$FIRST_STATE_MACHINE_LINES, implode(",\n", $stateStrings), ...$FINAL_STATE_MACHINE_LINES], "./$outFolder/states.php");
