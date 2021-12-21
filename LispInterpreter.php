<?php

require_once 'LispIntepreterException.php';

Class LispInterpreter {
    /**
     * Массив чисел для произведения операций.
     * Наполняется в процессе парсинга исходной строки при создании класса.
     *
     * @var array
     */
    protected array $digits = [];

    /**
     * @var string
     */
    protected string $operator;

    /**
     * @var bool
     */
    protected bool $log = false;

    public function __construct() {}

    /**
     * Включает или выключает режим отладки.
     *
     * @param bool $bool
     * @return $this
     */
    public function setLog(bool $bool) {
        $this->log = $bool;
    }

    protected function log($message) {
        if ($this->log) {
            echo "$message\n";
        }
    }

    /**
     * Устанавливает интерпретируемую строку.
     *
     * @param string $expression
     * @return $this
     * @throws LispIntepreterException
     */
    public function setExpression(string $expression) {
        $this->parse($expression);
    }

    /**
     * Разбирает интерпретируемую строку, определяет оператор, раскрывает вложения и наполняет массив чисел.
     *
     * @string $expression
     * @return $this
     * @throws LispIntepreterException
     */
    public function parse($expression) {
        $expression = $this->parseOperator($expression);
        $expression = $this->executeSubexpressions($expression);

        $digits = preg_split("/\s/", $expression, flags:PREG_SPLIT_NO_EMPTY);
        foreach ($digits as $digit) {
            if (!is_numeric($digit)) {
                throw new LispIntepreterException(sprintf('Unexpected digit %s. Source: %s', $digit, $expression));
            }
        }

        $this->digits = $digits;
        return $this;
    }

    /**
     * Находит и убирает открывающие и закрывающие скобки.
     *
     * @param string $expression
     * @return string
     * @throws LispIntepreterException
     */
    protected function parseBasicScope(string $expression) {
        $this->log(sprintf('Start parsing expression: %s', $expression));
        $expression = str_replace(array("\n", "\r", "\t"), " ", $expression);
        $expression = trim($expression);

        if ($expression[0] !== '(') {
            throw new LispIntepreterException(sprintf(
                'Parse failed, expected (, given: %s. Source: %s',
                $expression[0],
                $expression
            ));
        }

        $strlen = strlen($expression);
        if ($expression[$strlen - 1] !== ')') {
            throw new LispIntepreterException(sprintf(
                'Parse failed, expected ), given: %s. Source: %s',
                $expression[$strlen - 1],
                $expression
            ));
        }

        return $expression;
    }

    /**
     * Находит в интерпретируемой строке оператор и записывает его в стройства класса для проведения вычислений.
     *
     * @param string $expression
     * @return string
     * @throws LispIntepreterException
     */
    protected function parseOperator(string $expression) {
        $expression = $this->parseBasicScope($expression);

        $expression = trim(substr($expression, 1, -1));
        $this->log(sprintf("() found successfully, crafted expression: %s", $expression));

        if (!in_array($expression[0], ['+', '-', '*', '/'])) {
            throw new LispIntepreterException(sprintf(
                'Parse failed, operator expected, given: %s. Source: %s',
                $expression[0],
                $expression
            ));
        }

        $this->operator = $expression[0];
        $expression = trim(substr($expression, 1));
        $this->log(sprintf("Operator found successfully: %s, crafted expression: %s", $this->operator, $expression));

        return $expression;
    }

    /**
     * Выполняет поиск подвыражения в строке побуквенно.
     * Исполняет найденное выраажение и заменяет его значением в исходной строке.
     *
     * @param string $expression
     * @param int $subStart Позиция в строке, с которой начинается вложенное выражение
     * @return string
     * @throws LispIntepreterException
     */
    protected function executeSubexpression(string $expression, int $subStart) {
        $opened = 1;
        $subEnd = false;

        $startPosition = ($subStart + 1);
        $endPosition = strlen($expression);
        $this->log(sprintf("Searching scope from pos %d to pos %d in expression %s", $startPosition, $endPosition, $expression));
        for ($i = $startPosition; $i < $endPosition; $i++) {
            $this->log(sprintf("Searching... Next symbol: %s", $expression[$i]));
            if ($expression[$i] === '(') {
                $opened++;
                $this->log(sprintf('Opened scopes counter set to %d', $opened));
            }

            if ($expression[$i] === ')') {
                $opened = $opened - 1;
                $this->log(sprintf('Closing found, opened scopes: %d', $opened));
                if ($opened === 0) {
                    $subEnd = $i;
                    break;
                }
            }
        }

        if ($subEnd === false) {
            throw new LispIntepreterException(sprintf("Expected closing ) not found, source: %s", $expression));
        }

        $subExpressionSource = substr($expression, $subStart, ($subEnd - $subStart + 1));
        $this->log(sprintf("Subexpression found: %s, source expression: %s", $subExpressionSource, $expression));

        $subExpression = new self();
        $subExpression->setLog($this->log);
        $subExpression->setExpression($subExpressionSource);
        $result = $subExpression->execute();
        $expression = str_replace($subExpressionSource, ' ' . $result . ' ', $expression);
        $this->log(sprintf(
            "Subexpression calculated successfully, %s replaced with %f, crafted expression: %s",
            $subExpressionSource,
            $result,
            $expression,
        ));

        return $expression;
    }

    /**
     * Проверка интерпретируемой строки на вложенные выражения, замена вложений вычисленными числами.
     *
     * @param $expression
     * @return string
     * @throws LispIntepreterException
     */
    protected function executeSubexpressions($expression) {
        $this->log("Searching subexpressions");
        while (($subStart = strpos($expression, '(')) !== false) {
            $this->log(sprintf("Subexpression found at pos: %d", $subStart));
            $expression = $this->executeSubexpression($expression, $subStart);
        }

        return $expression;
    }

    /**
     * Выполняет вычисления распарсенной строки.
     *
     * @return float|int
     * @throws LispIntepreterException
     */
    public function execute() {
        $result = null;

        $this->log('Start execute expression');
        foreach ($this->digits as $digit) {
            if ($result === null) {
                $result = $digit;
                $this->log('First digit skipped');
                continue;
            }

            if (($this->operator === '/') && ($digit === 0)) {
                throw new LispIntepreterException('Divine by zero exception');
            }

            $loggedResult = $result;
            $result = match($this->operator) {
                '+' => $result + $digit,
                '-' => $result - $digit,
                '*' => $result * $digit,
                '/' => $result / $digit,
            };

            $this->log(sprintf("%f %s %f = %f", $loggedResult, $this->operator, $digit, $result));
        }

        return $result;
    }
}