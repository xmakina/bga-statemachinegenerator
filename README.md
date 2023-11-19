# BGA State Machine Generator
Create the BGA State Machine boilerplate from a DOT Graphviz diagram.

## Setting up
[Composer](https://getcomposer.org/doc/00-intro.md) is used to fetch various dependencies

## How To Use
After creating a state machine with the DOT language, you should also add:

* `label` to all edges (transitions) which are not the default transition of `""`
* `type`, `possibleActions`, `description` and `descriptionmyturn` to all verticies (states)

After creating a state machine in the [DOT Language](https://graphviz.org/doc/info/lang.html), run

`php generate_state_machine.php statemachine.dot outfolder`

This will generate the following in `outfolder`

## argFunctions.php
The state argument functions

## consts.php
The consts generated for each state number

## playeractions.php
Minimum boilerplate for the specified actions

## stateactions.php
Functions which are called upon entering each state

## states.php
The BGA State Machine, can replace `states.inc.php`

# Notes

A demo state machine for Eminent Domain has been included