<?php
require './dot_file_parser.php';

use DotParser\DotParser;

class StateMachineOutput
{
    public $machine;
    function set_machine($machine)
    {
        $this->$machine = $machine;
    }
}

function buildFunctionFor($name)
{
    $trimName = trim($name);

    if (strlen($trimName) === 0) {
        return "";
    }

    return "function $trimName()\n{\nself::checkAction('$trimName');\n}";
}

function buildState($id, $const_id, $type, $description, $descriptionmyturn, $possibleActions, $transitions, $updateGameProgression = false)
{
    $result = [];
    $ucFirstId = ucfirst($id);
    $possibleActionsString = '"' . join('", "', array_map('trim', $possibleActions)) . '"';

    $result[STATE_FUNCTION] = ["function st$ucFirstId() { }"];   // called on entering state
    $result[ARG_FUNCTION] = ["function arg$ucFirstId() { }"];     // returns state values
    $result[POSSIBLE_ACTION_FUNCTIONS] = array_filter(array_map('buildFunctionFor', $possibleActions), function ($builtFunction) {
        return strlen($builtFunction) > 0;
    });
    $result[STATE_STRING] = "$const_id => [
        \"name\" => \"$id\",
        \"description\" => clienttranslate('$description'),
        \"descriptionmyturn\" => clienttranslate('$descriptionmyturn'),
        \"type\" => \"$type\",
        \"action\" => \"st$ucFirstId\",
        \"args\" => \"arg$ucFirstId\",
        \"possibleactions\" => [$possibleActionsString],
        \"transitions\" => [$transitions]";

    if($updateGameProgression) {
        $result[STATE_STRING] .= "\"updateGameProgression\" => true";
    }

    $result[STATE_STRING] .= "]";

    return $result;
}

function makeIdentifiers($states)
{
    $result = [];
    foreach ($states as $state) {
        $id = $state->getId();
        $snake_case = preg_replace(
            '/(?<!^)([A-Z][a-z]|(?<=[a-z])[^a-z]|(?<=[A-Z])[0-9_])/',
            '_$1',
            $id
        );
        $snake_case = strtoupper($snake_case);
        $result[$id] = $snake_case;
    }
    return $result;
}

function buildStates($states, $const_ids)
{
    $builtStates = [];
    foreach ($states as $state) {
        $stateId = $state->getId();
        $transitionString = "";

        $transitions = [];
        foreach ($state->getVerticesEdgeTo() as $destination) {
            $destinationId = $destination->getId();
            foreach ($state->getEdgesTo($destination) as $transition) {
                $label = $transition->getAttribute("label", '');

                $transition = "\"" . $label . "\" => " .  $const_ids[$destinationId];
                if (in_array($transition, $transitions) == false) {
                    array_push($transitions, $transition);
                }
            }
        }

        $transitionString .= implode(', ', $transitions);

        $type = $state->getAttribute('type') ?? "NOT_SPECIFIED";
        $description = $state->getAttribute('description') ?? "NOT_SPECIFIED";
        $descriptionmyturn = $state->getAttribute('descriptionmyturn') ?? "NOT_SPECIFIED";
        $possibleActions = explode(',', $state->getAttribute('possibleActions') ?? "");

        $const_id = $const_ids[$stateId];
        $builtState = buildState($stateId, $const_id, $type, $description, $descriptionmyturn, $possibleActions, $transitionString);
        $builtStates[$stateId] = $builtState;
    }

    return $builtStates;
}

function cleanDotFile($inFilePath)
{
    $inFile = fopen($inFilePath, "r") or die("Unable to open $inFilePath!");
    $inFileContents = '';
    while (!feof($inFile)) {
        $line = fgets($inFile);
        $inFileContents .= "\n$line";
    }

    //  Removes multi-line comments and does not create
    //  a blank line, also treats white spaces/tabs 
    $inFileContents = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $inFileContents);
    $inFileContents = preg_replace('!/\*.*?\*/!s', '', $inFileContents);
    //  Removes single line '//' comments, treats blank characters
    $inFileContents = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $inFileContents);
    //  Strip blank lines
    $inFileContents = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $inFileContents);
    return $inFileContents;
}

function writeResult($result, $outFilePath)
{
    $outFile = fopen($outFilePath, "w") or die("Unable to open $outFilePath!");
    fwrite($outFile, "<?php\n");
    fwrite($outFile, implode("\n", $result));
    fclose($outFile);
}

function parseDotFile($inFilePath)
{
    $inFileContents = cleanDotFile($inFilePath);
    $parser = new DotParser();
    return $parser->parseDotFile($inFileContents);
}

function buildConstString($const, $value)
{
    switch ($const) {
        case "GAME_SETUP":
            return "const GAME_SETUP = 1;";
        case "GAME_END":
            return "const GAME_END = 99;";
        case "":
            return "";
        default:
            return "const $const = $value;";
    }
}
