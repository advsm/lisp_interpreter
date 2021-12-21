<?php

/**
 * Написать на php простой интерпретатор математических выражений
 * из четырех математических операций (сложение, вычитание, умножение, деление).
 * Для простоты они будут записаны в LISP-стиле:
 * (+ 1 2)
 * "(+ 1 (* (- 3 4) 5))"
 * "(+ 1 2 3 4)". Допускать форматирование формулы
 * (наличие дополнительных пробелов, переносов, табуляции, чтобы записать сложную формулу красиво в несколько строк.
 */

require_once 'LispInterpreter.php';

$expressions = [
    '(+ 2 2 3)'           => 7,
    '(+ 2 (+ 2 3))'       => 7,
    '(- 2 (+ 3 4) 5)'       => -10,
    '(+ 1 (* (- 3 4) 5))' => -4,
    '(+ 1 2 3 4)'         => 10,
    ' ( *  2  (+ 3 3) 5 (/ 4 2) 10)' => 1200,
    '(
        / 
            2000  
                (+ 800 25 75  
                    (* 2  10  5)
                )
        2
    )' => 1
];


foreach ($expressions as $expression => $expectedResult) {
    try {
        $interpreter = new LispInterpreter();
        $interpreter->setLog(true);
        $interpreter->setExpression($expression);
        $result = $interpreter->execute();
        echo "Result: $result, expected: $expectedResult\n\n";
    } catch (LispIntepreterException $e) {
        echo $e->getMessage() . "\n";
    }
}