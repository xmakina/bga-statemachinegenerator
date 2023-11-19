<?php

namespace DotParser;

require_once 'vendor/autoload.php';

class DotParser
{
    private $grammar = <<<DOT_GRAMMAR
    graph :=> 'strict'? ('graph'|'digraph') ID? '{' stmt_list '}'.
    ID :=> /"(.*?)"|([-]?(.[0-9]+|[0-9]+(.[0-9]*)?))|([a-zA-Z\200-\377_][0-9a-zA-Z\200-\377_]*)/.
    stmt_list :=> (stmt ';'? stmt_list)?.
    stmt :=> node_stmt
        :=> edge_stmt
        :=> attr_stmt
        :=> ID '=' ID
        :=> subgraph.
    attr_stmt :=> ('graph'|'node'|'edge') attr_list.
    attr_list :=> '[' a_list? ']' attr_list?.
    a_list :=> ID '=' ID (';' | ',')? a_list?.
    edge_stmt :=> (node_id | subgraph) edgeRHS attr_list?.
    edgeRHS :=> edgeop (node_id | subgraph) edgeRHS?.
    edgeop :=> ('->'|'--').
    node_stmt :=> node_id attr_list?.
    node_id :=> ID port?.
    port :=> ':' ID (':' compass_pt)?
        :=> ':' compass_pt.
    subgraph :=> (subgraph ID?)? '{' stmt_list '}'.
    compass_pt :=> ('n'|'ne'|'e'|'se'|'s'|'sw'|'w'|'nw'|'c'|'_').
    DOT_GRAMMAR;

    function cleanValue(\ParserGenerator\SyntaxTreeNode\Base $node)
    {
        return trim($node->toString(), '"\'');
    }

    function parseAttributes($stmt, \Fhaculty\Graph\Attribute\AttributeAware $attributeAware)
    {
        $attrList = $stmt->findFirst('attr_list');
        if ($attrList) {
            foreach ($attrList->findAll('a_list') as $aList) {
                $attribute = null;
                // we pull each ID out, but the ID's are paired up as Name then Value
                foreach ($aList->findAll('ID') as $attributePart) {
                    if($attribute == null) {
                        $attribute = $attributePart;
                        continue;
                    }
                    $value = $attributePart;
                    
                    $attributeAware->setAttribute($this->cleanValue($attribute), $this->cleanValue($value));
                    $attribute = null;
                }
            }
        }
    }

    public function parseDotFile($smallOne)
    {
        $parser = new \ParserGenerator\Parser($this->grammar, ['ignoreWhitespaces' => true]);
        $parsed = $parser->parse($smallOne, 'graph');
        if (false === $parsed) {
            fwrite(STDERR, $parser->getErrorString($smallOne));
            exit(1);
        }

        $graph = new \Fhaculty\Graph\Graph();
        foreach ($parsed->findAll('stmt') as $stmt) {
            $first_node = $stmt->findFirst('node_id');
            $nodeId = $this->cleanValue($first_node);
            $left = $graph->createVertex($nodeId, true);

            $edgeRHS = $stmt->findFirst('edgeRHS');

            if ($edgeRHS === null) {
                $this->parseAttributes($stmt, $left);
            } else {
                $rightId = $this->cleanValue($edgeRHS->findFirst('node_id'));
                $right = $graph->createVertex($rightId, true);

                if ($this->cleanValue($edgeRHS->findFirst('edgeop')) === '->') {
                    $edge = $left->createEdgeTo($right);
                } else {
                    $edge = $left->createEdge($right);
                }

                $this->parseAttributes($stmt, $edge);
            }
        }

        return $graph;
    }
}
