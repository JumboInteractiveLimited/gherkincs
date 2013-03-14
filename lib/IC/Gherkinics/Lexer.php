<?php
/**
 * @copyright 2013 Instaclick Inc.
 */
namespace IC\Gherkinics;

use IC\Gherkinics;

/**
 * Gherkin Lexer
 *
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */
final class Lexer
{
    public function analyze($content)
    {
        $lineList      = $this->cleanUp($content);
        $tokenList     = array();
        $previousToken = null;

        foreach ($lineList as $lineNo => $content) {
            $token = $this->makeModel($lineNo, $content);

            if ( ! $token) {
                continue;
            }

            if ($previousToken) {
                $token->setPrevious($previousToken);
                $previousToken->setNext($token);
            }

            $tokenList[]   = $token;
            $previousToken = $token;
        }

        return $tokenList;
    }

    private function makeModel($lineNo, $content)
    {
        if (preg_match('/^\s*$/', $content)) {
            // Note: will be removed in the future.
            return new Model\Blank($lineNo, $content, null);
        }

        if (preg_match('/^\s*\@/', $content)) {
            return new Model\TagLine($lineNo, $content, null);
        }

        if (preg_match('/^\s*Background:/', $content)) {
            return new Model\Background($lineNo, $content, null);
        }

        if (preg_match('/^\s*Examples:/', $content)) {
            return new Model\Example($lineNo, $content, null);
        }

        if (preg_match('/^\s*\|.+\|/', $content)) {
            return new Model\TabularData($lineNo, $content, null);
        }

        $matches = array();

        preg_match('/^\s*(?P<prefix>Feature:|Scenario[^:]*:|Given|Then|And|But|When)(?<context>.*)$/i', $content, $matches);

        if ( ! $matches) {
            return new Model\Node($lineNo, $content, null);
        }

        switch (true) {
            case $matches['prefix'] === 'Feature:':
                return new Model\Feature($lineNo, $content, $matches['context']);
            case $matches['prefix'] === 'Scenario:' || $matches['prefix'] === 'Scenario Outline:':
                return new Model\Scenario($lineNo, $content, $matches['context']);
            case $matches['prefix'] === 'Given':
                return new Model\Precondition($lineNo, $content, $matches['context']);
            case $matches['prefix'] === 'Then':
                return new Model\Assertion($lineNo, $content, $matches['context']);
            case $matches['prefix'] === 'And' || $matches['prefix'] === 'But':
                return new Model\Continuation($lineNo, $content, $matches['context']);
            case $matches['prefix'] === 'When':
                return new Model\Action($lineNo, $content, $matches['context']);
        }

        throw new \RuntimeException('Unable to determine the type of token.');
    }

    private function cleanUp($content)
    {
        $lineNumber  = 0;
        $rawLineList = explode(PHP_EOL, $content);
        $lineList    = array();

        foreach ($rawLineList as $line) {
            $lineList[++$lineNumber] = $line;
        }

        return $lineList;
    }
}