<?php

xdescribe('Validating CSV of Form Specification', function () {
    context('validates form specs against DSL', function () {
        ini_set('auto_detect_line_endings', true);

        $handle = fopen(__DIR__.'/../data/cumbria.csv','r'); // todo get csv from google api

            while (($data = fgetcsv($handle)) !== false) {
                it('checks that ' . $data[0] . ' has complete data set', function () use ($data) {
                    if(!$data[0]) exit('ERROR: name field empty for an element in the specification');
                    if ($data[0] && !$data[1] || !$data[2] || !$data[3] || !$data[4] || !$data[5]) {
                        expect(!$data[1] && !$data[2] && !$data[3] && !$data[4] && !$data[5])->toBe(true); //, $data[0] . ' section header has unexpected data'
                    } else {
                        expect($data[0] && $data[1] && $data[2] && $data[3] && $data[4] && $data[5])->toBe(true); //, $data[0] . ' is missing data'
                    }
                });

                if (!$data[1] || !$data[2] || !$data[3] || !$data[4] || !$data[5]) {
                    continue;
                }

                it('checks ' . $data[2] . ' is acceptable type for ' . $data[0], function () use ($data) {
                    $accepted = [
                        "Radio Button",
                        "Drop Down",
                        "Text Area",
                        "Text Box",
                        "Checkbox",
                        "Date Picker",
                        "Message",
                    ];
                    expect(in_array($data[2], $accepted))->toBe(true);
                });
                it('checks that if ' . $data[0] . ' has validator:' . $data[3] . ' that it is accepted',
                    function () use ($data) {
                        $acceptedValidators = [
                            '(PAST DATE VALIDATION)',
                            '(NHS NO. VALIDATION)',
                            '(POSTCODE VALIDATION)',
                            '(PHONE NUMBER VALIDATION)',
                            '(MOBILE PHONE NUMBER VALIDATION)',
                            '(EMAIL VALIDATION)',
                            '(500 CHARACTERS)',
                            '(PAST DATE VALIDATION)',
                        ];
                        if (preg_match('/^\(.*\)/', $data[3])) {
                            expect(in_array($data[3], $acceptedValidators))->toBe(true);
                        }
                    });
                it('checks that if ' . $data[0] . ' option-values: ' . $data[3] . ' they are acceptable and there are at least two of them',
                    function () use ($data) {
                        if ($data[2] === "Radio Button" || $data[2] === "Drop Down" || $data[2] === "Checkbox") {
                            if (!strpos($data[3], 'As per list')) {
                                expect(count(preg_split("/\\r\\n|\\r|\\n/", $data[3])) > 1)->toBe(true);
                            }
                        }
                    });
//            it('checks that mapped fields have the correct $data[0] for Iaptus', function () {
////                if($data[4] === trim('Yes')){
////
////                } todo check names for fields that don't go into custom table have the right name, Names all lowercase with underscores
                //todo    '\\Mayden\\Form\\Validator\\IsFutureDateValidator',
//            });
                it('checks ' . $data[0] . ' required status: ' . $data[5] . ' is acceptable', function () use ($data) {
                    $acceptedPatterns = [ //todo check values are a element name
                        'yes' => '/Yes/i',
                        'no' => '/No/i',
                        'or' => '/^EITHER .* OR .*/i',
                        'if' => '/^if .* = .*/i',
                        'match' => '/^MUST MATCH .*/',
                    ];
                    $match = false;
                    foreach ($acceptedPatterns as $pattern) {
                        if (preg_match($pattern, trim($data[5]))) {
                            $match = true;
                        }
                    }
                    expect($match)->toBe(true); //, $data[0] . ' $data[5] field did not match accepted pattern'
                });// todo if data[1] = ^if yes  then check data[5] = if * =
            }

    });
});