<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;

/**
 * "FILTER_VALUE_CONVERSION(FieldMame, :parameter)"
 *
 * FilterFieldConversion ::=
 *     "RECORD_FILTER_VALUE_CONVERSION" "(" StringPrimary, InParameter ["," integer | null ] ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterValueConversion extends FunctionNode
{
    public $fieldName;
    public $columnExpression;
    public $strategy;

    public function getSql(SqlWalker $sqlWalker)
    {
        /** @var \Closure $whereBuilder */
        if (!($whereBuilder = $sqlWalker->getQuery()->getHint('where_builder_conversions'))) {
            throw new \LogicException('Missing "where_builder_conversions" hint for FilterFieldConversion.');
        }

        $whereBuilder = $whereBuilder();
        /** @var \Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder $whereBuilder */

        $fieldName = is_object($this->fieldName) ? trim($this->fieldName->dispatch($sqlWalker), "'") : $this->fieldName;

        return $whereBuilder->getValueConversionSql($fieldName, $this->columnExpression->dispatch($sqlWalker), null, null, $this->strategy);
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->fieldName = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->columnExpression = $parser->InParameter();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            if ($lexer->isNextToken(Lexer::T_NULL)) {
                $parser->match(Lexer::T_NULL);
                $this->strategy = null;
            } else {
                $this->strategy = $parser->Literal();
            }
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
