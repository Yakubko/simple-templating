<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use \Yakub\SimpleTemplating\Replace;

final class ReplaceTest extends TestCase {

    /**
     * Replace reflection class
     */
    private static $replaceReflectionClass = null;

    /**
     * Before test
     */
    public static function setUpBeforeClass(): void {
        // Create global replace reflection class
        self::$replaceReflectionClass = new \ReflectionClass(Replace::class);
    }

    /**
     * Check constructor is not public
     */
    public function testMethodConstructorIsNotPublic(): void {
        $method = self::$replaceReflectionClass->getConstructor();

        $this->assertEquals(
            false,
            $method->isPublic()
        );
    }

    /**
     * Run instance test
     */
    public function testMethodCompileReturnSelf(): void {
        $this->assertInstanceOf(
            Replace::class,
            Replace::compile('')
        );
    }

    public function testCompileGetVariableFromScope(): void {
        $output = ['name' => 'test'];
        $compiled = Replace::compile('{{array[0]}}', ['array' => [$output]]);

        $this->assertEquals(
            $output,
            $compiled->onlyOneParamValue
        );
    }

    public function testCompileUseFlagUrlencode(): void {
        $compiled = [];
        $compiled[] = (string) Replace::compile('{{fullName}} a {{surName}}', ['fullName' => 'Jakub Miškech', 'surName' => 'Miškech'], Replace::USE_URLENCODE);
        $compiled[] = (string) Replace::compile('{{url}}', ['url' => 'https://test.com'], Replace::USE_URLENCODE);
        $compiled[] = (string) Replace::compile('https://test.com?a={{url}}', ['url' => 'https://test.com'], Replace::USE_URLENCODE);

        $this->assertEquals(
            [
                'Jakub%20Mi%C5%A1kech a Mi%C5%A1kech',
                'https://test.com',
                'https://test.com?a=https%3A%2F%2Ftest.com'
            ],
            $compiled
        );
    }

    public function testCompileWrongParamInFUnction(): void {
        $compiled = Replace::compile('Hi ({{fn.date()}})');

        $this->assertEquals(
            'Hi ()',
            $compiled
        );
    }

    public function testCompileTojson(): void {
        $compiled = Replace::compile('{{fullName}}', ['fullName' => 'Jakub Miškech']);

        $this->assertEquals(
            '"Jakub Mi\u0161kech"',
            json_encode($compiled)
        );
    }

    public function testCompileString(): void {
        $compiled = (string) Replace::compile('Hi <b>this</b> is empty test (ahoj)');

        $this->assertEquals(
            'Hi <b>this</b> is empty test (ahoj)',
            $compiled
        );
    }

    public function testCompileStringWithScope(): void {
        $compiled = (string) Replace::compile('Hi <b>this</b> is empty test ({{variable}})', ['variable' => 'ahoj']);

        $this->assertEquals(
            'Hi <b>this</b> is empty test (ahoj)',
            $compiled
        );
    }

    public function testCompileStringWithWrongScope(): void {
        $compiled = (string) Replace::compile('Hi <b>this</b> is empty test ({{variable_typo}})', ['variable' => 'ahoj']);

        $this->assertEquals(
            'Hi <b>this</b> is empty test ()',
            $compiled
        );
    }

    public function testCompileStringCharFromScope(): void {
        $compiled = (string) Replace::compile('Hi ({{variable[1]}})', ['variable' => 'ahoj']);

        $this->assertEquals(
            'Hi (h)',
            $compiled
        );
    }

    public function testCompileStringWrongCharPositionFromScope(): void {
        $compiled = (string) Replace::compile('Hi ({{variable[10]}})', ['variable' => 'ahoj']);

        $this->assertEquals(
            'Hi ()',
            $compiled
        );
    }

    public function testCompileStringFunctionTrimStriptagsStrreplaceUrlencodeRawurlencode(): void {
        $compiled = (string) Replace::compile(
            'Hi ({{variable}}, {{fn.trim(variable)}}), {{fn.strip_tags(tags)}}, {{fn.str_replace(\'Cau\', \'Hi\', tags)}}, '.
            '{{fn.urlencode(encode)}}, {{fn.rawurlencode(encode)}}',
            ['variable' => '     ahoj  ', 'tags' => '<a>Cau</a>', 'encode' => 'Jakub Miškech']
        );

        $this->assertEquals(
            'Hi (     ahoj  , ahoj), Cau, <a>Hi</a>, Jakub+Mi%C5%A1kech, Jakub%20Mi%C5%A1kech',
            $compiled
        );
    }

    public function testCompileStringFunctionStrlenSubstrStrposStrstrSprintf(): void {
        $compiled = (string) Replace::compile(
            'Hi ({{variable}}, {{fn.strlen(variable)}}), {{fn.substr(variable, -3, 2)}}, '.
            '{{fn.strpos(variable, \'ho\')}}, {{fn.strstr(variable, \'h\')}}, {{fn.sprintf(\'Test %s\', variable)}}',
            ['variable' => 'ahoj']
        );

        $this->assertEquals(
            'Hi (ahoj, 4), ho, 1, hoj, Test ahoj',
            $compiled
        );
    }

    public function testCompileStringFunctionUcfirstUcwordsStrtoupperStrtolower(): void {
        $compiled = (string) Replace::compile(
            'Hi ({{variable}}, {{fn.ucfirst(variable)}}), {{fn.ucwords(words)}}, {{fn.strtoupper(variable)}}, {{fn.strtolower(title)}}',
            ['variable' => 'ahoj', 'words' => 'ahoj test', 'title' => 'TITle']
        );

        $this->assertEquals(
            'Hi (ahoj, Ahoj), Ahoj Test, AHOJ, title',
            $compiled
        );
    }

    public function testCompileNumberWithScope(): void {
        $compiled = (string) Replace::compile('Hi ({{number}})', ['number' => 10]);

        $this->assertEquals(
            'Hi (10)',
            $compiled
        );
    }

    public function testCompileNumberFunctionRoundRandPowFloorAbs(): void {
        $compiled = (string) Replace::compile(
            'Hi ({{number}}), {{fn.round(number)}}, {{fn.round(number2)}}, {{fn.rand(2,2)}}, {{fn.pow(2,3)}}, {{fn.floor(2.9)}}, {{fn.abs(number)}}',
            ['number' => 10.4, 'number2' => 10.6]
        );

        $this->assertEquals(
            'Hi (10.4), 10, 11, 2, 8, 2, 10.4',
            $compiled
        );
    }

    public function testCompileArrayWithScope(): void {
        $compiled = (string) Replace::compile('Hi ({{array}})', ['array' => [1, 2]]);

        $this->assertEquals(
            'Hi ()',
            $compiled
        );
    }

    public function testCompileArrayItemFromScope(): void {
        $compiled = (string) Replace::compile(
            'Hi ({{array[0]}}, {{array[1]}}), {{object.title}}, {{object[\'title\']}}, {{object[titleName]}}, {{nested[0].name}}',
            ['array' => [1, 'ahoj'], 'object' => ['title' => 'Hi'], 'titleName' => 'title', 'nested' => [['name' => 'Yes']]]
        );

        $this->assertEquals(
            'Hi (1, ahoj), Hi, Hi, Hi, Yes',
            $compiled
        );
    }

    public function testCompileArrayFunctionExplodeImplodeArraycolumnArraypushArraymerge(): void {
        $compiled = (string) Replace::compile(
            "Hi ({{explode}}), {{fn.explode(',', explode)[1]}}, [{{fn.implode(', ', implode)}}], ".
            "{{fn.array_column(a_column, 'name')[1]}}",
            ['explode' => 'a,b,c', 'implode' => ['d', 'e', 'f'], 'a_column' => [['name' => 'test'], ['name' => 'try']]]
        );

        $this->assertEquals(
            'Hi (a,b,c), b, [d, e, f], try',
            $compiled
        );

        $compiled = Replace::compile(
            "{{fn.array_push(array, 'test', add)}}",
            ['array' => ['a', 'b', 'c'], 'add' => ['yes', 'no']]
        );

        $this->assertEquals(
            ['a', 'b', 'c', 'test', ['yes', 'no']],
            $compiled->onlyOneParamValue
        );

        $compiled = Replace::compile(
            "{{fn.array_merge(array, add)}}",
            ['array' => ['a', 'b', 'c'], 'add' => ['yes', 'no']]
        );

        $this->assertEquals(
            ['a', 'b', 'c', 'yes', 'no'],
            $compiled->onlyOneParamValue
        );
    }

    public function testCompileTimeFunctionTimeDateGmdateStrtotimeStrtodate(): void {
        $compiled = (string) Replace::compile(
            "Hi ({{fn.time()}}), {{fn.date('Y-m-d, H:i')}}, {{fn.gmdate('H:i:s', 64)}}, {{fn.strtotime('today')}}, {{fn.strtodate('today', fn.substr('Y-m-d,, H:i', 0))}}"
        );

        $this->assertEquals(
            'Hi ('.time().'), '.date('Y-m-d, H:i').', '.gmdate('H:i:s', 64).', '.strtotime('today').', '.date('Y-m-d,, H:i', strtotime('today')),
            $compiled
        );
    }

    public function testCompileArithmeticOperators(): void {
        $compiled = (string) Replace::compile(
            "Hi {{4 444}} {{1--4}} {{-4+4--5*-3}} ({{1 + 3 + (3+3) +3 + (2 * (1+1) + (1+2)) * (3+3) * (3+5 * (3.5+4,5))+4* ( 1+2)+2}})"
        );

        $this->assertEquals(
            'Hi 4 444 5 -15 (1833)',
            $compiled
        );
    }

    public function testCompileArithmeticOperatorsWithScope(): void {
        $compiled = (string) Replace::compile(
            "Hi {{a + 3*  b + 3/  a-    a*4 *4 +3 334+4/4*4/3+3}}",
            ['a' => 3, 'b' => '5']
        );

        $this->assertEquals(
            'Hi 3309.3333333333',
            $compiled
        );
    }

    public function testCompileArithmeticOperatorsWithScopeFunctions(): void {
        $compiled = (string) Replace::compile(
            'Hi {{2.1*fn.explode(\',\', a)[0]*fn.round(1,4)}} {{1 + fn.time() + 1}} {{fn.round(2.1*fn.round(10/3), 1)}}, '.
            '{{fn.round(2.1*fn.round(10/3), 1) + fn.round(2.1*fn.round(10/3)) + fn.round(1.5) + (4*4)}}, {{fn.round(answered / total, 4)*100}}, {{fn.round((answered / total)*100, 2)}}',
            ['a' => '10.3', 'total' => 100, 'answered' => 1.2444]
        );

        $this->assertEquals(
            'Hi 21.63 '.(time()+2).' 6.3, 30.3, 1.24, 1.24',
            $compiled
        );
    }

    public function testCompileCondition(): void {
        $compiled = (string) Replace::compile(
            'Hi {{condition ? a : b}} {{conditionFalse ? a : b}} {{condition ? (conditionFalse ? b : a) : b}} {{conditionFalse ? a : (condition ? b : a)}} '.
            '({{(condition ? b : a) ? a : b}}) {{! condition ? "false" : \'O\\\'K\'}}',
            ['condition' => true, 'conditionFalse' => false, 'a' => 'Ahoj' , 'b' => 'Hi']
        );

        $this->assertEquals(
            'Hi Ahoj Hi Ahoj Hi (Ahoj) O\'K',
            $compiled
        );
    }

    public function testCompileConditionWithLogicOperators(): void {
        $compiled = (string) Replace::compile(
            'Hi {{(conditionFalse || condition) ? ok : notOk}} {{(conditionFalse && condition) ? notOk : ok}} {{((2 && (0 || 1)) && 1) ? ok : notOk}}',
            ['condition' => true, 'conditionFalse' => false, 'ok' => 'ok', 'notOk' => 'notOK']
        );

        $this->assertEquals(
            'Hi ok ok ok',
            $compiled
        );
    }

    public function testCompileConditionWithComparisonOperators(): void {
        $compiled = (string) Replace::compile(
            '{{a === b ? ok : notOk}} {{( a !== c )? ok : notOk}} {{a == c ? ok : notOk}} {{a != d ? ok : notOk}} {{a <> d ? ok : notOk}} {{d >= a ? ok : notOk}} {{d <= 1 ? notOk : ok}} '.
            '{{a > b ? notOk : ok}} {{a < 5 ? ok : notOk}}',
            ['a' => 1 , 'b' => 1, 'c' => '1', 'd' => 2, 'ok' => 'ok', 'notOk' => 'notOK']
        );

        $this->assertEquals(
            'ok ok ok ok ok ok ok ok ok',
            $compiled
        );
    }

    public function testCompileConditionWithErrorInConditionLogicComparison(): void {
        $compiled = (string) Replace::compile(
            'Hi {{condition ? a :}} {{(1 && ) ? a : b}} {{( 1 <= ) ? a : b}}',
            ['condition' => true, 'conditionFalse' => false, 'a' => 'Ahoj' , 'b' => 'Hi']
        );

        $this->assertEquals(
            'Hi   ',
            $compiled
        );
    }

    public function testCompileAllInOne(): void {
        $compiled = (string) Replace::compile(
            'Hi {{object.title}}, {{((1 && 2 && (0 || 1) ? (a >= 1+4*1) : false)) ? (fn.round((a / 22)*100, 1)) : \'error\\\'s\'}} {{\'error\\\'s\'}} {{"success\'s"}}',
            [
                'object' => [
                    'title' => 'Jakub Miškech'
                ],
                'a' => 5,
            ]
        );

        $this->assertEquals(
            'Hi Jakub Miškech, 22.7 error\'s success\'s',
            $compiled
        );
    }
}
