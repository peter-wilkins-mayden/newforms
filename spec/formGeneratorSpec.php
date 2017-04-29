<?php

Kahlan\Matcher::register('toBeOneOf', 'NewForms\toBeOneOf');
Kahlan\Matcher::register('toContainArray', 'NewForms\toContainArray');

describe('Form Generator validation', function () {
    $title = 'Cumbria Self Referral Form';
    $dbPaywall = new PDO('mysql:host=192.168.20.99;dbname=paywall', 'root', '');
    $sql = 'SELECT spec, title FROM form_configs
         WHERE title=:title;';
    $stmt = $dbPaywall->prepare($sql);
    $stmt->execute([':title' => $title,]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $formTitle = $row['title'];
    $spec = json_decode($row['spec'], true);
//        echo '<?php
//return ';
//        var_export($spec);
//        echo ';';exit;

    context('validates Form Configuration for ' . $formTitle, function () use ($spec, $title) {
        ini_set('auto_detect_line_endings', true);
        $handle = fopen(__DIR__ . '/../cumbria.csv',
            'r'); // todo organise Fiona, spreadsheet and use get csv from google api
        if (!$handle) {
            exit('csv file not found');
        }
        $index = 0;
        $elementIndex = [];
        while (($data = fgetcsv($handle)) !== false) {
            $elementIndex[$index] = $data[0];
            $index++;
        }
        rewind($handle);
        $index = 0;
        $unspecifiedElements = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (!in_array($data[0], $elementIndex)) {
                $unspecifiedElements[] = $data[0];
                $index++;
                continue;
            }// todo check names are ok for iaptus etc
            it('generated $spec[\'elements\'][' . $index . '][\'spec\'][\'name\'] for ' . $data[0],
                function () use ($data, $index, $spec) {
                    expect($spec['elements'][$index]['spec']['name'])->toBe($data[0]);
                });
            it('generated $spec[\'elements\'][' . $index . '][\'flags\'][\'priority\'] based on index for' . $data[0],
                function () use ($data, $index, $spec) {
                    expect($spec['elements'][$index]['flags']['priority'])->toBe((string)-$index);
                });
            it('generated $spec[\'elements\'][' . $index . '][\'flags\'][\'priority\'] [\'spec\'][\'type\'] = \'Mayden\\Form\\Form\\Element\\SectionHeading\' if section heading',
                function () use ($data, $index, $spec) {
                    if (!$data[1] && !$data[2] && !$data[3] && !$data[4]) {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Mayden\\Form\\Form\\Element\\SectionHeading');
                    }
                });
            if (!$data[1] && !$data[2] && !$data[3] && !$data[4]) {
                $index++;
                continue;
            }
            it('generated $spec[\'elements\'][' . $index . '][\'spec\'][\'type\'] = \'Mayden\\Form\\Form\\Element\\NoteText\' if it is a NoteText',
                function () use ($data, $index, $spec) {
                    if ($data[2] === 'Message') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Mayden\\Form\\Form\\Element\\NoteText');
                    }
                });
            if ($data[2] === 'Message') {
                $index++;
                continue;
            }
            it('generated $spec[\'elements\'][' . $index . '][\'spec\'][\'options\'][\'label\'] for' . $data[0],
                function () use ($data, $index, $spec) {
                    expect($spec['elements'][$index]['spec']['options']['label'])->toBe($data[1]);
                });
            it('generated required configs for ' . $data[0] . ' if it is a required element',
                function () use ($data, $index, $spec) {
                    if ($data[5] === 'Yes') {
                        expect($spec['elements'][$index]['spec']['attributes']['required'])->toBe('required');
                        expect($spec['input_filter'][$index]['required'])->toBe(true);
                    }
                });
            it('generated required configs for ' . $data[0] . ' if it is not a required element',
                function () use ($data, $index, $spec) {
                    if ($data[5] === 'No') {
                        expect($spec['input_filter'][$index]['required'])->toBe(false);
                    }
                });

            it('generated correct config for ' . $data[0] . ' if it is a Text Box',
                function () use ($data, $index, $spec) {
                    if ($data[2] === 'Text Box' && !preg_match('/^(.*)$/', $data[3])) {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Zend\\Form\\Element\\Text');
                        expect($spec['input_filter'][$index]['validators'])->toContain(['name' => '\\Mayden\\Form\\Validator\\SafeCharacters']);
                        expect($spec['input_filter'][$index]['filters'])->toContain(['name' => 'Zend\\Filter\\StringTrim',]);
                    }
                });
            it('generated correct config for ' . $data[0] . ' if it is a Text Area',
                function () use ($data, $index, $spec) {
                    if ($data[2] === 'Text Area') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Zend\\Form\\Element\\Textarea');
                        expect($spec['input_filter'][$index]['validators'])->toContain(['name' => '\\Mayden\\Form\\Validator\\SafeCharacters']);
                        expect($spec['input_filter'][$index]['filters'])->toContain(['name' => '\\Zend\\Filter\\StringTrim',]);
                    }
                });

            it('generated config for ' . $data[0] . 'if it is a Drop Down',
                //todo check that the config has the secure default value
                function () use ($data, $index, $spec) {
                    if ($data[2] === 'Drop Down') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Zend\\Form\\Element\\Select');
                        expect($spec['elements'][$index]['spec']['options']['empty_option'])->toBe('Please Select a Value...');
                        foreach (preg_split("/\\r\\n|\\r|\\n/", $data[3]) as $item) {
                            expect($spec['elements'][$index]['spec']['options']['value_options'])->toContain($item);
                        }
                        foreach ($spec['elements'][$index]['spec']['value_options_order'] as $listId) {
                            expect($spec['elements'][$index]['spec']['options']['value_options'])->toContainKey($listId);
                        }
                    }
                });

            it('generated config for ' . $data[0] . 'if it is a Radio Button',
                function () use ($data, $index, $spec) {
                    if ($data[2] === 'Radio Button') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Zend\\Form\\Element\\Radio');
                        foreach (preg_split("/\\r\\n|\\r|\\n/", $data[3]) as $item) {
                            expect($spec['elements'][$index]['spec']['options']['value_options'])->toContain($item);
                        }
                        foreach ($spec['elements'][$index]['spec']['value_options_order'] as $listId) {
                            expect($spec['elements'][$index]['spec']['options']['value_options'])->toContainKey($listId);
                        }
                    }
                });
            it('generated config for ' . $data[0] . 'if it is a Check box',
                function () use ($data, $index, $spec) {
                    if ($data[2] === 'Checkbox') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Zend\\Form\\Element\\MultiCheckBox');
                        foreach (preg_split("/\\r\\n|\\r|\\n/", $data[3]) as $item) {
                            expect($spec['elements'][$index]['spec']['options']['value_options'])->toContain($item);
                        }
                        foreach ($spec['elements'][$index]['spec']['value_options_order'] as $listId) {
                            expect($spec['elements'][$index]['spec']['options']['value_options'])->toContainKey($listId);
                        }
                    }
                });
            it('generated correct config for ' . $data[0] . ' if it is a Date Picker Past',
                function () use ($data, $index, $spec) {
                    if ($data[2] === 'Date Picker') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Mayden\\Form\\Form\\Element\\DatePicker');
                        expect($spec['input_filter'][$index]['filters'])->toContain(['name' => '\\Zend\\Filter\\StringTrim']);
                        expect($spec['input_filter'][$index]['validators'])->toContainArray([
                            'name' => 'Date',
                            'options' =>
                                [
                                    'format' => 'd/m/Y',
                                    'break_chain_on_failure' => true,
                                ],
                        ]);
                    }
                });
            it('generated config for ' . $data[0] . 'if it is a Past Date',
                function () use ($data, $index, $spec) {
                    if ($data[3] === '(PAST DATE VALIDATION)') {
                        expect($spec['input_filter'][$index]['validators'])->toContainArray([
                            'name' => '\\Mayden\\Form\\Validator\\IsPastDateValidator',
                            'options' =>
                                [
                                    'inclusive' => false,
                                    'comparison_date' => 'now',
                                    'timezone' => null,
                                    'format' => 'd/m/Y',
                                ],
                        ]);
                    }
                });

            it('generated  for ' . $data[0] . 'if it is a Future Date',
                function () use ($data, $index, $spec) {
                    if ($data[3] === '(FUTURE DATE VALIDATION)') {
                        expect($spec['input_filter'][$index]['validators'])->toContain([
                            'name' => '\\Mayden\\Form\\Validator\\IsFutureDateValidator',
                            'options' =>
                                [
                                    'inclusive' => false,
                                    'comparison_date' => 'now',
                                    'timezone' => null,
                                    'format' => 'd/m/Y',
                                ],
                        ]);
                    }
                });

            it('generated config for ' . $data[0] . 'if it is a Postcode',
                function () use ($data, $index, $spec) {
                    if ($data[3] === '(POSTCODE VALIDATION)') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('\\Mayden\\Form\\Form\\Element\\PostcodeUK');
                    }
                });
            it('generated config for ' . $data[0] . 'if it is a phone',
                function () use ($data, $index, $spec) {
                    if ($data[3] === '(PHONE NUMBER VALIDATION)') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('\\Mayden\\Form\\Form\\Element\\PhoneNumber');
                        expect($spec['input_filter'][$index]['validators'])->toContain(['name' => '\\Mayden\\Form\\Validator\\PhoneNumberOrEmpty',]);
                    }
                });
            it('generated  for ' . $data[0] . 'if it is a ',
                function () use ($data, $index, $spec) {
                    if ($data[3] === '(MOBILE PHONE NUMBER VALIDATION)') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('\\Mayden\\Form\\Form\\Element\\PhoneNumber');
                        expect($spec['input_filter'][$index]['validators'])->toContain(['name' => '\\Mayden\\Form\\Validator\\PhoneNumberOrEmpty',]);
                        expect($spec['input_filter'][$index]['validators'])->toContain(['name' => '\\Mayden\\Form\\Validator\\UnitedKingdomMobile',]);
                    }
                });
            it('generated  for ' . $data[0] . 'if it is a ',
                function () use ($data, $index, $spec) {
                    if ($data[3] === '(EMAIL VALIDATION)') {
                        expect($spec['elements'][$index]['spec']['type'])->toBe('Mayden\\Form\\Form\\Element\\Email');
                    }
                });
            it('generated config for ' . $data[0] . ' if it has a conditional validator',
                function () use ($data, $index, $spec, $elementIndex) {
                    if (preg_match('/^if (.*) = (.*)/i', $data[5], $ifMatches)) {
                        expect(in_array_recursive($data[0],
                            $spec['input_filter'][array_search($ifMatches[1], $elementIndex)]['validators'],
                            true))->toBe(true);
                        expect(in_array_recursive('SelfReferral',
                            $spec['input_filter'][array_search($ifMatches[1], $elementIndex)]['validators'],
                            true))->toBe(true);
                        if ($ifMatches[2] === 'TRUE') {
                            expect(in_array_recursive('\\Mayden\\Form\\Validator\\ConditionalRequirementValidator',
                                $spec['input_filter'][array_search($ifMatches[1], $elementIndex)]['validators'],
                                true))->toBe(true);
                        } else {
                            expect(in_array_recursive($ifMatches[2],
                                $spec['input_filter'][array_search($ifMatches[1], $elementIndex)]['validators'],
                                true))->toBe(true);
                            expect(in_array_recursive('Mayden\\Form\\Validator\\ConditionalRequirementSpecifiedValuesValidator',
                                // leading backlashes are meant to be missing from this string
                                $spec['input_filter'][array_search($ifMatches[1], $elementIndex)]['validators'],
                                true))->toBe(true);
                        }
                    }
                });
            it('generated config for ' . $data[0] . 'if it is a Linked Field Value Validator',
                function () use ($data, $index, $spec, $elementIndex) {
                    if (preg_match('/MUST MATCH (.*)/i', $data[5], $orMatches)) {
                        if ($orMatches[2] === 'TRUE') {
                            expect(in_array_recursive($data[0],
                                $spec['input_filter'][array_search($orMatches[1], $elementIndex)]['validators'],
                                true))->toBe(true);
                            expect(in_array_recursive('Mayden\\Form\\Validator\\LinkedFieldValueValidator',
                                $spec['input_filter'][array_search($orMatches[1], $elementIndex)]['validators'],
                                true))->toBe(true);
                        }
                    }
                });
            it('generated config for ' . $data[0] . 'if it is a Conditional Requirement Specified Values Validator',
                function () use ($data, $index, $spec, $elementIndex) {
                    if (preg_match('/EITHER (.*) OR BOTH (.*)/i', $data[5], $orMatches)) {
                        if ($orMatches[2] !== 'TRUE') {
                            expect(in_array_recursive($data[0],
                                $spec['input_filter'][array_search($orMatches[1], $elementIndex)]['validators'],
                                true))->toBe(true);
                            expect(in_array_recursive('\\Mayden\\Form\\Validator\\EitherOrBothValidator',
                                $spec['input_filter'][array_search($orMatches[1], $elementIndex)]['validators'],
                                true))->toBe(true);
                        }
                    }
                });

            it('is checking ' . $index . ': ' . $data[0] . ' has a  valid types',
                function () use ($data, $index, $spec) {
                    $expectedTypes = [
                        'Zend\\Form\\Element\\Textarea',
                        'Zend\\Form\\Element\\Select',
                        'Zend\\Form\\Element\\Text',
                        'Zend\\Form\\Element\\Radio',
                        'Mayden\\Form\\Form\\Element\\NoteText',
                        'Mayden\\Form\\Form\\Element\\DatePicker',
                        'Mayden\\Form\\Form\\Element\\SectionHeading',
                        '\\Mayden\\Form\\Form\\Element\\PostcodeUK',
                        '\\Mayden\\Form\\Form\\Element\\PhoneNumber',
                        'Zend\\Form\\Element\\MultiCheckBox',
                        'Mayden\\Form\\Form\\Element\\Email',
                    ];
                    expect($spec['elements'][$index]['spec']['type'])->toBeOneOf($expectedTypes);
                });
            it('is checking that all filters for ' . $index . ': ' . $data[0] . ' have valid types',
                function () use ($data, $index, $spec) {
                    if (isset($spec['input_filter'][$index]['filters'])) {
                        $expectedFilterTypes = [
                            '\\Zend\\Filter\\StringTrim',
                        ];
                        for ($i = 0; $i < count($spec['input_filter'][$index]['filters']); $i++) {
                            if (isset($spec['input_filter'][$index]['filters'][$i]['name'])) {
                                expect($spec['input_filter'][$index]['filters'][$i]['name'])->toBeOneOf($expectedFilterTypes);
                            }
                        }
                    }
                });
            it('is checking that all validators for ' . $index . ': ' . $data[0] . ' have valid types',
                function () use ($data, $index, $spec) {
                    if (isset($spec['input_filter'][$index]['validators'])) {
                        $expectedValidatorTypes = [
                            '\\Mayden\\Form\\Validator\\ContextIdenticalValidator',
                            'Zend\\Validator\\StringLength',
                            'Zend\\Validator\\Digits',
                            '\\Mayden\\Form\\Validator\\CharacterHyphenAndQuote',
                            '\\Mayden\\Form\\Validator\\IsPastDateValidator',
                            '\\Mayden\\Form\\Validator\\IsFutureDateValidator',
                            'Date',
                            '\\Zend\\I18n\\Validator\\Alnum',
                            '\\Mayden\\Form\\Validator\\ConditionalRequirementValidator',
                            '\\Mayden\\Form\\Validator\\EitherOrBothValidator',
                            '\\Mayden\\Form\\Validator\\PhoneNumberOrEmpty',
                            'Mayden\\Form\\Validator\\LinkedFieldValueValidator',
                            '\\Mayden\\Form\\Validator\\UnitedKingdomMobile',
                            '\\Mayden\\Form\\Validator\\SafeCharacters',
                            'Mayden\\Form\\Validator\\ConditionalRequirementSpecifiedValuesValidator',
                        ];
                        foreach ($spec['input_filter'][$index]['validators'] as $validators) {
                            for ($i = 0; $i < count($spec['input_filter'][$index]['validators']); $i++) {
                                if (isset($spec['input_filter'][$index]['validators'][$i]['name'])) {
                                    expect($spec['input_filter'][$index]['validators'][$i]['name'])->toBeOneOf($expectedValidatorTypes);
                                }
                            }
                        }
                    }
                });
            it('generated  for ' . $data[0] . 'if it is a ',
                //todo test element / validator / filter arrays have correct format and indexes.
                function () use ($data, $index, $spec) {

                });
            it('generated  for ' . $data[0] . 'if it is a ',
                function () use (
                    $data,
                    $index,
                    $spec
                ) { // todo test the list ids and other value-options keys if possible

                });
            it('generated  for ' . $data[0] . 'if it is a ',
                function () use ($data, $index, $spec) {

                });
            $index++;
        }
        echo 'Unexpected elements: ' . implode(', ', $unspecifiedElements) . PHP_EOL;
    });
});
function in_array_recursive($needle, $haystack, $strict = true)
{
    foreach ($haystack as $value) {
        if (($strict ? $value === $needle : $value == $needle) || (is_array($value) && in_array_recursive($needle,
                    $value,
                    $strict))
        ) {
            return true;
        }
    }
    return false;
}
