<?php
/**
 * Написать на php простой интерпретатор математических выражений
 * из четырех математических операций (сложение, вычитание, умножение, деление).
 * Для простоты они будут записаны в LISP-стиле:
 * (+ 1 2)
 * "(+ 1 (* (- 3 4) 5))"
 * "(+ 1 2 3 4)". Допускать форматирование формулы
 * (наличие дополнительных пробелов, переносов, табуляции, чтобы записать сложную формулу красиво в несколько строк.
 *
 *
 * В этом файле неудачная попытка быстрой реализации через регулярные выражения.
 */

echo "start\n";

$expressions = [
    '(+ 2 2 3)'           => 7,
    '(+ 2 (+ 2 3))'       => 7,
    '(- 2 (+ 3 4) 5)'     => -10,
    '(+ 1 (* (- 3 4) 5))' => 36,
    '(+ 1 2 3 4)'         => 10,
];

/**
 * @param $expression
 * @return mixed
 * @throws Exception
 */
function e($expression) {
    $regexp = '/\s*\(\s*([+-\\\*\/])\s*(.+?)\)[\d\s]*$/';
    preg_match($regexp, $expression, $matches);
    //var_dump($matches);

    if (!$matches[2]) {
        throw new Exception("Выражение не распознано, нет скобок");
    }

    $operator = $matches[1];
    $arguments = $matches[2];
    if (preg_match($regexp, $arguments, $matches)) {

        $e = e($matches[0]);
        //var_dump($arguments, $matches[0], $e);die();
        $arguments = str_replace($matches[0], ' ' . $e, $arguments);
        echo 'args: ';var_dump($arguments);
        if (is_numeric(trim($arguments))) {
            return $arguments;
        }

    };

    $result = null;




    //var_dump(explode(' ', $arguments));
    foreach (explode(' ', $arguments) as $item) {
        if ($result === null) {
            $result = $item;
            continue;
        }

        if ($item === '') {
            continue;
        }

        $result = eo($result, $item, $operator);
    }

    return $result;
}

/**
 * @param $item1
 * @param $item2
 * @param $operator
 * @return int|float
 */
function eo($item1, $item2, $operator): int|float
{
    echo "$item1 $operator $item2 = ";
    $result = match($operator) {
        '+' => $item1 + $item2,
        '-' => $item1 - $item2,
        '*' => $item1 * $item2,
        '/' => $item1 / $item2,
    };

    echo "$result\n";
    return $result;
}

foreach ($expressions as $expression) {
    echo "$expression\n\n";
    echo "\nTotal result: ";
    echo e($expression);
    echo "\nsuccessful";
}
